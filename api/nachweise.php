<?php
/**
 * @file        api/nachweise.php
 * @layer       4 – API-Endpunkt
 * @description Nachweise für ermäßigte Preise hochladen, ansehen und entfernen.
 *
 *                GET     -> { nachweise: [...], kiVerfuegbar: bool,
 *                             herabstufungZurueckgenommen: bool }
 *                POST    { art, bild, mimeTyp }            -> 201, mit KI
 *                POST    { art, datum }                    -> 201, im Demo-Modus
 *                DELETE  { id }                            -> 200, eigenen entfernen
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
 *              DIE KI ENTSCHEIDET NICHTS. Sie liest Name und Datumsangaben vom
 *              Bild ab. Ob daraus ein gültiger Nachweis wird - richtige
 *              Ausweisart, Name wie im Konto, Datum in der Zukunft, Alter
 *              mindestens 65 - entscheidet dieser Endpunkt.
 *
 *              EIN MITGLIED, EIN NACHWEIS. Ein neuer Nachweis ersetzt den
 *              bisherigen, siehe NachweisRepository::anlegen().
 *
 *              EIN NEUER NACHWEIS NIMMT DIE HERABSTUFUNG ZURUECK. Fehlte der
 *              Nachweis, hat api/mitgliedschaften.php den Vertrag zum
 *              Monatsersten auf den Standardpreis vorgemerkt. Passt der neue
 *              Nachweis zum laufenden Vertrag, verwirft der POST diese
 *              Vormerkung und meldet das in herabstufungZurueckgenommen.
 * @see         assets/js/services/nachweise.js
 * @see         src/Ausweispruefung.php
 * @see         src/Repositories/NachweisRepository.php
 * @see         src/Repositories/MitgliedschaftRepository.php
 * @see         docs/decisions/ADR-0011-ausweispruefung-mit-ki.md
 * @see         docs/decisions/ADR-0020-nachweise-absichern.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Delight\Auth\TooManyRequestsException;
use Repositories\MitgliedRepository;
use Repositories\MitgliedschaftRepository;
use Repositories\NachweisRepository;

header('Content-Type: application/json; charset=utf-8');

/** Arten von Nachweisen, die hochgeladen werden können. */
const NACHWEISARTEN = ['schueler', 'student', 'senior'];

/** Wie das geprüfte Dokument im gespeicherten Hinweis heißt. */
const AUSWEISNAMEN = [
    'schueler' => 'Schülerausweis',
    'student'  => 'Studierendenausweis',
    'senior'   => 'Lichtbildausweis',
];

/** Ab diesem Alter gilt der Seniorenpreis. */
const SENIORENALTER = 65;

/**
 * So weit im Voraus erkennen wir einen befristeten Nachweis höchstens an.
 * Steht auf dem Ausweis ein späteres Datum, gilt dieses hier - danach ist ein
 * neuer Upload fällig. Fängt verlesene Jahreszahlen (2072 statt 2027) und
 * Ausweise ab, die für das ganze Studium ausgestellt sind.
 */
const HOECHSTDAUER = ['schueler' => '+2 years', 'student' => '+1 year'];

/**
 * So viele Bilder darf ein Mitglied am Stück prüfen lassen. Danach kommt
 * alle acht Stunden ein Versuch dazu (drei je Tag). Jede Prüfung kostet eine
 * Anfrage aus dem Tageskontingent der kostenlosen Stufe.
 */
const PRUEFVERSUCHE = 3;

/**
 * Größte erlaubte Bildgröße in Byte, vor der base64-Kodierung.
 * Vercel nimmt höchstens 4,5 MB je Anfrage an. Base64 macht aus 3 MB rund
 * 4 MB, dazu kommt der Rest des JSON - 3 MB passen also mit Reserve.
 * In der Praxis verkleinert assets/js/lib/bild.js das Foto vorher ohnehin
 * auf deutlich weniger.
 */
const MAX_BILD_BYTE = 3 * 1024 * 1024;

/**
 * Buchstaben, die auf Ausweis und im Konto verschieden geschrieben sein
 * können. Ausweise drucken oft "MUELLER" oder lassen Akzente weg.
 */
const NAMEN_ERSATZ = [
    'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
    'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ą' => 'a',
    'ç' => 'c', 'ć' => 'c', 'č' => 'c',
    'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ę' => 'e', 'ě' => 'e',
    'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ı' => 'i',
    'ñ' => 'n', 'ń' => 'n', 'ł' => 'l', 'ğ' => 'g', 'ř' => 'r',
    'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
    'ś' => 's', 'š' => 's', 'ş' => 's',
    'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ů' => 'u',
    'ý' => 'y', 'ź' => 'z', 'ż' => 'z', 'ž' => 'z',
];

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Du bist nicht angemeldet.');
    }

    $mitglieder = new MitgliedRepository();
    $mitgliedId = $mitglieder->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        Api::fehler(500, 'Zu deinem Konto fehlen die Stammdaten.');
    }

    $nachweise = new NachweisRepository();

    /** Der Nachweisstand, wie ihn die Seite nach jeder Anfrage braucht. */
    $standAntworten = static function (int $status, bool $herabstufungZurueckgenommen = false) use ($nachweise, $mitgliedId): void {
        Api::antworten([
            'nachweise' => array_map(static fn (array $zeile): array => [
                'id'         => (int) $zeile['id'],
                'art'        => $zeile['art'],
                'gueltigBis' => $zeile['gueltig_bis'],
                'quelle'     => $zeile['quelle'],
                'hinweis'    => $zeile['hinweis'],
                'geprueftAm' => $zeile['geprueft_am'],
            ], $nachweise->alleFinden($mitgliedId)),

            // Die Seite muss wissen, ob sie ein Bildfeld oder ein Datumsfeld
            // anzeigt. Ohne Schlüssel gibt es nichts auszulesen.
            'kiVerfuegbar' => Ausweispruefung::verfuegbar(),

            // Nur nach einem POST true: Der Upload hat den vorgemerkten
            // Wechsel auf den Standardpreis verworfen.
            'herabstufungZurueckgenommen' => $herabstufungZurueckgenommen,
        ], $status);
    };

    /**
     * Zerlegt einen Namen in vergleichbare Wörter: klein, Umlaute
     * ausgeschrieben, ohne Akzente und Bindestriche.
     * "Müller-Lüdenscheidt" -> ['mueller', 'luedenscheidt']
     *
     * @return array<int, string>
     */
    $namensteile = static fn (string $name): array => preg_split(
        '/[^a-z]+/',
        strtr(mb_strtolower($name), NAMEN_ERSATZ),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    /**
     * Gehört der Ausweis der Person, auf die das Konto läuft?
     *
     * Der Nachname muss gleich sein. Beim Vornamen genügt es, wenn der aus
     * dem Konto unter den Vornamen auf dem Ausweis steht - dort stehen oft
     * mehrere ("Felix Maximilian"), im Konto meist nur der Rufname.
     *
     * @param array{vorname: string, nachname: string} $konto
     * @param array{vorname: string, nachname: string} $ausweis
     */
    $namePasst = static function (array $konto, array $ausweis) use ($namensteile): bool {
        $vornamen = $namensteile($konto['vorname']);
        $nachname = $namensteile($konto['nachname']);

        return $vornamen !== []
            && $nachname !== []
            && $nachname === $namensteile($ausweis['nachname'])
            && array_diff($vornamen, $namensteile($ausweis['vorname'])) === [];
    };

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $standAntworten(200);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = (int) Api::text(Api::eingabe(), 'id');

        if ($id <= 0) {
            Api::fehler(400, 'Es wurde kein Nachweis angegeben.');
        }

        // Auch wenn nichts zu löschen war, ist das Ergebnis dasselbe: Diesen
        // Nachweis gibt es nicht mehr. Deshalb kein 404. Läuft ein Vertrag
        // zum ermäßigten Preis, stuft ihn api/mitgliedschaften.php beim
        // nächsten Aufruf zum Monatsersten herab - wie bei einem Ablauf.
        $nachweise->entfernen($mitgliedId, $id);

        $standAntworten(200);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe = Api::eingabe();

        $art = Api::text($eingabe, 'art');

        if (!in_array($art, NACHWEISARTEN, true)) {
            Api::fehler(400, 'Bitte gib an, was du nachweisen möchtest.');
        }

        // Der Nachweis, den dieser Upload ersetzen würde. Wird weiter unten
        // noch einmal gebraucht, deshalb hier einmal geholt.
        $vorhanden = $nachweise->aktuellenFinden($mitgliedId);

        // Ein Seniorennachweis gilt unbefristet - ein zweiter kann nichts
        // verbessern. Das steht schon fest, bevor das Bild gelesen wurde,
        // also hier abbrechen: Das spart eine Anfrage an die Prüfung und
        // damit Kontingent der kostenlosen Stufe.
        if ($art === 'senior' && $vorhanden !== null && $vorhanden['art'] === 'senior') {
            Api::fehler(409, 'Deinen Seniorennachweis hast du schon hinterlegt. Er gilt unbefristet, ein weiterer Upload ändert daran nichts.');
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
                Api::fehler(400, 'Das Bild ist zu groß. Bitte lade es mit höchstens 3 MB hoch.');
            }

            // Erst hier zählen, nicht weiter oben: Ein Versuch ist nur, was
            // wirklich an die Prüfung geht. Benutzt wird die Drosselung der
            // Login-Bibliothek - wie beim Login ist sie mit 'debug' aus,
            // sonst sperrt sich das Team beim Testen selbst aus.
            try {
                $auth->throttle(['nachweisPruefung', $mitgliedId], PRUEFVERSUCHE, 86400);
            } catch (TooManyRequestsException) {
                Api::fehler(429, 'Du hast deine ' . PRUEFVERSUCHE . ' Prüfversuche aufgebraucht. Bitte versuche es in ein paar Stunden noch einmal.');
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

            $quelle = 'ki';
            $modell = $gelesen['modell'];

            // Senioren weisen mit einem Lichtbildausweis nach, alle anderen
            // mit einem Schüler- oder Studierendenausweis. Wer ein
            // Semesterticket für den Seniorenpreis hochlädt, bekommt hier ein Nein.
            if ($art === 'senior' && $gelesen['art'] !== 'senior') {
                Api::fehler(422, 'Auf dem Bild ist kein Personalausweis, Reisepass oder Führerschein zu erkennen.');
            }
            if ($art !== 'senior' && $gelesen['art'] !== $art) {
                Api::fehler(422, 'Das Bild passt nicht zu der Art von Nachweis, die du ausgewählt hast.');
            }

            // Ohne diesen Abgleich könnte jemand den Ausweis eines anderen
            // hochladen - der Vater den Schülerausweis seines Sohnes. Der
            // gelesene Name wird nur hier gebraucht und danach verworfen.
            if ($gelesen['vorname'] === '' || $gelesen['nachname'] === '') {
                Api::fehler(422, 'Auf dem Bild war kein Name zu lesen. Bitte fotografiere die Seite, auf der Name und Datum stehen.');
            }
            if (!$namePasst($mitglieder->findenNachUserId($auth->getUserId()), $gelesen)) {
                Api::fehler(422, 'Der Name auf dem Ausweis passt nicht zu dem Namen in deinem Konto. Ein Nachweis gilt nur für die Person, auf die das Konto läuft.');
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
            // ungelesen im Arbeitsspeicher liegen. Ohne Bild gibt es auch
            // keinen Namen, der sich abgleichen ließe.
            $datum  = Api::text($eingabe, 'datum');
            $quelle = 'demo';
            $modell = null;
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
            $geprueft   = 'mindestens ' . SENIORENALTER . ' Jahre alt';
        } else {
            if ($gelesenesDatum < $heute) {
                Api::fehler(422, 'Dieser Ausweis ist am ' . $gelesenesDatum->format('d.m.Y') . ' abgelaufen.');
            }

            $spaetestens = $heute->modify(HOECHSTDAUER[$art]);
            $gekuerzt    = $gelesenesDatum > $spaetestens;
            $gueltigBis  = ($gekuerzt ? $spaetestens : $gelesenesDatum)->format('Y-m-d');

            $geprueft = $gekuerzt
                ? 'aufgedruckt ist ein späteres Datum, anerkannt bis ' . $spaetestens->format('d.m.Y')
                : 'gültig bis ' . $gelesenesDatum->format('d.m.Y');
        }

        // Derselbe Ausweis noch einmal bringt nichts. Ein weiterer Nachweis
        // derselben Art wird nur angenommen, wenn er LAENGER gilt als der
        // vorhandene. Eine andere Art ersetzt den vorhandenen ohne Vergleich -
        // wer vom Schüler zum Studenten wird, soll nicht warten müssen.
        //
        // Beide Daten stehen als 'JJJJ-MM-TT' da. In diesem Format ist der
        // Zeichenvergleich gleichbedeutend mit dem Datumsvergleich.
        if ($vorhanden !== null && $vorhanden['art'] === $art && $vorhanden['gueltig_bis'] >= $gueltigBis) {
            $bisher = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $vorhanden['gueltig_bis']);

            Api::fehler(409, 'Du hast dafür schon einen Nachweis bis ' . $bisher->format('d.m.Y')
                . '. Lade erst wieder einen hoch, wenn er länger gilt.');
        }

        // Den Satz bauen wir selbst, statt ihn vom Modell schreiben zu lassen:
        // So steht garantiert weder ein Name noch ein Geburtsdatum darin.
        $hinweis = $quelle === 'demo'
            ? 'Im Demo-Modus von Hand eingetragen, ohne Prüfung eines Bildes.'
            : AUSWEISNAMEN[$art] . ' geprüft: ' . $geprueft . ', Name stimmt mit dem Konto überein.';

        $nachweise->anlegen($mitgliedId, $art, $gueltigBis, $quelle, $modell, $hinweis);

        // Fehlte der Nachweis, hat api/mitgliedschaften.php den Vertrag zum
        // Monatsersten auf den Standardpreis vorgemerkt. Mit dem neuen
        // Nachweis ist das hinfällig.
        //
        // Die Tabelle merkt sich nicht, wer eine Vormerkung angelegt hat. Zu
        // erkennen ist die Herabstufung nur an ihrer Form: derselbe Tarif zum
        // Standardpreis. Deshalb wird sie hier beim Hochladen verworfen und
        // nicht bei jedem Aufruf - wer bewusst auf den Standardpreis
        // wechselt, soll das trotz gültigem Nachweis können.
        $mitgliedschaften = new MitgliedschaftRepository();
        $laufend          = $mitgliedschaften->aktiveFinden($mitgliedId);
        $geplant          = $mitgliedschaften->geplanteFinden($mitgliedId);

        // Der neue Nachweis muss zur Preisgruppe des laufenden Vertrags
        // passen: Ein Seniorennachweis rettet keinen Schülerpreis.
        $herabstufungHinfaellig = $laufend !== null
            && $geplant !== null
            && $geplant['kennung'] === $laufend['kennung']
            && $geplant['preisgruppe'] === 'standard'
            && in_array($art, NachweisRepository::PREISGRUPPE_ARTEN[$laufend['preisgruppe']] ?? [], true);

        if ($herabstufungHinfaellig) {
            // Der Tarif bleibt derselbe - an den gebuchten Kursen ändert sich
            // also nichts, anders als bei einer Rücknahme auf der Tarifseite.
            $mitgliedschaften->vormerkungZuruecknehmen($mitgliedId);
        }

        $standAntworten(201, $herabstufungHinfaellig);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
