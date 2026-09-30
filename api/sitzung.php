<?php
/**
 * @file        api/sitzung.php
 * @layer       4 – API-Endpunkt
 * @description Anmelden, Abmelden und "Wer ist gerade angemeldet?".
 *
 *                GET     -> { vorname, nachname, email, profilbild } oder 401
 *                POST    { email, passwort } -> anmelden
 *                PUT     { profilbild } -> Profilbild speichern
 *                DELETE  -> abmelden
 *
 *              Kein Tabellenname, weil die Sitzung in der PHP-Session liegt,
 *              nicht in einer eigenen Tabelle.
 *
 *              Bei falschen Anmeldedaten gibt es absichtlich EINE Meldung für
 *              beide Fälle (Adresse unbekannt, Passwort falsch). Sonst ließe
 *              sich durchprobieren, welche E-Mail-Adressen ein Konto haben.
 * @see         assets/js/services/mitglieder.js
 * @see         src/Auth.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Delight\Auth\InvalidEmailException;
use Delight\Auth\InvalidPasswordException;
use Delight\Auth\TooManyRequestsException;
use Repositories\MitgliedRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = Auth::instanz();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!$auth->isLoggedIn()) {
            Api::fehler(401, 'Du bist nicht angemeldet.');
        }

        $mitglied = (new MitgliedRepository())->findenNachUserId($auth->getUserId());

        Api::antworten([
            'vorname'  => $mitglied['vorname'] ?? '',
            'nachname' => $mitglied['nachname'] ?? '',
            'email'    => $auth->getEmail(),
            'profilbild' => $mitglied['profilbild'] ?? null,
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!$auth->isLoggedIn()) {
            Api::fehler(401, 'Du bist nicht angemeldet.');
        }

        $eingabe = Api::eingabe();
        $profilbild = Api::text($eingabe, 'profilbild');
        $bilddaten = base64_decode($profilbild, true);

        if ($profilbild === '' || strlen($profilbild) > 1500000 || $bilddaten === false) {
            Api::fehler(400, 'Das Profilbild ist ungültig oder zu groß.');
        }

        $bild = getimagesizefromstring($bilddaten);
        if ($bild === false || $bild['mime'] !== 'image/jpeg'
            || $bild[0] > 512 || $bild[1] > 512 || strlen($bilddaten) > 1100000) {
            Api::fehler(400, 'Bitte wähle ein gültiges JPEG-Profilbild aus.');
        }

        (new MitgliedRepository())->profilbildSpeichern($auth->getUserId(), $profilbild);
        Api::antworten(['gespeichert' => true]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe = Api::eingabe();

        // Ungültige Adresse wird zu '' - die Bibliothek meldet dann
        // InvalidEmailException und es kommt dieselbe Meldung wie immer.
        $email    = Auth::emailNormalisieren(Api::text($eingabe, 'email')) ?? '';
        $passwort = Api::text($eingabe, 'passwort');

        try {
            $auth->login($email, $passwort);
        } catch (InvalidEmailException | InvalidPasswordException) {
            Api::fehler(401, 'E-Mail-Adresse oder Passwort ungültig.');
        } catch (TooManyRequestsException) {
            Api::fehler(429, 'Zu viele Fehlversuche. Bitte warte etwas und versuche es dann erneut.');
        }

        Api::antworten(['angemeldet' => true]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Auch ohne Rumpf: eingabe() prüft den Content-Type (Schutz vor CSRF).
        Api::eingabe();

        $auth->logOut();
        $auth->destroySession();

        Api::antworten(['angemeldet' => false]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
