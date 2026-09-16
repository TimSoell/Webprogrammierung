# ADR-0005 — Mitglieder-Login mit delight-im/auth über Composer

**Status:** angenommen
**Datum:** 2026-09-16

## Kontext

Issue #8 verlangt Registrierung, Login und „Passwort vergessen“ für Mitglieder.
Es ist das erste Feature, bei dem Sicherheit wirklich zählt: Hinter dem Login
sollen später Stammdaten, Verträge, Buchungen und Zahlungsinformationen liegen.

Was dabei leicht schiefgeht, wenn man es von Hand baut: Passwort-Hashing,
neue Session-ID nach dem Login (Session-Fixation), Schutz vor
Durchprobieren von Passwörtern, Einmal-Tokens zum Zurücksetzen, die nur als
Hash gespeichert werden und ablaufen.

Randbedingungen aus [`CLAUDE.md`](../../CLAUDE.md) und
[`ADR-0002`](ADR-0002-schichtenarchitektur.md): Backend ist PHP 8 mit MySQL,
kein npm, kein Build-Schritt, sechs Schichten. Bibliotheken nur nach Absprache.

## Entscheidung

Der Login baut auf der PHP-Bibliothek
[**delight-im/auth**](https://github.com/delight-im/PHP-Auth) (Version 9, MIT)
auf, installiert mit Composer.

- **`vendor/` wird eingecheckt.** Niemand im Team braucht Composer, um das
  Projekt zu starten. Composer braucht nur, wer die Bibliothek aktualisiert.
- **`src/Auth.php` ist die einzige Stelle, die die Bibliothek erzeugt**,
  analog zu `src/Database.php`. Dort stehen auch unsere Regeln für E-Mail
  und Passwort.
- **`src/bootstrap.php` startet die Session** für jede Seite, weil das vor
  jeder Ausgabe passieren muss. Die Bibliothek verbindet sich dabei nicht mit
  der Datenbank (`PdoDsn`, verzögert). Seiten ohne Login laufen weiter ohne
  MySQL.
- **Die Tabellen der Bibliothek (`users`, `users_*`) stehen in
  `database/schema.sql`**, unverändert bis auf `ENGINE=InnoDB` statt MyISAM.
  Sie weichen bewusst von unseren Namenskonventionen ab.
- **Fachliche Daten liegen in unserer eigenen Tabelle `mitglieder`**, verbunden
  über `user_id`. Spätere Features verweisen auf `mitglieder.id`.
- **Das SQL der Bibliothek gilt als Infrastruktur**, wie PDO selbst. Die harte
  Regel „Kein SQL außerhalb von `src/Repositories/`“ gilt für unseren Code.
- **`'debug' => true` in `config/config.php`** schaltet die Drosselung der
  Bibliothek ab und zeigt den Link zum Zurücksetzen direkt auf der Seite an,
  weil XAMPP keine E-Mails verschickt.

Feste Regel fürs Team: **`vendor/` wird nie von Hand geändert.**

## Konsequenzen

**Positiv**

- Die heiklen Teile sind von einer Bibliothek gelöst, die seit Jahren im
  Einsatz ist: bcrypt mit Vor-Hash (auch lange Passwörter), neue Session-ID
  beim Login, SameSite-Cookie, Tokens nur als Hash, Audit-Log.
- Schutz gegen Durchprobieren gibt es ohne eigenen Code: nach 20
  Fehlversuchen pro Stunde von derselben IP ist der Login gesperrt.
- Der Einbau folgt trotzdem den sechs Schichten: Seite → Seitenskript →
  Service → Endpunkt → Bibliothek bzw. Repository → Tabelle.
- Später nachrüstbar ohne neue Bibliothek: E-Mail-Bestätigung,
  „Angemeldet bleiben“, Zwei-Faktor-Anmeldung, Rollen.

**Negativ**

- Eine fremde Abhängigkeit mit sechs Paketen in `vendor/` (rund 1,2 MB).
  Sicherheitsupdates kommen nicht von allein; wer aktualisiert, braucht
  Composer.
- Acht Tabellen mit englischen Namen, davon vier aktuell ungenutzt. Die
  Bibliothek fragt sie trotzdem ab.
- Zwei Datenbankverbindungen pro Anfrage, wenn Login und Repository zusammen
  arbeiten (Registrierung). Eine gemeinsame Transaktion ist deshalb nicht
  möglich. Schlägt das Anlegen der Stammdaten fehl, löscht
  `api/mitglieder.php` das eben erzeugte Konto wieder.
- Weniger selbst geschriebener Code zum Vorzeigen. Wer den Login erklären
  will, muss die Bibliothek verstehen.
- Die Bibliothek nutzt Schreibweisen, die ab **PHP 8.4** als veraltet gemeldet
  werden. XAMPP liefert PHP 8.2. Wer eine neuere PHP-Version benutzt, sieht mit
  `'debug' => true` Deprecated-Hinweise, die JSON-Antworten zerstören können.

## Verworfene Alternativen

**BetterAuth.**
Eine TypeScript-Bibliothek, die einen eigenen Node.js-Server braucht. Das
hieße npm, einen zweiten Server neben PHP und eine Session, die sich beide
teilen müssen. Widerspricht `CLAUDE.md` und ADR-0002 direkt.

**Selbst gebaut mit PHP-Bordmitteln** (`password_hash()`, `session_start()`).
Ohne Abhängigkeit und gut vorzuzeigen. Drosselung, Token-Verwaltung und
Session-Fixation hätten wir aber selbst schreiben und testen müssen.

**`vendor/` in `.gitignore`.**
In Profi-Projekten üblich, hier aber hinderlich: Alle fünf müssten Composer
installieren und nach jedem Pull `composer install` ausführen, auch für die
Abgabe über XAMPP.
