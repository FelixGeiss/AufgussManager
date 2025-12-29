<?php
/**
 * API für Mitarbeiter-Verwaltung
 *
 * Diese Datei stellt REST-ähnliche Endpunkte für die Verwaltung von Mitarbeitern bereit:
 * - GET: Alle Mitarbeiter abrufen
 * - POST: Neuen Mitarbeiter erstellen
 * - PUT: Mitarbeiter aktualisieren
 * - DELETE: Mitarbeiter löschen
 */

// CORS-Header für JavaScript-Aufrufe
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// OPTIONS-Anfragen für CORS beantworten
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Session für Sicherheit starten
session_start();

// Konfiguration laden
require_once __DIR__ . '/../../src/config/config.php';

// Datenbankverbindung
require_once __DIR__ . '/../../src/db/connection.php';

$db = Database::getInstance()->getConnection();

/**
 * Hauptlogik basierend auf HTTP-Methode
 */
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Alle Mitarbeiter abrufen
            handleGetMitarbeiter($db);
            break;

        case 'POST':
            // Neuen Mitarbeiter erstellen
            handleCreateMitarbeiter($db);
            break;

        case 'PUT':
            // Mitarbeiter aktualisieren
            handleUpdateMitarbeiter($db);
            break;

        case 'DELETE':
            // Mitarbeiter löschen
            handleDeleteMitarbeiter($db);
            break;

        default:
            sendResponse(false, 'HTTP-Methode nicht unterstützt', null, 405);
    }
} catch (Exception $e) {
    error_log('API-Fehler in mitarbeiter.php: ' . $e->getMessage());
    sendResponse(false, 'Interner Serverfehler', null, 500);
}

/**
 * GET: Alle Mitarbeiter abrufen
 */
function handleGetMitarbeiter($db) {
    try {
        $stmt = $db->query("SELECT id, name, position, aktiv FROM mitarbeiter ORDER BY name ASC");
        $mitarbeiter = $stmt->fetchAll();
        sendResponse(true, 'Mitarbeiter erfolgreich abgerufen', ['mitarbeiter' => $mitarbeiter]);
    } catch (Exception $e) {
        sendResponse(false, 'Fehler beim Abrufen der Mitarbeiter', null, 500);
    }
}

/**
 * POST: Neuen Mitarbeiter erstellen
 */
function handleCreateMitarbeiter($db) {
    // JSON-Daten oder Form-Data lesen
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }

    // Validierung - Name kann leer sein, dann wird ein Platzhalter verwendet
    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        $name = 'Unbenannter Mitarbeiter';
    }

    try {
        $stmt = $db->prepare("INSERT INTO mitarbeiter (name, position) VALUES (?, ?)");
        $stmt->execute([
            $name,
            trim($input['position'] ?? '')
        ]);

        $mitarbeiterId = $db->lastInsertId();
        sendResponse(true, 'Mitarbeiter erfolgreich erstellt', ['mitarbeiter_id' => $mitarbeiterId]);
    } catch (Exception $e) {
        sendResponse(false, 'Fehler beim Erstellen des Mitarbeiters', null, 500);
    }
}

/**
 * PUT: Mitarbeiter aktualisieren
 */
function handleUpdateMitarbeiter($db) {
    // JSON-Daten lesen
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || empty($input['id'])) {
        sendResponse(false, 'Mitarbeiter-ID ist erforderlich', null, 400);
        return;
    }

    // Validierung - Name kann leer sein, dann wird ein Platzhalter verwendet
    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        $name = 'Unbenannter Mitarbeiter';
    }

    try {
        $stmt = $db->prepare("UPDATE mitarbeiter SET name = ?, position = ? WHERE id = ?");
        $stmt->execute([
            $name,
            trim($input['position'] ?? ''),
            $input['id']
        ]);

        sendResponse(true, 'Mitarbeiter erfolgreich aktualisiert');
    } catch (Exception $e) {
        sendResponse(false, 'Fehler beim Aktualisieren des Mitarbeiters', null, 500);
    }
}

/**
 * DELETE: Mitarbeiter löschen
 */
function handleDeleteMitarbeiter($db) {
    // Mitarbeiter-ID aus Query-Parameter oder Request-Body
    $mitarbeiterId = $_GET['id'] ?? null;

    if (!$mitarbeiterId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $mitarbeiterId = $input['id'] ?? null;
    }

    if (!$mitarbeiterId) {
        sendResponse(false, 'Mitarbeiter-ID ist erforderlich', null, 400);
        return;
    }

    try {
        $stmt = $db->prepare("DELETE FROM mitarbeiter WHERE id = ?");
        $stmt->execute([$mitarbeiterId]);

        sendResponse(true, 'Mitarbeiter erfolgreich gelöscht');
    } catch (Exception $e) {
        sendResponse(false, 'Fehler beim Löschen des Mitarbeiters', null, 500);
    }
}

/**
 * Hilfsfunktion für API-Antworten
 */
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