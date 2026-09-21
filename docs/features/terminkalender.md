# Feature: Terminkalender

**Status:** fertig
**Verantwortlich:** Jonny
**Zuletzt geprüft:** 2026-09-21

## Was kann man damit

Es gibt zwei Kalender und einen dritten Blick auf die eigenen Buchungen.

**Kurstermine.** Auf jeder Kursseite öffnet der Button **„Termin buchen"** ein
dunkles Fenster mit den nächsten zwei Wochen als **Kreisen**, einer pro Tag,
darin nur die Tageszahl. Jedes Format findet zweimal pro Woche statt.

- **Kreis überfahren:** Darunter erscheinen die Kurse dieses Tages.
- **Kreis anklicken:** Der Tag bleibt stehen, auch wenn die Maus auf dem Weg
  zu den Terminen über andere Kreise fährt.
- **Termin überfahren:** Coach, Teilnehmerzahl und Formatbeschreibung klappen
  auf.
- **Termin anklicken:** Daneben erscheinen Stufenauswahl und **„Buchen"**.
  Ist er voll: **„Leider ist hier schon alles vollgeschwitzt."**

Die Kreise zeigen auf einen Blick: Ring in Limette = Kurse, roter Ring =
alles ausgebucht, gedämpft = keine Kurse, gefüllt = gewählter Tag, Punkt unter
der Zahl = selbst gebucht.

**Probetraining.** Unter „Probetraining buchen" (Kopfzeile) und „Probetraining
sichern" (Kursseiten) wählt man einen Coach, gibt seine Stufe an und nimmt
einen der freien Termine dieses Coaches. Nach jeder Wahl scrollt die Seite zur
nächsten offenen Frage. Das Probetraining ist unabhängig von den Kursen.

**Meine Termine.** Im Profil stehen alle gebuchten Kurse und Probetrainings in
einem Kalender, jeweils mit „Stornieren" bis zum Beginn.

Bei jeder Buchung gibt man eine der drei Stufen an: Einsteiger,
Fortgeschritten, Erfahren. Das Format eines Kurses („Small Group") ist nur
Einordnung, keine buchbare Kategorie.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `probetraining.php`, `mein-konto.php` (Karte „Meine Termine"), `programme/*.php` (Button) |
| 1 Baustein | `partials/kurskalender.php` (das Fenster), `partials/header.php` (Menüpunkt) |
| 2 Seitenskript | `assets/js/pages/probetraining.page.js`, `strength/move/fight.page.js` |
| 2 Komponente | `assets/js/components/kurskalender.js`, `meine-termine.js`, `stufen.js` |
| 2 Hilfsmittel | `assets/js/lib/datum.js` |
| 3 Service | `assets/js/services/kurstermine.js`, `probetrainings.js` |
| 4 Endpunkt | `api/kurstermine.php`, `api/kursbuchungen.php`, `api/verfuegbarkeiten.php`, `api/probetrainings.php` |
| 4 Unterbau | `src/Terminplan.php` (Termine ausrechnen und prüfen, ohne SQL) |
| 5 Repository | `src/Repositories/KursterminRepository.php`, `KursbuchungRepository.php`, `ProbetrainingRepository.php` |
| 6 Tabelle | `kurstermine`, `kursbuchungen`, `verfuegbarkeiten`, `probetrainings` in `database/schema.sql` |
| 6 Inhalte | Wochenplan und Verfügbarkeiten in `database/seed.sql` |
| CSS | `assets/css/components/kalender.css`, `probetraining.css`, Variante in `modal.css` |

## Wie Termine entstehen

In der Datenbank stehen **Wochenmuster**, keine Einzeltermine — Begründung in
[ADR-0014](../decisions/ADR-0014-terminkalender-wochenplan.md).

| Tabelle | Beispielzeile | wird zu |
|---|---|---|
| `kurstermine` | Move · Small Group · Wochentag 2 · 18:00 · 60 Min. | jeder Dienstag der nächsten 14 Tage |
| `verfuegbarkeiten` | Sofia Lindqvist · Wochentag 1 · 10:00–13:00 | Montag 10, 11, 12 Uhr, je 60 Min. |

`wochentag` zählt **1 = Montag bis 7 = Sonntag**, wie PHPs `date('N')`. Nicht
MySQLs `DAYOFWEEK()` nehmen — das beginnt mit 1 = Sonntag.

Das Ausrechnen macht `src/Terminplan.php`. Dieselben Methoden prüfen beim
Buchen, ob der Termin existiert. Termine, die schon begonnen haben, fallen
weg.

## Belegung und „ausgebucht"

```
belegt = stammplaetze + Anzahl Buchungen für diesen Termin an diesem Tag
voll   = belegt >= max_teilnehmer
```

**`max_teilnehmer` ist 20 — außer beim Einzelcoaching.** Dort betreut ein
Coach eine Person, es gibt also höchstens so viele Plätze wie das Programm
Coaches hat (bei Strength: 3). `seed.sql` zählt die Coaches, statt die Zahl
fest einzutragen. Kommt ein Coach dazu, stimmt sie nach dem nächsten
Einspielen von selbst.

**Stammplätze** sind jede Woche schon vergeben. Mit ihnen lässt sich
„ausgebucht" vorführen, ohne zwanzig Konten anzulegen. In `seed.sql` sind drei
Termine immer voll und zwei haben genau noch einen Platz frei:

| immer voll | ein Platz frei |
|---|---|
| Strength · Einzelcoaching · Do 17:00 (3/3) | Strength · Small Group · Fr 18:00 (19/20) |
| Move · Morgenroutine · Do 07:00 (20/20) | Fight · Konditionsrunde · Sa 11:00 (19/20) |
| Fight · Gruppenkurs · Mi 19:30 (20/20) | — |

Das Einzelcoaching am Dienstag hat 1 von 3 belegt.

Zum Vorführen: Den „ein Platz frei"-Termin mit einem Konto buchen, dann mit
einem zweiten versuchen — das zweite bekommt „vollgeschwitzt".

## Datenform

`termineLaden('move')`:

`von` und `bis` sind erster und letzter Tag des Kalenders, auch ohne Termin -
daraus entstehen die Kreise. Sie kommen vom Server, damit Browser und Server
beim selben „heute" anfangen.

```json
{
  "angemeldet": true,
  "von": "2026-09-21",
  "bis": "2026-10-04",
  "termine": [
    {
      "terminId": 7, "datum": "2026-09-22", "beginn": "18:00", "ende": "19:00",
      "format": "Small Group", "formatBeschreibung": "…",
      "coach": "Sofia Lindqvist", "coachSchwerpunkt": "…",
      "belegt": 14, "max": 20, "gebucht": false
    }
  ]
}
```

`freieTermineLaden(coachId)`:

```json
{ "termine": [{ "beginntAm": "2026-09-22 10:00", "datum": "2026-09-22", "beginn": "10:00", "ende": "11:00" }] }
```

`beginntAm` geht beim Buchen genau so zurück.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/kurstermine.php?slug=move` | Zeitraum, Termine, Belegung | siehe oben · 404 |
| GET | `api/kursbuchungen.php` | eigene kommende Kurse | `{ "buchungen": [...] }` |
| POST | `api/kursbuchungen.php` | `{ terminId, datum, stufe }` | 201 · 400 · 409 voll oder doppelt |
| DELETE | `api/kursbuchungen.php` | `{ id }` stornieren | `{ "storniert": true }` · 404 · 409 begonnen |
| GET | `api/verfuegbarkeiten.php` | Coaches mit Verfügbarkeit | `{ "coaches": [...] }` |
| GET | `api/verfuegbarkeiten.php?coach=13` | freie Termine | siehe oben · 404 |
| GET | `api/probetrainings.php` | eigene kommende Probetrainings | `{ "probetrainings": [...] }` |
| POST | `api/probetrainings.php` | `{ coachId, beginntAm, stufe }` | 201 · 400 · 409 vergeben |
| DELETE | `api/probetrainings.php` | `{ id }` stornieren | `{ "storniert": true }` · 404 · 409 begonnen |

Lesen von Kalender und Verfügbarkeiten geht ohne Anmeldung. Alles andere
antwortet ohne Anmeldung mit 401.

## Woher kommen die Daten aktuell

**MySQL.** Nach dem Pull einmal einspielen:

```bash
mysql --default-character-set=utf8mb4 -u root < database/schema.sql
mysql --default-character-set=utf8mb4 -u root < database/seed.sql
```

Unter Windows mit vollem Pfad, siehe `README.md`.

> **`seed.sql` neu einspielen löscht alle Buchungen** — und die gemerkten
> Auswahlen. Vor einer Vorführung nicht tun.

## Gut zu wissen

- **Überfahren zeigt einen Tag nur an, der Klick wählt ihn.** Sonst tauschte
  jeder Kreis, über den die Maus auf dem Weg nach unten fährt, die Termine
  aus, und man käme nie bei ihnen an. Verlässt die Maus die Kreise, steht
  wieder der gewählte Tag da.
- **Die Tagesansicht hat eine Mindesthöhe.** Ohne sie würde das Fenster beim
  Überfahren der Kreise ständig die Höhe wechseln, weil Tage ohne Kurse viel
  niedriger sind.
- **Beim Überfahren schwebt die Info, statt die Liste aufzuschieben.** Würde
  sie die Liste aufschieben, klappte beim Weiterfahren mit der Maus der obere
  Termin zu, alles rutschte nach oben, und der Klick träfe den falschen
  Termin. Das ist beim Testen genau so passiert. Erst nach dem Klick steht
  der Termin normal in der Liste. Die Regeln dazu stehen in `kalender.css`.
- **Auf dem Handy gibt es kein Überfahren.** Dort öffnet der Tipp den Termin
  direkt mit Infos und Buchen-Bereich.
- **Kalendertage sind `<div>`, nicht `<section>`.** `03-layout.css` gibt jedem
  `<section>` 118 px Innenabstand.
- **Die Stufen stehen dreimal:** `Terminplan::STUFEN` (PHP), `STUFEN` in
  `components/stufen.js` (Beschriftung) und als ENUM in zwei Tabellen. Eine
  vierte Stufe braucht alle drei Stellen.
- **Die Meldung „vollgeschwitzt" steht zweimal:** in `api/kursbuchungen.php`
  und in `kurskalender.js`. Der Kalender vergleicht den Text, um zu erkennen,
  dass er neu laden soll. Wer den Satz ändert, ändert ihn an beiden Stellen.
- **Coach-Fotos:** Wie auf den Kursseiten erscheinen Initialen, solange
  `assets/img/coaches/` leer ist.

## Was fehlt noch

- Ausfälle an einzelnen Tagen („am 3. Oktober kein Kurs"), siehe ADR-0014.
- Eine Warteliste für volle Termine.
- Eine Grenze, wie viele Probetrainings ein Mitglied buchen darf. Aktuell
  beliebig viele.
- Eine Prüfung, ob sich ein Verfügbarkeitsfenster mit einem Kurs desselben
  Coaches überschneidet. Die Testdaten sind so gebaut, dass es nicht vorkommt.
- Eine Bestätigung per E-Mail. Die gibt es projektweit noch nicht.
