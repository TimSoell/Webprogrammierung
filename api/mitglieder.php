<?php
/**
 * @file        api/mitglieder.php
 * @layer       4 – API-Endpunkt
 * @description Registrierung eines neuen Mitglieds und Ändern des eigenen
 *              Namens.
 *
 *                POST  { vorname, nachname, email, passwort, passwortWiederholung }
 *                      -> 201 { "id": 7 }, Mitglied ist danach angemeldet
 *                PUT   { vorname, nachname }
 *                      -> 200 { vorname, nachname }, nur angemeldet
 *
 *              Das Konto (E-Mail, Passwort) legt die Login-Bibliothek in der
 *              Tabelle users an, die Stammdaten (Vor- und Nachname) legt
 *              MitgliedRepository in der Tabelle mitglieder an.
 *
 *              Wurde die E-Mail über "Freunde werben" eingeladen, bekommt der
 *              Werber hier seinen Gutschein - siehe
 *              docs/features/freunde-werben.md
 *
 *              DER NAME IST GESPERRT, SOBALD DAS PERSONAL DIE PERSON AM
 *              EMPFANG BESTAETIGT HAT - für immer, auch wenn der Nachweis
 *              später abläuft. Das Konto gehört dann dieser einen Person.
 *
 *              Davor ist er gesperrt, solange ein gültiger Nachweis vorliegt.
 *              Der Nachweis wurde gegen den Namen im Konto geprüft. Ließe sich
 *              der Name danach frei ändern, könnte jemand kurz den Namen eines
 *              anderen annehmen, dessen Ausweis hochladen und sich wieder
 *              umbenennen. Wer seinen Namen ändern will, entfernt zuerst den
 *              Nachweis - siehe
 *              docs/decisions/ADR-0023-name-aendern-und-ausweis-am-empfang.md
 * @see         assets/js/services/mitglieder.js
 * @see         src/Auth.php
 * @see         src/Repositories/MitgliedRepository.php
 * @see         src/Repositories/EmpfehlungRepository.php
 * @see         src/Repositories/NachweisRepository.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Delight\Auth\TooManyRequestsException;
use Delight\Auth\UserAlreadyExistsException;
use Repositories\EmpfehlungRepository;
use Repositories\GutscheinRepository;
use Repositories\MitgliedRepository;
use Repositories\NachweisRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    /**
     * Liest Vor- und Nachname aus der Eingabe und bricht mit 400 ab, wenn
     * einer fehlt oder zu lang ist. Registrierung und Namensänderung prüfen
     * damit dasselbe.
     *
     * @param array<string, mixed> $eingabe
     * @return array{0: string, 1: string}  Vorname, Nachname
     */
    $nameLesen = static function (array $eingabe): array {
        $vorname  = trim(Api::text($eingabe, 'vorname'));
        $nachname = trim(Api::text($eingabe, 'nachname'));

        if ($vorname === '' || mb_strlen($vorname) > 100) {
            Api::fehler(400, 'Bitte gib deinen Vornamen an (höchstens 100 Zeichen).');
        }
        if ($nachname === '' || mb_strlen($nachname) > 100) {
            Api::fehler(400, 'Bitte gib deinen Nachnamen an (höchstens 100 Zeichen).');
        }

        return [$vorname, $nachname];
    };

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe = Api::eingabe();

        [$vorname, $nachname] = $nameLesen($eingabe);

        $email        = Auth::emailNormalisieren(Api::text($eingabe, 'email'));
        $passwort     = Api::text($eingabe, 'passwort');
        $wiederholung = Api::text($eingabe, 'passwortWiederholung');

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
            $mitgliedId = (new MitgliedRepository())->anlegen($userId, $vorname, $nachname);
        } catch (Throwable $fehler) {
            // Ohne Stammdaten wäre das Konto halb fertig und die E-Mail-Adresse
            // trotzdem belegt. Also wieder entfernen, damit ein neuer Versuch geht.
            $auth->admin()->deleteUserById($userId);
            throw $fehler;
        }

        // Freunde werben: Wurde diese E-Mail eingeladen, bekommt der Werber
        // seinen Gutschein. Scheitert das, ist die Registrierung trotzdem
        // geglückt - das neue Mitglied soll davon nichts merken.
        try {
            (new EmpfehlungRepository())->registrierungVerbuchen($email, $mitgliedId, GutscheinRepository::neuerCode());
        } catch (Throwable $fehler) {
            error_log((string) $fehler);
        }

        $auth->login($email, $passwort);

        Api::antworten(['id' => $userId], 201);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $auth = Auth::instanz();

        if (!$auth->isLoggedIn()) {
            Api::fehler(401, 'Du bist nicht angemeldet.');
        }

        [$vorname, $nachname] = $nameLesen(Api::eingabe());

        $mitglieder = new MitgliedRepository();
        $mitgliedId = $mitglieder->idFindenNachUserId($auth->getUserId());

        if ($mitgliedId === null) {
            Api::fehler(500, 'Zu deinem Konto fehlen die Stammdaten.');
        }

        // Die Sperre sitzt hier und nicht im Browser - der Knopf auf der
        // Kontoseite erklärt nur, warum es gerade nicht geht.
        if ($mitglieder->empfangsstandFinden($mitgliedId)['bestaetigt_am'] !== null) {
            Api::fehler(409, 'Dein Name wurde am Empfang mit deinem Ausweis bestätigt und lässt sich nicht mehr ändern.');
        }
        if ((new NachweisRepository())->aktuellenFinden($mitgliedId) !== null) {
            Api::fehler(409, 'Solange ein gültiger Nachweis hinterlegt ist, lässt sich der Name nicht ändern. Entferne zuerst den Nachweis und lade ihn danach neu hoch.');
        }

        $mitglieder->nameAendern($mitgliedId, $vorname, $nachname);

        Api::antworten(['vorname' => $vorname, 'nachname' => $nachname]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
    // geht deshalb nicht an den Browser, sondern nur ins Log.
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
