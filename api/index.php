<?php
/**
 * @file        api/index.php
 * @layer       Infrastruktur – nur auf Vercel
 * @description Der einzige Einstieg auf Vercel. KEIN Endpunkt.
 *
 *              Vercel führt PHP nur als Funktion unter api/ aus. Statt jede
 *              Seite dorthin zu verschieben, leitet vercel.json jede Anfrage
 *              außer /assets/ an diese Datei. Sie sucht die passende Datei im
 *              Projekt und bindet sie ein - danach läuft alles genau so wie
 *              lokal mit start.sh. Seiten und Endpunkte merken keinen
 *              Unterschied.
 *
 *              Erreichbar ist nur, was auf der Positivliste steht:
 *                /                    -> index.php
 *                /<name>.php          -> Seiten der obersten Ebene
 *                /programme/<name>.php
 *                /api/<name>.php      -> Endpunkte
 *              Alles andere - src/, vendor/, config/, partials/, database/,
 *              router.php und diese Datei selbst - antwortet mit 404. Ohne
 *              diese Liste könnte man sich z. B. den Quelltext von
 *              config/config.umgebung.php ausliefern lassen.
 *
 *              Lokal wird diese Datei nie benutzt; dort beantwortet der
 *              PHP-Server jede Datei direkt.
 * @see         vercel.json
 * @see         docs/decisions/ADR-0016-hosting-auf-vercel.md
 */

declare(strict_types=1);

/**
 * Die Datei, die zur angefragten Adresse gehört - oder null, wenn es keine
 * gibt oder sie nicht von außen erreichbar sein darf.
 *
 * In einer Funktion, damit keine Hilfsvariable in die eingebundene Seite
 * durchsickert. Eingebunden wird die Seite aber unten, außerhalb der
 * Funktion - sonst liefen alle Seiten in einem anderen Gültigkeitsbereich
 * als lokal.
 */
$vercelDatei = (static function (): ?string {
    $wurzel = dirname(__DIR__);
    $pfad   = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

    if ($pfad === '/') {
        $pfad = '/index.php';
    }

    $erlaubt = preg_match('#^/(?:(?:programme|api)/)?[a-z0-9-]+\.php$#', $pfad) === 1
        && !in_array($pfad, ['/api/index.php', '/router.php'], true);

    if (!$erlaubt) {
        return null;
    }

    $datei = realpath($wurzel . $pfad);

    return $datei !== false && str_starts_with($datei, $wurzel . DIRECTORY_SEPARATOR)
        ? $datei
        : null;
})();

if ($vercelDatei === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Seite nicht gefunden.');
}

chdir(dirname($vercelDatei));

require $vercelDatei;
