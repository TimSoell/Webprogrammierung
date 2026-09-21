<?php
/**
 * @file        api/kursbuchungen.php
 * @layer       4 – API-Endpunkt
 * @description Kurstermine buchen, die eigenen Buchungen lesen, stornieren.
 *
 *                GET                                  -> kommende eigene Buchungen
 *                POST    { terminId, datum, stufe }   -> 201 gebucht
 *                                                     -> 409 ausgebucht oder schon gebucht
 *                DELETE  { id }                       -> storniert, bis zum Terminbeginn
 *
 *              Alles nur für angemeldete Mitglieder, sonst 401.
 *
 *              Ob der Termin an dem Tag überhaupt stattfindet, prüft
 *              Terminplan::kursterminBuchbar() - dieselbe Rechnung, die dem
 *              Kalender die Termine liefert. Ob noch Platz ist, prüft
 *              KursbuchungRepository::buchen() unter Sperre.
 * @see         assets/js/services/kurstermine.js
 * @see         src/Repositories/KursbuchungRepository.php
 * @see         docs/features/terminkalender.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\KursbuchungRepository;
use Repositories\KursterminRepository;
use Repositories\MitgliedRepository;

header('Content-Type: application/json; charset=utf-8');

/** Die Meldung für einen vollen Termin. Steht so auch im Kalender, siehe kurskalender.js. */
const AUSGEBUCHT = 'Leider ist hier schon alles vollgeschwitzt.';

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Melde dich an, um einen Kurs zu buchen.');
    }

    $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        Api::fehler(409, 'Zu diesem Konto fehlen die Stammdaten.');
    }

    $buchungen = new KursbuchungRepository();
    $jetzt     = Terminplan::jetzt();

    // --- Eigene Buchungen ----------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $liste = array_values(array_filter(
            $buchungen->fuerMitgliedFinden($mitgliedId, $jetzt->format('Y-m-d')),
            // Heutige Termine, die schon vorbei sind, gehören nicht mehr in
            // "kommende Termine".
            static fn (array $b): bool => !Terminplan::hatBegonnen($b['datum'] . ' ' . $b['beginn'], $jetzt)
        ));

        Api::antworten(['buchungen' => array_map(static function (array $b): array {
            $beginn = new DateTimeImmutable($b['datum'] . ' ' . $b['beginn']);

            return [
                'id'       => (int) $b['id'],
                'datum'    => $b['datum'],
                'beginn'   => $beginn->format('H:i'),
                'ende'     => $beginn->modify('+' . (int) $b['dauer_minuten'] . ' minutes')->format('H:i'),
                'stufe'    => $b['stufe'],
                'programm' => $b['programm'],
                'slug'     => $b['slug'],
                'format'   => $b['format'],
                'coach'    => $b['coach'],
            ];
        }, $liste)]);
    }

    // --- Buchen --------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe  = Api::eingabe();
        $terminId = is_int($eingabe['terminId'] ?? null) ? $eingabe['terminId'] : 0;
        $datum    = Api::text($eingabe, 'datum');
        $stufe    = Api::text($eingabe, 'stufe');

        if (!in_array($stufe, Terminplan::STUFEN, true)) {
            Api::fehler(400, 'Bitte wähle deine Stufe: Einsteiger, Fortgeschritten oder Erfahren.');
        }

        $termin = (new KursterminRepository())->findenNachId($terminId);

        if ($termin === null || !Terminplan::kursterminBuchbar($termin, $datum, $jetzt)) {
            Api::fehler(400, 'Diesen Termin gibt es nicht oder er hat schon begonnen.');
        }

        $ergebnis = $buchungen->buchen($mitgliedId, $terminId, $datum, $stufe);

        if ($ergebnis === KursbuchungRepository::VOLL) {
            Api::fehler(409, AUSGEBUCHT);
        }
        if ($ergebnis === KursbuchungRepository::DOPPELT) {
            Api::fehler(409, 'Diesen Termin hast du schon gebucht.');
        }

        Api::antworten(['gebucht' => true], 201);
    }

    // --- Stornieren ----------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $eingabe = Api::eingabe();
        $id      = is_int($eingabe['id'] ?? null) ? $eingabe['id'] : 0;
        $buchung = $buchungen->findenFuerMitglied($id, $mitgliedId);

        // Dieselbe Antwort für "gibt es nicht" und "gehört jemand anderem":
        // Sonst ließe sich durchprobieren, welche ids es gibt.
        if ($buchung === null) {
            Api::fehler(404, 'Diese Buchung gibt es nicht.');
        }

        if (Terminplan::hatBegonnen($buchung['datum'] . ' ' . $buchung['beginn'], $jetzt)) {
            Api::fehler(409, 'Der Termin hat schon begonnen und kann nicht mehr storniert werden.');
        }

        $buchungen->stornieren($id, $mitgliedId);

        Api::antworten(['storniert' => true]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
