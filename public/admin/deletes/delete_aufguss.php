<?php
// public/admin/delete_aufguss.php

session_start();
require_once __DIR__ . '/../../../src/config/config.php';
require_once __DIR__ . '/../../../src/db/connection.php';
require_once __DIR__ . '/../../../src/models/aufguss.php';

if (!isset($_GET['id'])) {
    header('Location: ../pages/aufguesse.php?error=no_id');
    exit;
}

$aufgussId = (int)$_GET['id'];

if ($aufgussId <= 0) {
    header('Location: ../pages/aufguesse.php?error=invalid_id');
    exit;
}

$aufgussModel = new Aufguss();

// Prüfen, ob der Aufguss existiert
if (!$aufgussModel->checkAufgussExists($aufgussId)) {
    header('Location: ../pages/aufguesse.php?error=not_found');
    exit;
}

// Aufguss löschen
$success = $aufgussModel->deleteAufguss($aufgussId);

if ($success) {
    header('Location: ../pages/aufguesse.php?deleted=1');
} else {
    header('Location: ../pages/aufguesse.php?error=delete_failed');
}
exit;
?>
