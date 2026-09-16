<?php
/**
 * @file        api/mitglieder.php
 * @layer       4 – API-Endpunkt
 * @description Registrierung eines neuen Mitglieds.
 *
 *                POST  { vorname, nachname, email, passwort, passwortWiederholung }
 *                      -> 201 { "id": 7 }, Mitglied ist danach angemeldet
 *
 *              Das Konto (E-Mail, Passwort) legt die Login-Bibliothek in der
 *              Tabelle users an, die Stammdaten (Vor- und Nachname) legt
 *              MitgliedRepository in der Tabelle mitglieder an.
 * @see         assets/js/services/mitglieder.js
 * @see         src/Auth.php
 * @see         src/Repositories/MitgliedRepository.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use Repositories\MitgliedRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe = Api::eingabe();

        $vorname      = trim(Api::text($eingabe, 'vorname'));
        $nachname     = trim(Api::text($eingabe, 'nachname'));
        $email        = Auth::emailNormalisieren(Api::text($eingabe, 'email'));
        $passwort     = Api::text($eingabe, 'passwort');
        $wiederholung = Api::text($eingabe, 'passwortWiederholung');

        if ($vorname === '' || mb_strlen($vorname) > 100) {
            Api::fehler(400, 'Bitte gib deinen Vornamen an (höchstens 100 Zeichen).');
        }
        if ($nachname === '' || mb_strlen($nachname) > 100) {
            Api::fehler(400, 'Bitte gib deinen Nachnamen an (höchstens 100 Zeichen).');
        }
        if ($email === null) {
            Api::fehler(400, 'Bitte gib eine gültige E-Mail-Adresse an, z. B. name@beispiel.de.');
        }

        $passwortFehler = Auth::passwortFehler($passwort);
        if ($passwortFehler !== null) {
            Api::fehler(400, $passwortFehler);
        }
        if ($passwort !== $wiederholung) {
            Api::fehler(400, 'Die beiden Passwörter stimmen nicht überein.');
        }

        $auth = Auth::instanz();

        try {
            // null als Benutzername und ohne Callback: Das Konto ist sofort
            // aktiv, eine Bestätigungs-E-Mail verlangt Issue #8 nicht.
            $userId = $auth->register($email, $passwort);
        } catch (UserAlreadyExistsException) {
            Api::fehler(409, 'Mit dieser E-Mail-Adresse gibt es schon ein Konto. Melde dich an oder setze dein Passwort zurück.');
        } catch (TooManyRequestsException) {
            Api::fehler(429, 'Zu viele Registrierungen von deinem Anschluss. Bitte versuche es später erneut.');
        }

        try {
            (new MitgliedRepository())->anlegen($userId, $vorname, $nachname);
        } catch (Throwable $fehler) {
            // Ohne Stammdaten wäre das Konto halb fertig und die E-Mail-Adresse
            // trotzdem belegt. Also wieder entfernen, damit ein neuer Versuch geht.
            $auth->admin()->deleteUserById($userId);
            throw $fehler;
        }

        $auth->login($email, $passwort);

        Api::antworten(['id' => $userId], 201);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
    // geht deshalb nicht an den Browser, sondern nur ins Log.
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
