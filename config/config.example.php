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
        'name'     => 'schwitzkasten',
        'user'     => 'root',
        'password' => '',
    ],

    // true  = Entwicklung und Demo:
    //         - PHP-Fehler werden im Browser angezeigt
    //         - Login-Drosselung aus (sonst sperrt man sich beim Testen aus)
    //         - "Passwort vergessen" zeigt den Link direkt auf der Seite an,
    //           weil XAMPP keine E-Mails verschickt
    // false = Fehler werden nur geloggt, Drosselung an, Link nur im Server-Log
    'debug' => true,
];
