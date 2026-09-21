<?php
/**
 * @file        api/nachweise.php
 * @layer       4 – API-Endpunkt
 * @description Nachweise für ermäßigte Preise hochladen und ansehen.
 *
 *                GET   -> { nachweise: [...], kiVerfuegbar: bool }
 *                POST  { art, bild, mimeTyp }              -> 201, mit KI
 *                POST  { art, datum }                      -> 201, im Demo-Modus
 *
 *              Nur für angemeldete Mitglieder, sonst 401.
 *
 *              DAS BILD KOMMT ALS BASE64 IM JSON, nicht als multipart/form-data.
 *              Grund: Api::eingabe() verlangt den Content-Type application/json,
 *              und genau das ist unser CSRF-Schutz (siehe src/Api.php). Ein
 *              klassischer Datei-Upload würde diese Regel für diesen einen
 *              Endpunkt aushebeln. Der Preis sind rund 33 % mehr Datenmenge,
 *              abgefangen durch das Größenlimit unten.
 *
 *              DAS BILD WIRD NICHT GESPEICHERT. Es geht durch diesen Endpunkt
 *              an die Prüfung und ist danach weg - es gibt keinen Ordner und
 *              keine Spalte dafür. Gespeichert wird nur das Ergebnis.
 *
 *              DIE KI ENTSCHEIDET NICHTS. Sie liest Datumsangaben vom Bild ab.
 *              Ob daraus ein gültiger Nachweis wird - richtige Ausweisart,
 *              Datum in der Zukunft, Alter mindestens 65 - entscheidet dieser
 *              Endpunkt.
 * @see         assets/js/services/nachweise.js
 * @see         src/Ausweispruefung.php
 * @see         src/Repositories/NachweisRepository.php
 * @see         docs/decisions/ADR-0011-ausweispruefung-mit-ki.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\MitgliedRepository;
use Repositories\NachweisRepository;

header('Content-Type: application/json; charset=utf-8');

/** Arten von Nachweisen, die hochgeladen werden können. */
const NACHWEISARTEN = ['schueler', 'student', 'senior'];

/** Ab diesem Alter gilt der Seniorenpreis. */
const SENIORENALTER = 65;

/**
 * Größte erlaubte Bildgröße in Byte, vor der base64-Kodierung.
 * 4 MB passen mit Reserve unter das übliche post_max_size von 8 MB.
 */
const MAX_BILD_BYTE = 4 * 1024 * 1024;

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Du bist nicht angemeldet.');
    }

    $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        Api::fehler(500, 'Zu deinem Konto fehlen die Stammdaten.');
    }

    $nachweise = new NachweisRepository();

    $alsAntwort = static fn (array $zeile): array => [
        'art'        => $zeile['art'],
        'gueltigBis' => $zeile['gueltig_bis'],
        'quelle'     => $zeile['quelle'],
        'hinweis'    => $zeile['hinweis'],
        'geprueftAm' => $zeile['geprueft_am'],
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        Api::antworten([
            'nachweise' => array_map($alsAntwort, $nachweise->alleFinden($mitgliedId)),

            // Die Seite muss wissen, ob sie ein Bildfeld oder ein Datumsfeld
            // anzeigt. Ohne Schlüssel gibt es nichts auszulesen.
            'kiVerfuegbar' => Ausweispruefung::verfuegbar(),
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe = Api::eingabe();

        $art = Api::text($eingabe, 'art');

        if (!in_array($art, NACHWEISARTEN, true)) {
            Api::fehler(400, 'Bitte gib an, was du nachweisen möchtest.');
        }

        $heute = new DateTimeImmutable('today');

        if (Ausweispruefung::verfuegbar()) {
            // --- Echte Prüfung ------------------------------------------------
            $mimeTyp = Api::text($eingabe, 'mimeTyp');
            $bild    = base64_decode(Api::text($eingabe, 'bild'), true);

            if (!in_array($mimeTyp, Ausweispruefung::ERLAUBTE_TYPEN, true)) {
                Api::fehler(400, 'Bitte lade ein Bild als JPG, PNG oder WebP hoch.');
            }
            if ($bild === false || $bild === '') {
                Api::fehler(400, 'Das Bild konnte nicht gelesen werden. Bitte versuche es erneut.');
            }
            if (strlen($bild) > MAX_BILD_BYTE) {
                Api::fehler(400, 'Das Bild ist zu groß. Bitte lade es mit höchstens 4 MB hoch.');
            }

            try {
                $gelesen = Ausweispruefung::auslesen($bild, $mimeTyp);
            } catch (RuntimeException $fehler) {
                error_log((string) $fehler);
                Api::fehler(502, 'Die Prüfung war gerade nicht erreichbar. Bitte versuche es in ein paar Minuten erneut.');
            }

            // Ab hier wird das Bild nicht mehr gebraucht. Es liegt nur noch im
            // Arbeitsspeicher und ist mit dem Ende des Skripts weg.
            unset($bild);

            $quelle  = 'ki';
            $modell  = $gelesen['modell'];
            $hinweis = $gelesen['hinweis'] !== '' ? $gelesen['hinweis'] : 'Kein Hinweis geliefert.';

            // Senioren weisen mit einem Lichtbildausweis nach, alle anderen
            // mit einem Schüler- oder Studierendenausweis. Wer ein
            // Semesterticket für den Seniorenpreis hochlädt, bekommt hier ein Nein.
            if ($art === 'senior' && $gelesen['art'] !== 'senior') {
                Api::fehler(422, 'Auf dem Bild ist kein Personalausweis, Reisepass oder Führerschein zu erkennen.');
            }
            if ($art !== 'senior' && $gelesen['art'] !== $art) {
                Api::fehler(422, 'Das Bild passt nicht zu der Art von Nachweis, die du ausgewählt hast.');
            }

            $datum = $art === 'senior' ? $gelesen['geburtsdatum'] : $gelesen['gueltigBis'];

            if ($datum === '') {
                Api::fehler(422, $art === 'senior'
                    ? 'Auf dem Bild war kein Geburtsdatum zu lesen. Bitte fotografiere den Ausweis heller und gerade von oben.'
                    : 'Auf dem Bild war kein Ablaufdatum zu lesen. Bitte fotografiere den Ausweis heller und gerade von oben.');
            }
        } else {
            // --- Demo-Modus ---------------------------------------------------
            // Ohne API-Schlüssel gibt es nichts auszulesen. Das Datum, das die
            // Prüfung sonst vom Bild liest, wird hier eingetippt. Ein Bild wird
            // in diesem Modus gar nicht erst entgegengenommen - es würde nur
            // ungelesen im Arbeitsspeicher liegen.
            $datum   = Api::text($eingabe, 'datum');
            $quelle  = 'demo';
            $modell  = null;
            $hinweis = 'Im Demo-Modus von Hand eingetragen, ohne Prüfung eines Bildes.';
        }

        // --- Ab hier gelten für beide Betriebsarten dieselben Regeln ----------
        $gelesenesDatum = DateTimeImmutable::createFromFormat('!Y-m-d', $datum);

        if ($gelesenesDatum === false) {
            Api::fehler(422, 'Das Datum konnte nicht gelesen werden. Erwartet wird JJJJ-MM-TT.');
        }

        if ($art === 'senior') {
            // Das Geburtsdatum wird nur hier gebraucht und danach verworfen.
            // Gespeichert wird allein die Tatsache "ist Senior".
            $alter = $gelesenesDatum->diff($heute)->y;

            if ($alter < SENIORENALTER) {
                Api::fehler(422, 'Der Seniorenpreis gilt ab ' . SENIORENALTER . ' Jahren.');
            }

            // Unbefristet: Wer einmal 65 ist, bleibt es.
            $gueltigBis = null;
        } else {
            if ($gelesenesDatum < $heute) {
                Api::fehler(422, 'Dieser Ausweis ist am ' . $gelesenesDatum->format('d.m.Y') . ' abgelaufen.');
            }

            $gueltigBis = $gelesenesDatum->format('Y-m-d');
        }

        $nachweise->anlegen($mitgliedId, $art, $gueltigBis, $quelle, $modell, $hinweis);

        Api::antworten([
            'nachweise'    => array_map($alsAntwort, $nachweise->alleFinden($mitgliedId)),
            'kiVerfuegbar' => Ausweispruefung::verfuegbar(),
        ], 201);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
