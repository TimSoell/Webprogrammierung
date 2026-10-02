# assets/img/bewertungen/ — Bilder zu den Beispielbewertungen

Die Bilder erscheinen an zwei Stellen: **groß als Kachel** im Abschnitt
„Was andere sagen" auf der Startseite und **rund und klein** neben der Karte
im Fenster „Alle Bewertungen". Die Bilder sind KI-generiert und kommen aus
dem Team.

**Stand: Die Bilder fehlen noch.** Deshalb steht bei den sechs Beispielen in
der Datenbank kein Bild (`bild` ist `NULL`), und sie erscheinen als
Zitat-Kachel — ein großes Anführungszeichen statt des Fotos. So sucht die
Seite keine Dateien, die es nicht gibt.

## Welche Dateien gebraucht werden

| Name | Datei |
|---|---|
| Lea M. | `lea-m.jpg` |
| Can Y. | `can-y.jpg` |
| Johanna K. | `johanna-k.jpg` |
| Marco S. | `marco-s.jpg` |
| Aylin T. | `aylin-t.jpg` |
| Ben W. | `ben-w.jpg` |

## So kommen die Bilder auf die Seite

1. Die Dateien mit genau diesen Namen in diesen Ordner legen.
2. In `database/seed.sql` im Abschnitt „FEATURE BEWERTUNGEN" bei jeder
   Bewertung das `NULL` vor dem Datum durch den Pfad ersetzen, z. B.
   `'assets/img/bewertungen/lea-m.jpg'`.
3. Dieselbe Änderung in beide Datenbanken bringen — erst
   `schwitzkasten-dev`, dann Produktion (SQL Editor im Supabase-Dashboard):

   ```sql
   UPDATE bewertungen
      SET bild = 'assets/img/bewertungen/' || replace(lower(replace(name, '.', '')), ' ', '-') || '.jpg'
    WHERE mitglied_id IS NULL;
   ```

   Aus „Lea M." wird so `assets/img/bewertungen/lea-m.jpg`. Nur die
   Beispiele (ohne Konto) werden angefasst.

Fehlt danach doch eine Datei, wird die Kachel von selbst wieder zur
Zitat-Kachel.

Bewertungen echter Konten bekommen **kein Bild aus diesem Ordner**, sondern
das Profilbild, das die Person in „Mein Konto" hochlädt und freigibt (siehe
`docs/features/profilbilder.md`). Ohne Profilbild erscheinen sie als
Zitat-Kachel.

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
