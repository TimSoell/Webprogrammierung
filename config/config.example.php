<?php
/**
 * @file        config/config.example.php
 * @layer       Konfiguration
 * @description Vorlage für die lokale Konfiguration.
 *              Jedes Teammitglied kopiert diese Datei EINMAL nach
 *              config/config.php und trägt seine eigenen XAMPP-Daten ein.
 *
 *              config/config.php steht in .gitignore und wird NIE committet.
 *              Grund: Zugangsdaten gehören nicht ins Repository, und jeder
 *              hat ein anderes lokales Setup.
 * @see         docs/ARCHITECTURE.md
 * @see         config/README.md
 */

return [
    // Zugangsdaten der lokalen MySQL-Datenbank (XAMPP-Standard vorbelegt).
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'baseline',
        'user'     => 'root',
        'password' => '',
    ],

    // true  = PHP-Fehler werden im Browser angezeigt (während der Entwicklung)
    // false = Fehler werden nur geloggt (für die Abgabe/Präsentation)
    'debug' => true,
];
