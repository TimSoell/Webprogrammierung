<?php
/**
 * @file        api/probetrainings.php
 * @layer       4 – API-Endpunkt
 * @description Probetraining buchen, die eigenen lesen, stornieren.
 *
 *                GET                                    -> kommende eigene Probetrainings
 *                POST    { coachId, beginntAm, stufe }  -> 201 gebucht
 *                                                       -> 409 inzwischen vergeben
 *                DELETE  { id }                         -> storniert, bis zum Terminbeginn
 *
 *              Alles nur für angemeldete Mitglieder, sonst 401.
 *
 *              beginntAm kommt als 'JJJJ-MM-TT HH:MM' - genau so, wie
 *              api/verfuegbarkeiten.php ihn geliefert hat.
 * @see         assets/js/services/probetrainings.js
 * @see         src/Repositories/ProbetrainingRepository.php
 * @see         docs/features/terminkalender.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\MitgliedRepository;
use Repositories\ProbetrainingRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Melde dich an, um ein Probetraining zu buchen.');
    }

    $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        Api::fehler(409, 'Zu diesem Konto fehlen die Stammdaten.');
    }

    $repository = new ProbetrainingRepository();
    $jetzt      = Terminplan::jetzt();

    // --- Eigene Probetrainings -----------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $liste = array_values(array_filter(
            $repository->fuerMitgliedFinden($mitgliedId, $jetzt->format('Y-m-d')),
            static fn (array $t): bool => !Terminplan::hatBegonnen($t['beginnt_am'], $jetzt)
        ));

        Api::antworten(['probetrainings' => array_map(static function (array $t): array {
            $beginn = new DateTimeImmutable($t['beginnt_am']);

            return [
                'id'       => (int) $t['id'],
                'datum'    => $beginn->format('Y-m-d'),
                'beginn'   => $beginn->format('H:i'),
                'ende'     => $beginn->modify('+' . Terminplan::PROBETRAINING_MINUTEN . ' minutes')->format('H:i'),
                'stufe'    => $t['stufe'],
                'coach'    => $t['coach'],
                'programm' => $t['programm'],
            ];
        }, $liste)]);
    }

    // --- Buchen --------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe   = Api::eingabe();
        $coachId   = is_int($eingabe['coachId'] ?? null) ? $eingabe['coachId'] : 0;
        $beginntAm = Api::text($eingabe, 'beginntAm');
        $stufe     = Api::text($eingabe, 'stufe');

        if (!in_array($stufe, Terminplan::STUFEN, true)) {
            Api::fehler(400, 'Bitte wähle deine Stufe: Einsteiger, Fortgeschritten oder Erfahren.');
        }

        if ($repository->coachFinden($coachId) === null) {
            Api::fehler(400, 'Bitte wähle einen Coach.');
        }

        if (!Terminplan::probetrainingBuchbar($repository->fensterFinden($coachId), $beginntAm, $jetzt)) {
            Api::fehler(400, 'Zu dieser Zeit bietet der Coach kein Probetraining an.');
        }

        if (!$repository->buchen($mitgliedId, $coachId, $beginntAm, $stufe)) {
            Api::fehler(409, 'Dieser Termin wurde gerade vergeben. Bitte wähle einen anderen.');
        }

        Api::antworten(['gebucht' => true], 201);
    }

    // --- Stornieren ----------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $eingabe = Api::eingabe();
        $id      = is_int($eingabe['id'] ?? null) ? $eingabe['id'] : 0;
        $termin  = $repository->findenFuerMitglied($id, $mitgliedId);

        if ($termin === null) {
            Api::fehler(404, 'Dieses Probetraining gibt es nicht.');
        }

        if (Terminplan::hatBegonnen($termin['beginnt_am'], $jetzt)) {
            Api::fehler(409, 'Das Probetraining hat schon begonnen und kann nicht mehr storniert werden.');
        }

        $repository->stornieren($id, $mitgliedId);

        Api::antworten(['storniert' => true]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
