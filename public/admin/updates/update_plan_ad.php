<?php
/**
 * Plan-Werbung Upload + Einstellungen
 */

session_start();

require_once __DIR__ . '/../../../src/config/config.php';
require_once __DIR__ . '/../../../src/db/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $planId = (int)($_POST['plan_id'] ?? 0);
    $enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 0;
    $intervalMinutes = isset($_POST['interval_minutes']) ? (int)$_POST['interval_minutes'] : 10;
    $durationSeconds = isset($_POST['duration_seconds']) ? (int)$_POST['duration_seconds'] : 10;

    if ($planId <= 0) {
        throw new Exception('Ungueltige Plan-ID');
    }

    $intervalMinutes = max(1, $intervalMinutes);
    $durationSeconds = max(1, $durationSeconds);

    $mediaPath = null;
    $mediaType = null;
    $mediaName = null;

    if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['media'];

        if ($file['size'] > 50 * 1024 * 1024) {
            throw new Exception('Datei ist zu gross (max. 50MB)');
        }

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'video/mp4',
            'video/webm',
            'video/ogg'
        ];
        if (!in_array($file['type'], $allowedTypes, true)) {
            throw new Exception('Ungueltiger Dateityp');
        }

        $uploadBaseDir = UPLOAD_PATH;
        if (!is_dir($uploadBaseDir)) {
            mkdir($uploadBaseDir, 0755, true);
        }

        $uploadSubDir = 'werbung';
        $uploadDir = $uploadBaseDir . $uploadSubDir . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $stmt = $db->prepare("SELECT werbung_media FROM plaene WHERE id = ?");
        $stmt->execute([$planId]);
        $oldMedia = $stmt->fetchColumn();
        if ($oldMedia && file_exists($uploadBaseDir . $oldMedia)) {
            unlink($uploadBaseDir . $oldMedia);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Fehler beim Speichern der Datei');
        }

        $mediaPath = $uploadSubDir . '/' . $filename;
        $mediaType = str_starts_with($file['type'], 'video/') ? 'video' : 'image';
        $mediaName = $file['name'];

        $stmt = $db->prepare("UPDATE plaene SET werbung_media = ?, werbung_media_typ = ?, werbung_interval_minuten = ?, werbung_dauer_sekunden = ?, werbung_aktiv = ? WHERE id = ?");
        $stmt->execute([$mediaPath, $mediaType, $intervalMinutes, $durationSeconds, $enabled, $planId]);
    } else {
        $stmt = $db->prepare("UPDATE plaene SET werbung_interval_minuten = ?, werbung_dauer_sekunden = ?, werbung_aktiv = ? WHERE id = ?");
        $stmt->execute([$intervalMinutes, $durationSeconds, $enabled, $planId]);

        $stmt = $db->prepare("SELECT werbung_media, werbung_media_typ FROM plaene WHERE id = ?");
        $stmt->execute([$planId]);
        $row = $stmt->fetch();
        if ($row) {
            $mediaPath = $row['werbung_media'] ?? null;
            $mediaType = $row['werbung_media_typ'] ?? null;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'media_path' => $mediaPath,
            'media_type' => $mediaType,
            'media_name' => $mediaName
        ]
    ]);
} catch (Exception $e) {
    error_log('Plan ad update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
