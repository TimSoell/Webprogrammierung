<?php
/**
 * @file        partials/passwort-kriterien.php
 * @layer       1 – Seite (Baustein)
 * @description Die Liste der Passwort-Anforderungen unter einem Passwortfeld.
 *              Eingebunden bei der Registrierung (anmelden.php) und beim
 *              Zurücksetzen (passwort-zuruecksetzen.php).
 *
 *              Das Abhaken beim Tippen übernimmt
 *              assets/js/components/passwort-kriterien.js. Die Werte in
 *              data-kriterium müssen zu den Schlüsseln dort passen.
 *
 *              Das Passwortfeld verweist mit aria-describedby="passwort-kriterien"
 *              auf diese Liste, damit Screenreader sie vorlesen.
 * @see         assets/js/components/passwort-kriterien.js
 * @see         src/Auth.php
 */

declare(strict_types=1);

if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Dieser Baustein kann nicht einzeln aufgerufen werden.');
}
?>
          <ul class="auth-rules" id="passwort-kriterien">
            <li data-kriterium="laenge">Mindestens 8 Zeichen, besser 12 oder mehr</li>
            <li data-kriterium="gross">Ein Großbuchstabe (A–Z)</li>
            <li data-kriterium="klein">Ein Kleinbuchstabe (a–z)</li>
            <li data-kriterium="zahl">Eine Zahl (0–9)</li>
            <li data-kriterium="sonderzeichen">Ein Sonderzeichen, z. B. ! ? # %</li>
          </ul>
