<?php
/**
 * @file        api/kurstermine.php
 * @layer       4 – API-Endpunkt
 * @description Die Kurstermine eines Programms für die nächsten zwei Wochen,
 *              mit Coach und Belegung.
 *
 *                GET  ?slug=move  -> 200 { angemeldet, von, bis, termine: [...] }
 *                                 -> 404 wenn es das Programm nicht gibt
 *
 *              Für alle lesbar - den Kalender sieht man auch ohne Anmeldung.
 *              Ist jemand angemeldet, steht an seinen eigenen Terminen
 *              zusätzlich gebucht: true.
 *
 *              Gebucht wird über api/kursbuchungen.php.
 * @see         assets/js/services/kurstermine.js
 * @see         src/Terminplan.php
 * @see         src/Repositories/KursterminRepository.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\KursterminRepository;
use Repositories\MitgliedRepository;
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
    [$von, $bis] = Terminplan::zeitraum($jetzt);

    $kurstermine = new KursterminRepository();
    $wochenplan  = $kurstermine->wochenplanFuerProgramm($programm['id']);
    $anzahl      = $kurstermine->buchungenZaehlen($programm['id'], $von, $bis);

    // Wer nicht angemeldet ist, hat nichts gebucht. Kein Fehler - der
    // Kalender ist öffentlich.
    $auth     = Auth::instanz();
    $gebucht  = [];

    if ($auth->isLoggedIn()) {
        $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

        if ($mitgliedId !== null) {
            $gebucht = $kurstermine->vonMitgliedGebucht($mitgliedId, $programm['id'], $von, $bis);
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
    }, Terminplan::kursterminAusrollen($wochenplan, $jetzt));

    Api::antworten([
        'angemeldet' => $auth->isLoggedIn(),
        // Erster und letzter Tag des Kalenders. Der Browser braucht beide,
        // um auch die Tage ohne Termin als Kreis zu zeichnen - und nimmt
        // sie vom Server statt aus der eigenen Uhr, damit beide beim selben
        // "heute" anfangen.
        'von'        => $von,
        'bis'        => $bis,
        'termine'    => $termine,
    ]);

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
