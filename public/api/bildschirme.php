<?php
/**
 * API fuer Bildschirm-Konfigurationen.
 *
 * GET  -> Liste oder einzelner Bildschirm
 * POST -> Bildschirm-Konfiguration speichern
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/auth.php';

$storageDir = __DIR__ . '/../../storage';
$storageFile = $storageDir . '/bildschirme.json';
$screenCount = 5;

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        handleGetScreens($storageFile, $screenCount);
    }

    if ($method === 'POST') {
        if (!is_admin_logged_in()) {
            sendResponse(false, 'Nicht angemeldet', null, 401);
        }
        handleSaveScreen($storageDir, $storageFile, $screenCount);
    }

    sendResponse(false, 'HTTP-Methode nicht unterstuetzt', null, 405);
} catch (Exception $e) {
    error_log('API-Fehler in bildschirme.php: ' . $e->getMessage());
    sendResponse(false, 'Interner Serverfehler', null, 500);
}

function handleGetScreens($storageFile, $screenCount) {
    $screenId = isset($_GET['screen_id']) ? (int)$_GET['screen_id'] : 0;
    $config = readScreenConfig($storageFile, $screenCount);
    $globalAd = $config['global_ad'] ?? defaultGlobalAd();

    if ($screenId > 0) {
        $screen = $config['screens'][$screenId] ?? defaultScreen($screenId);
        sendResponse(true, 'Bildschirm geladen', ['screen' => $screen, 'global_ad' => $globalAd]);
    }

    $screens = array_values($config['screens']);
    sendResponse(true, 'Bildschirme geladen', ['screens' => $screens, 'global_ad' => $globalAd]);
}

function handleSaveScreen($storageDir, $storageFile, $screenCount) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    $hasGlobalAd = array_key_exists('global_ad_path', $input) || array_key_exists('global_ad_type', $input);
    $screenId = (int)($input['screen_id'] ?? 0);
    if (!$hasGlobalAd || $screenId > 0) {
        if ($screenId < 1 || $screenId > $screenCount) {
            sendResponse(false, 'Ungueltige Bildschirm-ID', null, 400);
        }
    }

    $mode = $input['mode'] ?? 'plan';
    $mode = $mode === 'image' ? 'image' : 'plan';

    $planId = isset($input['plan_id']) ? (int)$input['plan_id'] : 0;
    $planId = $planId > 0 ? (string)$planId : null;

    $imagePath = sanitizePath($input['image_path'] ?? null);
    $backgroundPath = sanitizePath($input['background_path'] ?? null);

    if ($mode === 'image') {
        $planId = null;
    }

    $config = readScreenConfig($storageFile, $screenCount);
    $screen = null;
    if ($screenId > 0) {
        $screen = $config['screens'][$screenId] ?? defaultScreen($screenId);
        $screen['mode'] = $mode;
        $screen['plan_id'] = $planId;
        $screen['image_path'] = $imagePath;
        $screen['background_path'] = $backgroundPath;
        $screen['updated_at'] = date('c');

        $config['screens'][$screenId] = $screen;
    }

    if ($hasGlobalAd) {
        $globalAdPath = sanitizePath($input['global_ad_path'] ?? null);
        $globalAdType = $input['global_ad_type'] ?? null;
        if (!$globalAdType || !in_array($globalAdType, ['image', 'video'], true)) {
            $globalAdType = inferAdType($globalAdPath);
        }
        $config['global_ad'] = [
            'path' => $globalAdPath,
            'type' => $globalAdPath ? $globalAdType : null
        ];
    }

    writeScreenConfig($storageDir, $storageFile, $config);

    sendResponse(true, 'Bildschirm gespeichert', [
        'screen' => $screen,
        'global_ad' => $config['global_ad'] ?? defaultGlobalAd()
    ]);
}

function readScreenConfig($storageFile, $screenCount) {
    $config = ['screens' => [], 'global_ad' => defaultGlobalAd()];

    if (file_exists($storageFile)) {
        $raw = file_get_contents($storageFile);
        $data = $raw ? json_decode($raw, true) : null;
        if (is_array($data) && isset($data['screens']) && is_array($data['screens'])) {
            $config['screens'] = $data['screens'];
        }
        if (is_array($data) && isset($data['global_ad']) && is_array($data['global_ad'])) {
            $config['global_ad'] = array_merge(defaultGlobalAd(), $data['global_ad']);
        }
    }

    for ($i = 1; $i <= $screenCount; $i++) {
        if (!isset($config['screens'][$i])) {
            $config['screens'][$i] = defaultScreen($i);
        }
    }

    return $config;
}

function writeScreenConfig($storageDir, $storageFile, $config) {
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0775, true);
    }
    file_put_contents($storageFile, json_encode($config, JSON_PRETTY_PRINT), LOCK_EX);
}

function defaultScreen($screenId) {
    return [
        'id' => (int)$screenId,
        'mode' => 'plan',
        'plan_id' => null,
        'image_path' => null,
        'background_path' => null,
        'updated_at' => null
    ];
}

function defaultGlobalAd() {
    return [
        'path' => null,
        'type' => null
    ];
}

function sanitizePath($path) {
    if ($path === null) {
        return null;
    }
    $path = trim((string)$path);
    if ($path === '') {
        return null;
    }
    if (strpos($path, '..') !== false) {
        return null;
    }
    return ltrim($path, "/\\");
}

function inferAdType($path) {
    if (!$path) {
        return 'image';
    }
    $clean = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return in_array($clean, ['mp4', 'webm', 'ogg'], true) ? 'video' : 'image';
}

function sendResponse($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}
?>
