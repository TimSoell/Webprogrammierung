<?php
/**
 * @file        src/bootstrap.php
 * @layer       Infrastruktur
 * @description Startpunkt jeder Seite. Wird als ERSTE Zeile jeder .php-Seite
 *              eingebunden und erledigt vier Dinge:
 *                1. Konfiguration laden (config/config.php)
 *                2. ROOT_PATH definieren  -> Pfade im DATEISYSTEM (require)
 *                3. BASE_URL  definieren  -> Pfade im BROWSER (href/src)
 *                4. Session starten (für den Mitglieder-Login)
 *
 *              Warum BASE_URL berechnet wird und nicht fest eingetragen ist:
 *              Bei fünf Personen liegt das Projekt bei jedem woanders im
 *              htdocs-Ordner. Die Berechnung unten funktioniert bei allen,
 *              egal ob das Projekt unter http://localhost/ oder unter
 *              http://localhost/Webprogrammierung/ läuft - und auch für
 *              Unterseiten wie /programme/strength.php.
 * @see         docs/ARCHITECTURE.md
 */

declare(strict_types=1);

/** Absoluter Pfad zum Projektordner im Dateisystem (ohne / am Ende). */
define('ROOT_PATH', dirname(__DIR__));

// --- Konfiguration laden ---------------------------------------------------
$configFile = ROOT_PATH . '/config/config.php';

if (!is_file($configFile)) {
    http_response_code(500);
    exit(
        'Konfiguration fehlt. Bitte einmalig config/config.example.php '
        . 'nach config/config.php kopieren. Siehe README.md.'
    );
}

/** @var array{db: array{host: string, port: int, name: string, user: string, password: string}, debug: bool} $config */
$config = require $configFile;

// --- Fehleranzeige ---------------------------------------------------------
// Während der Entwicklung sollen Fehler sichtbar sein, sonst sucht man ewig.
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');

// --- BASE_URL berechnen ----------------------------------------------------
// Beispiel: Projekt liegt unter C:/xampp/htdocs/Webprogrammierung
//           DOCUMENT_ROOT ist   C:/xampp/htdocs
//           -> BASE_URL wird    /Webprogrammierung/
$projectPath  = str_replace('\\', '/', ROOT_PATH);
$documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

define('BASE_URL', rtrim(str_replace($documentRoot, '', $projectPath), '/') . '/');

// --- Ausgabe-Hilfsfunktion --------------------------------------------------
/**
 * Macht Text sicher für die Ausgabe in HTML ("escapen").
 *
 * IMMER benutzen, sobald Text ausgegeben wird, der nicht fest im Code steht -
 * also alles aus der Datenbank, aus Formularen oder aus der URL:
 *
 *     <h1><?= e($kurs['name']) ?></h1>
 *
 * Ohne das könnte jemand <script>...</script> als Kursnamen speichern und
 * damit Code im Browser aller Besucher ausführen (Cross-Site-Scripting).
 *
 * @param string|null $value  Der auszugebende Text
 * @return string             Fassung, in der < > " & unschädlich sind
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// --- Autoloader ------------------------------------------------------------
// Lädt Klassen aus src/ automatisch, sobald sie zum ersten Mal benutzt werden.
// Dadurch braucht ihr in den API-Endpunkten kein einziges require mehr.
// Namenskonvention: Klasse "BeispielFeatureRepository" im Namespace "Repositories"
// liegt in src/Repositories/BeispielFeatureRepository.php
spl_autoload_register(static function (string $class): void {
    $file = ROOT_PATH . '/src/' . str_replace('\\', '/', $class) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

// --- Bibliotheken ----------------------------------------------------------
// Lädt die Klassen aus vendor/, aktuell nur die Login-Bibliothek delight-im/auth.
// vendor/ liegt im Repository, niemand im Team braucht dafür Composer.
// Siehe docs/decisions/ADR-0005-login-bibliothek.md
require ROOT_PATH . '/vendor/autoload.php';

// --- Session und Login -----------------------------------------------------
// Startet die Session. Das muss vor jeder Ausgabe passieren, weil dabei ein
// Cookie gesetzt wird - deshalb hier und nicht erst in partials/header.php.
// Eine Datenbankverbindung entsteht dabei nicht, siehe src/Auth.php.
Auth::instanz();
