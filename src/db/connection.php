<?php
/**
 * Datenbank-Verbindungsklasse mit PDO (PHP Data Objects)
 *
 * Diese Datei stellt eine sichere und wiederverwendbare Verbindung zur MySQL-Datenbank her.
 * Sie verwendet das Singleton-Pattern, um sicherzustellen, dass nur eine Datenbankverbindung
 * pro PHP-Anfrage existiert (effizienter und sicherer).
 *
 * Als Anfänger solltest du wissen:
 * - PDO ist die moderne, sichere Art, mit Datenbanken in PHP zu arbeiten
 * - Prepared Statements verhindern SQL-Injection-Angriffe
 * - Singleton bedeutet: Nur eine Instanz der Klasse kann existieren
 * - Diese Klasse wird von den Models (z.B. Aufguss.php) verwendet
 */

// Konfiguration laden
require_once dirname(__DIR__) . '/config/config.php';

/**
 * Database-Klasse für die Datenbankverbindung
 *
 * Implementiert das Singleton-Pattern:
 * - Nur eine Instanz kann zur gleichen Zeit existieren
 * - Spart Ressourcen und verhindert Verbindungsprobleme
 */
class Database {
    /**
     * Die einzige Instanz dieser Klasse (Singleton)
     * Statische Variable, die die Instanz speichert
     */
    private static $instance = null;

    /**
     * Das PDO-Objekt für die Datenbankverbindung
     * Wird in __construct() initialisiert
     */
    private $pdo;

    /**
     * PRIVATER Konstruktor - kann nur von innerhalb der Klasse aufgerufen werden
     *
     * Das ist wichtig für das Singleton-Pattern:
     * Niemand von außen kann new Database() aufrufen!
     */
    private function __construct() {
        try {
            // DSN (Data Source Name) - beschreibt die Datenbank-Verbindung
            // mysql:host=localhost;dbname=aufgussplan;charset=utf8
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';

            // PDO-Verbindung erstellen mit den Zugangsdaten aus config.php
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);

            // Fehlerbehandlung: Bei Fehlern Exception werfen (statt still scheitern)
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Standard-Abrufmodus: Ergebnisse als assoziatives Array zurückgeben
            // Beispiel: $row['name'] statt $row[0]
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            // Bei Verbindungsfehlern: Script beenden und Fehlermeldung anzeigen
            // In Produktion würde man das eleganter handhaben (ohne Details anzeigen)
            die('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Singleton-Getter: Gibt die einzige Instanz zurück
     *
     * Diese Methode wird immer aufgerufen, um an die Datenbankverbindung zu kommen:
     * $db = Database::getInstance();
     */
    public static function getInstance() {
        // Falls noch keine Instanz existiert, eine neue erstellen
        if (self::$instance === null) {
            self::$instance = new self();
        }

        // Immer die gleiche Instanz zurückgeben
        return self::$instance;
    }

    /**
     * Gibt das PDO-Objekt zurück
     *
     * Diese Methode wird von anderen Klassen verwendet, um SQL-Abfragen auszuführen:
     * $pdo = Database::getInstance()->getConnection();
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * ============================================================================
     * HILFSFUNKTIONEN FÜR DATENBANK-OPERATIONEN
     * ============================================================================
     *
     * Diese Methoden erleichtern häufige Datenbank-Operationen.
     * Sie verwenden alle Prepared Statements für Sicherheit.
     */

    /**
     * Führt eine vorbereitete SQL-Abfrage aus
     *
     * @param string $sql - Die SQL-Abfrage mit Platzhaltern (?)
     * @param array $params - Die Parameter für die Platzhalter
     * @return PDOStatement - Das vorbereitete Statement-Objekt
     */
    public function query($sql, $params = []) {
        // Prepared Statement vorbereiten
        $stmt = $this->pdo->prepare($sql);

        // Parameter einsetzen und Abfrage ausführen
        $stmt->execute($params);

        // Statement zurückgeben (für fetchAll(), fetch(), etc.)
        return $stmt;
    }

    /**
     * Holt alle Zeilen einer Abfrage als Array
     *
     * Beispiel: $users = $db->fetchAll("SELECT * FROM users WHERE active = ?", [1]);
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Holt eine einzelne Zeile einer Abfrage
     *
     * Beispiel: $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [123]);
     */
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Einfügen neuer Daten in eine Tabelle
     *
     * Beispiel:
     * $id = $db->insert('users', ['name' => 'Max', 'email' => 'max@example.com']);
     *
     * @param string $table - Tabellenname
     * @param array $data - Assoziatives Array mit Spalten => Werten
     * @return string - Die ID des neuen Datensatzes
     */
    public function insert($table, $data) {
        // Spaltennamen aus dem Array extrahieren
        $columns = implode(', ', array_keys($data));

        // Platzhalter (:name, :email) für Prepared Statement erstellen
        $placeholders = ':' . implode(', :', array_keys($data));

        // SQL-Statement zusammenbauen
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        // Abfrage ausführen
        $this->query($sql, $data);

        // ID des neuen Datensatzes zurückgeben
        return $this->pdo->lastInsertId();
    }

    /**
     * Aktualisiert vorhandene Daten in einer Tabelle
     *
     * Beispiel:
     * $affectedRows = $db->update('users', ['name' => 'Neuer Name'], 'id = ?', [123]);
     *
     * @param string $table - Tabellenname
     * @param array $data - Zu aktualisierende Daten
     * @param string $where - WHERE-Bedingung mit Platzhaltern
     * @param array $whereParams - Parameter für WHERE-Bedingung
     * @return int - Anzahl der aktualisierten Zeilen
     */
    public function update($table, $data, $where, $whereParams = []) {
        // SET-Teil erstellen: "name = :name, email = :email"
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "$column = :$column";
        }
        $setClause = implode(', ', $setParts);

        // Vollständiges SQL-Statement
        $sql = "UPDATE $table SET $setClause WHERE $where";

        // Parameter zusammenführen (UPDATE-Daten + WHERE-Parameter)
        $params = array_merge($data, $whereParams);

        // Abfrage ausführen und Anzahl betroffener Zeilen zurückgeben
        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Löscht Daten aus einer Tabelle
     *
     * Beispiel:
     * $deletedRows = $db->delete('users', 'id = ?', [123]);
     *
     * @param string $table - Tabellenname
     * @param string $where - WHERE-Bedingung
     * @param array $params - Parameter für WHERE-Bedingung
     * @return int - Anzahl der gelöschten Zeilen
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params)->rowCount();
    }
}

/**
 * GLOBALE DATENBANK-INSTANZ
 *
 * Diese Variable wird in anderen Dateien verwendet:
 * require_once 'connection.php';
 * $db->fetchAll("SELECT * FROM mitarbeiter");
 */
$db = Database::getInstance()->getConnection();
?>
