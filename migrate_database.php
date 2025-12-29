<?php
/**
 * Datenbank-Migration Script
 *
 * Führt die Migration für Zeitbereiche bei Aufgüssen aus.
 * Verwendung: http://localhost/aufgussplan/migrate_database.php
 */

require_once __DIR__ . '/src/db/connection.php';

echo "<h1>Datenbank-Migration: Zeitbereiche für Aufgüsse</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>";

try {
    $db = Database::getInstance()->getConnection();

    echo "<h2>Starte Migration...</h2>";

    // Neue Spalten hinzufügen
    echo "<p>1. Füge zeit_anfang und zeit_ende Spalten hinzu...</p>";
    $sql1 = "ALTER TABLE aufguesse
             ADD COLUMN zeit_anfang TIME NULL AFTER zeit,
             ADD COLUMN zeit_ende TIME NULL AFTER zeit_anfang";
    $db->exec($sql1);
    echo "<p style='color: green;'>✓ Spalten erfolgreich hinzugefügt</p>";

    // Bestehende Daten migrieren
    echo "<p>2. Migriere bestehende Zeit-Daten...</p>";
    $sql2 = "UPDATE aufguesse SET zeit_anfang = zeit WHERE zeit IS NOT NULL";
    $affected = $db->exec($sql2);
    echo "<p style='color: green;'>✓ $affected Datensätze migriert</p>";

    // Beispiel-Endzeiten setzen
    echo "<p>3. Setze Beispiel-Endzeiten (15 Minuten nach Anfang)...</p>";
    $sql3 = "UPDATE aufguesse SET zeit_ende = ADDTIME(zeit_anfang, '00:15:00')
             WHERE zeit_anfang IS NOT NULL AND zeit_ende IS NULL";
    $affected2 = $db->exec($sql3);
    echo "<p style='color: green;'>✓ $affected2 Endzeiten gesetzt</p>";

    echo "<h2 style='color: green;'>Migration erfolgreich abgeschlossen!</h2>";
    echo "<p>Die Datenbank wurde erfolgreich aktualisiert. Sie können jetzt:</p>";
    echo "<ul>";
    echo "<li><a href='public/admin/index.php'>Zur Admin-Seite gehen</a></li>";
    echo "<li><a href='public/admin/aufguesse.php'>Aufgüsse anzeigen</a></li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2 style='color: red;'>Fehler bei der Migration:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Bitte wenden Sie sich an den Administrator oder überprüfen Sie die Datenbankberechtigungen.</strong></p>";
}