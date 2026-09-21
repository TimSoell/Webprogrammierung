# Feature: Programm-Details

**Status:** fertig
**Verantwortlich:** Jonny
**Zuletzt geprüft:** 2026-09-21

## Was kann man damit

Auf den drei Programmseiten (Strength, Move, Fight) stehen unter dem
Einleitungstext drei Blöcke: **Fokus**, **Level** und **Format**. Ein Klick
klappt einen Block auf und zeigt einen ausführlichen Text. Es ist immer nur
einer offen.

Bei **Level** und **Format** stehen darin mehrere Varianten zur Auswahl.
Wer eine anklickt, sieht deren Beschreibung; die gewählte Variante steht
danach auch in der zugeklappten Kopfzeile. Die Auswahl bleibt beim nächsten
Besuch erhalten.

Rechts, unter dem gelben Kasten mit dem Probetraining, stehen die **Coaches**
des Programms: Gesicht, Name und darunter kursiv, welche Art Training die
Person macht.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `programme/strength.php`, `programme/move.php`, `programme/fight.php` |
| 2 Seitenskript | `assets/js/pages/strength.page.js`, `move.page.js`, `fight.page.js` |
| 2 Komponente | `assets/js/components/programm-details.js` |
| 3 Service | `assets/js/services/programme.js` |
| 4 Endpunkt | `api/programme.php` |
| 5 Repository | `src/Repositories/ProgrammRepository.php` |
| 6 Tabelle | `programme`, `merkmale`, `coaches` in `database/schema.sql` |
| 6 Inhalte | `database/seed.sql` |
| CSS | `assets/css/components/merkmale.css`, `coach-karte.css` |
| Bilder | `assets/img/coaches/` |

Die drei Seitenskripte enthalten je eine Zeile und reichen nur das Kürzel an
die Komponente durch. Das Verhalten steht einmal in
`components/programm-details.js` — dieselbe Aufteilung wie bei
`auth-formular.js`, das von zwei Seiten benutzt wird.

## Datenform

Was `programmDetailsLaden('move')` zurückgibt:

```json
{
  "name": "Move",
  "merkmale": {
    "fokus":  [{ "titel": "Mobilität, Balance und Ausdauer", "beschreibung": "…" }],
    "level":  [{ "titel": "Sanfter Einstieg", "beschreibung": "…" }, { "…": "…" }],
    "format": [{ "titel": "Small Group", "beschreibung": "…" }, { "…": "…" }]
  },
  "coaches": [
    {
      "name": "Sofia Lindqvist",
      "schwerpunkt": "Mobilität für Schulter und Hüfte, Atemarbeit",
      "bild": "assets/img/coaches/sofia-lindqvist.jpg",
      "bildAlt": "Porträtfoto von Coach Sofia Lindqvist"
    }
  ]
}
```

Die drei Schlüssel unter `merkmale` sind **immer** vorhanden, notfalls als
leeres Array. Die Seite muss also nicht prüfen, ob es sie gibt.

Ob ein Block eine Auswahl bekommt, entscheidet allein die **Anzahl** der
Einträge: einer bedeutet nur Text (so ist Fokus gebaut), mehrere bedeuten
Schaltflächen. Es gibt keinen zusätzlichen Schalter in der Datenbank.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/programme.php?slug=move` | Merkmale und Coaches eines Programms | siehe oben · 400 ohne `slug` · 404 unbekannt |

Nur Lesen. Inhalte pflegt das Team über `seed.sql` oder phpMyAdmin, deshalb
gibt es kein POST und keine CSRF-Prüfung über `Api::eingabe()`.

## Woher kommen die Daten aktuell

**MySQL.** Vor dem ersten Aufruf beides einspielen:

```bash
mysql -u root < database/schema.sql
mysql -u root < database/seed.sql
```

`seed.sql` darf beliebig oft laufen — es löscht die Programmdaten vorher und
legt sie neu an. Konten und Mitglieder bleiben unangetastet.

**Ohne MySQL zeigen die Programmseiten unter dem Einleitungstext eine
Fehlermeldung statt der Blöcke.** Vor diesem Feature liefen sie ohne
Datenbank — siehe [ADR-0006](../decisions/ADR-0006-programm-details-aus-der-datenbank.md).

## Wo die Auswahl gespeichert wird

**Für nicht angemeldete Besucher** im `localStorage` des Browsers, unter dem
Schlüssel

```
schwitzkasten:programm:<slug>:<art>
```

also z. B. `schwitzkasten:programm:move:level`. Gespeichert wird der Titel der
Option, nicht ihre Nummer — so bleibt die Auswahl auch dann richtig, wenn in
`seed.sql` die Reihenfolge geändert wird. Gibt es den gemerkten Titel nicht
mehr, fällt die Anzeige auf den ersten Eintrag zurück.

Alle Zugriffe stehen in `try/catch`: Im privaten Fenster und bei blockierten
Website-Daten wirft `localStorage` eine Ausnahme, statt `null` zu liefern.
Der Code dafür steht in `assets/js/components/auswahl-speicher.js`, nicht in
`programm-details.js` — er wird auch beim Anmelden gebraucht.

**Für angemeldete Mitglieder** zusätzlich am Konto, aber erst auf Knopfdruck.
Das ist ein eigenes Feature: [Gemerkte Auswahl](gemerkte-auswahl.md). Beim
Öffnen der Seite gewinnt das Konto über den Browser.

## Gut zu wissen

- **Fehlt ein Coach-Foto, zeigt die Karte die Initialen** statt eines kaputten
  Bildsymbols. Welche Dateien erwartet werden, steht in
  `assets/img/coaches/README.md`.
- **Kein `innerHTML` mit Daten aus der Datenbank.** Die Komponente baut alles
  über `createElement` und `textContent` auf. Das ist im JavaScript das
  Gegenstück zu `e()` im PHP.
- **Die Blöcke sind `<div>`, nicht `<section>`.** `03-layout.css` gibt jedem
  `<section>` 118 px Innenabstand; als `<section>` gebaut wären die Blöcke
  dreimal so hoch. Die Bedeutung tragen die Überschrift und `role="region"`.
- **Dieselbe Regel setzt `.program-layout > section { padding: 0 }` zurück.**
  Die linke Spalte ist ein `<section>`, die rechte ein `<aside>` — ohne das
  Zurücksetzen begänne der Text links 118 px tiefer als der gelbe Kasten.
  Wer an `03-layout.css` etwas ändert, sollte beide Stellen kennen.
- **Der gelbe Kasten ist nicht mehr so hoch wie die ganze Spalte.** Er liegt
  jetzt zusammen mit den Coaches in `.program-aside`, das seine Kinder oben
  zusammenhält.

## Was fehlt noch

- Die Fotos in `assets/img/coaches/`. Bis dahin zeigen die Karten Initialen.
- Ein Pflegebereich im Browser. Aktuell geht Ändern nur über `seed.sql` oder
  phpMyAdmin — für die Studienarbeit bewusst nicht gebaut.
- Der Einleitungstext und das Hero-Bild stehen weiterhin fest im PHP der
  jeweiligen Seite. Nur der untere Teil kommt aus der Datenbank.
