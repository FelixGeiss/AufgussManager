<?php
/**
 * PLAN LÖSCHEN
 *
 * Diese Seite verarbeitet das Löschen von Aufguss-Plänen über AJAX.
 * Sie wird von der JavaScript-Funktion deletePlan() aufgerufen.
 */

// Session für Sicherheit starten
session_start();

// Konfiguration laden
require_once __DIR__ . '/../../../src/config/config.php';

/**
 * SICHERHEIT: LOGIN-PRÜFUNG (auskommentiert für Entwicklung)
 *
 * In Produktion: Geschützter Admin-Bereich
 */
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('HTTP/1.1 403 Forbidden');
//     echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
//     exit;
// }

// Datenbankverbindung
require_once __DIR__ . '/../../../src/db/connection.php';

// Aufguss-Model laden
require_once __DIR__ . '/../../../src/models/aufguss.php';

// Nur POST-Requests erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'error' => 'Nur POST-Requests erlaubt']);
    exit;
}

header('Content-Type: application/json');

try {
    // Plan-ID aus POST-Daten lesen
    $planId = $_POST['plan_id'] ?? null;

    if (!$planId || !is_numeric($planId)) {
        throw new Exception('Ungültige Plan-ID');
    }

    // Plan löschen
    $aufgussModel = new Aufguss();
    $success = $aufgussModel->deletePlan((int)$planId);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Plan konnte nicht gelöscht werden']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>