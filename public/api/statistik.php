<?php
/**
 * API: Statistik-Logging fuer abgeschlossene Aufgüsse
 *
 * Erwartet: POST mit JSON {"aufguss_id": 123}
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nur POST erlaubt']);
    exit;
}

session_start();

require_once __DIR__ . '/../../src/config/config.php';
require_once __DIR__ . '/../../src/db/connection.php';

$input = json_decode(file_get_contents('php://input'), true);
$aufgussId = $input['aufguss_id'] ?? null;

if (!$aufgussId || !is_numeric($aufgussId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'aufguss_id fehlt oder ungueltig']);
    exit;
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT datum, aufguss_name_id, duftmittel_id, sauna_id, plan_id, staerke FROM aufguesse WHERE id = ?");
$stmt->execute([(int)$aufgussId]);
$aufguss = $stmt->fetch();

if (!$aufguss) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Aufguss nicht gefunden']);
    exit;
}

$datum = $aufguss['datum'] ?: date('Y-m-d');
$aufgussNameId = $aufguss['aufguss_name_id'] ?? null;
$duftmittelId = $aufguss['duftmittel_id'] ?? null;
$saunaId = $aufguss['sauna_id'] ?? null;
$planId = $aufguss['plan_id'] ?? null;
$staerke = $aufguss['staerke'] ?? null;

$stmt = $db->prepare("INSERT IGNORE INTO statistik_log (aufguss_id, datum) VALUES (?, ?)");
$stmt->execute([(int)$aufgussId, $datum]);
if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => true, 'message' => 'bereits geloggt']);
    exit;
}

// Null-sichere Suche, damit NULLs nicht doppelt zaehlen.
$stmt = $db->prepare(
    "SELECT id, anzahl FROM statistik
     WHERE datum = ?
       AND aufguss_name_id <=> ?
       AND duftmittel_id <=> ?
       AND sauna_id <=> ?
       AND plan_id <=> ?
       AND staerke <=> ?
     LIMIT 1"
);
$stmt->execute([$datum, $aufgussNameId, $duftmittelId, $saunaId, $planId, $staerke]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $db->prepare("UPDATE statistik SET anzahl = anzahl + 1 WHERE id = ?");
    $stmt->execute([(int)$existing['id']]);
} else {
    $stmt = $db->prepare(
        "INSERT INTO statistik (datum, aufguss_name_id, duftmittel_id, sauna_id, plan_id, staerke, anzahl)
         VALUES (?, ?, ?, ?, ?, ?, 1)"
    );
    $stmt->execute([$datum, $aufgussNameId, $duftmittelId, $saunaId, $planId, $staerke]);
}

echo json_encode(['success' => true]);
