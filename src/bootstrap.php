<?php
/**
 * @file        src/bootstrap.php
 * @layer       Infrastruktur
 * @description Startpunkt jeder Seite. Wird als ERSTE Zeile jeder .php-Seite
 *              eingebunden und erledigt vier Dinge:
 *                1. Konfiguration laden (lokal config/config.php, auf
 *                   Vercel aus Umgebungsvariablen)
 *                2. ROOT_PATH definieren  -> Pfade im DATEISYSTEM (require)
 *                3. BASE_URL  definieren  -> Pfade im BROWSER (href/src)
 *                4. Session starten (für den Mitglieder-Login), gespeichert
 *                   in der Datenbank
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

// --- Läuft das hier auf Vercel? --------------------------------------------
// Vercel setzt diese Variable in jeder Funktion. Lokal ist sie leer.
// Siehe docs/decisions/ADR-0016-hosting-auf-vercel.md
$aufVercel = getenv('VERCEL') === '1';

// --- Konfiguration laden ---------------------------------------------------
// Lokal steht sie in config/config.php (nicht im Repository). Auf Vercel gibt
// es diese Datei nicht - dort kommen dieselben Werte aus den
// Umgebungsvariablen des Projekts, siehe config/config.umgebung.php.
$configFile = $aufVercel
    ? ROOT_PATH . '/config/config.umgebung.php'
    : ROOT_PATH . '/config/config.php';

if (!is_file($configFile)) {
    http_response_code(500);
    exit(
        'Konfiguration fehlt. Bitte einmalig config/config.example.php '
        . 'nach config/config.php kopieren. Siehe README.md.'
    );
}

/** @var array{db: array{host: string, port: int, name: string, user: string, password: string}, ki: array{api_key: string, modell: string}, debug: bool, demo_reset_link: bool} $config */
$config = require $configFile;

// config.php liegt nicht in Git. Kommt in der Vorlage ein Wert dazu, bekommt
// ihn niemand per Pull - die Seite läuft dann mit veralteten Werten und
// scheitert irgendwo mittendrin. Deshalb lokal gegen die Vorlage prüfen.
if (!$aufVercel) {
    $vorlage = require ROOT_PATH . '/config/config.example.php';
    $fehlend = [];

    foreach ($vorlage as $schluessel => $wert) {
        if (!array_key_exists($schluessel, $config)) {
            $fehlend[] = $schluessel;
            continue;
        }
        if (is_array($wert)) {
            foreach (array_keys($wert) as $unterschluessel) {
                if (!array_key_exists($unterschluessel, (array) $config[$schluessel])) {
                    $fehlend[] = $schluessel . '.' . $unterschluessel;
                }
            }
        }
    }

    if ($fehlend !== []) {
        http_response_code(500);
        exit(
            'config/config.php ist veraltet, es fehlt: ' . implode(', ', $fehlend)
            . '. Bitte config/config.example.php erneut nach config/config.php '
            . 'kopieren und das Passwort wieder eintragen. Siehe README.md.'
        );
    }
}

/**
 * Dieselbe Konfiguration für Klassen wie Database und Auth, die keinen
 * Zugriff auf die Variable $config haben.
 */
define('CONFIG', $config);

// --- Fehleranzeige ---------------------------------------------------------
// Während der Entwicklung sollen Fehler sichtbar sein, sonst sucht man ewig.
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');

// --- Zeitzone --------------------------------------------------------------
// Lokal kommt Europe/Berlin aus der php.ini von XAMPP, auf Vercel wäre es
// UTC - und "heute" oder "jetzt" läge dort ein bis zwei Stunden daneben.
// Die Datenbank rechnet ebenfalls in Berliner Zeit, siehe database/schema.sql.
date_default_timezone_set('Europe/Berlin');

// --- BASE_URL berechnen ----------------------------------------------------
// Beispiel: Projekt liegt unter C:/xampp/htdocs/Webprogrammierung
//           DOCUMENT_ROOT ist   C:/xampp/htdocs
//           -> BASE_URL wird    /Webprogrammierung/
// Auf Vercel liegt das Projekt immer ganz oben auf der Domain.
$projectPath  = str_replace('\\', '/', ROOT_PATH);
$documentRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

define('BASE_URL', $aufVercel ? '/' : rtrim(str_replace($documentRoot, '', $projectPath), '/') . '/');

// --- Hinter dem Proxy von Vercel -------------------------------------------
if ($aufVercel) {
    // PHP sieht als Absender den Proxy, nicht den Besucher. Ohne diese Zeile
    // teilen sich alle Besucher EINEN Zähler der Login-Drosselung und sperren
    // sich gegenseitig aus. Vercel überschreibt X-Forwarded-For selbst mit der
    // echten Adresse - von außen lässt sich der Wert nicht fälschen.
    $weitergeleitet = (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');

    if ($weitergeleitet !== '') {
        $_SERVER['REMOTE_ADDR'] = trim(explode(',', $weitergeleitet)[0]);
    }

    // Die Seite ist dort nur über HTTPS erreichbar, also soll das
    // Sitzungs-Cookie auch nie über eine unverschlüsselte Verbindung gehen.
    ini_set('session.cookie_secure', '1');
}

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
// Sitzungen liegen in der Datenbank statt als Datei: Auf Vercel teilen sich
// die Aufrufe keine Festplatte, der Login wäre beim nächsten Klick weg.
// Lokal gilt dasselbe, damit sich beide Umgebungen gleich verhalten.
// Siehe docs/decisions/ADR-0017-postgresql-auf-supabase.md
session_set_save_handler(new SitzungsSpeicher(), true);

// Kein Skript im Projekt braucht das Sitzungs-Cookie. HttpOnly verhindert,
// dass eingeschleustes JavaScript es auslesen und die Sitzung übernehmen
// kann - so empfiehlt es auch die Login-Bibliothek.
ini_set('session.cookie_httponly', '1');

// Startet die Session. Das muss vor jeder Ausgabe passieren, weil dabei ein
// Cookie gesetzt wird - deshalb hier und nicht erst in partials/header.php.
Auth::instanz();
