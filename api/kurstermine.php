<?php
/**
 * @file        api/kurstermine.php
 * @layer       4 – API-Endpunkt
 * @description Die Kurstermine eines Programms für einen Monat, mit Coach
 *              und Belegung.
 *
 *                GET  ?slug=move               -> 200 laufender Monat
 *                GET  ?slug=move&monat=2026-10 -> 200 dieser Monat
 *                     { angemeldet, monat, ersterMonat, letzterMonat,
 *                       heute, von, bis, tarif, wechselAb, termine: [...] }
 *                                              -> 400 Monat außerhalb des Kalenders
 *                                              -> 404 wenn es das Programm nicht gibt
 *
 *              Blättern kann man vom laufenden Monat bis
 *              Terminplan::MONATE_VORAUS Monate weiter.
 *
 *              Für alle lesbar - den Kalender sieht man auch ohne Anmeldung.
 *              Ist jemand angemeldet, steht an seinen eigenen Terminen
 *              zusätzlich gebucht: true, und tarif sagt, ob in diesem Monat
 *              Kurse im Tarif enthalten sind.
 *
 *              Gebucht wird über api/kursbuchungen.php. Dort wird der Tarif
 *              noch einmal geprüft - tarif hier ist nur für die Anzeige.
 * @see         assets/js/services/kurstermine.js
 * @see         src/Terminplan.php
 * @see         src/Repositories/KursterminRepository.php
 * @see         src/Repositories/MitgliedschaftRepository.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\KursterminRepository;
use Repositories\MitgliedRepository;
use Repositories\MitgliedschaftRepository;
use Repositories\ProgrammRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        Api::fehler(405, 'Methode nicht erlaubt.');
    }

    $slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';

    if ($slug === '') {
        Api::fehler(400, 'Es wurde kein Programm angegeben.');
    }

    $programm = (new ProgrammRepository())->findenNachSlug($slug);

    if ($programm === null) {
        Api::fehler(404, 'Dieses Programm gibt es nicht.');
    }

    $jetzt = Terminplan::jetzt();
    $heute = $jetzt->format('Y-m-d');
    [$ersterMonat, $letzterMonat] = Terminplan::kursMonate($jetzt);

    $monat = is_string($_GET['monat'] ?? null) ? $_GET['monat'] : $ersterMonat;

    // 'JJJJ-MM' lässt sich als Text vergleichen - dieselbe Reihenfolge wie
    // im Kalender. Der Musterabgleich zuerst, sonst ginge "2026-1" durch.
    if (preg_match('/^\d{4}-\d{2}$/', $monat) !== 1 || $monat < $ersterMonat || $monat > $letzterMonat) {
        Api::fehler(400, 'Diesen Monat zeigt der Kalender nicht.');
    }

    [$von, $bis] = Terminplan::monatsZeitraum($monat);

    $kurstermine = new KursterminRepository();
    $wochenplan  = $kurstermine->wochenplanFuerProgramm($programm['id']);
    $anzahl      = $kurstermine->buchungenZaehlen($programm['id'], $von, $bis);

    // Wer nicht angemeldet ist, hat nichts gebucht und keinen Tarif. Kein
    // Fehler - der Kalender ist öffentlich.
    $auth      = Auth::instanz();
    $gebucht   = [];
    $tarif     = null;
    $wechselAb = null;

    if ($auth->isLoggedIn()) {
        $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

        if ($mitgliedId !== null) {
            $gebucht = $kurstermine->vonMitgliedGebucht($mitgliedId, $programm['id'], $von, $bis);

            // Ein Tarifwechsel gilt immer ab einem Monatsersten. Innerhalb
            // eines Monats ändert sich der Tarif also nicht, und ein Tag
            // reicht: der erste, an dem man in diesem Monat noch buchen kann.
            $mitgliedschaften = new MitgliedschaftRepository();
            $vertrag          = $mitgliedschaften->amTagFinden($mitgliedId, max($heute, $von));
            $wechselAb        = $mitgliedschaften->naechsterWechseltermin();

            if ($vertrag !== null) {
                $tarif = [
                    'name'  => $vertrag['name'],
                    'kurse' => (bool) $vertrag['zugang_kurse'],
                ];
            }
        }
    }

    // Wochenplan nach id, damit jeder ausgerollte Termin seine Angaben findet.
    $nachId = array_column($wochenplan, null, 'id');

    $termine = array_map(static function (array $termin) use ($nachId, $anzahl, $gebucht): array {
        $plan      = $nachId[$termin['terminId']];
        $schluessel = $termin['terminId'] . '|' . $termin['datum'];

        return $termin + [
            'format'            => $plan['format'],
            'formatBeschreibung' => $plan['format_beschreibung'],
            'coach'             => $plan['coach'],
            'coachSchwerpunkt'  => $plan['coach_schwerpunkt'],
            // Stammplätze zählen mit - sie sind jede Woche vergeben.
            'belegt'            => (int) $plan['stammplaetze'] + ($anzahl[$schluessel] ?? 0),
            'max'               => (int) $plan['max_teilnehmer'],
            'gebucht'           => isset($gebucht[$schluessel]),
        ];
    }, Terminplan::kursterminAusrollen($wochenplan, $jetzt, $von, $bis));

    Api::antworten([
        'angemeldet'   => $auth->isLoggedIn(),
        // Der gezeigte Monat und wie weit man blättern kann.
        'monat'        => $monat,
        'ersterMonat'  => $ersterMonat,
        'letzterMonat' => $letzterMonat,
        // Erster und letzter Tag des Monats. Der Browser braucht beide, um
        // auch die Tage ohne Termin als Kreis zu zeichnen - und nimmt sie
        // samt "heute" vom Server statt aus der eigenen Uhr, damit beide
        // dieselben Tage für vergangen halten.
        'heute'        => $heute,
        'von'          => $von,
        'bis'          => $bis,
        // null = nicht angemeldet oder in diesem Monat kein Vertrag.
        'tarif'        => $tarif,
        // Ab wann ein heute vorgemerkter Tarifwechsel gilt.
        'wechselAb'    => $wechselAb,
        'termine'      => $termine,
    ]);

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
