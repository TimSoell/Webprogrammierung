# Feature: Gemerkte Auswahl

**Status:** fertig
**Verantwortlich:** Jonny
**Zuletzt geprüft:** 2026-09-21

## Was kann man damit

Wer angemeldet ist und auf einer Programmseite Level und Format gewählt hat,
kann die Auswahl mit dem Button **„Auswahl merken"** an seinem Konto ablegen.
Unter **Mein Konto → Meine Auswahl** stehen dann alle gemerkten Programme mit
Level und Format, jedes mit Link zur Kursseite und einem Knopf zum Entfernen.

Wer nicht angemeldet ist, sieht an dieser Stelle einen Hinweis mit Link zur
Anmeldung. Die Auswahl gilt dann weiterhin nur in diesem Browser.

Meldet man sich später an, wird eine als Gast getroffene Auswahl übernommen —
aber nur für Programme, zu denen am Konto noch nichts steht.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `mein-konto.php`, `programme/strength.php`, `move.php`, `fight.php` |
| 2 Seitenskript | `assets/js/pages/mein-konto.page.js`, `anmelden.page.js` |
| 2 Komponente | `assets/js/components/programm-details.js`, `auswahl-speicher.js` |
| 3 Service | `assets/js/services/auswahl.js` |
| 4 Endpunkt | `api/auswahl.php` |
| 5 Repository | `src/Repositories/MitgliedAuswahlRepository.php` |
| 5 ergänzt | `MitgliedRepository::idFindenNachUserId()`, `ProgrammRepository::merkmalIdFinden()` |
| 6 Tabelle | `mitglied_auswahl` in `database/schema.sql` |
| CSS | `assets/css/components/auswahl-liste.css`, Button in `merkmale.css` |

Aufbauend auf [Programm-Details](programm-details.md) und
[Mitglieder-Login](mitglieder-login.md) — dieses Feature verbindet beide.

## Wo die Auswahl liegt

Das ist die Frage, die beim Weiterarbeiten am häufigsten kommt. Es sind
**zwei Orte**, und welcher gilt, hängt davon ab, ob jemand angemeldet ist:

| | nicht angemeldet | angemeldet |
|---|---|---|
| Gespeichert wird | bei jedem Klick | erst mit dem Button |
| Wohin | `localStorage` des Browsers | Tabelle `mitglied_auswahl` |
| Sichtbar in `mein-konto.php` | nein | ja |
| Gilt auf anderem Gerät | nein | ja |

Beim Öffnen einer Programmseite **gewinnt das Konto**: Steht dort etwas, wird
es vorausgewählt, auch wenn im Browser etwas anderes steht. Der Knopfdruck war
die bewusstere Entscheidung.

Der `localStorage` wird dabei nicht gelöscht. Wer sich abmeldet, findet seine
lokale Auswahl also wieder vor.

Schlüsselaufbau im Browser (steht genau einmal im Code, in
`components/auswahl-speicher.js`):

```
schwitzkasten:programm:<slug>:<art>      z. B. schwitzkasten:programm:move:level
```

## Datenform

Was `meineAuswahlLaden()` zurückgibt:

```json
{
  "auswahl": [
    { "slug": "move", "programm": "Move", "level": "Athletisch", "format": "Small Group" }
  ]
}
```

`level` und `format` können `null` sein — dann wurde nur eines gewählt, oder
das Merkmal ist in der Datenbank weggefallen (`ON DELETE SET NULL`). Die Liste
im Konto zeigt in dem Fall nur die verbliebene Angabe.

`auswahlFuerProgrammLaden('move')` liefert stattdessen ein einzelnes Objekt
oder `null`:

```json
{ "auswahl": { "level": "Athletisch", "format": "Small Group" } }
```

## Endpunkte

Alle setzen eine Anmeldung voraus und antworten sonst mit **401**.

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/auswahl.php` | alle gemerkten Auswahlen | `{ "auswahl": [...] }` |
| GET | `api/auswahl.php?slug=move` | die zu einem Programm | `{ "auswahl": {...}\|null }` |
| PUT | `api/auswahl.php` | merken oder überschreiben | `{ "gemerkt": true }` · 400 · 404 |
| DELETE | `api/auswahl.php` | entfernen | `{ "entfernt": true }` · 404 |

**PUT und DELETE übergeben Titel, keine ids.** Der Browser kennt die Merkmale
nur über ihren Titel — so steht es auch im `localStorage`. `merkmalIdFinden()`
macht daraus eine id, und diese Suche ist zugleich die Prüfung: Ein Level, das
zu einem anderen Programm gehört, führt zu 400. Damit kann über den Endpunkt
kein fremdes Merkmal untergeschoben werden.

DELETE antwortet auch dann mit Erfolg, wenn nichts zu löschen war — das
Ergebnis ist dasselbe.

## Woher kommen die Daten aktuell

**MySQL.** `database/schema.sql` einspielen, dann `seed.sql`. Die Tabelle
`mitglied_auswahl` füllt sich nur über die Oberfläche, in `seed.sql` stehen
dazu keine Zeilen (sie bräuchten ein Konto).

> **Vor einer Vorführung `seed.sql` nicht neu einspielen.** Das `DELETE FROM
> programme` darin greift über `ON DELETE CASCADE` bis in `mitglied_auswahl`
> durch — die gemerkten Auswahlen aller Mitglieder sind danach weg.

## Gut zu wissen

- **Der Button hat drei Beschriftungen:** „Auswahl merken" (noch nichts am
  Konto), „Auswahl aktualisieren" (am Konto steht etwas anderes) und
  „Gemerkt" (gesperrt, Anzeige und Konto stimmen überein). Zuständig ist
  `beschriften()` in `programm-details.js`.
- **Die Programmseiten kennen den Login-Zustand über ein data-Attribut.**
  `$angemeldet = Auth::instanz()->isLoggedIn();` oben in der Seite, ausgegeben
  als `data-angemeldet` am Container `#merken`. Das kostet keine zusätzliche
  Anfrage — `isLoggedIn()` liest nur die Session.
- **Die Übernahme beim Anmelden läuft still.** Sie steht in `try/catch` ohne
  Rückmeldung: Sie ist eine Bequemlichkeit, kein Teil des Anmeldens. Scheitert
  sie, wird man trotzdem angemeldet.
- **Jede Abfrage filtert nach `mitglied_id`.** Ohne das könnte jemand über eine
  erratene Kennung die Auswahl eines anderen lesen oder löschen.
- **Wird ein Konto gelöscht, verschwindet die Auswahl mit** — über
  `mitglieder` und zwei Stufen `ON DELETE CASCADE`.

## Was fehlt noch

- Ändern direkt im Mitgliedsbereich. Bewusst nicht gebaut, Begründung in
  [ADR-0007](../decisions/ADR-0007-gemerkte-auswahl-am-konto.md).
- Der Merken-Button kennt nur „alles" — einzeln nur Level oder nur Format zu
  merken, geht über die Oberfläche nicht. Die Tabelle könnte es (beide Spalten
  sind `NULL` erlaubt).
- Eine Sortierung nach `geaendert_am`. Die Spalte ist da und wird gepflegt,
  die Liste sortiert aber nach Programmreihenfolge.
