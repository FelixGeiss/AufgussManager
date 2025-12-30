<?php
/**
 * STATISTIKEN
 *
 * Platzhalterseite fuer Statistik-Ansichten.
 *
 * URL: http://localhost/aufgussplan/admin/statistik.php
 */

session_start();

require_once __DIR__ . '/../../src/config/config.php';

// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken - Aufgussplan</title>
    <link rel="stylesheet" href="../dist/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h2 class="text-2xl font-bold mb-4">Statistiken</h2>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-600">
                Diese Seite ist vorbereitet. Hier kommen spaeter die Auswertungen hin.
            </p>
        </div>
    </div>
</body>
</html>
