<?php
/**
 * @file        api/auslastung.php
 * @layer       4 – API-Endpunkt
 * @description Die aktuelle Studioauslastung, die Tagesvorhersage und die
 *              Besuche, die ein Mitglied angekündigt hat.
 *
 *                GET                      -> Kurve des heutigen Tages, aktueller
 *                                            Stand und die eigenen Besuche
 *                POST    { art, zeit }    -> Besuch eintragen
 *                DELETE  { id }           -> eigenen Besuch entfernen
 *
 *              GET ist bewusst OHNE Anmeldung erreichbar - das Diagramm steht
 *              auf der Startseite und soll jeden Besucher erreichen. Angemeldet
 *              enthält die Antwort zusätzlich das Feld "meine".
 *
 *              POST und DELETE setzen eine Anmeldung voraus und antworten
 *              sonst mit 401.
 *
 *              art ist 'jetzt' oder 'geplant'. Bei 'jetzt' wird zeit
 *              ignoriert und der aktuelle Zeitpunkt genommen; bei 'geplant'
 *              ist zeit eine Uhrzeit im Format HH:MM des heutigen Tages.
 * @see         assets/js/services/auslastung.js
 * @see         src/Repositories/AuslastungRepository.php
 * @see         docs/features/auslastung.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\AuslastungRepository;
use Repositories\MitgliedRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    $auslastung = new AuslastungRepository();
    $jetzt      = new DateTimeImmutable();

    /**
     * Sucht die mitglied_id zur angemeldeten Sitzung und bricht sonst ab.
     *
     * @return int
     */
    $mitgliedIdHolen = static function (): int {
        $auth = Auth::instanz();

        if (!$auth->isLoggedIn()) {
            Api::fehler(401, 'Du bist nicht angemeldet.');
        }

        $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

        if ($mitgliedId === null) {
            // Konto ohne Stammdaten. Kommt nur vor, wenn jemand direkt in der
            // Datenbank gearbeitet hat - api/mitglieder.php legt immer beides an.
            Api::fehler(409, 'Zu diesem Konto fehlen die Stammdaten.');
        }

        return $mitgliedId;
    };

    /**
     * Stuft eine Personenzahl in eine Ampel ein.
     *
     * Die Schwellen stehen hier und nicht im Browser, damit Startseite und
     * Mitgliedsbereich dieselbe Auskunft geben.
     *
     * @param int $personen
     * @return string  'entspannt', 'gut besucht' oder 'voll'
     */
    $einstufen = static function (int $personen): string {
        $anteil = $personen / AuslastungRepository::KAPAZITAET;

        if ($anteil < 0.45) {
            return 'entspannt';
        }

        return $anteil < 0.75 ? 'gut besucht' : 'voll';
    };

    // --- Lesen ---------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $basis     = $auslastung->basiskurveFinden((int) $jetzt->format('N'));
        $proStunde = $auslastung->besucheProStundeZaehlen($jetzt);

        $verlauf = [];

        for ($stunde = 0; $stunde < 24; $stunde++) {
            $basiswert = $basis[$stunde] ?? 0;
            $gebucht   = $proStunde[$stunde] ?? 0;

            $verlauf[] = [
                'stunde'  => $stunde,
                'basis'   => $basiswert,
                'gebucht' => $gebucht,
                'gesamt'  => $basiswert + $gebucht,
            ];
        }

        $aktuelleStunde = (int) $jetzt->format('G');
        $jetztPersonen  = ($basis[$aktuelleStunde] ?? 0) + $auslastung->besucheZaehlen($jetzt);

        $antwort = [
            'kapazitaet' => AuslastungRepository::KAPAZITAET,
            'stand'      => $jetzt->format('c'),
            'jetzt'      => [
                'personen' => $jetztPersonen,
                'prozent'  => (int) round($jetztPersonen / AuslastungRepository::KAPAZITAET * 100),
                'stufe'    => $einstufen($jetztPersonen),
                'stunde'   => $aktuelleStunde,
            ],
            'verlauf'    => $verlauf,
        ];

        // Nur für Angemeldete, und ohne 401 für alle anderen: das Diagramm
        // selbst funktioniert auch ohne Konto.
        if (Auth::instanz()->isLoggedIn()) {
            $mitgliedId = (new MitgliedRepository())->idFindenNachUserId(Auth::instanz()->getUserId());

            if ($mitgliedId !== null) {
                $antwort['meine'] = $auslastung->offeneBesucheFinden($mitgliedId);
            }
        }

        Api::antworten($antwort);
    }

    // --- Besuch eintragen ----------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mitgliedId = $mitgliedIdHolen();
        $eingabe    = Api::eingabe();
        $art        = trim(Api::text($eingabe, 'art'));

        if ($art !== 'jetzt' && $art !== 'geplant') {
            Api::fehler(400, 'Unbekannte Art des Besuchs.');
        }

        if ($art === 'jetzt') {
            $beginn = $jetzt;
        } else {
            $zeit = trim(Api::text($eingabe, 'zeit'));

            if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $zeit) !== 1) {
                Api::fehler(400, 'Bitte eine Uhrzeit im Format HH:MM angeben.');
            }

            $beginn = new DateTimeImmutable($jetzt->format('Y-m-d') . ' ' . $zeit . ':00');

            // Eine Uhrzeit, die heute schon vorbei ist, meint fast immer
            // morgen - sonst traegt man einen Besuch in die Vergangenheit ein.
            if ($beginn < $jetzt) {
                $beginn = $beginn->modify('+1 day');
            }
        }

        $ende = $beginn->modify('+' . AuslastungRepository::DAUER_MINUTEN . ' minutes');

        if ($auslastung->ueberschneidungFinden($mitgliedId, $beginn, $ende)) {
            Api::fehler(409, 'Für diese Zeit ist schon ein Besuch eingetragen.');
        }

        $id = $auslastung->besuchEintragen($mitgliedId, $beginn, $ende, $art);

        Api::antworten([
            'besuch' => [
                'id'     => $id,
                'beginn' => $beginn->format('Y-m-d H:i:s'),
                'ende'   => $ende->format('Y-m-d H:i:s'),
                'art'    => $art,
            ],
        ], 201);
    }

    // --- Besuch entfernen ----------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $mitgliedId = $mitgliedIdHolen();
        $id         = (int) Api::text(Api::eingabe(), 'id');

        if ($id <= 0) {
            Api::fehler(400, 'Es wurde kein Besuch angegeben.');
        }

        if (!$auslastung->besuchLoeschen($mitgliedId, $id)) {
            // Entweder gibt es die id nicht, oder sie gehoert jemand anderem.
            // Beides beantworten wir gleich - sonst liesse sich durch Raten
            // herausfinden, welche ids vergeben sind.
            Api::fehler(404, 'Diesen Besuch gibt es nicht.');
        }

        Api::antworten(['entfernt' => true]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
