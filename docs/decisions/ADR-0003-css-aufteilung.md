# ADR-0003 — CSS in Tokens, Basis, Layout und Komponenten

**Status:** angenommen
**Datum:** 2026-09-09

## Kontext

Das gesamte CSS lag in `<style>`-Blöcken in den vier HTML-Dateien, jeweils
vollständig dupliziert. Eine Änderung an der Markenfarbe hätte vier Dateien
betroffen.

Gleichzeitig sollen fünf Personen parallel an verschiedenen Bereichen der
Seite arbeiten können, ohne sich ständig in denselben Zeilen zu begegnen.

## Entscheidung

Das CSS wird in Dateien aufgeteilt und über `@import` in einer einzigen
`main.css` zusammengeführt, die als einzige eingebunden wird:

```
01-tokens.css      Farben, Schriften, Maße als CSS-Variablen
02-base.css        Reset und Grundtypografie
03-layout.css      Seitengerüst, Kopfzeile, Fußzeile
components/*.css   je ein Baustein pro Datei
```

Die Nummerierung gibt die Ladereihenfolge vor: spätere Dateien dürfen frühere
überschreiben, nicht umgekehrt.

Alle Design-Werte, die mehr als einmal vorkommen, werden zu Variablen in
`01-tokens.css`. Feste Farbwerte in Komponenten sind nicht erlaubt.

Zusätzlich gilt: **keine nackten Element-Selektoren in Komponenten.** Da das
CSS jetzt auf jeder Seite geladen wird, würde eine Regel wie
`h2 { font-size: 5rem }` alle Seiten treffen. Deshalb bekamen die
Programmseiten eigene Klassen (`.program-heading` statt `h2`).

## Konsequenzen

**Positiv**

- Die Markenfarbe steht an einer Stelle. `--lime` ändern reicht.
- Zwei Personen an zwei Komponenten fassen zwei verschiedene Dateien an.
- Man findet Regeln, indem man den Dateinamen zur Klasse errät — das
  funktioniert, weil beide gleich heißen.
- Media Queries stehen bei der jeweiligen Komponente und nicht in einem
  Sammelblock am Ende. Dadurch sieht man alle Zustände eines Bausteins
  auf einmal.

**Negativ**

- `@import` lädt die Dateien nacheinander statt gleichzeitig. Auf `localhost`
  ist das nicht messbar. Für einen echten Produktivbetrieb würde man die
  Dateien zusammenfassen — das ist hier bewusst nicht nötig.
- Eine neue Komponente muss in `main.css` eingetragen werden. Wird das
  vergessen, fehlt sie ohne Fehlermeldung. Der Hinweis steht deshalb im
  Kopfkommentar von `main.css` und in `CLAUDE.md`.

## Verworfene Alternativen

**Alle Komponenten einzeln per `<link>` einbinden.**
Lädt zwar parallel, aber `head.php` hätte dann über zehn `<link>`-Zeilen, und
jede neue Komponente hätte eine weitere gebraucht. Eine Einstiegsdatei ist
übersichtlicher.

**Ein CSS-Framework wie Bootstrap oder Tailwind.**
Verworfen, weil das vorhandene Design bereits steht und ein Framework es
überschreiben statt unterstützen würde. Außerdem wurde in der Vorlesung
handgeschriebenes CSS behandelt.

**CSS Cascade Layers (`@layer`).**
Technisch die sauberste Lösung für Reihenfolgekonflikte, aber ein zusätzliches
Konzept, das im Projekt aktuell kein vorhandenes Problem löst. Die
Nummerierung der Dateien reicht. Kann später ergänzt werden.
