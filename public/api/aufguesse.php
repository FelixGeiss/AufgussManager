<?php
/**
 * API für Aufguss-Verwaltung mit Plan-Unterstützung
 *
 * Diese Datei stellt REST-ähnliche Endpunkte für die Verwaltung von Aufgüssen bereit:
 * - GET: Aufgüsse abrufen (mit optionaler Plan-Filterung)
 * - POST: Neuen Aufguss erstellen
 * - PUT: Aufguss aktualisieren
 * - DELETE: Aufguss löschen
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

// Datenbankverbindung für PHP-Operationen
require_once __DIR__ . '/../../src/db/connection.php';

// Aufguss-Modell und Service
require_once __DIR__ . '/../../src/models/aufguss.php';
require_once __DIR__ . '/../../src/services/aufgussService.php';

$aufgussModel = new Aufguss();
$aufgussService = new AufgussService();

/**
 * Hauptlogik basierend auf HTTP-Methode
 */
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Aufgüsse abrufen (mit optionaler Plan-Filterung)
            handleGetAufgüsse($aufgussModel);
            break;

        case 'POST':
            // Neuen Aufguss erstellen
            handleCreateAufguss($aufgussService);
            break;

        case 'PUT':
            // Aufguss aktualisieren
            handleUpdateAufguss($aufgussService);
            break;

        case 'DELETE':
            // Aufguss löschen
            handleDeleteAufguss($aufgussModel);
            break;

        default:
            sendResponse(false, 'HTTP-Methode nicht unterstützt', null, 405);
    }
} catch (Exception $e) {
    error_log('API-Fehler in aufguesse.php: ' . $e->getMessage());
    sendResponse(false, 'Interner Serverfehler', null, 500);
}

/**
 * GET: Aufgüsse abrufen
 */
function handleGetAufgüsse($aufgussModel) {
    $datum = $_GET['datum'] ?? null;
    $planId = $_GET['plan_id'] ?? null;

    if ($planId) {
        // Nur Aufgüsse eines bestimmten Plans
        $aufgüsse = $aufgussModel->getAufgüsseByPlan($planId);
    } elseif ($datum) {
        // Aufgüsse für ein bestimmtes Datum filtern
        $alleAufgüsse = $aufgussModel->getAll();
        $aufgüsse = array_filter($alleAufgüsse, function($aufguss) use ($datum) {
            return $aufguss['datum'] === $datum;
        });
        $aufgüsse = array_values($aufgüsse); // Indizes zurücksetzen
    } else {
        // Alle Aufgüsse
        $aufgüsse = $aufgussModel->getAll();
    }

    sendResponse(true, 'Aufgüsse erfolgreich abgerufen', ['aufguesse' => $aufgüsse]);
}

/**
 * POST: Neuen Aufguss erstellen
 */
function handleCreateAufguss($aufgussService) {
    try {
        // Daten aus Formular oder JSON lesen
        $data = $_POST;

        // JSON-Fallback für API-Calls
        if (empty($data)) {
            $data = json_decode(file_get_contents('php://input'), true) ?? [];
        }

        // Service für die Erstellung verwenden
        $result = $aufgussService->createAufguss($data);

        if ($result['success']) {
            sendResponse(true, 'Aufguss erfolgreich erstellt', ['aufguss_id' => $result['aufguss_id']]);
        } else {
            sendResponse(false, $result['error'] ?? 'Fehler beim Erstellen des Aufgusses', null, 400);
        }
    } catch (Exception $e) {
        error_log('Fehler beim Erstellen des Aufgusses: ' . $e->getMessage());
        sendResponse(false, 'Fehler beim Erstellen des Aufgusses', null, 500);
    }
}

/**
 * PUT: Aufguss aktualisieren
 */
function handleUpdateAufguss($aufgussService) {
    try {
        // JSON-Daten aus dem Request-Body lesen
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['id'])) {
            sendResponse(false, 'Aufguss-ID ist erforderlich', null, 400);
            return;
        }

        // Service für die Aktualisierung verwenden
        $result = $aufgussService->updateAufguss($input['id'], $input);

        if ($result['success']) {
            sendResponse(true, 'Aufguss erfolgreich aktualisiert');
        } else {
            sendResponse(false, $result['error'] ?? 'Fehler beim Aktualisieren des Aufgusses', null, 400);
        }
    } catch (Exception $e) {
        error_log('Fehler beim Aktualisieren des Aufgusses: ' . $e->getMessage());
        sendResponse(false, 'Fehler beim Aktualisieren des Aufgusses', null, 500);
    }
}

/**
 * DELETE: Aufguss löschen
 */
function handleDeleteAufguss($aufgussModel) {
    try {
        // Aufguss-ID aus URL-Parameter lesen
        $aufgussId = $_GET['id'] ?? null;

        if (!$aufgussId) {
            sendResponse(false, 'Aufguss-ID ist erforderlich', null, 400);
            return;
        }

        // Prüfe, ob die ID numerisch ist
        if (!is_numeric($aufgussId)) {
            sendResponse(false, 'Aufguss-ID muss numerisch sein', null, 400);
            return;
        }

        $aufgussId = (int)$aufgussId;

        // Prüfe, ob der Aufguss existiert
        if (!$aufgussModel->checkAufgussExists($aufgussId)) {
            sendResponse(false, 'Aufguss nicht gefunden', null, 404);
            return;
        }

        // Aufguss aus der Datenbank löschen
        $success = $aufgussModel->deleteAufguss($aufgussId);

        if ($success) {
            sendResponse(true, 'Aufguss erfolgreich gelöscht');
        } else {
            sendResponse(false, 'Aufguss konnte nicht gelöscht werden', null, 500);
        }
    } catch (Exception $e) {
        error_log('Fehler beim Löschen des Aufgusses: ' . $e->getMessage());
        sendResponse(false, 'Fehler beim Löschen des Aufgusses', null, 500);
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