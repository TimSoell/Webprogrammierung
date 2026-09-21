# Feature: Auslastungsdiagramm

**Status:** in Arbeit
**Verantwortlich:** offen
**Zuletzt geprueft:** 2026-09-21

## Was kann man damit

Auf der Startseite zeigt ein Balkendiagramm, wie voll das Studio im
Tagesverlauf ist. Ueber dem Diagramm steht die aktuelle Zahl mit einer Ampel
("entspannt", "gut besucht", "voll").

Angemeldete Mitglieder koennen unter **Mein Konto** sagen, wann sie kommen -
entweder sofort ("Jetzt einchecken") oder fuer eine Uhrzeit spaeter. Jeder
so eingetragene Besuch erhoeht die Auslastung unmittelbar. Das ist der Kern
der Vorfuehrung: man traegt sich ein, wechselt zur Startseite und sieht den
Balken wachsen.

## Woher kommen die Daten

Die angezeigte Zahl ist immer eine **Summe aus zwei Quellen**:

| Anteil | Quelle | Aendert sich |
|---|---|---|
| Basiswert | Tabelle `auslastung_basis` | nie im Betrieb |
| Aufschlag | Tabelle `besuche` | bei jedem Check-in |

Der Basiswert ist **erfunden**. Ein echtes Studio wuerde ihn aus
Drehkreuzdaten gewinnen; hier steht er in einer Tabelle, damit die Vorhersage
aus der Datenbank kommt und nicht aus dem Quelltext. Wer die Kurve aendern
will, aendert `database/seed.sql` - kein PHP.

Im Diagramm sind die beiden Anteile getrennt zu sehen: der Basiswert gedeckt,
der Aufschlag in der Markenfarbe obendrauf.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `index.php` (Abschnitt `.auslastung`), `mein-konto.php` (Karte "Mein Besuch") |
| 2 Seitenskript | `assets/js/pages/index.page.js`, `assets/js/pages/mein-konto.page.js` |
| 2 Komponente | `assets/js/components/auslastung-diagramm.js` |
| 3 Service | `assets/js/services/auslastung.js` |
| 4 Endpunkt | `api/auslastung.php` |
| 5 Repository | `src/Repositories/AuslastungRepository.php` |
| 6 Datenbank | `database/schema.sql`, `database/seed.sql` |
| CSS | `assets/css/components/auslastung.css` |

## Datenform

Antwort von `GET api/auslastung.php`:

```json
{
  "kapazitaet": 170,
  "stand": "2026-09-21T15:39:42+02:00",
  "jetzt": { "personen": 42, "prozent": 25, "stufe": "entspannt", "stunde": 15 },
  "verlauf": [
    { "stunde": 0, "basis": 3, "gebucht": 0, "gesamt": 3 },
    { "stunde": 15, "basis": 37, "gebucht": 5, "gesamt": 42 }
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
4. Zurueck zur Startseite: die Zahl ist um eins hoeher, der lime Aufsatz auf
   der aktuellen Stunde waechst

Die Startseite laedt alle 20 Sekunden von selbst nach. Wer beide Seiten in
zwei Tabs offen hat, sieht die Aenderung also auch ohne Neuladen - das wirkt
in der Vorfuehrung am besten.

Fuer eine Vorfuehrung mit mehr Bewegung lassen sich Besuche direkt eintragen:

```sql
INSERT INTO besuche (mitglied_id, beginn, ende, art)
SELECT 1, NOW(), NOW() + INTERVAL 90 MINUTE, 'jetzt'
  FROM auslastung_basis LIMIT 20;
```

Und wieder weg mit `DELETE FROM besuche;`.

## Was fehlt noch

- Die Kurve kennt keine Feiertage und keine Ferien.
- Es gibt keine Obergrenze: theoretisch kann sich ein Studio ueber die
  Kapazitaet hinaus fuellen. Fuer die Anzeige ist das verkraftbar - die
  Skala waechst dann mit, damit nichts abgeschnitten wird.
- Ein Mitglied kann beliebig viele Besuche fuer verschiedene Zeiten
  eintragen. Nur Ueberschneidungen sind gesperrt.
