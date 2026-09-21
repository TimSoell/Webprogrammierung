# Feature: Mitglieder-Login

**Status:** fertig
**Verantwortlich:** Tim
**Zuletzt geprüft:** 2026-09-21

Umsetzung von Issue #8 „Mitglieder-Registrierung & Login“.

## Was kann man damit

In der Kopfzeile steht auf jeder Seite der Button **„Login / Registrierung“**.
Er führt auf eine Seite mit zwei Reitern. Dort kann man sich mit Vorname,
Nachname, E-Mail-Adresse und Passwort registrieren oder sich anmelden.
Beim Tippen des Passworts hakt die Seite ab, welche Anforderungen schon erfüllt
sind. Wer sein Passwort vergessen hat, fordert einen Link an und legt damit ein
neues fest.

Nach dem Login heißt der Button **„Mein Konto“**. Dort stehen die Stammdaten
und der Button zum Abmelden.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `anmelden.php`, `passwort-zuruecksetzen.php`, `mein-konto.php` |
| 1 Baustein | `partials/header.php` (Button), `partials/passwort-kriterien.php` |
| 2 Seitenskript | `assets/js/pages/anmelden.page.js`, `passwort-zuruecksetzen.page.js`, `mein-konto.page.js` |
| 2 Komponente | `assets/js/components/passwort-kriterien.js`, `auth-formular.js` |
| 3 Service | `assets/js/services/mitglieder.js` |
| 4 Endpunkt | `api/mitglieder.php`, `api/sitzung.php`, `api/passwort-reset.php` |
| 4 Unterbau | `src/Api.php` (JSON lesen und antworten), `src/Auth.php` (Login-Bibliothek, Regeln) |
| 5 Repository | `src/Repositories/MitgliedRepository.php` |
| 5 Bibliothek | `vendor/delight-im/auth`, siehe [ADR-0005](../decisions/ADR-0005-login-bibliothek.md) |
| 6 Tabelle | `mitglieder` und `users`, `users_*` in `database/schema.sql` |
| CSS | `assets/css/components/auth.css`, Button in `assets/css/03-layout.css` |

## Datenform

Was `angemeldetesMitgliedLaden()` zurückgibt:

```json
{ "vorname": "Erika", "nachname": "Mustermann", "email": "erika@beispiel.de" }
```

Im Fehlerfall wie überall: `{ "error": "Text für das Formular" }`.

## Endpunkte

Alle Anfragen, die etwas ändern, müssen `Content-Type: application/json`
schicken, sonst antwortet der Endpunkt mit 415. Das ist der Schutz vor CSRF
(Erklärung in `src/Api.php`). `api.js` erledigt das automatisch.

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| POST | `api/mitglieder.php` | registrieren, danach angemeldet | 201 `{ "id": 7 }` · 400 · 409 E-Mail vergeben |
| GET | `api/sitzung.php` | wer ist angemeldet | Stammdaten · 401 |
| POST | `api/sitzung.php` | anmelden | `{ "angemeldet": true }` · 401 · 429 |
| DELETE | `api/sitzung.php` | abmelden | `{ "angemeldet": false }` |
| POST | `api/passwort-reset.php` | Link anfordern | `{ "nachricht": "…", "demoLink"?: "…" }` · 429 |
| PUT | `api/passwort-reset.php` | neues Passwort setzen | `{ "geaendert": true }` · 400 |

## Regeln aus dem Issue und wo sie geprüft werden

| Regel | Browser | Server |
|---|---|---|
| E-Mail-Form `benutzer@domain.tld` | `type="email"` | `Auth::emailNormalisieren()` |
| E-Mail unabhängig von Groß-/Kleinschreibung | – | wird kleingeschrieben gespeichert und gesucht |
| E-Mail nur einmal | – | Bibliothek, Antwort 409 |
| Passwort: 8 Zeichen, A–Z, a–z, 0–9, Sonderzeichen | `passwort-kriterien.js` | `Auth::passwortFehler()` |
| Passwort wiederholen | `passwortPruefen()` | Endpunkt vergleicht |
| Eine Meldung bei falschem Login | – | `api/sitzung.php` |

Als Sonderzeichen zählt alles außer Buchstaben und Ziffern. Umlaute sind
Buchstaben, keine Sonderzeichen. Leerzeichen am Anfang und Ende des Passworts
entfernt die Bibliothek vor dem Speichern, deshalb prüfen beide Seiten ohne sie.

## Woher kommen die Daten aktuell

**MySQL.** Vor dem ersten Test `database/schema.sql` einspielen (legt die
Datenbank `schwitzkasten` samt Tabellen an). Testkonten legt man über die
Registrierung an, `seed.sql` darf keine Passwörter enthalten.

## Demo-Modus für „Passwort vergessen“

XAMPP verschickt keine E-Mails. Der Link zum Zurücksetzen landet deshalb

1. immer im Server-Log, beim Start über `./start.sh` direkt im Terminal
   (Zeile beginnt mit `[Demo-Mail]`),
2. zusätzlich auf der Seite, solange in `config/config.php` `'debug' => true`
   steht.

Der Link ist 60 Minuten gültig und funktioniert nur einmal. Echten Mailversand
baut man in `api/passwort-reset.php` an der markierten Stelle ein.

## Gut zu wissen

- **Drosselung** ist mit `'debug' => true` aus. Mit `false`: Login nach 20
  Fehlversuchen pro Stunde je IP gesperrt, höchstens 5 Registrierungen pro
  12 Stunden je IP. Beim Vorführen also nicht zu oft registrieren.
- **Geschützte Seite bauen:** direkt nach `bootstrap.php`
  `Auth::nurFuerMitglieder();` aufrufen. Die Daten dann über einen Endpunkt
  laden, der `Auth::instanz()->isLoggedIn()` prüft.
- Nach einem Passwort-Reset werden alle anderen Sitzungen des Kontos abgemeldet.
- **`mein-konto.php` und `anmelden.page.js` gehören nicht mehr allein zu diesem
  Feature.** Das Konto zeigt zusätzlich die Karte „Meine Auswahl", und nach dem
  Anmelden wird eine lokal getroffene Programm-Auswahl übernommen — siehe
  [Gemerkte Auswahl](gemerkte-auswahl.md). Wer hier etwas ändert, prüft beides.

## Was fehlt noch

- Echter E-Mail-Versand (SMTP), dafür braucht es Zugangsdaten und eine weitere
  Bibliothek oder einen Mailserver.
- Stammdaten ändern, Buchungen, Zahlungen: eigene Issues, die auf
  `mitglieder.id` aufbauen. Der erste davon ist gebaut — die Tarife und
  Verträge stehen in [Mitgliedschaften](mitgliedschaften.md).
- Die Kopfzeile ist zwischen 801 und etwa 880 px Breite sehr voll. Dort bricht
  „Probetraining buchen“ auf zwei Zeilen um.
