<?php
/**
 * @file        api/passwort-reset.php
 * @layer       4 – API-Endpunkt
 * @description "Passwort vergessen" in zwei Schritten.
 *
 *                POST  { email }
 *                      -> Link zum Zurücksetzen erzeugen (60 Minuten gültig)
 *                PUT   { selector, token, passwort, passwortWiederholung }
 *                      -> neues Passwort mit dem Link aus Schritt 1 setzen
 *
 *              DEMO-MODUS: Das Projekt verschickt (noch) keine E-Mails.
 *              Statt einer E-Mail landet der Link im Server-Log (lokal im
 *              Terminal von start.sh, auf Vercel in den Runtime Logs).
 *              Solange 'demo_reset_link' => true gesetzt ist, schickt der
 *              Endpunkt ihn zusätzlich als "demoLink" mit, damit die Seite
 *              ihn anzeigen kann. In Produktion ist das aus - sonst könnte
 *              jeder, der eine E-Mail-Adresse kennt, das Konto übernehmen.
 *              Echter Mailversand wird später genau an der markierten
 *              Stelle unten eingebaut.
 * @see         assets/js/services/mitglieder.js
 * @see         passwort-zuruecksetzen.php
 * @see         docs/features/mitglieder-login.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Delight\Auth\EmailNotVerifiedException;
use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidSelectorTokenPairException;
use Delight\Auth\ResetDisabledException;
use Delight\Auth\TokenExpiredException;
use Delight\Auth\TooManyRequestsException;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = Auth::instanz();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe = Api::eingabe();
        $email   = Auth::emailNormalisieren(Api::text($eingabe, 'email'));

        if ($email === null) {
            Api::fehler(400, 'Bitte gib eine gültige E-Mail-Adresse an, z. B. name@beispiel.de.');
        }

        $link = null;

        try {
            // Die Bibliothek speichert nur einen Hash des Tokens und ruft
            // die Funktion mit dem Klartext auf - genau einmal, genau hier.
            $auth->forgotPassword($email, static function (string $selector, string $token) use (&$link): void {
                $link = BASE_URL . 'passwort-zuruecksetzen.php?'
                    . http_build_query(['selector' => $selector, 'token' => $token]);
            }, 60 * 60);
        } catch (InvalidEmailException | EmailNotVerifiedException | ResetDisabledException) {
            // Kein (zurücksetzbares) Konto mit dieser Adresse. Nach außen
            // antworten wir trotzdem wie im Erfolgsfall - sonst ließe sich
            // hierüber prüfen, welche Adressen registriert sind.
        } catch (TooManyRequestsException) {
            Api::fehler(429, 'Du hast schon mehrere Links angefordert. Bitte warte etwas.');
        }

        if ($link !== null) {
            // ---------------------------------------------------------------
            // HIER WIRD SPÄTER DIE E-MAIL VERSCHICKT.
            // Bis dahin steht der Link im Server-Log.
            // ---------------------------------------------------------------
            error_log('[Demo-Mail] Passwort-Reset für ' . $email . ': ' . $link);
        }

        $antwort = [
            'nachricht' => 'Falls es ein Konto mit dieser Adresse gibt, ist der Link zum Zurücksetzen unterwegs. Er ist 60 Minuten gültig.',
        ];

        if ($config['demo_reset_link'] && $link !== null) {
            $antwort['demoLink'] = $link;
        }

        Api::antworten($antwort);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $eingabe      = Api::eingabe();
        $passwort     = Api::text($eingabe, 'passwort');
        $wiederholung = Api::text($eingabe, 'passwortWiederholung');

        $passwortFehler = Auth::passwortFehler($passwort);
        if ($passwortFehler !== null) {
            Api::fehler(400, $passwortFehler);
        }
        if ($passwort !== $wiederholung) {
            Api::fehler(400, 'Die beiden Passwörter stimmen nicht überein.');
        }

        try {
            // Setzt das Passwort, löscht den Link (nur einmal nutzbar) und
            // meldet das Konto auf allen anderen Geräten ab.
            $auth->resetPassword(Api::text($eingabe, 'selector'), Api::text($eingabe, 'token'), $passwort);
        } catch (InvalidSelectorTokenPairException | TokenExpiredException | ResetDisabledException) {
            Api::fehler(400, 'Dieser Link ist ungültig oder abgelaufen. Bitte fordere einen neuen an.');
        } catch (TooManyRequestsException) {
            Api::fehler(429, 'Zu viele Versuche. Bitte warte etwas und versuche es dann erneut.');
        }

        Api::antworten(['geaendert' => true]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
