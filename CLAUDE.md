# CLAUDE.md — Regelwerk für dieses Projekt

Diese Datei wird von Claude Code bei jeder Sitzung automatisch gelesen. Sie
gilt für **alle** im Team und für **jede** KI-gestützte Änderung.

Sie ist bewusst als Regelwerk formuliert, nicht als Beschreibung. Wer sie
befolgt, dokumentiert automatisch mit — darum geht es.

---

## 1. Das Projekt

**SCHWITZKASTEN Athletic Club** — Website eines Fitnessstudios.
Studienarbeit im Fach Webprogrammierung (DHBW, 3. Semester), 5 Personen.

| | |
|---|---|
| Frontend | HTML, CSS, JavaScript als ES-Module. **Kein Framework** |
| Backend | PHP 8, PostgreSQL bei Supabase über PDO — siehe [`ADR-0017`](docs/decisions/ADR-0017-postgresql-auf-supabase.md) |
| Login | [delight-im/auth](https://github.com/delight-im/PHP-Auth) über Composer, `vendor/` eingecheckt — siehe [`ADR-0005`](docs/decisions/ADR-0005-login-bibliothek.md) |
| Umgebung | Lokal PHP-Server aus XAMPP, veröffentlicht auf Vercel — siehe [`ADR-0016`](docs/decisions/ADR-0016-hosting-auf-vercel.md) |
| Aufbau | Sechs Schichten, siehe [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) |

**Kein React, kein Vue, kein Bootstrap, kein Tailwind, kein npm, kein
Build-Schritt.** Die Aufgabenstellung gibt HTML/CSS/JS vor. Bibliotheken
werden nicht ohne Absprache eingeführt.

---

## 2. Starten

```bash
cp config/config.example.php config/config.php   # einmalig, dann DB-Passwort eintragen
./start.sh                                       # Windows: start.bat
```

Dann `http://localhost:8000/` aufrufen. Das Skript startet den in PHP
eingebauten Entwicklungsserver direkt aus dem Projektordner — **das Projekt
muss nicht in `xampp/htdocs/` liegen.** Beenden mit `Ctrl+C`.

Die Datenbank ist **`schwitzkasten-dev` bei Supabase** — lokal läuft keine
Datenbank mehr, aber ohne Internet läuft auch keine Seite. Nie lokal gegen
die Produktionsdatenbank arbeiten.

**Veröffentlicht** wird automatisch: Pull Request → Preview auf Vercel,
Merge nach `main` → Produktion (`.github/workflows/vercel.yml`). Auf Vercel
ist `api/index.php` der einzige Einstieg und bindet Seiten und Endpunkte ein,
siehe [`ADR-0016`](docs/decisions/ADR-0016-hosting-auf-vercel.md). Neue Seiten
brauchen dafür nichts Zusätzliches, solange sie in der obersten Ebene, in
`programme/` oder in `api/` liegen.

---

## 3. Die sechs Schichten

```
1  Seite            *.php                        HTML. Sonst nichts
2  Seitenskript     assets/js/pages/*.page.js    Klicks, Anzeige
3  Service          assets/js/services/*.js      Der einzige Ort mit fetch()
4  Endpunkt         api/*.php                    Prüft Eingaben, gibt JSON
5  Repository       src/Repositories/*.php       Der einzige Ort mit SQL
6  Datenbank        database/schema.sql          Tabellen
```

**Jede Schicht spricht nur mit der direkt darunter. Nie überspringen.**

---

## 4. Harte Regeln

Diese sechs Punkte sind nicht verhandelbar. Wer sie bricht, hebelt die
Architektur aus.

1. **Kein `fetch()` außerhalb von `assets/js/services/`.**
2. **Kein SQL außerhalb von `src/Repositories/`.**
3. **Kein `<style>` und kein `style="..."` in PHP- oder HTML-Dateien.**
   Aussehen gehört nach `assets/css/`.
4. **Kein `<script>`-Block in PHP- oder HTML-Dateien.**
   Verhalten gehört nach `assets/js/`.
5. **Jede Ausgabe escapen:** `<?= e($wert) ?>`, nie `<?= $wert ?>`.
6. **Nie Werte in SQL-Strings schreiben.** Immer `prepare()` mit `?`.

Weitere feste Punkte:

- `declare(strict_types=1);` in jeder PHP-Datei.
- **Zugangsdaten gehören nie ins Repository** — es ist öffentlich.
  `config/config.php` steht in `.gitignore` und bleibt dort; auf Vercel
  stehen die Werte in den Umgebungsvariablen des Projekts.
- **Neue Tabelle = `ENABLE ROW LEVEL SECURITY`** am Ende von `schema.sql`,
  sonst ist sie über die öffentliche Schnittstelle von Supabase lesbar.
- `require` bekommt `ROOT_PATH`, `href` und `src` bekommen `BASE_URL`.
  Diese beiden zu verwechseln ist der häufigste Fehler im Projekt.
- **`vendor/` wird nie von Hand geändert.** Die Login-Bibliothek wird nur
  über Composer aktualisiert. Erzeugt wird sie nur in `src/Auth.php`.
- **Seiten nur für Mitglieder** rufen direkt nach `bootstrap.php`
  `Auth::nurFuerMitglieder();` auf.

---

## 5. Dokumentationspflichten

**Das ist der automatische Teil.** Diese vier Regeln greifen bei jeder
Änderung, ohne dass jemand daran denken muss.

### 5.1 Neue Datei → Kopfkommentar

Jede neue Datei beginnt mit einem Kopfkommentar. **Ohne Ausnahme**, auch bei
CSS und SQL.

```php
/**
 * @file        api/kurse.php
 * @layer       4 – API-Endpunkt
 * @description Liefert alle Kurse als JSON und nimmt neue Kurse entgegen.
 * @see         src/Repositories/KursRepository.php
 */
```

Formate für JS, CSS und SQL: [`docs/templates/datei-header.md`](docs/templates/datei-header.md)

### 5.2 Neues Feature → Feature-Doku

Sobald an einem Feature begonnen wird, entsteht
`docs/features/<name>.md` aus [`docs/templates/feature-doku.md`](docs/templates/feature-doku.md).

Nicht danach — **beim Beginnen**. Eine halb ausgefüllte Datei ist besser als
keine.

### 5.3 Architekturentscheidung → ADR

Wird etwas entschieden, das schwer rückgängig zu machen ist, entsteht ein
nummeriertes ADR in `docs/decisions/` aus
[`docs/templates/adr.md`](docs/templates/adr.md).

Auslöser: eine neue Technik, eine geänderte Ordnerstruktur, eine neue feste
Regel fürs Team.

Kein ADR für: Farben, Texte, einzelne Dateien.

**Ein ADR wird nie gelöscht und nie umgeschrieben.** Ändert sich die
Entscheidung, entsteht ein neues, und im alten wird der Status auf
`ersetzt durch ADR-YYYY` gesetzt.

### 5.4 Öffentliche Funktion → Parameter dokumentieren

```js
/**
 * Lädt alle Kurse eines Wochentags.
 *
 * @param {string} tag  Wochentag, z. B. 'Montag'
 * @returns {Promise<Array<{id: number, name: string}>>}
 * @throws {ApiError}   Wenn der Server nicht erreichbar ist
 */
```

Kein Selbstzweck: VS Code liest das und zeigt es beim Tippen als Hilfe an.

### 5.5 Wenn eine Änderung eine Doku widerlegt

Dann wird die Doku **im selben Commit** mitgeändert. Eine falsche
Dokumentation ist schlechter als keine — sie schickt Leute in die Irre.

Betroffen sind meistens: die Ordner-`README.md`, `docs/features/<name>.md`
und die Tabelle in `docs/ARCHITECTURE.md`.

---

## 6. Namenskonventionen

| Was | Regel | Beispiel |
|---|---|---|
| Seiten | kleingeschrieben, Bindestrich | `kurse.php`, `online-shop.php` |
| Seitenskripte | wie die Seite plus `.page.js` | `kurse.page.js` |
| Services | Thema in Mehrzahl | `kurse.js` |
| Endpunkte | wie die Tabelle | `api/kurse.php` |
| PHP-Klassen | `PascalCase`, Einzahl | `KursRepository` |
| PHP-Methoden | `camelCase`, deutsch, fachlich | `kurseAmTagFinden()` |
| JS-Funktionen | `camelCase`, deutsch, fachlich | `alleLaden()` |
| CSS-Klassen | kleingeschrieben, Bindestrich | `.program-card` |
| CSS-Varianten | zwei Bindestriche | `.button--dark` |
| CSS-Dateien | wie die Hauptklasse | `program-card.css` |
| Tabellen | kleingeschrieben, Mehrzahl | `kurse`, `buchungen` |
| Spalten | kleingeschrieben, Unterstrich | `erstellt_am`, `kurs_id` |

**Deutsch oder Englisch?** Fachbegriffe aus der Domäne auf Deutsch
(`Kurs`, `Buchung`, `Mitglied`), technische Begriffe auf Englisch
(`Repository`, `Service`, `id`). Umlaute nie in Datei-, Tabellen- oder
Spaltennamen — in Texten und Inhalten selbstverständlich schon.

---

## 7. Neues Feature — Reihenfolge

Am Beispiel „Kursplan". Von unten nach oben:

1. Tabelle in `database/schema.sql`, in `schwitzkasten-dev` einspielen, **im Team ansagen**
2. `src/Repositories/KursRepository.php` — das SQL
3. `api/kurse.php` — Eingaben prüfen, JSON zurückgeben
4. `assets/js/services/kurse.js` — `alleLaden()` und Co.
5. `assets/js/pages/kurse.page.js` — Anzeige und Klicks
6. `kurse.php` — das HTML-Gerüst
7. `assets/css/components/kursliste.css` — **und in `main.css` eintragen**
8. Menüpunkt in `partials/header.php` im Array `$navItems`
9. `docs/features/kursplan.md` aus der Vorlage

Zwischenstand ist erlaubt: Schritt 4 zuerst mit erfundenen Daten, dann 6 und
5 bauen. Dann sieht man sofort etwas, und 1–3 kommen später nach — ohne dass
sich an 5 und 6 etwas ändert.

Als Vorlage dienen die sechs Dateien des Beispiel-Features. Sie enthalten
absichtlich keinen Code, sondern beschreiben pro Schicht, was hineingehört:

```
beispiel-feature.php
assets/js/pages/beispiel-feature.page.js
assets/js/services/beispiel-feature.js
api/beispiel-feature.php
src/Repositories/BeispielFeatureRepository.php
database/schema.sql
```

---

## 8. Zusammenarbeit im Team

Bei fünf Personen ist die größte Gefahr, dass zwei dieselbe Datei anfassen.

**Branches**

```
feature/<kurzname>     z. B. feature/kursplan
fix/<kurzname>         z. B. fix/nav-mobil
```

Nie direkt auf `main` arbeiten.

**Commits** — auf Deutsch, im Imperativ, eine Sache pro Commit:

```
Kursliste als Komponente ergänzen
Fehler in der mobilen Navigation beheben
```

Nicht: `Änderungen`, `Update`, `fix`, `asdf`.

**Ownership** — pro Feature übernimmt eine Person die Federführung. Sie legt
die Feature-Doku an und pflegt sie.

**Vor jedem Push**

- [ ] Läuft die Seite im Browser ohne Fehler in der Konsole?
- [ ] Haben alle neuen Dateien einen Kopfkommentar?
- [ ] Ist die Feature-Doku aktuell?
- [ ] Wurde `config/config.php` versehentlich mit eingecheckt? (Darf nicht sein)
- [ ] Bei Tabellenänderung: `schema.sql` angepasst und im Team angesagt?

---

## 9. Wenn du Claude Code benutzt

- **Halte dich an die Schichten.** Vorschläge, die eine Schicht überspringen,
  sind falsch, auch wenn sie kürzer sind.
- **Frag nach, statt zu raten.** Wenn unklar ist, in welche Schicht etwas
  gehört, ist die Frage billiger als der Umbau.
- **Bau nichts auf Vorrat.** Keine Konfigurierbarkeit, keine Abstraktion für
  einen einzigen Anwendungsfall, keine Fehlerbehandlung für unmögliche Fälle.
  Das Projekt hat eine Abgabefrist.
- **Ändere nichts, was nicht zur Aufgabe gehört.** Keine spontane
  Verschönerung von Nachbarcode, keine Umformatierung.
- **Prüfe das Ergebnis im Browser**, nicht nur im Editor.
