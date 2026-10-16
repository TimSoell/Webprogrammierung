<?php
/**
 * @file        api/empfang.php
 * @layer       4 – API-Endpunkt
 * @description Die Liste für das Personal am Empfang - welche Konten sind
 *              noch nicht mit dem Personalausweis bestätigt? - und das
 *              Bestätigen selbst, oder das Melden einer Unstimmigkeit.
 *
 *                GET  -> { offen: [...] }
 *                PUT  { mitgliedId }        -> 200 { offen: [...] }, Konto bestätigt
 *                POST { mitgliedId, grund } -> 200 { offen: [...] }, Unstimmigkeit gemeldet
 *
 *              NUR FUER DAS PERSONAL (Rolle EMPLOYEE), sonst 401 bzw. 403.
 *              empfang.php sperrt die Seite ebenfalls, verbindlich ist aber
 *              diese Prüfung: Die Seite lässt sich umgehen, der Endpunkt nicht.
 *
 *              BESTAETIGEN heißt: Das Personal hat den Personalausweis
 *              gesehen, das Foto passt zur Person und der Name zum Konto. Ab
 *              dann ist der Name für immer fest. Schüler-, Studierenden- und
 *              Seniorennachweise beurteilt der Empfang nicht - die prüft die
 *              Ausweisprüfung in api/nachweise.php. Siehe
 *              docs/decisions/ADR-0024-empfang-bestaetigt-konto-mit-personalausweis.md
 *
 *              NIE SELBST: Das eigene Konto und Konten, die das Personal über
 *              "Freunde werben" selbst geworben hat, bestätigt jemand anderes
 *              (403). Die Bestätigung gibt nämlich den Gutschein des Werbers
 *              frei, siehe api/gutscheine.php.
 *
 *              UNSTIMMIGKEIT heißt: Der Personalausweis ist falsch, oder die
 *              Person passt nicht zum Konto. Das Personal schreibt kurz dazu,
 *              was nicht passt; löschen kann es nichts. Das tut ein Admin auf
 *              admin.php, siehe
 *              docs/decisions/ADR-0026-schutz-vor-falschen-konten.md
 *
 *              WER MUSS: jedes Mitglied mit Tarif, binnen 14 Tagen nach der
 *              ersten Tarifwahl (api/mitgliedschaften.php). Konten ohne Tarif
 *              stehen auch in der Liste, müssen aber nicht.
 *
 *              War wegen verpasster Frist schon der Standardpreis vorgemerkt,
 *              nimmt die Bestätigung das zurück - nach derselben Regel wie
 *              beim Hochladen in api/nachweise.php.
 * @see         assets/js/services/empfang.js
 * @see         src/Repositories/EmpfehlungRepository.php
 * @see         src/Repositories/MitgliedRepository.php
 * @see         src/Repositories/MitgliedschaftRepository.php
 * @see         src/Repositories/NachweisRepository.php
 * @see         docs/features/empfang.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\EmpfehlungRepository;
use Repositories\MitgliedRepository;
use Repositories\MitgliedschaftRepository;
use Repositories\NachweisRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Du bist nicht angemeldet.');
    }
    if (!Auth::istPersonal()) {
        Api::fehler(403, 'Der Empfang ist nur für das Personal.');
    }

    $mitglieder = new MitgliedRepository();
    $eigeneId   = $mitglieder->idFindenNachUserId($auth->getUserId());
    $heute      = (new DateTimeImmutable('today'))->format('Y-m-d');

    /** Antwortet mit der aktuellen Liste - nach jeder Anfrage dieselbe Form. */
    $listeAntworten = static function () use ($mitglieder, $eigeneId, $heute): void {
        // Das eigene Konto steht nicht in der Liste: Das bestätigt jemand
        // anderes, siehe unten.
        $zeilen = array_filter(
            $mitglieder->unbestaetigteFinden(),
            static fn (array $zeile): bool => (int) $zeile['mitglied_id'] !== $eigeneId
        );

        Api::antworten([
            'offen' => array_values(array_map(static fn (array $zeile): array => [
                'mitgliedId'    => (int) $zeile['mitglied_id'],
                'vorname'       => $zeile['vorname'],
                'nachname'      => $zeile['nachname'],
                'email'         => $zeile['email'],
                'registriertAm' => substr((string) $zeile['erstellt_am'], 0, 10),

                // Art des gültigen Ermäßigungsnachweises oder null. Nur für
                // die Anzeige: Verpasst so jemand die Frist, ruht der Preis.
                'ermaessigung'  => $zeile['ermaessigung'],

                // Gesetzt mit der ersten Tarifwahl, null = noch kein Tarif.
                'vorzeigenBis'  => $zeile['vorzeigen_bis'],

                // Beide Daten als 'JJJJ-MM-TT': Der Zeichenvergleich ist
                // hier dasselbe wie der Datumsvergleich.
                'fristVerpasst' => $zeile['vorzeigen_bis'] !== null && $zeile['vorzeigen_bis'] < $heute,

                // Gemeldet vom Personal, null = nichts gemeldet.
                'unstimmigkeit' => $zeile['unstimmigkeit_am'] === null ? null : [
                    'am'    => substr((string) $zeile['unstimmigkeit_am'], 0, 10),
                    'grund' => $zeile['unstimmigkeit_grund'],
                ],
            ], $zeilen)),
        ]);
    };

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $listeAntworten();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        Api::fehler(405, 'Methode nicht erlaubt.');
    }

    $eingabe    = Api::eingabe();
    $mitgliedId = (int) Api::text($eingabe, 'mitgliedId');

    if ($mitgliedId <= 0) {
        Api::fehler(400, 'Es wurde kein Mitglied angegeben.');
    }

    // --- Unstimmigkeit melden ---------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $grund = trim(Api::text($eingabe, 'grund'));

        if ($mitgliedId === $eigeneId) {
            Api::fehler(403, 'Dein eigenes Konto kannst du nicht melden.');
        }
        // 200 Zeichen passen in die Spalte mitglieder.unstimmigkeit_grund.
        if ($grund === '' || mb_strlen($grund) > 200) {
            Api::fehler(400, 'Schreib kurz dazu, was nicht passt – höchstens 200 Zeichen.');
        }
        if (!$mitglieder->unstimmigkeitMelden($mitgliedId, $grund)) {
            Api::fehler(409, 'Dieses Konto ist schon gemeldet oder steht nicht mehr in der Liste.');
        }

        $listeAntworten();
    }

    // --- Bestätigen ---------------------------------------------------------

    // Das eigene Konto bestätigt jemand anderes - sonst könnte sich das
    // Personal selbst jeden Namen geben.
    if ($mitgliedId === $eigeneId) {
        Api::fehler(403, 'Dein eigenes Konto muss jemand anderes am Empfang bestätigen.');
    }

    // Ebenso Konten, die das Personal selbst geworben hat: Die Bestätigung
    // gibt den Gutschein des Werbers frei - sonst könnte es sich mit einem
    // erfundenen Freund selbst Gratiszeit verschaffen.
    if ($eigeneId !== null && (new EmpfehlungRepository())->hatGeworben($eigeneId, $mitgliedId)) {
        Api::fehler(403, 'Dieses Konto hast du selbst geworben – bestätigen muss es jemand anderes am Empfang.');
    }

    if (!$mitglieder->bestaetigen($mitgliedId)) {
        // Meist hat jemand anderes am Empfang gerade schneller geklickt.
        Api::fehler(409, 'Dieses Konto ist schon bestätigt oder steht nicht mehr in der Liste.');
    }

    // Hat das Mitglied die Frist verpasst, hat api/mitgliedschaften.php den
    // ermäßigten Vertrag womöglich schon auf den Standardpreis vorgemerkt.
    // Mit der Bestätigung gilt der Nachweis wieder - die Vormerkung wird
    // verworfen wie beim Hochladen in api/nachweise.php: nur derselbe Tarif
    // zum Standardpreis, und nur, wenn der Nachweis zur Preisgruppe des
    // laufenden Vertrags passt.
    $mitgliedschaften = new MitgliedschaftRepository();
    $laufend          = $mitgliedschaften->aktiveFinden($mitgliedId);
    $geplant          = $mitgliedschaften->geplanteFinden($mitgliedId);

    if ($laufend !== null
        && $geplant !== null
        && $laufend['preisgruppe'] !== 'standard'
        && $geplant['kennung'] === $laufend['kennung']
        && $geplant['preisgruppe'] === 'standard'
        && (new NachweisRepository())->gueltigenFinden($mitgliedId, $laufend['preisgruppe']) !== null) {
        $mitgliedschaften->vormerkungZuruecknehmen($mitgliedId);
    }

    $listeAntworten();

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
