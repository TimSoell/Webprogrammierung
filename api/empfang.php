<?php
/**
 * @file        api/empfang.php
 * @layer       4 – API-Endpunkt
 * @description Die Liste für das Personal am Empfang - welche Konten sind
 *              noch nicht mit dem Personalausweis bestätigt? - und das
 *              Bestätigen selbst.
 *
 *                GET  -> { offen: [...] }
 *                PUT  { mitgliedId } -> 200 { offen: [...] }, Konto bestätigt
 *
 *              NUR FUER DAS PERSONAL (Rolle EMPLOYEE), sonst 401 bzw. 403.
 *              empfang.php sperrt die Seite ebenfalls, verbindlich ist aber
 *              diese Prüfung: Die Seite lässt sich umgehen, der Endpunkt nicht.
 *
 *              BESTAETIGEN heißt: Das Personal hat den Personalausweis
 *              gesehen, das Foto passt zur Person und der Name zum Konto. Ab
 *              dann ist der Name für immer fest. Schüler-, Studierenden- und
 *              Seniorennachweise beurteilt der Empfang nicht - die prüft die
 *              Ausweisprüfung in api/nachweise.php. Passt der Personalausweis
 *              nicht, bestätigt das Personal einfach nicht; ein Ablehnen gibt
 *              es nicht. Siehe
 *              docs/decisions/ADR-0024-empfang-bestaetigt-konto-mit-personalausweis.md
 *
 *              War wegen verpasster Frist schon der Standardpreis vorgemerkt,
 *              nimmt die Bestätigung das zurück - nach derselben Regel wie
 *              beim Hochladen in api/nachweise.php.
 * @see         assets/js/services/empfang.js
 * @see         src/Repositories/MitgliedRepository.php
 * @see         src/Repositories/MitgliedschaftRepository.php
 * @see         src/Repositories/NachweisRepository.php
 * @see         docs/features/empfang.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

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

                // Nur gesetzt, wenn ein gültiger Ermäßigungsnachweis vorliegt -
                // dann läuft die Frist, und es eilt.
                'ermaessigung'  => $zeile['ermaessigung'],
                'vorzeigenBis'  => $zeile['vorzeigen_bis'],

                // Beide Daten als 'JJJJ-MM-TT': Der Zeichenvergleich ist
                // hier dasselbe wie der Datumsvergleich.
                'fristVerpasst' => $zeile['ermaessigung'] !== null
                    && $zeile['vorzeigen_bis'] !== null
                    && $zeile['vorzeigen_bis'] < $heute,
            ], $zeilen)),
        ]);
    };

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $listeAntworten();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        Api::fehler(405, 'Methode nicht erlaubt.');
    }

    $mitgliedId = (int) Api::text(Api::eingabe(), 'mitgliedId');

    if ($mitgliedId <= 0) {
        Api::fehler(400, 'Es wurde kein Mitglied angegeben.');
    }

    // Das eigene Konto bestätigt jemand anderes - sonst könnte sich das
    // Personal selbst jeden Namen geben.
    if ($mitgliedId === $eigeneId) {
        Api::fehler(403, 'Dein eigenes Konto muss jemand anderes am Empfang bestätigen.');
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
