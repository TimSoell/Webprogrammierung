<?php
/**
 * @file        src/Repositories/SitzungRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest und schreibt PHP-Sitzungen (Tabelle sitzungen).
 *
 *              Wird nur von src/SitzungsSpeicher.php benutzt, den PHP selbst
 *              bei jedem session_start() und am Ende jedes Aufrufs anspricht.
 *              Kein Endpunkt und keine Seite ruft diese Klasse auf.
 *
 *              Die Sitzungs-IDs stehen hier im Klartext. Wer die Tabelle lesen
 *              kann, könnte sich damit als jemand anderes anmelden - deshalb
 *              ist sie wie alle Tabellen per Row Level Security gesperrt,
 *              siehe database/schema.sql.
 * @see         database/schema.sql
 * @see         src/SitzungsSpeicher.php
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class SitzungRepository
{
    /**
     * Lädt den Inhalt einer Sitzung.
     *
     * @param string $id  Sitzungs-ID aus dem Cookie
     * @return string|null  PHPs serialisierte Sitzungsdaten, null wenn unbekannt
     */
    public function datenFinden(string $id): ?string
    {
        $stmt = Database::connection()->prepare(
            'SELECT daten FROM sitzungen WHERE id = ?'
        );
        $stmt->execute([$id]);

        $daten = $stmt->fetchColumn();

        return $daten === false ? null : (string) $daten;
    }

    /**
     * Legt eine Sitzung an oder überschreibt sie, in einer Anweisung.
     *
     * @param string $id       Sitzungs-ID
     * @param string $daten    serialisierte Sitzungsdaten
     * @param int    $zuletzt  Unix-Zeitstempel des Zugriffs
     * @return void
     */
    public function speichern(string $id, string $daten, int $zuletzt): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO sitzungen (id, daten, zuletzt)
                  VALUES (?, ?, ?)
             ON CONFLICT (id)
             DO UPDATE SET daten   = EXCLUDED.daten,
                           zuletzt = EXCLUDED.zuletzt'
        );
        $stmt->execute([$id, $daten, $zuletzt]);
    }

    /**
     * Löscht eine Sitzung, z. B. beim Abmelden.
     *
     * @param string $id  Sitzungs-ID
     * @return void
     */
    public function loeschen(string $id): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM sitzungen WHERE id = ?'
        );
        $stmt->execute([$id]);
    }

    /**
     * Löscht alle Sitzungen, auf die seit einem Zeitpunkt niemand mehr
     * zugegriffen hat.
     *
     * @param int $grenze  Unix-Zeitstempel; alles davor fliegt raus
     * @return int  Anzahl gelöschter Sitzungen
     */
    public function abgelaufeneLoeschen(int $grenze): int
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM sitzungen WHERE zuletzt < ?'
        );
        $stmt->execute([$grenze]);

        return $stmt->rowCount();
    }
}
