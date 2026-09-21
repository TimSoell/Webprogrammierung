<?php
/**
 * @file        router.php
 * @layer       Infrastruktur
 * @description Router für den eingebauten PHP-Server, gestartet über
 *              start.sh bzw. start.bat. Unter Apache wird er nicht benutzt.
 *
 *              Der eingebaute Server beantwortet keine Range-Anfragen
 *              ("schick mir nur Byte 1000 bis 2000"). Chrome kann in einem
 *              Video aber nur springen, wenn der Server das kann - sonst
 *              bleibt currentTime bei 0 und das Scroll-Video steht still.
 *
 *              Dieser Router beantwortet deshalb Range-Anfragen auf
 *              .mp4-Dateien selbst. Alles andere - Seiten, CSS, Bilder -
 *              gibt er mit `return false` unverändert an den eingebauten
 *              Server zurück.
 * @see         docs/decisions/ADR-0006-router-fuer-entwicklungsserver.md
 */

declare(strict_types=1);

// Unter Apache ist die Datei über /router.php erreichbar, hat dort aber
// nichts zu tun.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$datei = realpath(__DIR__ . urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

if (
    !isset($_SERVER['HTTP_RANGE'])
    || $datei === false
    || !str_starts_with($datei, __DIR__ . DIRECTORY_SEPARATOR)
    || !str_ends_with($datei, '.mp4')
) {
    return false;
}

$groesse = filesize($datei);

// Drei Formen sind erlaubt: "bytes=100-199", "bytes=100-" (bis zum Ende)
// und "bytes=-100" (die letzten 100 Byte).
if (preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $treffer) && $treffer[1] . $treffer[2] !== '') {
    if ($treffer[1] === '') {
        $von = max(0, $groesse - (int) $treffer[2]);
        $bis = $groesse - 1;
    } else {
        $von = (int) $treffer[1];
        $bis = $treffer[2] === '' ? $groesse - 1 : min((int) $treffer[2], $groesse - 1);
    }
}

if (!isset($von) || $von > $bis) {
    http_response_code(416);
    header("Content-Range: bytes */$groesse");
    exit;
}

http_response_code(206);
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header("Content-Range: bytes $von-$bis/$groesse");
header('Content-Length: ' . ($bis - $von + 1));

echo file_get_contents($datei, false, null, $von, $bis - $von + 1);
