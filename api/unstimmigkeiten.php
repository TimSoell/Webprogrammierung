<?php
/**
 * @file        api/unstimmigkeiten.php
 * @layer       4 – API-Endpunkt
 * @description Die Konten, bei denen das Personal am Empfang eine
 *              Unstimmigkeit gemeldet hat - und was ein Admin damit tut:
 *              die Markierung aufheben oder das Konto löschen.
 *
 *                GET                   -> { unstimmigkeiten: [...] }
 *                PUT    { mitgliedId } -> 200 { unstimmigkeiten: [...] }, Markierung aufgehoben
 *                DELETE { mitgliedId } -> 200 { unstimmigkeiten: [...] }, Konto gelöscht
 *
 *              NUR FUER ADMINS (Rolle ADMIN), sonst 401 bzw. 403 - auch das
 *              Personal bekommt 403. Gemeldet wird in api/empfang.php.
 *
 *              LOESCHEN geht nur bei gemeldeten Konten, nie beim eigenen,
 *              und lässt sich nicht rückgängig machen. Siehe
 *              docs/decisions/ADR-0026-schutz-vor-falschen-konten.md
 * @see         assets/js/services/unstimmigkeiten.js
 * @see         src/Repositories/MitgliedRepository.php
 * @see         src/Auth.php
 * @see         docs/features/unstimmigkeiten.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\MitgliedRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    $auth = Auth::instanz();

    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Du bist nicht angemeldet.');
    }
    if (!Auth::istAdmin()) {
        Api::fehler(403, 'Unstimmigkeiten bearbeiten nur Admins.');
    }

    $mitglieder = new MitgliedRepository();
    $methode    = $_SERVER['REQUEST_METHOD'];

    /** Antwortet mit der aktuellen Liste - nach jeder Anfrage dieselbe Form. */
    $listeAntworten = static function () use ($mitglieder): void {
        Api::antworten([
            'unstimmigkeiten' => array_map(static fn (array $zeile): array => [
                'mitgliedId'    => (int) $zeile['mitglied_id'],
                'vorname'       => $zeile['vorname'],
                'nachname'      => $zeile['nachname'],
                'email'         => $zeile['email'],
                'registriertAm' => substr((string) $zeile['erstellt_am'], 0, 10),
                'gemeldetAm'    => substr((string) $zeile['unstimmigkeit_am'], 0, 10),
                'grund'         => $zeile['unstimmigkeit_grund'],
            ], $mitglieder->unstimmigeFinden()),
        ]);
    };

    if ($methode === 'GET') {
        $listeAntworten();
    }

    if ($methode !== 'PUT' && $methode !== 'DELETE') {
        Api::fehler(405, 'Methode nicht erlaubt.');
    }

    $mitgliedId = (int) Api::text(Api::eingabe(), 'mitgliedId');

    if ($mitgliedId <= 0) {
        Api::fehler(400, 'Es wurde kein Mitglied angegeben.');
    }

    if ($methode === 'PUT' && !$mitglieder->unstimmigkeitAufheben($mitgliedId)) {
        // Meist hat gerade ein anderer Admin schneller geklickt.
        Api::fehler(409, 'Für dieses Konto ist nichts mehr gemeldet.');
    }

    if ($methode === 'DELETE') {
        if ($mitgliedId === $mitglieder->idFindenNachUserId($auth->getUserId())) {
            Api::fehler(403, 'Dein eigenes Konto kannst du hier nicht löschen.');
        }

        $geloescht = $mitglieder->unstimmigesKontoLoeschen(
            $mitgliedId,
            static fn (int $userId) => Auth::kontoLoeschen($userId)
        );

        if (!$geloescht) {
            Api::fehler(409, 'Dieses Konto ist nicht mehr gemeldet oder schon gelöscht.');
        }
    }

    $listeAntworten();

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
