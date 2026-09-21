# Feature: Nachweise für ermäßigte Preise

**Status:** in Arbeit
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-09-21

## Was kann man damit

Unter „Mein Konto" gibt es den Bereich **Nachweise**. Wer den ermäßigten
Preis für Schülerinnen, Schüler und Studierende oder den Seniorenpreis
nutzen will, fotografiert dort seinen Ausweis und lädt ihn hoch. Das Bild
wird ausgelesen, das Ergebnis gespeichert und **das Bild sofort verworfen**.

Danach steht die passende Preisgruppe auf der Tarifseite zur Verfügung.
Ohne Nachweis ist sie mit einem Schloss markiert: Man sieht, was der Preis
wäre, kann ihn aber nicht buchen.

Damit lässt sich die Mitgliedschaft vollständig online abschließen — bei
anderen Studios muss man mit dem Ausweis an den Tresen. Das ist der Punkt
des Features.

## Die Regeln

| | |
|---|---|
| Schüler- und Studierendenausweis | gilt bis zum aufgedruckten Ablaufdatum |
| Senior | ab 65 Jahren, gilt unbefristet |
| Standardpreis | braucht nie einen Nachweis |

**Läuft ein Nachweis ab, wird der Vertrag zum nächsten Monatsersten auf den
Standardpreis gestellt** — nach derselben Regel wie jeder Tarifwechsel
([ADR-0007](../decisions/ADR-0007-tarifwechsel-zum-monatsersten.md)). Den
laufenden Monat behält man zum ermäßigten Preis.

Ein Seniorennachweis läuft nie ab: Wer einmal 65 ist, bleibt es.

## Was gespeichert wird — und was nicht

Das ist der wichtigste Abschnitt dieser Datei.

**Gespeichert wird:** Art des Nachweises, Gültigkeitsdatum, ob per KI oder im
Demo-Modus geprüft, welches Modell geprüft hat, und ein Satz, was auf dem
Dokument zu sehen war.

**Nicht gespeichert wird:** das Bild, ein Pfad zu einem Bild, der Name,
die Ausweisnummer, das Geburtsdatum. Es gibt keinen Upload-Ordner. Das Bild
existiert nur für die Dauer einer Anfrage im Arbeitsspeicher.

Für das Alter heißt das: Das Geburtsdatum wird einmal gelesen, gegen 65
geprüft und verworfen. In der Tabelle steht danach nur noch
`art = 'senior'` mit `gueltig_bis = NULL`.

**Ausweisdaten verlassen für die Prüfung den Server.** Zum Testen und
Vorführen werden ausschließlich erfundene Ausweise benutzt, keine echten.

## Zwei Betriebsarten

| | Wenn `ki.api_key` in `config/config.php` gesetzt ist | Wenn nicht |
|---|---|---|
| Formular | Feld für ein Foto | Feld für ein Datum |
| Prüfung | Modell liest das Datum vom Bild | Datum wird eingetippt |
| `quelle` in der Tabelle | `'ki'` | `'demo'` |

Geprüft wird über die **Gemini-API von Google** auf der kostenlosen Stufe,
Modell einstellbar über `ki.modell` in `config/config.php`
([ADR-0009](../decisions/ADR-0009-gemini-statt-claude.md)).

> **Nur erfundene Ausweise hochladen.** Auf der kostenlosen Stufe darf Google
> die Eingaben zur Produktverbesserung verwenden, und menschliche Prüfer
> dürfen sie lesen. Für Testdokumente ist das unproblematisch, für echte
> Ausweise nicht — auch nicht für die eigenen.

Die kostenlose Stufe hat außerdem Kontingente pro Minute und pro Tag. Sind
sie erschöpft, antwortet die API mit 429 und das Hochladen scheitert mit
„Die Prüfung war gerade nicht erreichbar". Bei einer Vorführung mit mehreren
Uploads hintereinander ist das ein realistischer Fall.

Der Demo-Modus ist kein Notbehelf: Er hält das Projekt für alle im Team
lauffähig, die keinen Schlüssel haben, und für die Vorführung ohne Internet.
Dass er unsicher ist — jede Person kann sich ihr Datum aussuchen — ist der
Grund, warum er **nur** ohne hinterlegten Schlüssel greift und warum
`quelle` das in der Datenbank festhält.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `mein-konto.php`, `mitgliedschaft.php` |
| 2 Seitenskript | `assets/js/pages/mein-konto.page.js`, `assets/js/pages/mitgliedschaft.page.js` |
| Hilfsmittel | `assets/js/lib/bild.js` (verkleinert das Foto vor dem Upload) |
| 3 Service | `assets/js/services/nachweise.js` |
| 4 Endpunkt | `api/nachweise.php` |
| Infrastruktur | `src/Ausweispruefung.php` (einzige Stelle mit einem KI-Aufruf) |
| 5 Repository | `src/Repositories/NachweisRepository.php` |
| 6 Tabelle | `nachweise` in `database/schema.sql` |
| CSS | `assets/css/components/auth.css`, `assets/css/components/tarifkarte.css` |

## Datenform

```json
{
  "nachweise": [
    { "art": "student", "gueltigBis": "2027-03-31", "quelle": "ki",
      "hinweis": "Studierendenausweis der DHBW, gültig bis 31.03.2027.",
      "geprueftAm": "2026-09-21 11:40:24" },
    { "art": "senior", "gueltigBis": null, "quelle": "ki",
      "hinweis": "Personalausweis, Geburtsdatum 02.04.1956.",
      "geprueftAm": "2026-09-21 11:40:54" }
  ],
  "kiVerfuegbar": true
}
```

`gueltigBis: null` heißt unbefristet und kommt nur bei `art: "senior"` vor.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/nachweise.php` | eigene Nachweise, auch abgelaufene | Nachweisstand |
| POST | `api/nachweise.php` | Bild prüfen lassen bzw. Datum eintragen | 201, Nachweisstand |

Fehlerfälle des POST, alle mit deutscher Meldung für das Formular:

| Code | Wann |
|---|---|
| 400 | kein Bild, falsches Format, größer als 4 MB |
| 422 | Dokument passt nicht zur gewählten Art · kein Datum lesbar · Ausweis abgelaufen · noch keine 65 |
| 502 | Prüfdienst nicht erreichbar |

Der Upload geht **als base64 im JSON**, nicht als `multipart/form-data` —
sonst müsste der CSRF-Schutz aus `src/Api.php` für diesen Endpunkt
ausgehebelt werden.

## Wo die Sperre wirklich sitzt

In `api/mitgliedschaften.php`. Wer eine ermäßigte Preisgruppe ohne gültigen
Nachweis buchen will, bekommt **403**. Die Tarifseite sperrt die Auswahl
zusätzlich und erklärt, was fehlt — das ist Bequemlichkeit, keine
Sicherheit. Alles, was aus dem Browser kommt, ist manipulierbar.

## Was fehlt noch

- **Fälschungen werden nicht erkannt.** Das Modell liest ab, was dasteht.
  Ein sauber gebautes Falsifikat kommt durch.
- **Niemand kann eine Prüfung überstimmen.** Liest das Modell ein Datum
  falsch, hilft nur ein neuer Upload oder ein Eingriff in der Datenbank.
  Ein Admin-Bereich wäre ein eigenes Feature.
- **Nachweise lassen sich nicht löschen.** Wer einen hochgeladen hat, wird
  ihn über die Oberfläche nicht wieder los.
- **Die Herabstufung läuft bei jedem Aufruf mit**, statt einmal nachts.
  Ohne Cronjob ist das der einzige Weg — siehe
  [ADR-0008](../decisions/ADR-0008-ausweispruefung-mit-ki.md), Konsequenzen.
- Es gibt keine Erinnerung, bevor ein Nachweis ausläuft. Man sieht das
  Datum im Konto, aber es schreibt niemand eine E-Mail.
