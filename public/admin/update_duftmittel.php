<?php
/**
 * Duftmittel-Update-Script für Inline-Editing
 */

// Session für Sicherheit starten
session_start();

// Konfiguration laden
require_once __DIR__ . '/../../src/config/config.php';

// Datenbankverbindung
require_once __DIR__ . '/../../src/db/connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['id']) || !isset($input['field']) || !isset($input['value'])) {
        throw new Exception('Invalid input data');
    }

    $duftmittelId = (int)$input['id'];
    $field = $input['field'];
    $value = trim($input['value']);

    // Validierung
    if (!in_array($field, ['name', 'beschreibung'])) {
        throw new Exception('Invalid field');
    }

    if ($field === 'name' && empty($value)) {
        throw new Exception('Name darf nicht leer sein');
    }

    // Update durchführen
    $sql = "UPDATE duftmittel SET {$field} = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $success = $stmt->execute([$value, $duftmittelId]);

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Update failed');
    }

} catch (Exception $e) {
    error_log('Duftmittel update error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
