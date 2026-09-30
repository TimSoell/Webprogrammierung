<?php
/**
 * @file        src/SitzungsSpeicher.php
 * @layer       Infrastruktur (Unterbau für src/bootstrap.php)
 * @description Speichert PHP-Sitzungen in der Datenbank statt als Datei.
 *
 *              Warum: Auf Vercel läuft jeder Aufruf in einer Funktion ohne
 *              gemeinsame Festplatte. Eine Sitzungsdatei, die ein Aufruf
 *              schreibt, sieht der nächste nicht - der Login wäre beim
 *              nächsten Klick verschwunden, und zwar nur manchmal, je nachdem
 *              welche Instanz gerade antwortet.
 *
 *              PHP ruft die Methoden dieser Klasse selbst auf: read() bei
 *              session_start(), write() am Ende des Aufrufs, destroy() beim
 *              Abmelden, gc() gelegentlich zum Aufräumen. Registriert wird sie
 *              in src/bootstrap.php mit session_set_save_handler().
 *
 *              Das SQL steht nicht hier, sondern in
 *              src/Repositories/SitzungRepository.php - die Regel "kein SQL
 *              außerhalb von src/Repositories/" gilt auch für Infrastruktur.
 * @see         src/bootstrap.php
 * @see         docs/decisions/ADR-0017-postgresql-auf-supabase.md
 */

declare(strict_types=1);

use Repositories\SitzungRepository;

final class SitzungsSpeicher implements SessionHandlerInterface
{
    private SitzungRepository $sitzungen;

    public function __construct()
    {
        $this->sitzungen = new SitzungRepository();
    }

    /** Nichts zu öffnen - die Verbindung baut Database bei Bedarf auf. */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /** Nichts zu schließen - die Verbindung endet mit dem Aufruf. */
    public function close(): bool
    {
        return true;
    }

    /**
     * Liefert die gespeicherten Daten einer Sitzung.
     *
     * @param string $id  Sitzungs-ID aus dem Cookie
     * @return string  '' für eine neue oder unbekannte Sitzung - so will es PHP
     */
    public function read(string $id): string
    {
        return $this->sitzungen->datenFinden($id) ?? '';
    }

    /**
     * Speichert die Sitzung. PHP ruft das am Ende jedes Aufrufs auf, auch
     * wenn sich nichts geändert hat - dadurch bleibt zuletzt aktuell, und
     * gc() löscht keine Sitzung, die gerade benutzt wird.
     *
     * @param string $id    Sitzungs-ID
     * @param string $data  serialisierte Sitzungsdaten
     * @return bool
     */
    public function write(string $id, string $data): bool
    {
        $this->sitzungen->speichern($id, $data, time());

        return true;
    }

    /**
     * Löscht eine Sitzung, z. B. beim Abmelden.
     *
     * @param string $id  Sitzungs-ID
     * @return bool
     */
    public function destroy(string $id): bool
    {
        $this->sitzungen->loeschen($id);

        return true;
    }

    /**
     * Räumt Sitzungen weg, die länger als erlaubt nicht benutzt wurden.
     *
     * @param int $max_lifetime  Sekunden, aus session.gc_maxlifetime
     * @return int  Anzahl gelöschter Sitzungen
     */
    public function gc(int $max_lifetime): int
    {
        return $this->sitzungen->abgelaufeneLoeschen(time() - $max_lifetime);
    }
}
