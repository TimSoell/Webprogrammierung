<?php
/**
 * @file        api/auswahl.php
 * @layer       4 – API-Endpunkt
 * @description Die am Konto gemerkte Level- und Format-Auswahl eines Mitglieds.
 *
 *                GET                              -> alle gemerkten Auswahlen
 *                GET     ?slug=move               -> nur die zu einem Programm
 *                PUT     { slug, level, format }  -> merken oder überschreiben
 *                DELETE  { slug }                 -> entfernen
 *
 *              Alle drei Methoden setzen eine Anmeldung voraus und antworten
 *              sonst mit 401.
 *
 *              level und format werden als TITEL übergeben, nicht als id -
 *              der Browser kennt die Merkmale nur so, siehe
 *              assets/js/components/auswahl-speicher.js. Die Umwandlung in
 *              eine id macht ProgrammRepository::merkmalIdFinden(), und sie
 *              ist zugleich die Prüfung: Ein Titel, der nicht zu diesem
 *              Programm gehört, führt zu 400.
 * @see         assets/js/services/auswahl.js
 * @see         src/Repositories/MitgliedAuswahlRepository.php
 * @see         docs/features/gemerkte-auswahl.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\MitgliedAuswahlRepository;
use Repositories\MitgliedRepository;
use Repositories\ProgrammRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Du bist nicht angemeldet.');
    }

    $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        // Konto ohne Stammdaten. Kommt nur vor, wenn jemand direkt in der
        // Datenbank gearbeitet hat - api/mitglieder.php legt immer beides an.
        Api::fehler(409, 'Zu diesem Konto fehlen die Stammdaten.');
    }

    $programme = new ProgrammRepository();
    $auswahl   = new MitgliedAuswahlRepository();

    /**
     * Sucht das Programm zum Kürzel und bricht ab, wenn es das nicht gibt.
     *
     * @return array{id: int, slug: string, name: string}
     */
    $programmHolen = static function (string $slug) use ($programme): array {
        if ($slug === '') {
            Api::fehler(400, 'Es wurde kein Programm angegeben.');
        }

        $programm = $programme->findenNachSlug($slug);

        if ($programm === null) {
            Api::fehler(404, 'Dieses Programm gibt es nicht.');
        }

        return $programm;
    };

    // --- Lesen ---------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';

        if ($slug !== '') {
            $programm = $programmHolen($slug);

            Api::antworten([
                'auswahl' => $auswahl->fuerProgrammFinden($mitgliedId, $programm['id']),
            ]);
        }

        Api::antworten(['auswahl' => $auswahl->fuerMitgliedFinden($mitgliedId)]);
    }

    // --- Merken --------------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $eingabe  = Api::eingabe();
        $programm = $programmHolen(Api::text($eingabe, 'slug'));

        $levelTitel  = trim(Api::text($eingabe, 'level'));
        $formatTitel = trim(Api::text($eingabe, 'format'));

        if ($levelTitel === '' && $formatTitel === '') {
            Api::fehler(400, 'Es wurde weder ein Level noch ein Format ausgewählt.');
        }

        // null bedeutet "nicht angegeben", eine id bedeutet "geprüft und gültig".
        $levelId  = null;
        $formatId = null;

        if ($levelTitel !== '') {
            $levelId = $programme->merkmalIdFinden($programm['id'], 'level', $levelTitel);

            if ($levelId === null) {
                Api::fehler(400, 'Dieses Level gibt es bei diesem Programm nicht.');
            }
        }

        if ($formatTitel !== '') {
            $formatId = $programme->merkmalIdFinden($programm['id'], 'format', $formatTitel);

            if ($formatId === null) {
                Api::fehler(400, 'Dieses Format gibt es bei diesem Programm nicht.');
            }
        }

        $auswahl->merken($mitgliedId, $programm['id'], $levelId, $formatId);

        Api::antworten(['gemerkt' => true]);
    }

    // --- Entfernen -----------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $programm = $programmHolen(Api::text(Api::eingabe(), 'slug'));

        $auswahl->entfernen($mitgliedId, $programm['id']);

        // Auch wenn nichts zu löschen war, ist das Ergebnis dasselbe:
        // Zu diesem Programm ist nichts mehr gemerkt. Deshalb kein 404.
        Api::antworten(['entfernt' => true]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
