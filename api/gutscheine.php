<?php
/**
 * @file        api/gutscheine.php
 * @layer       4 – API-Endpunkt
 * @description Die eigenen Gutscheine lesen und einen einlösen.
 *
 *                GET              -> { gutscheine: [...] }
 *                POST  { code }   -> { gratisVon, gratisBis }
 *                                 -> 404 gibt es nicht (oder gehört jemand anderem)
 *                                 -> 409 schon eingelöst oder kein Basisplan
 *
 *              Beides nur angemeldet, sonst 401.
 *
 *              Einen Gutschein gibt es für eine geworbene Registrierung,
 *              siehe api/mitglieder.php. Er macht den BASISPLAN drei Monate
 *              gratis und lässt sich deshalb nur einlösen, wenn der Basisplan
 *              läuft oder zum nächsten Monatsersten vorgemerkt ist. Der
 *              Vertrag selbst bleibt unverändert - eingetragen wird nur der
 *              Gratiszeitraum, siehe
 *              docs/decisions/ADR-0021-gutschein-als-gratiszeitraum.md
 * @see         assets/js/services/gutscheine.js
 * @see         src/Repositories/GutscheinRepository.php
 * @see         docs/features/freunde-werben.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\GutscheinRepository;
use Repositories\MitgliedRepository;
use Repositories\MitgliedschaftRepository;

header('Content-Type: application/json; charset=utf-8');

/** tarife.kennung des Tarifs, für den der Gutschein gilt. */
const GUTSCHEIN_TARIF = 'basis';

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Melde dich an, um einen Gutschein einzulösen.');
    }

    $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        Api::fehler(409, 'Zu diesem Konto fehlen die Stammdaten.');
    }

    $gutscheine = new GutscheinRepository();

    // --- Eigene Gutscheine ---------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        Api::antworten(['gutscheine' => $gutscheine->fuerMitgliedFinden($mitgliedId)]);
    }

    // --- Einlösen ------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Groß und ohne Leerzeichen: "sk-7f3k 9qxm" soll auch gehen.
        $code = strtoupper(str_replace(' ', '', Api::text(Api::eingabe(), 'code')));

        if ($code === '') {
            Api::fehler(400, 'Bitte gib deinen Gutscheincode ein.');
        }

        $gutschein = $gutscheine->findenFuerMitglied($code, $mitgliedId);

        // Dieselbe Antwort für "gibt es nicht" und "gehört jemand anderem":
        // Sonst ließe sich durchprobieren, welche Codes es gibt.
        if ($gutschein === null) {
            Api::fehler(404, 'Diesen Gutschein gibt es nicht. Deine Codes stehen unter „Mein Konto“.');
        }

        if ($gutschein['eingeloest']) {
            Api::fehler(409, 'Diesen Gutschein hast du schon eingelöst.');
        }

        // Ab wann der Basisplan läuft: heute, wenn er der laufende Tarif ist
        // (null = heute), sonst ab dem vorgemerkten Wechsel.
        $mitgliedschaften = new MitgliedschaftRepository();
        $laufend          = $mitgliedschaften->aktiveFinden($mitgliedId);
        $geplant          = $mitgliedschaften->geplanteFinden($mitgliedId);

        if ($laufend !== null && $laufend['kennung'] === GUTSCHEIN_TARIF) {
            $abDatum = null;
        } elseif ($geplant !== null && $geplant['kennung'] === GUTSCHEIN_TARIF) {
            $abDatum = $geplant['beginnt_am'];
        } else {
            Api::fehler(409, 'Der Gutschein gilt für den Basisplan. Wähle zuerst den Basisplan, dann kannst du ihn einlösen.');
        }

        $zeitraum = $gutscheine->einloesen($gutschein['id'], $mitgliedId, $abDatum);

        // Zwei Klicks kurz hintereinander: Der zweite findet nichts mehr.
        if ($zeitraum === null) {
            Api::fehler(409, 'Diesen Gutschein hast du schon eingelöst.');
        }

        Api::antworten($zeitraum);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
    // geht deshalb nicht an den Browser, sondern nur ins Log.
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
