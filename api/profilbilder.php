<?php
/**
 * @file        api/profilbilder.php
 * @layer       4 – API-Endpunkt
 * @description Die Profilbilder der Mitglieder.
 *
 *                GET     ?mitglied=7&v=...   -> das Bild selbst (image/jpeg)
 *                PUT     { bild }            -> eigenes Bild speichern
 *                PUT     { oeffentlich }     -> "1" / "0": bei Bewertungen zeigen
 *                DELETE                      -> eigenes Bild entfernen
 *
 *              AUSNAHME VON DER REGEL "Endpunkte antworten mit JSON": GET
 *              liefert das Bild, damit die Adresse direkt in <img src>
 *              stehen kann. Fehler bei GET sind deshalb nur ein Statuscode.
 *
 *              Wer welches Bild sehen darf, entscheidet
 *              ProfilbildRepository::bildFinden(). PUT und DELETE gelten
 *              immer nur für das eigene Bild - die mitglied-id kommt aus der
 *              Sitzung, nie aus der Anfrage.
 *
 *              Das Bild kommt im Browser bereits quadratisch und verkleinert
 *              an (assets/js/lib/bild.js). Hier wird trotzdem geprüft, ob es
 *              wirklich ein JPEG in erlaubter Größe ist - auf den Browser
 *              verlassen wir uns nicht.
 * @see         assets/js/services/profilbilder.js
 * @see         src/Repositories/ProfilbildRepository.php
 * @see         docs/features/profilbilder.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\MitgliedRepository;
use Repositories\ProfilbildRepository;

$profilbilder = new ProfilbildRepository();
$auth         = Auth::instanz();

// --- Bild ausliefern -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $mitgliedId = (int) ($_GET['mitglied'] ?? 0);
        $eigenes    = $auth->isLoggedIn()
            && (new MitgliedRepository())->idFindenNachUserId($auth->getUserId()) === $mitgliedId;

        $bild = $mitgliedId > 0 ? $profilbilder->bildFinden($mitgliedId, $eigenes) : null;
    } catch (Throwable $fehler) {
        error_log((string) $fehler);
        http_response_code(500);
        exit;
    }

    if ($bild === null) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: image/jpeg');
    header('X-Content-Type-Options: nosniff');

    // Mit ?v= ändert sich die Adresse bei jedem neuen Bild - das alte darf
    // der Browser dann lange behalten. "private": nur der Browser, kein
    // Zwischenspeicher unterwegs. Wer die Freigabe zurücknimmt, soll nicht in
    // einem fremden Cache weiterleben.
    header(isset($_GET['v'])
        ? 'Cache-Control: private, max-age=31536000, immutable'
        : 'Cache-Control: private, no-cache');

    echo $bild;
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    if (!$auth->isLoggedIn()) {
        Api::fehler(401, 'Du bist nicht angemeldet.');
    }

    $mitgliedId = (new MitgliedRepository())->idFindenNachUserId($auth->getUserId());

    if ($mitgliedId === null) {
        // Konto ohne Stammdaten. Kommt nur vor, wenn jemand direkt in der
        // Datenbank gearbeitet hat - api/mitglieder.php legt immer beides an.
        Api::fehler(409, 'Zu diesem Konto fehlen die Stammdaten.');
    }

    // --- Bild speichern oder Freigabe ändern ---------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $eingabe = Api::eingabe();

        if (array_key_exists('oeffentlich', $eingabe)) {
            $wert = Api::text($eingabe, 'oeffentlich');

            if ($wert !== '1' && $wert !== '0') {
                Api::fehler(400, 'Ungültige Angabe zur Freigabe.');
            }

            if (!$profilbilder->freigabeSetzen($mitgliedId, $wert === '1')) {
                Api::fehler(404, 'Du hast noch kein Profilbild.');
            }

            Api::antworten(['oeffentlich' => $wert === '1']);
        }

        // true = streng: Alles, was kein base64 ist, ergibt false.
        $daten = base64_decode(Api::text($eingabe, 'bild'), true);

        if ($daten === false || $daten === '') {
            Api::fehler(400, 'Es wurde kein Bild übertragen.');
        }

        // Im Browser verkleinert ist ein Bild rund 50 KB groß. 300 KB lassen
        // Luft und halten die Datenbank trotzdem klein.
        if (strlen($daten) > 300_000) {
            Api::fehler(400, 'Das Bild ist zu groß.');
        }

        // Liest den Dateikopf, nicht die Endung: Nur ein echtes JPEG von
        // höchstens 1024 x 1024 Pixeln kommt durch.
        $info = getimagesizefromstring($daten);

        if ($info === false || $info[2] !== IMAGETYPE_JPEG || $info[0] > 1024 || $info[1] > 1024) {
            Api::fehler(400, 'Dieses Bild konnte nicht verarbeitet werden. Bitte wähle ein anderes Foto.');
        }

        $stand = $profilbilder->speichern($mitgliedId, base64_encode($daten));

        Api::antworten(['profilbild' => $stand['url'], 'oeffentlich' => $stand['oeffentlich']]);
    }

    // --- Bild entfernen ------------------------------------------------------
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Auch ohne Rumpf: eingabe() prüft den Content-Type (Schutz vor CSRF).
        Api::eingabe();

        $profilbilder->loeschen($mitgliedId);

        Api::antworten(['profilbild' => null]);
    }

    Api::fehler(405, 'Methode nicht erlaubt.');

} catch (Throwable $fehler) {
    // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
    // geht deshalb nicht an den Browser, sondern nur ins Log.
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
