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

    // Zugang für die KI-Prüfung hochgeladener Ausweise (Schüler-,
    // Studenten- und Seniorennachweis). Benutzt wird die Gemini-API von
    // Google, Schlüssel gibt es unter https://aistudio.google.com/apikey
    //
    // LEER LASSEN IST EIN GÜLTIGER ZUSTAND. Ohne Schlüssel läuft die Prüfung
    // im Demo-Modus: Das Ablaufdatum wird von Hand eingetragen, statt aus dem
    // Bild gelesen. So funktioniert das Projekt auch ohne Schlüssel und ohne
    // Internet - siehe docs/decisions/ADR-0008-ausweispruefung-mit-ki.md
    //
    // ACHTUNG BEI DER KOSTENLOSEN STUFE: Google darf die Eingaben zur
    // Produktverbesserung verwenden, und Menschen dürfen sie lesen. Deshalb
    // gilt für dieses Projekt: NUR ERFUNDENE AUSWEISE hochladen, keine
    // echten - auch nicht die eigenen.
    // Siehe docs/decisions/ADR-0009-gemini-statt-claude.md
    //
    // Der Schlüssel gehört NIE ins Repository. config/config.php steht in
    // .gitignore, diese Vorlage bleibt leer.
    'ki' => [
        'api_key' => '',

        // Leer = Standardmodell aus src/Ausweispruefung.php (gemini-3.5-flash).
        // Welche Modelle die kostenlose Stufe abdeckt, ändert Google
        // regelmäßig; die eigenen Grenzen stehen in Google AI Studio.
        //
        // Bei Fehler 503 ist das Modell überlastet, bei 404 gibt es das
        // Modell für diesen Schlüssel nicht. Dann hier ein anderes
        // eintragen - geprüft am 21.09.2026: 'gemini-3.1-flash-lite' läuft,
        // 'gemini-3.8-flash' war überlastet, 'gemini-2.5-flash' gab 404.
        'modell' => '',
    ],

    // true  = Entwicklung und Demo:
    //         - PHP-Fehler werden im Browser angezeigt
    //         - Login-Drosselung aus (sonst sperrt man sich beim Testen aus)
    //         - "Passwort vergessen" zeigt den Link direkt auf der Seite an,
    //           weil XAMPP keine E-Mails verschickt
    // false = Fehler werden nur geloggt, Drosselung an, Link nur im Server-Log
    'debug' => true,
];
