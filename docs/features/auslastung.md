# Feature: Auslastungsdiagramm

**Status:** in Arbeit
**Verantwortlich:** offen
**Zuletzt geprueft:** 2026-10-16

## Was kann man damit

Auf der Startseite zeigt ein Balkendiagramm, wie voll das Studio im
Tagesverlauf ist. Ueber dem Diagramm steht die aktuelle Zahl mit einer Ampel
("entspannt", "gut besucht", "voll").

Angemeldete Mitglieder koennen unter **Mein Konto** sagen, wann sie kommen -
entweder sofort ("Jetzt einchecken") oder fuer eine Uhrzeit spaeter. Jeder
so eingetragene Besuch vergrößert die grüne Füllung. Übersteigt die Anzahl
der Besuche den Durchschnitt, wächst der Balken darüber hinaus.

## Woher kommen die Daten

Die Balkenhöhe ist das **Maximum aus Durchschnitt und erfassten Besuchen**:

| Anteil | Quelle | Aendert sich |
|---|---|---|
| Basiswert | Tabelle `auslastung_basis` | nie im Betrieb |
| Grüne Füllung | Tabelle `besuche`, beide Besuchsarten | bei jedem Check-in und jeder Ankündigung |

Der Basiswert ist **erfunden**. Ein echtes Studio wuerde ihn aus
Drehkreuzdaten gewinnen; hier steht er in einer Tabelle, damit die Vorhersage
aus der Datenbank kommt und nicht aus dem Quelltext. Wer die Kurve aendern
will, aendert `database/seed.sql` - kein PHP.

Der Durchschnitt bildet den grauen Hintergrund. Besuche füllen ihn von unten
grün, ohne Addition zum Durchschnitt. Sind mehr Besuche erfasst als üblich,
wächst der grüne Balken über den Durchschnitt hinaus. Stunden sind als
`9:00` statt `9` beschriftet. Die aktuelle Zahl und Ampel verwenden ebenfalls
das Maximum, zählen Besuche aber minutengenau zum aktuellen Zeitpunkt.
Die Stundenbalken zählen dagegen alle Besuche, die die Stunde berühren.
Siehe [ADR-0022](../decisions/ADR-0022-auslastung-als-fuellung.md).

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `index.php` (Abschnitt `.auslastung`), `mein-konto.php` (Karte "Mein Besuch") |
| 2 Seitenskript | `assets/js/pages/index.page.js`, `assets/js/pages/mein-konto.page.js` |
| 2 Komponente | `assets/js/components/auslastung-diagramm.js` |
| 3 Service | `assets/js/services/auslastung.js` |
| 4 Endpunkt | `api/auslastung.php` |
| 5 Repository | `src/Repositories/AuslastungRepository.php` |
| 5 Demo-Daten | `src/Repositories/AuslastungDemoRepository.php` (nur CLI) |
| 6 Datenbank | `database/schema.sql`, `database/seed.sql` |
| CSS | `assets/css/components/auslastung.css` |

## Datenform

Antwort von `GET api/auslastung.php`:

```json
{
  "kapazitaet": 170,
  "stand": "2026-09-21T15:39:42+02:00",
  "jetzt": { "personen": 37, "prozent": 22, "stufe": "entspannt", "stunde": 15 },
  "verlauf": [
    { "stunde": 0, "basis": 3, "gebucht": 0, "gesamt": 3 },
    { "stunde": 15, "basis": 37, "gebucht": 5, "gesamt": 37 }
  ],
  "meine": [
    { "id": 7, "beginn": "2026-09-21 18:00:00", "ende": "2026-09-21 19:30:00", "art": "geplant" }
  ]
}
```

`verlauf` hat immer genau 24 Eintraege, Stunde 0 bis 23. `meine` fehlt, wenn
niemand angemeldet ist.

## Endpunkte

| Methode | Anmeldung | Zweck |
|---|---|---|
| `GET` | nein | Kurve, aktueller Stand, eigene Besuche |
| `POST` | ja | Besuch eintragen: `{ art, zeit }` |
| `DELETE` | ja | eigenen Besuch entfernen: `{ id }` |

`GET` ist bewusst ohne Anmeldung erreichbar - das Diagramm steht auf der
Startseite und soll jeden Besucher erreichen.

`art` ist `'jetzt'` oder `'geplant'`. Bei `'jetzt'` wird `zeit` ignoriert.
Bei `'geplant'` ist `zeit` eine Uhrzeit `HH:MM`; liegt sie heute schon in der
Vergangenheit, versteht der Server sie als morgen.

## Zwei Zahlen, die man kennen sollte

Beide stehen als Konstanten in `AuslastungRepository`:

| Konstante | Wert | Bedeutung |
|---|---|---|
| `KAPAZITAET` | 170 | Bezugsgroesse fuer Prozent und Ampel |
| `DAUER_MINUTEN` | 90 | So lange wird ein Besuch gezaehlt |

**Es gibt kein Auschecken.** Ein Besuch verschwindet von selbst aus der
Zaehlung, sobald seine 90 Minuten vorbei sind. Dadurch muss niemand daran
denken, und eine vergessene Zeile verfaelscht nichts. Begruendung:
[`ADR-0013`](../decisions/ADR-0013-auslastung-und-besuche.md).

Die Ampelschwellen liegen im Endpunkt, nicht im Browser - so geben Startseite
und Mitgliedsbereich dieselbe Auskunft:

| Anteil an der Kapazitaet | Stufe |
|---|---|
| unter 45 % | entspannt |
| 45 bis 75 % | gut besucht |
| ueber 75 % | voll |

## Wie eine Stunde gezaehlt wird

Ein Besuch zaehlt in **jede Stunde, die er beruehrt**, nicht nur in die, in
der er beginnt. Ein Besuch von 17:50 bis 19:20 erscheint also in Stunde 17,
18 und 19 - er ist in jeder davon zeitweise anwesend.

Deshalb gruppiert `besucheProStundeZaehlen()` nicht nach `beginn`, sondern
prueft je Stunde, ob der Zeitraum sie ueberlappt.

## Vorfuehren

1. Startseite oeffnen, Abschnitt "Wie voll ist es?" - Zahl merken
2. Anmelden, **Mein Konto**, Karte "Mein Besuch"
3. "Jetzt einchecken" klicken
4. Zurück zur Startseite: die grüne Füllung der aktuellen Stunde wächst.
   Die aktuelle Zahl wächst, sobald mehr Besuche als der Durchschnitt vorliegen.

Die Startseite laedt alle 20 Sekunden von selbst nach. Wer beide Seiten in
zwei Tabs offen hat, sieht die Aenderung also auch ohne Neuladen - das wirkt
in der Vorfuehrung am besten.

Demo-Daten werden über `AuslastungDemoRepository::heuteEintragen()` ergänzt
(siehe unten). Echte Besuche werden dabei weder gelöscht noch ersetzt.

## Was fehlt noch

- Die Kurve kennt keine Feiertage und keine Ferien.
- Es gibt keine Obergrenze: theoretisch kann sich ein Studio ueber die
  Kapazitaet hinaus fuellen. Fuer die Anzeige ist das verkraftbar - die
  Skala waechst dann mit, damit nichts abgeschnitten wird.
- Ein Mitglied kann beliebig viele Besuche fuer verschiedene Zeiten
  eintragen. Nur Ueberschneidungen sind gesperrt.

## Demo-Daten für die Entwicklungsdatenbank

`AuslastungDemoRepository` legt 150 gesperrte Demo-Mitglieder mit Adressen
`auslastung-demo-…@example.invalid` an. Sie besitzen keine verwendbaren
Zugangsdaten. Besuche dauern 90 Minuten: 24 ab 8:00, 18 ab 11:00, 120 ab
16:00, 65 ab 19:00 sowie bis zu 45 ab dem aktuellen Zeitpunkt. Überschneidungen
je Demo-Mitglied werden vermieden. So sieht man Teilfüllungen und Balken,
die über den Durchschnitt hinauswachsen.

Aus dem Projektordner ausführen (macOS mit XAMPP; andernfalls `php` verwenden):

```sh
/Applications/XAMPP/xamppfiles/bin/php -r 'define("CONFIG", require "config/config.php"); date_default_timezone_set("Europe/Berlin"); require "src/Database.php"; require "src/Repositories/AuslastungRepository.php"; require "src/Repositories/AuslastungDemoRepository.php"; echo (new Repositories\AuslastungDemoRepository())->heuteEintragen(), " Demo-Besuche eingetragen", PHP_EOL;'
```

Der Aufruf ist nur in der CLI und mit dem Entwicklungsziel aus der
Konfigurationsvorlage erlaubt. Er ersetzt ausschließlich heutige Besuche
der genannten Demo-Konten. Echte Benutzerkonten und ihre Besuche bleiben
erhalten und werden gemeinsam mit den Demo-Besuchen gezählt. Die Daten
sind an den heutigen Tag gebunden; für eine spätere Vorführung erneut ausführen.

Für eine ausdrücklich gewünschte Vorführung in Produktion kann der
Projektinhaber `src/Repositories/auslastung-demo.sql` im SQL Editor des
entsprechenden Supabase-Projekts ausführen. Das Skript verwendet Berliner
Zeit und ersetzt ausschließlich heutige Besuche seiner gesperrten
Demo-Mitglieder. Der CLI-Aufruf oben bleibt auf die Entwicklungsdatenbank
beschränkt. Die neue Diagrammdarstellung muss zusätzlich auf Vercel
veröffentlicht sein; das SQL ergänzt nur die Daten.

## Prüfung

- Diagrammtests ohne zusätzliche Pakete: `node --test tests/*.test.mjs`.
- API: `gesamt = max(basis, gebucht)` in allen 24 Stunden.
- Über ein angemeldetes Testkonto einen Besuch eintragen und entfernen:
  Die grüne Füllung wächst und kehrt anschließend zum vorherigen Wert zurück.
- Browser: Uhrzeiten, Teilfüllung, Überschreitung und Konsole prüfen.
