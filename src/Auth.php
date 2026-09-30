<?php
/**
 * @file        src/Auth.php
 * @layer       Infrastruktur (Unterbau für Seiten und Endpunkte)
 * @description Stellt die EINE Instanz der Login-Bibliothek delight-im/auth
 *              bereit und enthält unsere Regeln für E-Mail und Passwort.
 *
 *              Gegenstück zu src/Database.php: so wie dort nur EINE Stelle
 *              die Datenbankverbindung aufbaut, erzeugt hier nur EINE Stelle
 *              das Login-Objekt. Niemand schreibt sonst
 *              "new \Delight\Auth\Auth(...)".
 *
 *              Die Bibliothek übernimmt alles, was man leicht falsch macht:
 *              Passwort-Hashing, Session samt neuer Session-ID nach dem Login,
 *              Drosselung von Fehlversuchen und die Einmal-Tokens für
 *              "Passwort vergessen". Warum diese Bibliothek:
 *              docs/decisions/ADR-0005-login-bibliothek.md
 *
 *              Die Bibliothek benutzt dieselbe Datenbankverbindung wie alle
 *              Repositories (src/Database.php). Eine eigene zweite Verbindung
 *              pro Seitenaufruf wäre auf Vercel verschenkte Zeit.
 *
 *              Verwendung:
 *                  Auth::instanz()->isLoggedIn()     in Kopfzeile und Endpunkten
 *                  Auth::nurFuerMitglieder();        oben in geschützten Seiten
 * @see         src/bootstrap.php
 * @see         docs/features/mitglieder-login.md
 */

declare(strict_types=1);

use Delight\Auth\Auth as LoginBibliothek;

final class Auth
{
    /** Die einmal erzeugte Instanz. Null, solange noch keine gebraucht wurde. */
    private static ?LoginBibliothek $instanz = null;

    /** Diese Klasse wird nie instanziiert - sie hat nur statische Methoden. */
    private function __construct()
    {
    }

    /**
     * Liefert die Login-Bibliothek. Erzeugt sie beim ersten Aufruf.
     *
     * Beim Erzeugen startet die Bibliothek die PHP-Session und setzt dafür
     * ein Cookie. Deshalb ruft src/bootstrap.php diese Methode auf, bevor
     * irgendeine Seite HTML ausgibt.
     *
     * @return LoginBibliothek
     */
    public static function instanz(): LoginBibliothek
    {
        if (self::$instanz instanceof LoginBibliothek) {
            return self::$instanz;
        }

        // Vierter Parameter: Drosselung von Fehlversuchen an/aus.
        // Beim Entwickeln aus, sonst sperrt sich das Team nach fünf
        // Test-Registrierungen für zwölf Stunden selbst aus.
        self::$instanz = new LoginBibliothek(Database::connection(), null, null, !CONFIG['debug']);

        return self::$instanz;
    }

    /**
     * Schickt Besucher, die nicht angemeldet sind, zur Anmeldeseite.
     *
     * Gehört in jede Seite, die nur Mitglieder sehen dürfen - direkt nach
     * bootstrap.php, vor jeder Ausgabe.
     *
     * @return void  Kehrt nur zurück, wenn jemand angemeldet ist
     */
    public static function nurFuerMitglieder(): void
    {
        if (!self::instanz()->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'anmelden.php');
            exit;
        }
    }

    /**
     * Schickt angemeldete Mitglieder von der Anmeldeseite direkt ins Konto.
     *
     * @return void  Kehrt nur zurück, wenn niemand angemeldet ist
     */
    public static function nurFuerGaeste(): void
    {
        if (self::instanz()->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'mein-konto.php');
            exit;
        }
    }

    /**
     * Prüft eine E-Mail-Adresse und bringt sie in eine einheitliche Form.
     *
     * Kleinschreibung, damit "Max@Beispiel.de" und "max@beispiel.de" dasselbe
     * Konto sind. Geprüft wird die Form benutzer@domain.tld nach RFC 5322,
     * so weit PHP das mit FILTER_VALIDATE_EMAIL abdeckt, plus eine Endung
     * mit mindestens zwei Zeichen.
     *
     * @param string $email  Eingabe aus dem Formular
     * @return string|null   Die bereinigte Adresse, oder null wenn ungültig
     */
    public static function emailNormalisieren(string $email): ?string
    {
        $email = mb_strtolower(trim($email));

        // 249 Zeichen passen in die Spalte users.email.
        if (
            mb_strlen($email) > 249
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || preg_match('/\.[^.@]{2,}$/', $email) !== 1
        ) {
            return null;
        }

        return $email;
    }

    /**
     * Prüft ein neues Passwort gegen die Anforderungen aus Issue #8.
     *
     * Dieselben Regeln zeigt assets/js/components/passwort-kriterien.js beim
     * Tippen an. Die Prüfung im Browser ist Komfort, diese hier der Schutz.
     *
     * Geprüft wird ohne Leerzeichen am Anfang und Ende, weil die Bibliothek
     * das Passwort vor dem Speichern genauso kürzt.
     *
     * @param string $passwort  Das gewünschte Passwort
     * @return string|null      Meldung, was fehlt - oder null, wenn alles passt
     */
    public static function passwortFehler(string $passwort): ?string
    {
        $passwort = trim($passwort);
        $fehlt    = [];

        if (mb_strlen($passwort) < 8) {
            $fehlt[] = 'mindestens 8 Zeichen';
        }
        if (preg_match('/[A-Z]/', $passwort) !== 1) {
            $fehlt[] = 'einen Großbuchstaben';
        }
        if (preg_match('/[a-z]/', $passwort) !== 1) {
            $fehlt[] = 'einen Kleinbuchstaben';
        }
        if (preg_match('/[0-9]/', $passwort) !== 1) {
            $fehlt[] = 'eine Zahl';
        }
        // Sonderzeichen = alles, was weder Buchstabe noch Ziffer ist.
        // \p{L} zählt auch Umlaute als Buchstaben, nicht als Sonderzeichen.
        if (preg_match('/[^\p{L}\p{N}]/u', $passwort) !== 1) {
            $fehlt[] = 'ein Sonderzeichen';
        }

        return $fehlt === [] ? null : 'Dein Passwort braucht noch ' . implode(', ', $fehlt) . '.';
    }
}
