<?php
/**
 * @file        src/Database.php
 * @layer       Infrastruktur (Unterbau für Schicht 5 - Repositories)
 * @description Stellt die EINE Verbindung zur PostgreSQL-Datenbank bei
 *              Supabase bereit.
 *
 *              Diese Klasse ist die einzige Stelle im Projekt, die eine
 *              PDO-Verbindung aufbaut. Repositories fragen sie hier ab,
 *              statt selbst "new PDO(...)" zu schreiben.
 *              Auch die Login-Bibliothek bekommt über src/Auth.php genau
 *              diese Verbindung - pro Seitenaufruf gibt es also nur eine.
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
 *              ("lazy"). Weil die Sitzungen in der Datenbank liegen
 *              (src/SitzungsSpeicher.php), passiert das aber auf jeder Seite.
 *
 *              Verbunden wird über den Pooler von Supabase im Session-Modus
 *              (Port 5432). Die direkte Adresse db.<projekt>.supabase.co ist
 *              im kostenlosen Plan nur über IPv6 erreichbar, der
 *              Transaktions-Modus (Port 6543) kann keine Prepared Statements,
 *              und die braucht die Login-Bibliothek.
 * @see         docs/ARCHITECTURE.md
 * @see         docs/decisions/ADR-0017-postgresql-auf-supabase.md
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

        $db = CONFIG['db'];

        // sslmode=require: Die Verbindung läuft über das offene Internet,
        // also nie unverschlüsselt. Umlaute brauchen keinen eigenen Schalter -
        // PostgreSQL bei Supabase arbeitet durchgehend mit UTF-8.
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s;sslmode=require',
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

                // Echte Prepared Statements der Datenbank benutzen statt sie
                // in PHP nachzubauen. Sicherer und schneller.
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Die Originalmeldung enthält unter Umständen das DB-Passwort,
            // deshalb wird sie nicht an den Browser weitergereicht.
            throw new RuntimeException(
                'Keine Verbindung zur Datenbank möglich. Stimmen die Daten in '
                . 'config/config.php (auf Vercel: die Umgebungsvariablen DB_*)? '
                . 'Ist das Supabase-Projekt pausiert? Ist pdo_pgsql in der php.ini '
                . 'eingeschaltet?',
                0,
                $e
            );
        }

        return self::$connection;
    }
}
