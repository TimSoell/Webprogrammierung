<?php
/**
 * @file        api/empfang.php
 * @layer       4 – API-Endpunkt
 * @description Die Liste für das Personal am Empfang - wer muss seinen
 *              Ausweis noch zeigen? - und die beiden Entscheidungen dazu.
 *
 *                GET     -> { offen: [...] }
 *                PUT     { mitgliedId } -> 200 { offen: [...] }, Person bestätigt
 *                DELETE  { mitgliedId } -> 200 { offen: [...] }, Nachweis abgelehnt
 *
 *              NUR FUER DAS PERSONAL (Rolle EMPLOYEE), sonst 401 bzw. 403.
 *              empfang.php sperrt die Seite ebenfalls, verbindlich ist aber
 *              diese Prüfung: Die Seite lässt sich umgehen, der Endpunkt nicht.
 *
 *              BESTAETIGEN heißt: Das Personal hat den Ausweis gesehen, Foto
 *              und Name passen zur Person und zum Namen im Konto. Ab dann ist
 *              der Name für immer fest, und spätere Nachweise prüft allein die
 *              Ausweisprüfung. War wegen verpasster Frist schon der
 *              Standardpreis vorgemerkt, wird das zurückgenommen - nach
 *              derselben Regel wie beim Hochladen in api/nachweise.php.
 *
 *              ABLEHNEN heißt: Der Ausweis fehlt, passt nicht oder gehört
 *              jemand anderem. Der Nachweis wird entfernt, ein ermäßigter
 *              Vertrag wie bei einem Ablauf zum Monatsersten herabgestuft
 *              (api/mitgliedschaften.php). Die Frist läuft weiter.
 * @see         assets/js/services/empfang.js
 * @see         src/Repositories/NachweisRepository.php
 * @see         src/Repositories/MitgliedRepository.php
 * @see         src/Repositories/MitgliedschaftRepository.php
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
    $nachweise  = new NachweisRepository();
    $heute      = (new DateTimeImmutable('today'))->format('Y-m-d');

    /** Antwortet mit der aktuellen Liste - nach jeder Anfrage dieselbe Form. */
    $listeAntworten = static function () use ($nachweise, $heute): void {
        Api::antworten([
            'offen' => array_map(static fn (array $zeile): array => [
                'mitgliedId'   => (int) $zeile['mitglied_id'],
                'vorname'      => $zeile['vorname'],
                'nachname'     => $zeile['nachname'],
                'email'        => $zeile['email'],
                'art'          => $zeile['art'],
                'gueltigBis'   => $zeile['gueltig_bis'],
                'geprueftAm'   => $zeile['geprueft_am'],
                'vorzeigenBis' => $zeile['vorzeigen_bis'],

                // Beide Daten als 'JJJJ-MM-TT': Der Zeichenvergleich ist
                // hier dasselbe wie der Datumsvergleich.
                'fristVerpasst' => $zeile['vorzeigen_bis'] !== null && $zeile['vorzeigen_bis'] < $heute,
            ], $nachweise->amEmpfangOffeneFinden()),
        ]);
    };

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $listeAntworten();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        Api::fehler(405, 'Methode nicht erlaubt.');
    }

    $mitgliedId = (int) Api::text(Api::eingabe(), 'mitgliedId');

    if ($mitgliedId <= 0) {
        Api::fehler(400, 'Es wurde kein Mitglied angegeben.');
    }

    // Den eigenen Ausweis bestätigt jemand anderes - sonst könnte sich das
    // Personal selbst jeden Namen geben.
    if ($mitgliedId === $mitglieder->idFindenNachUserId($auth->getUserId())) {
        Api::fehler(403, 'Deinen eigenen Ausweis muss jemand anderes am Empfang bestätigen.');
    }

    $offen = $nachweise->amEmpfangOffenenFinden($mitgliedId);

    if ($offen === null) {
        // Meist hat jemand anderes am Empfang gerade schneller geklickt.
        Api::fehler(409, 'Für dieses Mitglied ist nichts mehr offen. Die Liste wurde inzwischen geändert.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $mitglieder->bestaetigen($mitgliedId);

        // Hat das Mitglied die Frist verpasst, hat api/mitgliedschaften.php
        // den ermäßigten Vertrag womöglich schon auf den Standardpreis
        // vorgemerkt. Mit der Bestätigung gilt der Nachweis wieder - die
        // Vormerkung wird verworfen wie beim Hochladen in api/nachweise.php:
        // nur derselbe Tarif zum Standardpreis, und nur, wenn der Nachweis
        // zur Preisgruppe des laufenden Vertrags passt.
        $mitgliedschaften = new MitgliedschaftRepository();
        $laufend          = $mitgliedschaften->aktiveFinden($mitgliedId);
        $geplant          = $mitgliedschaften->geplanteFinden($mitgliedId);

        if ($laufend !== null
            && $geplant !== null
            && $laufend['preisgruppe'] !== 'standard'
            && $geplant['kennung'] === $laufend['kennung']
            && $geplant['preisgruppe'] === 'standard'
            && $nachweise->gueltigenFinden($mitgliedId, $laufend['preisgruppe']) !== null) {
            $mitgliedschaften->vormerkungZuruecknehmen($mitgliedId);
        }

        $listeAntworten();
    }

    // DELETE: abgelehnt. Entfernt wird genau der offene Nachweis.
    $nachweise->entfernen($mitgliedId, (int) $offen['id']);

    $listeAntworten();

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
