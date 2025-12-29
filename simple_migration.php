<?php
/**
 * Einfache Datenbank-Migration für Zeitbereiche
 * Zu öffnen in: http://localhost/aufgussplan/simple_migration.php
 */

require_once __DIR__ . '/src/db/connection.php';

echo "<!DOCTYPE html><html><head><title>Datenbank-Migration</title><style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style></head><body>";
echo "<h1>Datenbank-Migration: Zeitbereiche für Aufgüsse</h1>";

try {
    $pdo = Database::getInstance()->getConnection();
    echo "<h2>Verbinde mit Datenbank...</h2>";
    echo "<p class='success'>✓ Verbindung erfolgreich</p>";

    // Spalten hinzufügen
    echo "<h2>1. Füge neue Spalten hinzu...</h2>";
    $sql = "ALTER TABLE aufguesse ADD COLUMN zeit_anfang TIME NULL AFTER zeit, ADD COLUMN zeit_ende TIME NULL AFTER zeit_anfang";
    echo "<pre>$sql</pre>";

    try {
        $pdo->exec($sql);
        echo "<p class='success'>✓ Spalten erfolgreich hinzugefügt</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "<p class='success'>✓ Spalten existieren bereits</p>";
        } else {
            throw $e;
        }
    }

    // Daten migrieren
    echo "<h2>2. Migriere bestehende Daten...</h2>";
    $sql = "UPDATE aufguesse SET zeit_anfang = zeit WHERE zeit IS NOT NULL AND zeit_anfang IS NULL";
    echo "<pre>$sql</pre>";
    $affected = $pdo->exec($sql);
    echo "<p class='success'>✓ $affected Datensätze migriert</p>";

    // Endzeiten setzen
    echo "<h2>3. Setze Beispiel-Endzeiten...</h2>";
    $sql = "UPDATE aufguesse SET zeit_ende = ADDTIME(zeit_anfang, '00:15:00') WHERE zeit_anfang IS NOT NULL AND zeit_ende IS NULL";
    echo "<pre>$sql</pre>";
    $affected = $pdo->exec($sql);
    echo "<p class='success'>✓ $affected Endzeiten gesetzt</p>";

    echo "<h2 class='success'>Migration erfolgreich abgeschlossen!</h2>";
    echo "<p>Sie können jetzt die neuen Zeitbereiche verwenden.</p>";
    echo "<p><a href='public/admin/index.php'>Zur Admin-Seite</a> | <a href='public/admin/aufguesse.php'>Aufgüsse anzeigen</a></p>";

} catch (Exception $e) {
    echo "<h2 class='error'>Fehler bei der Migration:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Lösung:</strong> Führen Sie die SQL-Befehle manuell in phpMyAdmin aus:</p>";
    echo "<pre>ALTER TABLE aufguesse
ADD COLUMN zeit_anfang TIME NULL AFTER zeit,
ADD COLUMN zeit_ende TIME NULL AFTER zeit_anfang;

UPDATE aufguesse SET zeit_anfang = zeit
WHERE zeit IS NOT NULL AND zeit_anfang IS NULL;

UPDATE aufguesse SET zeit_ende = ADDTIME(zeit_anfang, '00:15:00')
WHERE zeit_anfang IS NOT NULL AND zeit_ende IS NULL;</pre>";
}

echo "</body></html>";
?>