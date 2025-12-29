<?php

/**
 * ADMIN-DASHBOARD - Hauptseite des Admin-Bereichs
 *
 * Diese Seite ist das Herzstück der Verwaltung. Hier können Administratoren:
 * - Neue Aufgüsse erstellen und planen
 * - Übersicht über verschiedene Bereiche sehen
 * - Zu anderen Verwaltungsseiten navigieren
 *
 * Als Anfänger solltest du wissen:
 * - Diese Seite kombiniert PHP-Logik mit HTML-Formularen
 * - Sie verwendet Sessions für Sicherheit (auskommentiert)
 * - Formulare werden mit POST verarbeitet
 * - Daten kommen aus verschiedenen Datenbanktabellen
 *
 * URL: http://localhost/aufgussplan/admin/
 * Sicherheit: Sollte nur für eingeloggte Administratoren zugänglich sein
 */

// PHP-SESSION starten (für Login-Status, Nachrichten, etc.)
session_start();

// Konfiguration laden (Datenbank, Pfade, Sicherheit)
require_once __DIR__ . '/../../src/config/config.php';

/**
 * SICHERHEIT: LOGIN-PRÜFUNG
 *
 * Diese Prüfung ist auskommentiert, damit du die Seite zum Testen verwenden kannst.
 * In Produktion solltest du sie aktivieren:
 *
 * - Prüft, ob der Benutzer eingeloggt ist
 * - Leitet zu login.php um, falls nicht eingeloggt
 * - Schützt den Admin-Bereich vor unbefugtem Zugriff
 */
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     header('Location: login.php');
//     exit;
// }

/**
 * FORMULAR-VERARBEITUNG
 *
 * Wenn das Formular abgesendet wird (POST-Request), werden die Daten hier verarbeitet.
 * Dies ist die "Controller"-Logik in MVC-Architektur.
 */

// Variablen für Erfolgs-/Fehlermeldungen initialisieren
$message = '';  // Erfolgsmeldung
$errors = [];   // Array mit Fehlermeldungen

// Prüfen, ob ein POST-Request vorliegt (Formular abgesendet)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // AufgussService einbinden (für Geschäftslogik)
    require_once __DIR__ . '/../../src/services/aufgussService.php';

    // Service-Instanz erstellen
    $service = new AufgussService();

    // Formular verarbeiten lassen ($_POST = Formulardaten, $_FILES = hochgeladene Bilder)
    $result = $service->verarbeiteFormular($_POST, $_FILES);

    // Ergebnis prüfen
    if ($result['success']) {
        // ERFOLG: Meldung anzeigen und Formular zurücksetzen
        $message = $result['message'];
        $_POST = []; // Formularfelder leeren (optional)
    } else {
        // FEHLER: Fehlermeldungen sammeln
        $errors = $result['errors'];
    }
}

/**
 * DATEN FÜR SELECT-FELDER LADEN
 *
 * Das Formular hat Dropdown-Menüs für vorhandene Einträge.
 * Diese Daten müssen aus der Datenbank geladen werden.
 */

// Datenbankverbindung herstellen
require_once __DIR__ . '/../../src/db/connection.php';
$db = Database::getInstance()->getConnection();

// MITARBEITER für Dropdown laden (sortiert nach Name)
$mitarbeiter = $db->query("SELECT id, name FROM mitarbeiter ORDER BY name")->fetchAll();

// SAUNEN für Dropdown laden (sortiert nach Name)
$saunen = $db->query("SELECT id, name FROM saunen ORDER BY name")->fetchAll();

// DUFTMITTEL für Dropdown laden (sortiert nach Name)
$duftmittel = $db->query("SELECT id, name FROM duftmittel ORDER BY name")->fetchAll();

// AUFGÜSSE für Dropdown laden (sortiert nach Name)
$aufguesse = $db->query("SELECT id, name FROM aufguesse ORDER BY name")->fetchAll();

// PLÄNE für Dropdown laden (sortiert nach Name)
$plaene = $db->query("SELECT id, name FROM plaene ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Aufgussplan</title>
    <!-- Lokale Tailwind CSS -->
    <link rel="stylesheet" href="../dist/style.css">
    <!-- Admin-spezifische Styles -->
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>

<body class="bg-gray-100">
    <!-- NAVIGATION -->
    <nav class="bg-blue-600 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Aufgussplan Admin</h1>
            <div>
                <!-- Links zu anderen Admin-Seiten -->
                <a href="mitarbeiter.php" class="mr-4 hover:underline">Mitarbeiter</a>
                <a href="aufguesse.php" class="mr-4 hover:underline">Aufgüsse</a>
                <a href="mitarbeiter.php" class="mr-4 hover:underline">Statistiken</a>
                <a href="aufguesse.php" class="mr-4 hover:underline">Umfrage</a>
                <!-- Logout-Link (würde Session beenden) -->
                <a href="logout.php" class="hover:underline">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <!-- SEITENTITEL -->
        <h2 class="text-2xl font-bold mb-6">Dashboard</h2>

        <!-- ERFOLGS-/FEHLERMELDUNGEN -->
        <!-- Diese werden nur angezeigt, wenn das Formular verarbeitet wurde -->

        <?php if ($message): ?>
            <!-- ERFOLGSMELDUNG (grün) -->
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <!-- FEHLERMELDUNGEN (rot) -->
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD-INHALTE -->
        <!-- 3-spaltiges Grid-Layout für verschiedene Bereiche -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

            <div class="bg-white rounded-lg  p-6">
                <h3 class="text-lg font-semibold mb-2">Mitarbeiter</h3>
                <p class="text-gray-600">Verwalten Sie Ihre Mitarbeiter</p>
                <a href="mitarbeiter.php" class="mt-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Verwalten</a>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-2">Statistiken</h3>
                <p class="text-gray-600">Übersicht über Aktivitäten</p>
                <button class="mt-4 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Anzeigen</button>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-2">Umfrage</h3>
                <p class="text-gray-600">Umfrage Erstellen</p>
                <button class="mt-4 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Anzeigen</button>
            </div>

        </div>


        <div class="bg-white rounded-lg  p-6">
            <h3 class="text-lg font-semibold mb-2">Aufgüsse</h3>
            <p class="text-gray-600">Planen Sie Ihre Aufgüsse</p>
            <a href="aufguesse.php" class="mt-4 inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Verwalten</a>

            <!-- AUFGUSS-FORMULAR -->
            <!-- Ausklappbarer Bereich für das Formular -->
            <div class="mt-6 border-t border-gray-200 pt-6">
                <button type="button" onclick="toggleFormMain('main')" class="w-full flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-600 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Neuen Aufguss erstellen
                </button>

                <div id="form-main" class="hidden mt-4 bg-gray-50 p-4 rounded-lg">
                    <form class="space-y-6" method="POST" enctype="multipart/form-data">
                        <!-- Verstecktes Datum-Feld -->
                        <input type="hidden" name="datum" value="<?php echo date('Y-m-d'); ?>">
                        <!-- Plan-Auswahl -->
                        <div>
                            <label for="plan" class="block text-sm font-medium text-gray-900 mb-2 text-center">Plan zuordnen</label>
                            <input type="text" id="plan" name="plan_name" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)" placeholder="z.B. Wellness-Tag, Power-Aufgüsse" />

                            <!-- Select für vorhandene Pläne -->
                            <div class="mt-3">
                                <label for="plan-select" class="block text-sm font-medium text-gray-700 mb-1 text-center">Oder vorhandenen Plan auswählen:</label>
                                <select id="plan-select" name="plan_id" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)">
                                    <option class="border-2 border-solid text-center" style="border-color: var(--border-color)" value="">-- Plan auswählen --</option>
                                    <?php foreach ($plaene as $p): ?>
                                        <option class="text-center" value="<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Name des Aufgusses -->
                        <div>
                            <label for="aufguss-name" class="block text-sm font-medium text-gray-900 mb-2 text-center">Name des Aufgusses</label>
                            <input type="text" id="aufguss-name" name="aufguss_name" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)" placeholder="z.B. Wellness-Aufguss" />

                            <!-- Select für vorhandene Aufgüsse -->
                            <div class="mt-3">
                                <label for="aufguss-select" class="block text-sm font-medium text-gray-700 mb-1 text-center">Oder vorhandenen Aufguss auswählen:</label>
                                <select id="aufguss-select" name="aufguss_id" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)">
                                    <option class="border-2 border-solid text-center" style="border-color: var(--border-color)" value="">-- Aufguss auswählen --</option>
                                    <?php foreach ($aufguesse as $a): ?>
                                        <option class="text-center" value="<?php echo $a['id']; ?>">
                                            <?php echo htmlspecialchars($a['name'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Zeitbereich auswählen -->
                        <div class="text-center">
                            <label class="block text-sm font-medium text-gray-900 mb-2">Zeitbereich des Aufgusses</label>
                            <div class="flex justify-center items-center gap-4">
                                <div class="flex flex-col items-center">
                                    <label for="zeit_anfang" class="text-xs text-gray-600 mb-1">Anfang</label>
                                    <input type="time" id="zeit_anfang" name="zeit_anfang"
                                        class="rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid w-32"
                                        style="border-color: var(--border-color)">
                                </div>
                                <div class="flex items-center text-gray-400">
                                    <span class="text-sm">bis</span>
                                </div>
                                <div class="flex flex-col items-center">
                                    <label for="zeit_ende" class="text-xs text-gray-600 mb-1">Ende</label>
                                    <input type="time" id="zeit_ende" name="zeit_ende"
                                        class="rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid w-32"
                                        style="border-color: var(--border-color)">
                                </div>
                            </div>
                            <!-- Für Abwärtskompatibilität: verstecktes zeit-Feld -->
                            <input type="hidden" id="zeit" name="zeit" value="">
                        </div>

                        <!-- Verwendete Duftmittel -->
                        <div>
                            <label for="duftmittel" class="block text-sm font-medium text-gray-900 mb-2 text-center">Verwendete Duftmittel</label>
                            <input type="text" id="duftmittel" name="duftmittel" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)" placeholder="z.B. Eukalyptus, Minze" />

                            <!-- Select für vorhandene Duftmittel -->
                            <div class="mt-3">
                                <label for="duftmittel-select" class="block text-sm font-medium text-gray-700 mb-1 text-center">Oder vorhandene Duftmittel auswählen:</label>
                                <select id="duftmittel-select" name="duftmittel_id" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)">
                                    <option class="border-2 border-solid text-center" style="border-color: var(--border-color)" value="">-- Duftmittel auswählen --</option>
                                    <?php foreach ($duftmittel as $d): ?>
                                        <option class="text-center" value="<?php echo $d['id']; ?>">
                                            <?php echo htmlspecialchars($d['name'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Sauna -->
                        <div>
                            <label for="sauna" class="block text-sm font-medium text-gray-900 mb-2 text-center">Sauna</label>
                            <input type="text" id="sauna" name="sauna" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)" placeholder="z.B. Finnische Sauna" />

                            <!-- Select für Sauna (Datenbank) -->
                            <div class="mt-3">
                                <label for="sauna-select" class="block text-sm font-medium text-gray-700 mb-1 text-center">Oder vorhandene Sauna auswählen:</label>
                                <select id="sauna-select" name="sauna_id" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)">
                                    <option class="border-2 border-solid text-center" style="border-color: var(--border-color)" value="">-- Sauna auswählen --</option>
                                    <?php foreach ($saunen as $s): ?>
                                        <option class="text-center" value="<?php echo $s['id']; ?>">
                                            <?php echo htmlspecialchars($s['name'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Name des Aufgießers -->
                        <div>
                            <label for="aufgieser" class="block text-sm font-medium text-gray-900 mb-2 text-center">Name des Aufgießers</label>
                            <input type="text" id="aufgieser" name="aufgieser" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)" placeholder="z.B. Max Mustermann" />

                            <!-- Select für Mitarbeiter (Datenbank) -->
                            <div class="mt-3">
                                <label for="mitarbeiter-select" class="block text-sm font-medium text-gray-700 mb-1 text-center">Oder vorhandenen Mitarbeiter auswählen:</label>
                                <select id="mitarbeiter-select" name="mitarbeiter_id" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)">
                                    <option class="border-2 border-solid text-center" style="border-color: var(--border-color)" value="">-- Mitarbeiter auswählen --</option>
                                    <?php foreach ($mitarbeiter as $m): ?>
                                        <option class="text-center" value="<?php echo $m['id']; ?>">
                                            <?php echo htmlspecialchars($m['name'] ?? ''); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Stärke des Aufgusses -->
                        <div>
                            <label for="staerke" class="block text-sm font-medium text-gray-900 mb-2 text-center">Stärke des Aufgusses</label>
                            <select id="staerke" name="staerke" class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 border-2 border-solid text-center" style="border-color: var(--border-color)">
                                <option class="border-2 border-solid text-center" style="border-color: var(--border-color)" value="">-- Stärke wählen --</option>
                                <option class="text-center" value="1">1 - Sehr leicht</option>
                                <option class="text-center" value="2">2 - Leicht</option>
                                <option class="text-center" value="3">3 - Mittel</option>
                                <option class="text-center" value="4">4 - Stark</option>
                                <option class="text-center" value="5">5 - Sehr stark</option>
                                <option class="text-center" value="6">6 - Extrem stark</option>
                            </select>
                        </div>


                        <!-- Datenbank-Select Felder (für spätere Implementierung) -->
                        <div class="bg-white rounded-lg  p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Verknüpfungen</h3>





                            <!-- Bilder hochladen -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Bilder hochladen</h3>
                                <div class="space-y-8">
                                    <!-- Bild der Sauna -->
                                    <div>
                                        <label for="sauna-bild" class="block text-sm font-medium text-gray-900 mb-2">Bild der Sauna</label>
                                        <div class="mt-2 flex flex-col items-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10">
                                            <div class="text-center">
                                                <svg viewBox="0 0 24 24" fill="currentColor" data-slot="icon" aria-hidden="true" class="mx-auto size-12 text-gray-300">
                                                    <path d="M1.5 6a2.25 2.25 0 0 1 2.25-2.25h16.5A2.25 2.25 0 0 1 22.5 6v12a2.25 2.25 0 0 1-2.25 2.25H3.75A2.25 2.25 0 0 1 1.5 18V6ZM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0 0 21 18v-1.94l-2.69-2.689a1.5 1.5 0 0 0-2.12 0l-.88.879.97.97a.75.75 0 1 1-1.06 1.06l-5.16-5.159a1.5 1.5 0 0 0-2.12 0L3 16.061Zm10.125-7.81a1.125 1.125 0 1 1 2.25 0 1.125 1.125 0 0 1-2.25 0Z" clip-rule="evenodd" fill-rule="evenodd" />
                                                </svg>
                                                <div class="mt-4 flex flex-col text-sm text-gray-600">

                                                    <label for="sauna-bild" class="relative cursor-pointer rounded-md bg-transparent font-semibold text-indigo-600 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-indigo-600 hover:text-indigo-500">
                                                        <span>Sauna-Bild hochladen</span>
                                                        <input id="sauna-bild" name="sauna_bild" type="file" accept="image/*" class="sr-only" />
                                                    </label>

                                                    <p class="pl-1 flex">oder ziehen und ablegen</p>
                                                </div>
                                                <p class="text-xs text-gray-600">PNG, JPG, GIF bis zu 10MB</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bild des Mitarbeiters -->
                                    <div>
                                        <label for="mitarbeiter-bild" class="block text-sm font-medium text-gray-900 mb-2">Bild des Mitarbeiter</label>
                                        <div class="mt-2 flex flex-col items-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10">
                                            <div class="text-center">
                                                <svg viewBox="0 0 24 24" fill="currentColor" data-slot="icon" aria-hidden="true" class="mx-auto size-12 text-gray-300">
                                                    <path d="M18.685 19.097A9.723 9.723 0 0 0 21.75 12c0-5.385-4.365-9.75-9.75-9.75S2.25 6.615 2.25 12a9.723 9.723 0 0 0 3.065 7.097A9.716 9.716 0 0 0 12 21.75a9.716 9.716 0 0 0 6.685-2.653Zm-12.54-1.285A7.486 7.486 0 0 1 12 15a7.486 7.486 0 0 1 5.855 2.812A8.224 8.224 0 0 1 12 20.25a8.224 8.224 0 0 1-5.855-2.438ZM15.75 9a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" clip-rule="evenodd" fill-rule="evenodd" />
                                                </svg>
                                                <div class="mt-4 flex flex-col text-sm text-gray-600">

                                                    <label for="mitarbeiter-bild" class="relative cursor-pointer rounded-md bg-transparent font-semibold text-indigo-600 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-indigo-600 hover:text-indigo-500">
                                                        <span>Mitarbeiter-Bild hochladen</span>
                                                        <input id="mitarbeiter-bild" name="mitarbeiter_bild" type="file" accept="image/*" class="sr-only" />
                                                    </label>

                                                    <p class="pl-1 flex">oder ziehen und ablegen</p>
                                                </div>

                                                <p class="text-xs text-gray-600">PNG, JPG, GIF bis zu 10MB</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center justify-end gap-x-6 pt-6">
                                <button type="button" onclick="toggleFormMain('main')" class="text-sm font-semibold text-gray-900 hover:text-gray-700">Abbrechen</button>
                                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Speichern</button>
                            </div>
                    </form>
                </div>
            </div>


            <script src="../assets/js/admin.js"></script>
            <script src="../assets/js/admin-functions.js"></script>
</body>

</html>