<?php
/**
 * @file        api/verfuegbarkeiten.php
 * @layer       4 – API-Endpunkt
 * @description Wer bietet Probetrainings an, und wann ist noch etwas frei?
 *
 *                GET               -> { coaches: [...] }  alle Coaches mit Verfügbarkeit
 *                GET  ?coach=13    -> { termine: [...] }  freie Termine der nächsten zwei Wochen
 *                                  -> 404 wenn es den Coach nicht gibt
 *
 *              Für alle lesbar. Gebucht wird über api/probetrainings.php.
 *
 *              "Frei" heißt: liegt in einem Wochenfenster des Coaches, hat
 *              noch nicht begonnen und ist noch nicht vergeben. Die Rechnung
 *              steht in Terminplan::probetrainingsAusrollen().
 * @see         assets/js/services/probetrainings.js
 * @see         src/Repositories/ProbetrainingRepository.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\ProbetrainingRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        Api::fehler(405, 'Methode nicht erlaubt.');
    }

    $repository = new ProbetrainingRepository();

    // ctype_digit statt (int): "13abc" soll 400 geben, nicht still zu 13 werden.
    $coachParameter = is_string($_GET['coach'] ?? null) ? $_GET['coach'] : '';

    if ($coachParameter === '') {
        Api::antworten(['coaches' => $repository->coachesFinden()]);
    }

    if (!ctype_digit($coachParameter)) {
        Api::fehler(400, 'Ungültiger Coach.');
    }

    $coachId = (int) $coachParameter;

    if ($repository->coachFinden($coachId) === null) {
        Api::fehler(404, 'Diesen Coach gibt es nicht.');
    }

    $jetzt = Terminplan::jetzt();
    [$von, $bis] = Terminplan::zeitraum($jetzt);

    Api::antworten([
        'termine' => Terminplan::probetrainingsAusrollen(
            $repository->fensterFinden($coachId),
            $repository->belegtFinden($coachId, $von, $bis),
            $jetzt
        ),
    ]);

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
