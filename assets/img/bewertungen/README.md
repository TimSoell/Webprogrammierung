# assets/img/bewertungen/ — Bilder zu den Beispielbewertungen

Die Bilder erscheinen an zwei Stellen: **groß als Kachel** im Abschnitt
„Was andere sagen" auf der Startseite und **rund und klein** neben der Karte
im Fenster „Alle Bewertungen". Die Bilder sind KI-generiert und kommen aus
dem Team.

**Solange eine Datei fehlt, zeigt die Seite die Initialen des Namens** statt
eines kaputten Bildsymbols. Die Seite funktioniert also auch ohne Bilder.

## Welche Dateien erwartet werden

Die Dateinamen stehen in der Spalte `bewertungen.bild` (siehe
`database/seed.sql`). Aktuell sind das:

| Name | Datei |
|---|---|
| Lea M. | `lea-m.jpg` |
| Can Y. | `can-y.jpg` |
| Johanna K. | `johanna-k.jpg` |
| Marco S. | `marco-s.jpg` |
| Aylin T. | `aylin-t.jpg` |
| Ben W. | `ben-w.jpg` |

Auf der Startseite stehen davon die **drei neuesten** — Lea M., Can Y. und
Johanna K.

Anderer Name oder Dateiname? Dann in `seed.sql` ändern und neu einspielen —
nicht im PHP oder JavaScript suchen, dort steht kein Dateiname.

Bewertungen echter Konten haben kein Bild, dort stehen immer die Initialen.

## Anforderungen an die Bilder

- **Hochformat 4:5**, z. B. 800 × 1000 px. Die Kachel ist rund 400 px breit
  und 480 px hoch.
- **Gesicht in der oberen Hälfte.** Unten liegt auf der Kachel der Text, und
  im Fenster schneidet der Kreis das Bild von oben zu.
- Unter 200 KB pro Datei.
- `.jpg` — siehe `assets/img/README.md`.
- **Keine echten Personen nachbilden**, auch nicht aus dem Team: Die Seite
  ist öffentlich.

## Der alt-Text

Bleibt leer (`alt=""`). Der Name steht direkt neben dem Bild. Ein
beschreibender alt-Text würde von Screenreadern zusätzlich vorgelesen und
sagt nichts Neues.
