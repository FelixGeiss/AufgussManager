<?php
/**
 * MITARBEITER-VERWALTUNG
 *
 * Diese Seite ermöglicht es Administratoren, Mitarbeiter zu verwalten:
 * - Neue Mitarbeiter hinzufügen
 * - Bestehende Mitarbeiter anzeigen
 * - Mitarbeiter bearbeiten/löschen (zukünftig)
 *
 * Als Anfänger solltest du wissen:
 * - Diese Seite zeigt, wie CRUD-Operationen implementiert werden
 * - Sie kombiniert PHP für Datenbankoperationen mit JavaScript für Interaktivität
 * - Modal-Fenster werden mit JavaScript gesteuert
 * - Daten werden über AJAX an API-Endpunkte gesendet
 *
 * URL: http://localhost/aufgussplan/admin/mitarbeiter.php
 */

// Session für Sicherheit und Nachrichten starten
session_start();

// Konfiguration laden
require_once __DIR__ . '/../../src/config/config.php';

/**
 * SICHERHEIT: LOGIN-PRÜFUNG (auskommentiert für Entwicklung)
 *
 * In Produktion: Nur eingeloggte Admins dürfen zugreifen
 */
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }

// Datenbankverbindung für zukünftige PHP-Operationen
require_once __DIR__ . '/../../src/db/connection.php';

// Hier könnte zukünftig PHP-Logik stehen:
// - Direkte Datenbankabfragen für Server-Side Rendering
// - Formularverarbeitung ohne JavaScript
// - Sicherheitstoken generieren
// Aber aktuell lädt alles JavaScript über AJAX
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mitarbeiter verwalten - Aufgussplan</title>
    <!-- Lokale Tailwind CSS -->
    <link rel="stylesheet" href="../dist/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="bg-gray-100">
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Aufgussplan Admin</h1>
            <div>
                <a href="index.php" class="mr-4 hover:underline">Dashboard</a>
                <a href="aufguesse.php" class="mr-4 hover:underline">Aufgüsse</a>
                <a href="logout.php" class="hover:underline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Mitarbeiter verwalten</h2>
            <button onclick="openAddModal()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                + Neuen Mitarbeiter hinzufügen
            </button>
        </div>

        <!-- Mitarbeiter-Liste -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Position</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-center">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="mitarbeiterTable">
                        <!-- Hier werden die Mitarbeiter dynamisch geladen -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal für neuen Mitarbeiter -->
    <div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4">Neuen Mitarbeiter hinzufügen</h3>
            <form id="addMitarbeiterForm">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border rounded">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Position</label>
                    <input type="text" name="position" class="w-full px-3 py-2 border rounded">
                </div>
                <div class="flex justify-end">
                    <button type="button" onclick="closeAddModal()" class="mr-2 px-4 py-2 text-gray-600 hover:text-gray-800">Abbrechen</button>
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Hinzufügen</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/mitarbeiter.js"></script>
</body>
</html>
