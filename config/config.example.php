<?php
/**
 * @file        config/config.example.php
 * @layer       Konfiguration
 * @description Vorlage für die lokale Konfiguration.
 *              Jedes Teammitglied kopiert diese Datei EINMAL nach
 *              config/config.php und trägt dort das Datenbank-Passwort ein.
 *
 *              config/config.php steht in .gitignore und wird NIE committet.
 *              Grund: Zugangsdaten gehören nicht ins Repository, und jeder
 *              hat ein anderes lokales Setup.
 *
 *              Auf Vercel gibt es keine config.php. Dort kommen dieselben
 *              Werte aus Umgebungsvariablen, siehe config/config.umgebung.php.
 *              Neuer Wert hier = neuer Wert auch dort.
 * @see         docs/ARCHITECTURE.md
 * @see         config/README.md
 */

declare(strict_types=1);

return [
    // Zugang zur Entwicklungsdatenbank schwitzkasten-dev bei Supabase
    // (PostgreSQL). Lokal wird NIE die Produktionsdatenbank benutzt.
    //
    // host und user stehen im Supabase-Dashboard unter "Connect" ->
    // "Session pooler". Das Passwort gibt es nur im Team, per
    // Passwortmanager - nicht per Chat, nicht per Mail.
    'db' => [
        'host'     => 'aws-1-eu-central-1.pooler.supabase.com',
        'port'     => 5432,
        'name'     => 'postgres',
        'user'     => 'postgres.plxfgsroxhyhwqbbxkpc',
        'password' => '',
    ],

    // Zugang für die KI-Prüfung hochgeladener Ausweise (Schüler-,
    // Studenten- und Seniorennachweis). Benutzt wird die Gemini-API von
    // Google, Schlüssel gibt es unter https://aistudio.google.com/apikey
    //
    // LEER LASSEN IST EIN GÜLTIGER ZUSTAND. Ohne Schlüssel läuft die Prüfung
    // im Demo-Modus: Das Ablaufdatum wird von Hand eingetragen, statt aus dem
    // Bild gelesen. So funktioniert das Projekt auch ohne Schlüssel - siehe
    // docs/decisions/ADR-0011-ausweispruefung-mit-ki.md
    //
    // ACHTUNG BEI DER KOSTENLOSEN STUFE: Google darf die Eingaben zur
    // Produktverbesserung verwenden, und Menschen dürfen sie lesen. Deshalb
    // gilt für dieses Projekt: NUR ERFUNDENE AUSWEISE hochladen, keine
    // echten - auch nicht die eigenen.
    // Siehe docs/decisions/ADR-0012-gemini-statt-claude.md
    //
    // Der Schlüssel gehört NIE ins Repository. config/config.php steht in
    // .gitignore, diese Vorlage bleibt leer.
    'ki' => [
        'api_key' => '',

        // Leer = Standardmodell aus src/Ausweispruefung.php (gemini-3.5-flash-lite).
        // Welche Modelle die kostenlose Stufe abdeckt, ändert Google
        // regelmäßig; die eigenen Grenzen stehen in Google AI Studio.
        //
        // Bei Fehler 503 ist das Modell überlastet, bei 404 gibt es das
        // Modell für diesen Schlüssel nicht. Dann hier ein anderes
        // eintragen - geprüft am 31.10.2026: 'gemini-3.5-flash-lite' läuft,
        // 'gemini-3.5-flash' war oft überlastet, 'gemini-2.5-flash' gab 404.
        'modell' => '',
    ],

    // Mailversand über Resend, z. B. für "Passwort vergessen". Schlüssel gibt
    // es im Resend-Konto des Teams unter "API Keys" - nur mit "Sending
    // access" für schwitzkasten-gym.de anlegen.
    //
    // LEER LASSEN IST EIN GÜLTIGER ZUSTAND. Ohne Schlüssel läuft der
    // Demo-Modus: Die Mail wird nicht verschickt, ihr Link steht stattdessen
    // im Server-Log. Siehe docs/features/mailversand.md
    //
    // Der Schlüssel gehört NIE ins Repository. config/config.php steht in
    // .gitignore, diese Vorlage bleibt leer.
    'mail' => [
        'api_key' => '',
    ],

    // true  = Entwicklung:
    //         - PHP-Fehler werden im Browser angezeigt
    //         - Login-Drosselung aus (sonst sperrt man sich beim Testen aus)
    // false = Fehler werden nur geloggt, Drosselung an. So läuft Produktion.
    'debug' => true,

    // true  = "Passwort vergessen" zeigt den Link zusätzlich direkt auf der
    //         Seite an. Praktisch zum Testen, auch mit Mailversand.
    // false = Link nur per Mail (bzw. ohne Mail-Schlüssel im Server-Log).
    //
    // In Produktion IMMER false: Sonst kann jeder, der eine E-Mail-Adresse
    // kennt, das Passwort dieses Kontos ändern.
    'demo_reset_link' => true,
];
