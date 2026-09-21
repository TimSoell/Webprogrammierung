<?php
/**
 * @file        api/programme.php
 * @layer       4 – API-Endpunkt
 * @description Liefert die Details einer Programmseite als JSON.
 *
 *                GET  ?slug=move
 *                     -> 200 { name, merkmale: {...}, coaches: [...] }
 *                     -> 404 wenn es das Programm nicht gibt
 *
 *              Nur GET. Die Inhalte pflegt das Team über database/seed.sql
 *              oder phpMyAdmin, nicht über diesen Endpunkt - deshalb gibt es
 *              hier kein POST und keine CSRF-Prüfung über Api::eingabe().
 * @see         assets/js/services/programme.js
 * @see         src/Repositories/ProgrammRepository.php
 * @see         docs/features/programm-details.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\ProgrammRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        Api::fehler(405, 'Methode nicht erlaubt.');
    }

    // Aus der URL, nicht aus dem JSON-Rumpf: Ein GET hat keinen Rumpf.
    $slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';

    if ($slug === '') {
        Api::fehler(400, 'Es wurde kein Programm angegeben.');
    }

    $repository = new ProgrammRepository();
    $programm   = $repository->findenNachSlug($slug);

    if ($programm === null) {
        // Bewusst dieselbe Meldung für "gibt es nicht" und "Tippfehler" -
        // hier ist nichts geheim, es hält die Fälle nur zusammen.
        Api::fehler(404, 'Dieses Programm gibt es nicht.');
    }

    Api::antworten([
        'name'     => $programm['name'],
        'merkmale' => $repository->merkmaleFinden($programm['id']),
        'coaches'  => $repository->coachesFinden($programm['id']),
    ]);

} catch (Throwable $fehler) {
    // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
    // geht deshalb nicht an den Browser, sondern nur ins Log.
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
