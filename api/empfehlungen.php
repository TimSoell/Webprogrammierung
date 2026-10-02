<?php
/**
 * @file        api/empfehlungen.php
 * @layer       4 – API-Endpunkt
 * @description "Freunde werben": die eigenen Einladungen lesen und eine neue
 *              eintragen.
 *
 *                GET                    -> { einladungen: [...] }
 *                POST  { name, email }  -> 201 { eingeladen: true }
 *                                       -> 409 schon eingeladen, schon
 *                                          registriert oder zu viele offen
 *
 *              Beides nur angemeldet, sonst 401. Ohne Konto wüsste niemand,
 *              wem der Gutschein gehört.
 *
 *              Die Seite verschickt KEINE E-Mail an den Freund. Der Werber
 *              gibt den Link zur Registrierung selbst weiter. Den Gutschein
 *              gibt es, sobald sich jemand mit genau dieser E-Mail
 *              registriert - das verbucht api/mitglieder.php.
 * @see         assets/js/services/empfehlungen.js
 * @see         src/Repositories/EmpfehlungRepository.php
 * @see         docs/features/freunde-werben.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\EmpfehlungRepository;
use Repositories\MitgliedRepository;

header('Content-Type: application/json; charset=utf-8');

/**
 * So viele Einladungen dürfen gleichzeitig offen sein. Ohne Grenze könnte
 * jemand hunderte fremde Adressen eintragen und auf zufällige
 * Registrierungen hoffen.
 */
const OFFENE_HOECHSTENS = 5;

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Melde dich an, um Freunde einzuladen.');
    }

    $mitglieder = new MitgliedRepository();
    $mitgliedId = $mitglieder->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        Api::fehler(409, 'Zu diesem Konto fehlen die Stammdaten.');
    }

    $empfehlungen = new EmpfehlungRepository();

    // --- Eigene Einladungen --------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        Api::antworten(['einladungen' => $empfehlungen->fuerWerberFinden($mitgliedId)]);
    }

    // --- Freund einladen -----------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe = Api::eingabe();
        $name    = trim(Api::text($eingabe, 'name'));
        $email   = Auth::emailNormalisieren(Api::text($eingabe, 'email'));

        // 120 passt in die Spalte empfehlungen.freund_name.
        if ($name === '' || mb_strlen($name) > 120) {
            Api::fehler(400, 'Bitte gib den Namen deines Freundes an (höchstens 120 Zeichen).');
        }

        if ($email === null) {
            Api::fehler(400, 'Bitte gib eine gültige E-Mail-Adresse an, z. B. name@beispiel.de.');
        }

        if ($email === $auth->getEmail()) {
            Api::fehler(400, 'Dich selbst kannst du nicht einladen.');
        }

        // Dass es das Konto gibt, verrät auch die Registrierung
        // (api/mitglieder.php) - hier geht also nichts Neues nach außen.
        if ($mitglieder->emailVergeben($email)) {
            Api::fehler(409, 'Mit dieser E-Mail-Adresse gibt es schon ein Konto.');
        }

        if ($empfehlungen->offeneZaehlen($mitgliedId) >= OFFENE_HOECHSTENS) {
            Api::fehler(409, 'Du hast schon ' . OFFENE_HOECHSTENS . ' offene Einladungen. Warte, bis sich jemand registriert hat.');
        }

        if ($empfehlungen->anlegen($mitgliedId, $name, $email) === null) {
            Api::fehler(409, 'Diese E-Mail-Adresse wurde schon eingeladen.');
        }

        Api::antworten(['eingeladen' => true], 201);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
    // geht deshalb nicht an den Browser, sondern nur ins Log.
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
