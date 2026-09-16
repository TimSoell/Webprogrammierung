<?php
/**
 * @file        src/Api.php
 * @layer       Infrastruktur (Unterbau für Schicht 4 – Endpunkte)
 * @description Die Handgriffe, die jeder Endpunkt braucht: JSON-Rumpf lesen,
 *              einzelne Textfelder daraus holen, antworten.
 *
 *              Entstanden mit dem Mitglieder-Login, weil dort drei Endpunkte
 *              exakt dieselben Zeilen gebraucht hätten.
 * @see         api/README.md
 * @see         assets/js/services/api.js
 */

declare(strict_types=1);

final class Api
{
    /** Diese Klasse wird nie instanziiert - sie hat nur statische Methoden. */
    private function __construct()
    {
    }

    /**
     * Liest den JSON-Rumpf der Anfrage.
     *
     * Kommt kein JSON an, bricht der Endpunkt mit 415 ab. Das ist mehr als
     * Ordnung - es schützt vor CSRF: Ein Formular auf einer fremden Website
     * kann keinen Content-Type application/json schicken, und fetch() von
     * einer fremden Website bräuchte dafür eine CORS-Freigabe, die wir nie
     * erteilen. Jeder Endpunkt, der etwas ändert, ruft deshalb diese Methode auf.
     *
     * @return array<string, mixed>  Die Felder; leer, wenn der Rumpf leer war
     */
    public static function eingabe(): array
    {
        $typ = $_SERVER['CONTENT_TYPE'] ?? '';

        if (!str_starts_with($typ, 'application/json')) {
            self::fehler(415, 'Die Anfrage muss JSON enthalten.');
        }

        $daten = json_decode((string) file_get_contents('php://input'), true);

        return is_array($daten) ? $daten : [];
    }

    /**
     * Holt ein Textfeld aus der Eingabe.
     *
     * Fehlt das Feld oder ist es kein Text (z. B. eine Zahl oder ein Array),
     * kommt '' zurück. Dadurch muss der Endpunkt nur noch auf '' prüfen.
     *
     * @param array<string, mixed> $eingabe  Rückgabe von eingabe()
     * @param string               $feld     Name des Feldes, z. B. 'email'
     * @return string
     */
    public static function text(array $eingabe, string $feld): string
    {
        return is_string($eingabe[$feld] ?? null) ? $eingabe[$feld] : '';
    }

    /**
     * Gibt Daten als JSON aus und beendet den Endpunkt.
     *
     * @param array<string, mixed> $daten
     * @param int                  $status  HTTP-Statuscode, Standard 200
     * @return void  Kehrt nie zurück
     */
    public static function antworten(array $daten, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($daten, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Antwortet mit einer Fehlermeldung im Format, das
     * assets/js/services/api.js erwartet: { "error": "..." }
     *
     * @param int    $status   400, 401, 405, 409, 415, 429 oder 500
     * @param string $meldung  Text für Menschen, wird im Formular angezeigt
     * @return void  Kehrt nie zurück
     */
    public static function fehler(int $status, string $meldung): void
    {
        self::antworten(['error' => $meldung], $status);
    }
}
