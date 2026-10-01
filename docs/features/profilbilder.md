# Feature: Profilbilder

**Status:** fertig
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-09-30

Übernimmt den ersten Stand von Philipp (PR #87, zurückgenommen), mit
eigener Tabelle und Freigabe. Warum so: [ADR-0019](../decisions/ADR-0019-profilbilder-in-der-datenbank.md).

## Was kann man damit

In „Mein Konto" steht neben „Stammdaten" ein runder Kreis mit einem kleinen
grünen Kamera-Symbol. Ein Klick darauf öffnet die Dateiauswahl. Das Foto wird
im Browser quadratisch aus der Mitte zugeschnitten, verkleinert und
gespeichert und erscheint sofort im Kreis.

Gibt es ein Bild, stehen unter den Stammdaten:

- **„Profilbild bei meinen Bewertungen zeigen"** – anfangs an. Dann zeigt
  jede eigene Bewertung auf der Startseite das Bild statt des
  Anführungszeichens.
- **„Bild entfernen"** – nach einer Rückfrage.

## Wer sieht ein Bild

| Wer | Sieht das Bild |
|---|---|
| die Person selbst | immer |
| alle anderen | nur wenn freigegeben **und** die Person mindestens eine Bewertung geschrieben hat |

Die Regel steht an genau einer Stelle: `ProfilbildRepository::bildFinden()`.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `mein-konto.php` (Kreis, Schalter, „Bild entfernen") |
| 2 Seitenskript | `assets/js/pages/mein-konto.page.js` ruft `profilbildEinrichten()` auf |
| 2 Komponente | `assets/js/components/profilbild.js` |
| 2 Hilfsmittel | `quadratVerkleinern()` in `assets/js/lib/bild.js` |
| 3 Service | `assets/js/services/profilbilder.js` |
| 4 Endpunkt | `api/profilbilder.php`; `api/sitzung.php` liefert die Adresse des eigenen Bildes |
| 5 Repository | `src/Repositories/ProfilbildRepository.php`; `BewertungRepository` setzt das Bild bei Bewertungen ein |
| 6 Tabelle | `profilbilder` in `database/schema.sql` |
| CSS | `assets/css/components/konto.css` |

## Datenform

`api/sitzung.php` (GET) liefert zusätzlich:

```json
{ "profilbild": "api/profilbilder.php?mitglied=7&v=1790785399", "profilbildOeffentlich": true }
```

`profilbild` ist `null`, wenn es kein Bild gibt. Die Adresse ist relativ zur
`BASE_URL` und steht direkt in `<img src>`. `v` ist der Zeitpunkt der letzten
Änderung – ein neues Bild hat damit eine neue Adresse.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/profilbilder.php?mitglied=7&v=…` | das Bild selbst | `image/jpeg`, sonst 404 |
| PUT | `api/profilbilder.php` | `{ "bild": "<base64-JPEG>" }` speichern | `{ "profilbild": "…", "oeffentlich": true }` |
| PUT | `api/profilbilder.php` | `{ "oeffentlich": "1" }` oder `"0"` | `{ "oeffentlich": true }`, 404 ohne Bild |
| DELETE | `api/profilbilder.php` | eigenes Bild entfernen | `{ "profilbild": null }` |

PUT und DELETE nur angemeldet (sonst 401) und nur fürs eigene Bild. PUT mit
Bild antwortet mit 400, wenn es kein JPEG ist, größer als 300 KB oder größer
als 1024 × 1024 px.

## Woher kommen die Daten aktuell

**PostgreSQL**, Tabelle `profilbilder`. Seit 2026-09-30 in
`schwitzkasten-dev` angelegt.

## Was fehlt noch

- **Vor dem Merge:** Tabelle auch in der Produktionsdatenbank anlegen – den
  Abschnitt „FEATURE PROFILBILDER" aus `schema.sql` und die Zeile
  `ALTER TABLE profilbilder ENABLE ROW LEVEL SECURITY;`.
- Hochgeladene Bilder werden nicht vom Team geprüft, bevor sie bei
  Bewertungen erscheinen.
