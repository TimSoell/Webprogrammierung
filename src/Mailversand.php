<?php
/**
 * @file        src/Mailversand.php
 * @layer       Infrastruktur (Unterbau für Schicht 4 - Endpunkte)
 * @description Verschickt E-Mails über Resend. Die einzige Stelle im Projekt,
 *              die Mails versendet - so wie src/Database.php die einzige
 *              Stelle mit einer Datenbankverbindung ist.
 *
 *              Absender ist noreply@schwitzkasten-gym.de. Die Domain ist bei
 *              Resend verifiziert (DKIM, SPF und DMARC stehen im DNS bei
 *              Namecheap), Region Irland.
 *              Siehe docs/decisions/ADR-0027-mailversand-ueber-resend.md
 *
 *              OHNE API-SCHLÜSSEL läuft das Projekt im Demo-Modus weiter:
 *              verfuegbar() meldet false, und der Endpunkt schreibt den
 *              Inhalt ins Server-Log statt ihn zu verschicken. Das ist der
 *              Normalfall für alle im Team, die keinen Schlüssel haben.
 *
 *              KEIN COMPOSER-PAKET. Ein einzelner HTTPS-Aufruf mit curl wiegt
 *              weniger als eine zweite Bibliothek im vendor/-Ordner - wie bei
 *              src/Ausweispruefung.php.
 * @see         api/passwort-reset.php
 * @see         docs/features/mailversand.md
 * @see         https://resend.com/docs/api-reference/emails/send-email
 */

declare(strict_types=1);

final class Mailversand
{
    /** Die Adresse der Resend-API. */
    private const ENDPUNKT = 'https://api.resend.com/emails';

    /**
     * Absender jeder Mail. Muss auf der bei Resend verifizierten Domain
     * liegen, sonst lehnt Resend die Mail ab.
     */
    private const ABSENDER = 'SCHWITZKASTEN Athletic Club <noreply@schwitzkasten-gym.de>';

    /** Nach dieser Zeit gilt der Versand als gescheitert. */
    private const TIMEOUT_SEKUNDEN = 10;

    /**
     * Ist ein Schlüssel eingetragen? Ohne ihn verschickt das Projekt nichts,
     * sondern bleibt im Demo-Modus.
     *
     * @return bool
     */
    public static function verfuegbar(): bool
    {
        return trim((string) (CONFIG['mail']['api_key'] ?? '')) !== '';
    }

    /**
     * Verschickt eine Mail als reinen Text.
     *
     * @param string $an       Empfängeradresse
     * @param string $betreff  Betreffzeile
     * @param string $text     Inhalt als reiner Text, Zeilen mit \n getrennt
     * @return void
     * @throws RuntimeException Wenn Resend nicht erreichbar ist oder die Mail ablehnt
     */
    public static function senden(string $an, string $betreff, string $text): void
    {
        $anfrage = curl_init(self::ENDPUNKT);

        curl_setopt_array($anfrage, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT_SEKUNDEN,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim((string) CONFIG['mail']['api_key']),
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'from'    => self::ABSENDER,
                'to'      => [$an],
                'subject' => $betreff,
                'text'    => $text,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $antwort = curl_exec($anfrage);
        $status  = curl_getinfo($anfrage, CURLINFO_RESPONSE_CODE);
        $fehler  = curl_error($anfrage);

        curl_close($anfrage);

        if ($antwort === false) {
            throw new RuntimeException('Der Mailversand ist nicht erreichbar: ' . $fehler);
        }

        if ($status !== 200) {
            // Resend nennt bei 403 eine nicht verifizierte Domain, bei 422 ein
            // falsches Feld und bei 429 das erschöpfte Kontingent. Das gehört
            // ins Log, nicht an den Browser.
            error_log('Mailversand HTTP ' . $status . ': ' . $antwort);

            throw new RuntimeException('Der Mailversand hat mit Fehler ' . $status . ' geantwortet.');
        }
    }
}
