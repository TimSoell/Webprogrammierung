# SCHWITZKASTEN Athletic Club

Website eines Fitnessstudios. Studienarbeit im Fach **Webprogrammierung**,
DHBW, 3. Semester.

Umgesetzt mit HTML, CSS und JavaScript. PHP und PostgreSQL bilden den
serverseitigen Teil. Es kommt **kein Framework und kein Build-Werkzeug** zum
Einsatz — der Quelltext im Repository ist genau der Quelltext, der im Browser
ankommt.

Die veröffentlichte Fassung läuft auf **Vercel**, die Daten liegen bei
**Supabase**. Siehe [ADR-0016](docs/decisions/ADR-0016-hosting-auf-vercel.md)
und [ADR-0017](docs/decisions/ADR-0017-postgresql-auf-supabase.md).

---

## Starten

Voraussetzung: **XAMPP** ist installiert. Gebraucht wird davon nur das
mitgelieferte PHP — MySQL, Apache und phpMyAdmin nicht mehr. Und eine
Internetverbindung, denn die Datenbank liegt bei Supabase.

Der Projektordner darf liegen, wo du willst — er muss **nicht** in
`xampp/htdocs/`.

1. Repository klonen.
2. Konfiguration anlegen — einmalig:

   ```bash
   cp config/config.example.php config/config.php
   ```

   Unter Windows genügt Kopieren und Umbenennen im Explorer. Danach in
   `config/config.php` bei `'password'` das Passwort der
   Entwicklungsdatenbank eintragen. Das gibt es im Team per Passwortmanager.

3. Startskript ausführen:

   | System | Befehl |
   |---|---|
   | macOS, Linux | `./start.sh` |
   | Windows | `start.bat` — Doppelklick genügt |

4. Im Browser `http://localhost:8000/` aufrufen.

Beenden mit `Ctrl+C`, unter Windows `Strg+C`. Änderungen am Quelltext wirken
sofort, ein Neustart ist nicht nötig.

Fehlt Schritt 2, erscheint statt der Seite ein deutlicher Hinweis darauf.

Die Skripte starten den in PHP eingebauten Entwicklungsserver direkt aus dem
Projektordner, siehe
[`ADR-0004`](docs/decisions/ADR-0004-php-entwicklungsserver.md). Dabei läuft
`router.php` mit, damit der Server Videos stückweise ausliefern kann; ohne
das steht das Scroll-Video still. Siehe
[`ADR-0006`](docs/decisions/ADR-0006-router-fuer-entwicklungsserver.md).

Den Datenbank-Treiber `pdo_pgsql` laden die Skripte selbst dazu – an der
`php.ini` muss niemand etwas ändern. **macOS:** Ob XAMPP für macOS den Treiber
überhaupt mitbringt, zeigt `php -m | grep pdo_pgsql`. Fehlt er, PHP über
Homebrew installieren (`brew install php`).

### Datenbank

Lokal und in Preview-Deployments wird die gemeinsame Entwicklungsdatenbank
`schwitzkasten-dev` bei Supabase benutzt, nie die Produktionsdatenbank.
Tabellen ansehen und ändern: Supabase-Dashboard → **Table Editor**. Schema und
Testdaten einspielen: siehe [`database/README.md`](database/README.md).

**Supabase pausiert kostenlose Projekte nach sieben Tagen ohne Zugriff.** Dann
zeigt jede Seite einen Datenbankfehler. Im Dashboard das Projekt öffnen und
**Restore** klicken – vor jeder Vorführung einmal prüfen.

### Veröffentlichen

Nichts von Hand. Jeder Pull Request auf `main` bekommt eine Preview auf
Vercel, jeder Merge nach `main` geht in Produktion. Das erledigt
`.github/workflows/vercel.yml`.

---

## Aufbau

Das Projekt ist in **sechs Schichten** gegliedert. Jede Schicht hat eine
Aufgabe und spricht nur mit der direkt darunterliegenden.

```
1  Seite            index.php, programme/*.php     HTML und Struktur
2  Seitenskript     assets/js/pages/               Klicks, Formulare, Anzeige
3  Service          assets/js/services/            Datenbeschaffung
4  Endpunkt         api/                           JSON-Schnittstelle
5  Repository       src/Repositories/              Datenbankzugriff
6  Datenbank        database/schema.sql            Tabellen
```

Die ausführliche Erklärung mit Diagramm steht in
**[`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)**.

### Ordner

| Ordner | Inhalt |
|---|---|
| `programme/` | Detailseiten der Trainingsprogramme |
| `partials/` | Kopfzeile, Fußzeile und `<head>` — einmal für alle Seiten |
| `assets/css/` | Aussehen: Tokens, Basis, Layout, Komponenten |
| `assets/js/` | Verhalten: Komponenten, Seitenskripte, Services |
| `api/` | Endpunkte, die JSON liefern |
| `src/` | PHP-Klassen und Infrastruktur |
| `database/` | Bauplan und Testdaten der Datenbank |
| `config/` | Konfiguration (Zugangsdaten nicht im Repository) |
| `docs/` | Dokumentation |

**In jedem Ordner liegt eine `README.md`**, die erklärt, was dort hineingehört
und was nicht.

---

## Dokumentation

| Datei | Inhalt |
|---|---|
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Der Aufbau in einem Durchgang |
| [`docs/decisions/`](docs/decisions/) | Architekturentscheidungen mit Begründung und verworfenen Alternativen |
| [`docs/features/`](docs/features/) | Je ein Dokument pro Feature |
| [`docs/templates/`](docs/templates/) | Vorlagen für Kopfkommentare, Feature-Doku und Entscheidungen |
| [`CLAUDE.md`](CLAUDE.md) | Das Regelwerk fürs Team |

Zusätzlich beginnt **jede Quelldatei mit einem Kopfkommentar**, der ihren Ort
in der Architektur nennt:

```php
/**
 * @file        api/kurse.php
 * @layer       4 – API-Endpunkt
 * @description Liefert alle Kurse als JSON.
 * @see         src/Repositories/KursRepository.php
 */
```

---

## Aktueller Stand

Umgesetzt sind die **Startseite** und die drei **Programmseiten**
(Strength, Move, Fight), inklusive mobiler Navigation.

Dazu kommt das erste datenbankgestützte Feature: der **Mitglieder-Login**
mit Registrierung, Login, Mein Konto und „Passwort vergessen“ — siehe
[`docs/features/mitglieder-login.md`](docs/features/mitglieder-login.md).

Als Vorlage für weitere Features dient das **Beispiel-Feature**: sechs Dateien, eine pro Schicht, die absichtlich keinen
Code enthalten, sondern beschreiben, was in die jeweilige Schicht gehört.

```
beispiel-feature.php
assets/js/pages/beispiel-feature.page.js
assets/js/services/beispiel-feature.js
api/beispiel-feature.php
src/Repositories/BeispielFeatureRepository.php
database/schema.sql
```

Geplant: Kursplan mit Terminanfrage, Kontaktformular und ein kleiner
Produktbereich.
