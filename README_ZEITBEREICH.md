# Zeitbereich für Aufgüsse - Implementierung

## Übersicht
Das System wurde erweitert, um Zeitbereiche (Anfang und Ende) für Aufgüsse zu unterstützen, anstatt nur einer einzelnen Uhrzeit.

## Durchgeführte Änderungen

### 1. Datenbank-Migration
**Dateien:**
- `database_migration.sql` - SQL-Script für die Datenbank-Änderungen
- `migrate_database.php` - Web-Interface für die Migration

**Änderungen:**
- Neue Spalten `zeit_anfang` und `zeit_ende` zur Tabelle `aufguesse` hinzugefügt
- Bestehende Daten werden migriert (zeit → zeit_anfang)
- Beispiel-Endzeiten werden gesetzt (+15 Minuten)

### 2. Datenbank-Modell
**Datei:** `src/models/aufguss.php`

**Änderungen:**
- `create()`-Methode unterstützt jetzt `zeit_anfang` und `zeit_ende`
- `getAufgüsseByPlan()`-Methode sortiert nach `zeit_anfang`
- Abwärtskompatibilität mit altem `zeit`-Feld

### 3. Admin-Formular
**Datei:** `public/admin/index.php`

**Änderungen:**
- Einzelner Time-Picker wurde durch zwei Time-Picker ersetzt (Anfang + Ende)
- Layout mit "Anfang - Ende" Anzeige
- JavaScript für Abwärtskompatibilität

### 4. Tabellen-Anzeige
**Datei:** `public/admin/aufguesse.php`

**Änderungen:**
- Zeit-Spalte zeigt jetzt Zeitbereich als "HH:MM - HH:MM" an
- Fallback auf altes Format bei fehlenden neuen Daten

## Installation

### Schritt 1: Datenbank-Migration
1. Öffnen Sie phpMyAdmin oder ein anderes MySQL-Tool
2. Wählen Sie die Datenbank `aufgussplan` aus
3. Führen Sie die folgenden SQL-Befehle aus:

```sql
ALTER TABLE aufguesse
ADD COLUMN zeit_anfang TIME NULL AFTER zeit,
ADD COLUMN zeit_ende TIME NULL AFTER zeit_anfang;

UPDATE aufguesse SET zeit_anfang = zeit WHERE zeit IS NOT NULL;

UPDATE aufguesse SET zeit_ende = ADDTIME(zeit_anfang, '00:15:00')
WHERE zeit_anfang IS NOT NULL AND zeit_ende IS NULL;
```

**Oder verwenden Sie das Web-Interface:**
1. Öffnen Sie `http://localhost/aufgussplan/migrate_database.php` im Browser
2. Die Migration wird automatisch ausgeführt

### Schritt 2: Testen
1. Öffnen Sie `http://localhost/aufgussplan/public/admin/index.php`
2. Füllen Sie das Formular aus und wählen Sie Anfang- und Endzeiten
3. Speichern Sie den Aufguss
4. Öffnen Sie `http://localhost/aufgussplan/public/admin/aufguesse.php`
5. Überprüfen Sie, dass der Zeitbereich korrekt angezeigt wird

## Abwärtskompatibilität
- Bestehende Aufgüsse ohne Zeitbereich funktionieren weiterhin
- Das alte `zeit`-Feld wird für Legacy-Daten verwendet
- Neue Aufgüsse verwenden die Zeitbereiche

## Fehlerbehebung
- Falls Zeitbereiche nicht angezeigt werden: Überprüfen Sie die Datenbank-Migration
- Falls Formular-Fehler: Überprüfen Sie die Browser-Konsole auf JavaScript-Fehler
- Bei Datenbank-Fehlern: Stellen Sie sicher, dass Sie die richtigen Berechtigungen haben