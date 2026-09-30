<?php
/**
 * @file        config/config.umgebung.php
 * @layer       Konfiguration
 * @description Die Konfiguration auf Vercel. Dort gibt es keine
 *              config/config.php - dieselben Werte kommen aus den
 *              Umgebungsvariablen des Vercel-Projekts. src/bootstrap.php
 *              lädt diese Datei, wenn die Variable VERCEL gesetzt ist.
 *
 *              Aufbau genau wie config.example.php, damit der übrige Code
 *              keinen Unterschied merkt.
 *
 *              HIER STEHEN KEINE WERTE, NUR NAMEN. Eingetragen werden die
 *              Werte im Vercel-Dashboard unter Settings -> Environment
 *              Variables, getrennt für Production und Preview. Passwort und
 *              API-Schlüssel dort als "Sensitive".
 * @see         config/config.example.php
 * @see         docs/decisions/ADR-0016-hosting-auf-vercel.md
 */

declare(strict_types=1);

return [
    'db' => [
        'host'     => (string) getenv('DB_HOST'),
        'port'     => (int) (getenv('DB_PORT') ?: 5432),
        'name'     => (string) (getenv('DB_NAME') ?: 'postgres'),
        'user'     => (string) getenv('DB_USER'),
        'password' => (string) getenv('DB_PASSWORD'),
    ],

    'ki' => [
        'api_key' => (string) getenv('GEMINI_API_KEY'),
        'modell'  => (string) getenv('GEMINI_MODELL'),
    ],

    // Nur der Wert '1' schaltet ein. Fehlt die Variable, gilt die sichere
    // Einstellung: keine Fehler im Browser, kein Reset-Link auf der Seite.
    'debug'           => getenv('APP_DEBUG') === '1',
    'demo_reset_link' => getenv('DEMO_RESET_LINK') === '1',
];
