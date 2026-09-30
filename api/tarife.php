<?php
/**
 * @file        api/tarife.php
 * @layer       4 – API-Endpunkt
 * @description Liefert den Tarifkatalog des Studios.
 *
 *                GET  -> { "tarife": [ { kennung, name, beschreibung,
 *                                        preise: {...}, zugang: {...} } ] }
 *
 *              Öffentlich: Preise soll auch sehen, wer noch kein Konto hat.
 *              Deshalb steht hier keine Anmeldeprüfung.
 *
 *              Nur lesend. Die Tarife werden über database/seed.sql gepflegt.
 * @see         assets/js/services/tarife.js
 * @see         src/Repositories/TarifRepository.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\TarifRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Api::eingabe() fehlt hier absichtlich: Es prüft den Content-Type
        // als CSRF-Schutz, und den braucht nur, wer etwas ändert.
        $tarife = (new TarifRepository())->alleAngebotenenFinden();

        // PDO liefert numeric als Text und die Zugangsspalten als 0/1. Ohne die
        // Umwandlung stünde im JSON "29.90" statt 29.90 und 1 statt true -
        // und JavaScript müsste beides wieder geradebiegen.
        $antwort = array_map(static fn (array $zeile): array => [
            'kennung'      => $zeile['kennung'],
            'name'         => $zeile['name'],
            'beschreibung' => $zeile['beschreibung'],
            'preise'       => [
                // null heißt: diesen Tarif gibt es für diese Gruppe nicht.
                'standard'   => (float) $zeile['preis_standard'],
                'ermaessigt' => $zeile['preis_ermaessigt'] === null ? null : (float) $zeile['preis_ermaessigt'],
                'senior'     => $zeile['preis_senior'] === null ? null : (float) $zeile['preis_senior'],
            ],
            'zugang'       => [
                'geraete'  => (bool) $zeile['zugang_geraete'],
                'wellness' => (bool) $zeile['zugang_wellness'],
                'kurse'    => (bool) $zeile['zugang_kurse'],
            ],
        ], $tarife);

        Api::antworten(['tarife' => $antwort]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
    // geht deshalb nicht an den Browser, sondern nur ins Log.
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
