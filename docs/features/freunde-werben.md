# Feature: Freunde werben

**Status:** fertig
**Verantwortlich:** Jonny
**Zuletzt geprüft:** 2026-10-02

## Was kann man damit

Ganz unten auf der Startseite steht der Abschnitt **„Bring wen mit."** mit dem
Button **„Freund einladen"**. Er öffnet ein Fenster, in dem man Name und E-Mail
eines Freundes einträgt. Danach zeigt das Fenster einen **Link zum
Weitergeben** — die Seite verschickt selbst keine E-Mail.

Registriert sich jemand mit genau dieser E-Mail, bekommt der Werber einen
**Gutschein**: 3 Monate Basisplan gratis. Den Code sieht er unter „Mein Konto"
in der Karte **„Freunde werben"**, zusammen mit seinen Einladungen und deren
Stand (offen oder registriert).

Eingelöst wird der Code auf der Mitgliedschaftsseite unter **„Gutschein
einlösen"**. Danach steht in der Leiste dort, bis wann der Basisplan gratis ist.

Einladen und einlösen geht nur angemeldet. Gäste sehen im Fenster statt des
Formulars den Link zum Login.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `index.php` (Abschnitt und Fenster), `mein-konto.php` (Karte), `mitgliedschaft.php` (Feld „Gutschein einlösen") |
| 2 Seitenskript | `assets/js/pages/index.page.js`, `mein-konto.page.js`, `mitgliedschaft.page.js`, `anmelden.page.js` (`#registrierung`) |
| 2 Komponente | `assets/js/components/freunde-werben.js` |
| 3 Service | `assets/js/services/empfehlungen.js`, `gutscheine.js` |
| 4 Endpunkt | `api/empfehlungen.php`, `api/gutscheine.php`, dazu `api/mitglieder.php` (verbucht die Registrierung) und `api/mitgliedschaften.php` (liefert `gratis`) |
| 5 Repository | `src/Repositories/EmpfehlungRepository.php`, `GutscheinRepository.php`, dazu `MitgliedRepository::emailVergeben()` |
| 6 Tabelle | `empfehlungen`, `gutscheine` in `database/schema.sql` |
| CSS | `assets/css/components/freunde-werben.css` |

## Der Ablauf

```
1. Werber trägt Freund ein      POST api/empfehlungen.php   -> Zeile in empfehlungen, offen
2. Werber gibt den Link weiter  (außerhalb der Seite)
3. Freund registriert sich      POST api/mitglieder.php     -> Einladung erfüllt, Gutschein für den Werber
4. Werber löst den Code ein     POST api/gutscheine.php     -> gratis_von / gratis_bis eingetragen
```

Schritt 3 hängt allein an der **E-Mail**: Stimmt sie mit einer offenen
Einladung überein, zählt die Registrierung. Der Link ist für alle derselbe
(`anmelden.php#registrierung`) und trägt keine Kennung. Einladung erfüllen und
Gutschein anlegen passieren in einer Transaktion
(`EmpfehlungRepository::registrierungVerbuchen()`).

## Die Regeln

**Einladen**

- Eine E-Mail kann nur **einmal** eingeladen werden. Wer zuerst einträgt,
  bekommt den Gutschein. Das sichert der UNIQUE-Schlüssel auf
  `empfehlungen.freund_email`.
- Nicht die eigene E-Mail und keine, zu der es schon ein Konto gibt.
- Höchstens **5 offene** Einladungen gleichzeitig (`OFFENE_HOECHSTENS` in
  `api/empfehlungen.php`). Sonst könnte jemand hunderte fremde Adressen
  eintragen und auf zufällige Registrierungen hoffen.

**Gutschein**

- Er gehört dem Werber. **Nur er** kann ihn einlösen — ein erratener oder
  weitergegebener Code nützt niemand anderem. Für fremde und für erfundene
  Codes kommt dieselbe Antwort (404).
- Er gilt für den **Basisplan** und lässt sich nur einlösen, wenn der
  Basisplan läuft oder zum nächsten Monatsersten vorgemerkt ist. Die
  Gratiszeit beginnt dann heute bzw. am vorgemerkten Start und dauert
  **3 Monate** (`GutscheinRepository::GRATIS_MONATE`).
- Löst jemand mehrere Gutscheine ein, **hängen sie sich hintereinander**: Der
  nächste beginnt am Tag nach dem Ende des vorigen.
- Der Gutschein ändert **keinen Vertrag**. Er trägt nur einen Zeitraum ein, in
  dem der Basisplan nichts kostet. Begründung in
  [ADR-0021](../decisions/ADR-0021-gutschein-als-gratiszeitraum.md).

## Datenform

`einladungenLaden()`:

```json
{ "einladungen": [
  { "name": "Max Freund", "email": "max@beispiel.de", "registriert": true, "datum": "2026-10-02" }
] }
```

`gutscheineLaden()`:

```json
{ "gutscheine": [
  { "code": "SK-E44HP2ZW", "eingeloest": true, "gratisVon": "2026-11-01", "gratisBis": "2027-01-31" }
] }
```

`gratisVon` und `gratisBis` sind beide einschließlich und `null`, solange der
Gutschein nicht eingelöst ist.

`standLaden()` aus `mitgliedschaften.js` liefert zusätzlich
`"gratis": { "von": "2026-11-01", "bis": "2027-01-31" }` oder `null`.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/empfehlungen.php` | eigene Einladungen | siehe oben |
| POST | `api/empfehlungen.php` | `{ name, email }` einladen | 201 `{ "eingeladen": true }` · 400 · 409 schon eingeladen, schon registriert oder zu viele offen |
| GET | `api/gutscheine.php` | eigene Gutscheine | siehe oben |
| POST | `api/gutscheine.php` | `{ code }` einlösen | `{ gratisVon, gratisBis }` · 404 · 409 schon eingelöst oder kein Basisplan |

Alle vier antworten ohne Anmeldung mit 401.

## Woher kommen die Daten aktuell

**PostgreSQL bei Supabase.** Die beiden Tabellen sind in `schwitzkasten-dev`
eingespielt. **In der Produktion (`schwitzkasten`) noch nicht** — vor dem Merge
dort den Abschnitt „FEATURE FREUNDE WERBEN" aus `database/schema.sql` samt den
beiden `ENABLE ROW LEVEL SECURITY`-Zeilen im SQL Editor ausführen. Ohne die
Tabellen scheitern Einladen und Einlösen mit „Interner Serverfehler"; die
Registrierung läuft trotzdem, weil sie den Fehler abfängt.

## Gut zu wissen

- **Es wird keine E-Mail verschickt.** Das Projekt hat keinen Mail-Dienst.
  Der Freund erfährt von der Einladung nur über den Werber.
- **Der Link führt auf den Reiter „Registrierung".** `anmelden.page.js`
  schaltet bei `#registrierung` um; ohne den Zusatz bleibt es beim Login.
- **Die Gratiszeit ist an Tage gebunden, nicht an den Vertrag.** Wechselt
  jemand mittendrin weg vom Basisplan, läuft die Zeit weiter ab. Wechselt er
  zurück, gilt der Rest. Die Leiste auf der Mitgliedschaftsseite zeigt die
  Gratiszeit nur, wenn der Basisplan läuft oder vorgemerkt ist.
- **Es wird nichts abgerechnet**, siehe `mitgliedschaften.md`. „Gratis" ist
  deshalb eine Anzeige, kein Betrag auf einer Rechnung. `preis_monatlich` im
  Vertrag bleibt unverändert.
- **Scheitert das Verbuchen bei der Registrierung, merkt der Freund nichts.**
  Der Fehler landet im Log, das Konto entsteht trotzdem.
- **`hidden` braucht bei Formular und Bestätigung eine eigene CSS-Regel**,
  weil `display: grid` das Attribut sonst überstimmt
  (`freunde-werben.css`).

## Was fehlt noch

- Eine echte E-Mail an den Freund. Bräuchte einen Mail-Dienst.
- Ein persönlicher Link je Einladung, der zählt, auch wenn sich der Freund mit
  einer anderen E-Mail registriert.
- Eine Einladung zurücknehmen. Offene Einladungen bleiben, bis sich jemand
  registriert.
- Ein Hinweis im Bestätigungsfenster des Tarifwechsels, wenn man während der
  Gratiszeit vom Basisplan weg wechselt.
- Ein Gutschein auch für den Geworbenen.
