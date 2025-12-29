-- Migration: Zeitbereiche für Aufgüsse hinzufügen
-- Diese Migration fügt zeit_anfang und zeit_ende Spalten zur aufguesse Tabelle hinzu

-- Neue Spalten zur aufguesse Tabelle hinzufügen
ALTER TABLE aufguesse
ADD COLUMN zeit_anfang TIME NULL AFTER zeit,
ADD COLUMN zeit_ende TIME NULL AFTER zeit_anfang;

-- Bestehende zeit-Werte in zeit_anfang kopieren (für Abwärtskompatibilität)
UPDATE aufguesse SET zeit_anfang = zeit WHERE zeit IS NOT NULL;

-- Optional: zeit_ende mit zeit_anfang + 15 Minuten füllen (Beispielwert)
UPDATE aufguesse SET zeit_ende = ADDTIME(zeit_anfang, '00:15:00') WHERE zeit_anfang IS NOT NULL AND zeit_ende IS NULL;