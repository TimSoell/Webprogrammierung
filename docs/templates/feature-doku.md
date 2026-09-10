# Feature: NAME

**Status:** in Arbeit | fertig
**Verantwortlich:** Vorname
**Zuletzt geprüft:** JJJJ-MM-TT

## Was kann man damit

Zwei bis vier Sätze in normaler Sprache. Was sieht eine Besucherin der Seite,
und was kann sie tun? Keine Fachbegriffe.

## Beteiligte Dateien

Pro Schicht eine Zeile. Nicht vorhandene Schichten weglassen.

| Schicht | Datei |
|---|---|
| 1 Seite | `kurse.php` |
| 2 Seitenskript | `assets/js/pages/kurse.page.js` |
| 3 Service | `assets/js/services/kurse.js` |
| 4 Endpunkt | `api/kurse.php` |
| 5 Repository | `src/Repositories/KursRepository.php` |
| 6 Tabelle | `kurse` in `database/schema.sql` |
| CSS | `assets/css/components/kursliste.css` |

## Datenform

Wie sieht ein einzelner Datensatz aus, den der Service zurückgibt?

```json
{ "id": 1, "name": "Kraftzirkel", "tag": "Montag", "uhrzeit": "18:00" }
```

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/kurse.php` | alle Kurse | Array von Kursen |
| POST | `api/kurse.php` | Kurs anlegen | `{ "id": 7 }` |

## Woher kommen die Daten aktuell

Erfundene Daten im Service **oder** MySQL. Klar hinschreiben — das ist die
Frage, die beim Weiterarbeiten am häufigsten gestellt wird.

## Was fehlt noch

- Offene Punkte als Liste. Auch „nichts" ist eine gültige Antwort.
