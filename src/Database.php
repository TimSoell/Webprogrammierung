<?php
/**
 * @file        src/Database.php
 * @layer       Infrastruktur (Unterbau für Schicht 5 - Repositories)
 * @description Stellt die EINE Verbindung zur MySQL-Datenbank bereit.
 *
 *              Diese Klasse ist die einzige Stelle im Projekt, die eine
 *              PDO-Verbindung aufbaut. Repositories fragen sie hier ab,
 *              statt selbst "new PDO(...)" zu schreiben.
 *              Einzige Ausnahme: Die Login-Bibliothek baut über src/Auth.php
 *              ihre eigene Verbindung auf - ebenfalls erst bei Bedarf.
 *
 *              Verwendung in einem Repository:
 *                  $pdo = Database::connection();
 *                  $stmt = $pdo->prepare('SELECT * FROM kurse WHERE id = ?');
 *                  $stmt->execute([$id]);
 *                  $kurs = $stmt->fetch();
 *
 *              WICHTIG - immer prepare() + execute() benutzen, niemals
 *              Werte direkt in den SQL-String schreiben. Sonst ist die
 *              Anwendung offen für SQL-Injection.
 *
 *              Die Verbindung wird erst beim ersten Aufruf aufgebaut
 *              ("lazy"). Solange kein Repository Daten braucht, muss
 *              MySQL in XAMPP nicht laufen.
 * @see         docs/ARCHITECTURE.md
 * @see         database/schema.sql
 */

declare(strict_types=1);

final class Database
{
    /** Die einmal aufgebaute Verbindung. Null, solange noch keine gebraucht wurde. */
    private static ?PDO $connection = null;

    /** Diese Klasse wird nie instanziiert - sie hat nur statische Methoden. */
    private function __construct()
    {
    }

    /**
     * Liefert die PDO-Verbindung. Baut sie beim ersten Aufruf auf und
     * gibt bei jedem weiteren Aufruf dieselbe zurück.
     *
     * @throws RuntimeException wenn die Verbindung nicht aufgebaut werden kann
     */
    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        /** @var array $config */
        $config = require ROOT_PATH . '/config/config.php';
        $db     = $config['db'];

        // charset=utf8mb4 ist wichtig, damit Umlaute und Emojis korrekt
        // gespeichert werden. Ohne das gibt es später "Krafttraining fÃ¼r ...".
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $db['host'],
            $db['port'],
            $db['name']
        );

        try {
            self::$connection = new PDO($dsn, $db['user'], $db['password'], [
                // Fehler als Exception werfen, statt sie still zu schlucken.
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

                // fetch() liefert ein assoziatives Array ['name' => 'Wert'],
                // nicht zusätzlich noch die numerischen Schlüssel.
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

                // Echte Prepared Statements von MySQL benutzen statt sie in
                // PHP nachzubauen. Sicherer und schneller.
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Die Originalmeldung enthält unter Umständen das DB-Passwort,
            // deshalb wird sie nicht an den Browser weitergereicht.
            throw new RuntimeException(
                'Keine Verbindung zur Datenbank möglich. Läuft MySQL in XAMPP, '
                . 'und stimmen die Daten in config/config.php? '
                . 'Existiert die Datenbank "' . $db['name'] . '"?',
                0,
                $e
            );
        }

        return self::$connection;
    }
}
