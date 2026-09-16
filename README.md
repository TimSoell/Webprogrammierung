# SCHWITZKASTEN Athletic Club

Website eines Fitnessstudios. Studienarbeit im Fach **Webprogrammierung**,
DHBW, 3. Semester.

Umgesetzt mit HTML, CSS und JavaScript. PHP und MySQL bilden den serverseitigen
Teil. Es kommt **kein Framework und kein Build-Werkzeug** zum Einsatz — der
Quelltext im Repository ist genau der Quelltext, der im Browser ankommt.

---

## Starten

Voraussetzung: **XAMPP** ist installiert. Gebraucht wird davon zunächst nur
das mitgelieferte PHP. MySQL kommt dazu, sobald ein Feature Daten speichert.

Der Projektordner darf liegen, wo du willst — er muss **nicht** in
`xampp/htdocs/`.

1. Repository klonen.
2. Konfiguration anlegen — einmalig:

   ```bash
   cp config/config.example.php config/config.php
   ```

   Unter Windows genügt Kopieren und Umbenennen im Explorer. Die
   Standardwerte passen zu einer frischen XAMPP-Installation.

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
Projektordner. Warum das so gelöst ist und warum ein Symlink nach `htdocs`
**nicht** funktioniert, steht in
[`ADR-0004`](docs/decisions/ADR-0004-php-entwicklungsserver.md).

### Alternative: über Apache aus `htdocs`

Der klassische XAMPP-Weg gilt weiterhin — und er ist der Weg für die
**Abgabe und die Vorführung**:

1. Projektordner nach `xampp/htdocs/` legen.
2. In XAMPP **Apache** starten.
3. Im Browser `http://localhost/<projektordner>/` aufrufen.

Am Quelltext ändert das nichts: `BASE_URL` wird berechnet und stimmt in beiden
Fällen. Weil aber entwickelt und vorgeführt auf unterschiedlichen Servern
wird, gilt: **vor der Abgabe einmal über Apache prüfen.**

### Datenbank (für den Mitglieder-Login)

Startseite und Programmseiten laufen ohne Datenbank. Für Registrierung und
Login braucht es sie:

In XAMPP **MySQL** starten. Für phpMyAdmin zusätzlich **Apache** — das
Startskript ersetzt Apache nur für die Projektseite, nicht für phpMyAdmin.
Dann `http://localhost/phpmyadmin` öffnen und unter *Importieren* die Datei
`database/schema.sql` einspielen. Sie legt die Datenbank `baseline` samt
Tabellen an und darf beliebig oft eingespielt werden.

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
(Strength, Move, Fight), inklusive mobiler Navigation und dem Anmeldefenster
für Interessenten.

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
