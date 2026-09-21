<?php
/**
 * @file        api/mitgliedschaften.php
 * @layer       4 – API-Endpunkt
 * @description Den eigenen Tarifstand lesen und ändern.
 *
 *                GET   -> { mitgliedschaft, geplant, wechselAb }
 *                POST  { tarif, preisgruppe } -> derselbe Stand danach
 *
 *              Beides nur für angemeldete Mitglieder, sonst 401.
 *
 *              Ein POST bedeutet je nach Lage etwas anderes. Welcher Fall
 *              vorliegt, entscheidet dieser Endpunkt - nicht der Browser,
 *              denn der ist manipulierbar:
 *
 *                kein Vertrag          -> Erstwahl, gilt ab heute        (201)
 *                anderer Tarif gewählt -> Wechsel zum Monatsersten       (201)
 *                laufender Tarif und
 *                Vormerkung vorhanden  -> Vormerkung zurücknehmen        (200)
 *                laufender Tarif ohne
 *                Vormerkung            -> nichts zu tun                  (409)
 *
 *              Dass ein Wechsel erst zum Monatsersten wirkt, ist die
 *              eigentliche Regel des Features:
 *              docs/decisions/ADR-0010-tarifwechsel-zum-monatsersten.md
 *
 *              Kündigen gibt es bewusst nicht - siehe
 *              docs/features/mitgliedschaften.md, Abschnitt "Was fehlt noch".
 * @see         assets/js/services/mitgliedschaften.js
 * @see         src/Repositories/MitgliedschaftRepository.php
 * @see         src/Repositories/TarifRepository.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\MitgliedRepository;
use Repositories\MitgliedschaftRepository;
use Repositories\NachweisRepository;
use Repositories\TarifRepository;

header('Content-Type: application/json; charset=utf-8');

/** Die drei Preisgruppen. Muss zum ENUM in database/schema.sql passen. */
const PREISGRUPPEN = ['standard', 'ermaessigt', 'senior'];

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Du bist nicht angemeldet.');
    }

    // Alle fachlichen Tabellen hängen an mitglieder.id, nicht an users.id.
    $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        // Konto ohne Stammdaten: Das sollte api/mitglieder.php verhindern.
        Api::fehler(500, 'Zu deinem Konto fehlen die Stammdaten.');
    }

    $mitgliedschaften = new MitgliedschaftRepository();
    $nachweise        = new NachweisRepository();
    $tarife           = new TarifRepository();

    /**
     * Welche Preisgruppen dieses Mitglied wählen darf.
     *
     * Der volle Preis geht immer. Für die beiden ermäßigten braucht es einen
     * gültigen Nachweis - siehe api/nachweise.php.
     *
     * @return array<int, string>
     */
    $erlaubtePreisgruppen = static function () use ($nachweise, $mitgliedId): array {
        $erlaubt = ['standard'];

        foreach (array_keys(NachweisRepository::PREISGRUPPE_ARTEN) as $preisgruppe) {
            if ($nachweise->gueltigenFinden($mitgliedId, $preisgruppe) !== null) {
                $erlaubt[] = $preisgruppe;
            }
        }

        return $erlaubt;
    };

    /**
     * Stuft einen Vertrag auf den Standardpreis herab, wenn der Nachweis
     * dafür abgelaufen ist.
     *
     * Das läuft bei jedem Aufruf mit, weil es im Projekt keinen Cronjob gibt,
     * der nachts aufräumen könnte. Der Preis dafür: Ein GET kann eine
     * Vormerkung anlegen. Das ist bewusst in Kauf genommen und hier die
     * einzige Stelle, an der so etwas passiert - siehe ADR-0011.
     *
     * Herabgestuft wird nach derselben Regel wie jeder Wechsel: zum nächsten
     * Monatsersten. Wer bereits einen Wechsel vorgemerkt hat, wird in Ruhe
     * gelassen - der ersetzt den Vertrag ohnehin.
     */
    $ermaessigungPruefen = static function () use ($mitgliedschaften, $nachweise, $tarife, $mitgliedId): void {
        $laufend = $mitgliedschaften->aktiveFinden($mitgliedId);

        if ($laufend === null
            || $laufend['preisgruppe'] === 'standard'
            || $mitgliedschaften->geplanteFinden($mitgliedId) !== null
            || $nachweise->gueltigenFinden($mitgliedId, $laufend['preisgruppe']) !== null) {
            return;
        }

        $tarif = $tarife->angebotenenFindenNachKennung($laufend['kennung']);

        // Gibt es den Tarif nicht mehr, bleibt alles stehen: Ein Mitglied in
        // einen Tarif zu zwingen, den es nicht gewählt hat, wäre schlimmer.
        if ($tarif === null) {
            return;
        }

        $mitgliedschaften->wechselVormerken(
            $mitgliedId,
            (int) $tarif['id'],
            'standard',
            (float) $tarif['preis_standard']
        );
    };

    $ermaessigungPruefen();

    // Formt eine Datenbankzeile in die Antwort. MySQL liefert DECIMAL als
    // Text und TINYINT als '0'/'1' - im JSON sollen Zahl und Wahrheitswert
    // stehen.
    $alsAntwort = static fn (?array $zeile): ?array => $zeile === null ? null : [
        'tarif'          => $zeile['kennung'],
        'name'           => $zeile['name'],
        'beschreibung'   => $zeile['beschreibung'],
        'preisgruppe'    => $zeile['preisgruppe'],
        'preisMonatlich' => (float) $zeile['preis_monatlich'],
        'beginntAm'      => $zeile['beginnt_am'],
        'endetAm'        => $zeile['endet_am'],
        'zugang'         => [
            'geraete'  => (bool) $zeile['zugang_geraete'],
            'wellness' => (bool) $zeile['zugang_wellness'],
            'kurse'    => (bool) $zeile['zugang_kurse'],
        ],
    ];

    /**
     * Der vollständige Stand. Die Seite braucht immer alle drei Angaben:
     * was heute gilt, was vorgemerkt ist und ab wann ein neuer Wechsel
     * wirksam würde.
     */
    $standAntworten = static function (int $status) use ($mitgliedschaften, $mitgliedId, $alsAntwort, $erlaubtePreisgruppen): void {
        Api::antworten([
            'mitgliedschaft' => $alsAntwort($mitgliedschaften->aktiveFinden($mitgliedId)),
            'geplant'        => $alsAntwort($mitgliedschaften->geplanteFinden($mitgliedId)),
            'wechselAb'      => $mitgliedschaften->naechsterWechseltermin(),

            // Damit die Seite die Preisgruppen sperren kann, für die noch
            // kein Nachweis vorliegt. Die eigentliche Sperre sitzt im POST
            // unten - hier geht es nur um die Anzeige.
            'erlaubtePreisgruppen' => $erlaubtePreisgruppen(),
        ], $status);
    };

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Ein leerer Stand ist kein Fehler: "Mitglied ohne Tarif" ist ein
        // gültiger Zustand. Man registriert sich zuerst und wählt später.
        $standAntworten(200);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $eingabe = Api::eingabe();

        $kennung     = Api::text($eingabe, 'tarif');
        $preisgruppe = Api::text($eingabe, 'preisgruppe');

        if (!in_array($preisgruppe, PREISGRUPPEN, true)) {
            Api::fehler(400, 'Bitte wähle aus, zu welcher Preisgruppe du gehörst.');
        }

        // DIE SPERRE. Ohne sie wäre der ermäßigte Preis eine Auswahl, die
        // jeder treffen kann - der Upload wäre reine Dekoration. Geprüft wird
        // hier und nicht im Browser, weil nur der Server nicht manipulierbar ist.
        if (!in_array($preisgruppe, $erlaubtePreisgruppen(), true)) {
            Api::fehler(403, 'Für diesen Preis fehlt ein gültiger Nachweis. Lade ihn unter "Mein Konto" hoch.');
        }

        $tarif = $tarife->angebotenenFindenNachKennung($kennung);

        if ($tarif === null) {
            Api::fehler(400, 'Diesen Tarif gibt es nicht (mehr). Bitte lade die Seite neu.');
        }

        // Welche Spalte gilt, entscheidet die Preisgruppe. Bewusst hier und
        // nicht im Repository: Der Spaltenname darf nicht aus der Eingabe
        // zusammengebaut werden, sonst steht Nutzereingabe im SQL.
        $preis = match ($preisgruppe) {
            'ermaessigt' => $tarif['preis_ermaessigt'],
            'senior'     => $tarif['preis_senior'],
            default      => $tarif['preis_standard'],
        };

        if ($preis === null) {
            Api::fehler(400, 'Diesen Tarif gibt es für deine Preisgruppe nicht.');
        }

        $laufend = $mitgliedschaften->aktiveFinden($mitgliedId);

        if ($laufend === null) {
            // Erster Tarif überhaupt: gilt sofort, niemand soll auf den
            // Monatsersten warten, um trainieren zu dürfen.
            $mitgliedschaften->erstwahlSpeichern($mitgliedId, (int) $tarif['id'], $preisgruppe, (float) $preis);

            $standAntworten(201);
        }

        $bleibtGleich = $laufend['kennung'] === $kennung
            && $laufend['preisgruppe'] === $preisgruppe;

        if ($bleibtGleich) {
            if ($mitgliedschaften->geplanteFinden($mitgliedId) === null) {
                Api::fehler(409, 'Dieser Tarif läuft bereits. Es gibt nichts zu ändern.');
            }

            // Zurück auf den laufenden Tarif heißt: Der vorgemerkte Wechsel
            // wird verworfen.
            $mitgliedschaften->vormerkungZuruecknehmen($mitgliedId);

            $standAntworten(200);
        }

        $mitgliedschaften->wechselVormerken($mitgliedId, (int) $tarif['id'], $preisgruppe, (float) $preis);

        $standAntworten(201);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
