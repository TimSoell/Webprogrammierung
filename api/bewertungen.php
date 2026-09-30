<?php
/**
 * @file        api/bewertungen.php
 * @layer       4 – API-Endpunkt
 * @description Die Bewertungen des Studios.
 *
 *                GET                     -> alle Bewertungen, die neueste zuerst
 *                POST  { sterne, text }  -> eigene Bewertung abgeben
 *
 *              GET geht ohne Anmeldung, POST nur angemeldet (sonst 401).
 *              Pro Konto gibt es eine Bewertung (sonst 409).
 *
 *              Name und Mitgliedsstatus schickt der Browser NICHT mit - der
 *              Endpunkt liest beides selbst aus der Datenbank. Sonst könnte
 *              sich jeder als Mitglied ausgeben.
 * @see         assets/js/services/bewertungen.js
 * @see         src/Repositories/BewertungRepository.php
 * @see         docs/features/bewertungen.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\BewertungRepository;
use Repositories\MitgliedRepository;
use Repositories\MitgliedschaftRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    $bewertungen = new BewertungRepository();

    // --- Lesen ---------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        Api::antworten($bewertungen->alleFinden());
    }

    // --- Bewertung abgeben ---------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $auth = Auth::instanz();

        if (!$auth->isLoggedIn()) {
            Api::fehler(401, 'Zum Bewerten musst du angemeldet sein.');
        }

        $mitglieder = new MitgliedRepository();
        $mitgliedId = $mitglieder->idFindenNachUserId($auth->getUserId());
        $person     = $mitglieder->findenNachUserId($auth->getUserId());

        if ($mitgliedId === null || $person === null) {
            // Konto ohne Stammdaten. Kommt nur vor, wenn jemand direkt in der
            // Datenbank gearbeitet hat - api/mitglieder.php legt immer beides an.
            Api::fehler(409, 'Zu diesem Konto fehlen die Stammdaten.');
        }

        $eingabe = Api::eingabe();
        $sterne  = trim(Api::text($eingabe, 'sterne'));
        $text    = trim(Api::text($eingabe, 'text'));

        if (preg_match('/^[1-5]$/', $sterne) !== 1) {
            Api::fehler(400, 'Bitte vergib 1 bis 5 Sterne.');
        }

        if ($text === '') {
            Api::fehler(400, 'Schreib bitte ein paar Worte dazu.');
        }

        // 1000 passt in die Spalte bewertungen.text.
        if (mb_strlen($text) > 1000) {
            Api::fehler(400, 'Deine Bewertung darf höchstens 1000 Zeichen lang sein.');
        }

        if ($bewertungen->vorhanden($mitgliedId)) {
            Api::fehler(409, 'Du hast schon eine Bewertung abgegeben.');
        }

        // Mitglied heißt: ein aktiver Vertrag - festgehalten zum Zeitpunkt
        // des Schreibens, siehe docs/features/bewertungen.md
        $warMitglied = (new MitgliedschaftRepository())->aktiveFinden($mitgliedId) !== null;

        // "Lea M." statt des vollen Namens: Die Seite ist öffentlich.
        $name = $person['vorname'] . ' ' . mb_substr($person['nachname'], 0, 1) . '.';

        $id = $bewertungen->anlegen($mitgliedId, $name, $warMitglied, (int) $sterne, $text);

        Api::antworten(['id' => $id], 201);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
    // geht deshalb nicht an den Browser, sondern nur ins Log.
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
