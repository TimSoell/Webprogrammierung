# assets/css/ — Das Aussehen

Eingebunden wird **nur** `main.css`. Sie enthält keine eigenen Regeln, sondern
lädt alle anderen Dateien in fester Reihenfolge.

```
main.css              Einstieg, nur @import
01-tokens.css         Farben, Schriften, Maße als Variablen
02-base.css           Reset und Grundtypografie
03-layout.css         Seitengerüst, Kopfzeile, Fußzeile
components/           je ein Baustein pro Datei
```

Die Nummerierung ist die Ladereihenfolge. Spätere Dateien dürfen frühere
überschreiben, nicht umgekehrt.

## Neue Komponente anlegen

1. `components/name.css` anlegen, mit Kopfkommentar.
2. **In `main.css` unten eintragen.** Wird das vergessen, wird die Datei nicht
   geladen — ohne jede Fehlermeldung. Das ist der häufigste Stolperstein hier.

## Regeln

- **Keine festen Farbwerte in Komponenten.** Kommt eine Farbe mehr als einmal
  vor, wird sie zur Variablen in `01-tokens.css`.
- **Keine nackten Element-Selektoren in Komponenten.** `h2 { ... }` würde
  jede Seite treffen, weil dieses CSS überall geladen wird. Immer Klassen.
- **Media Queries stehen bei ihrer Komponente**, nicht gesammelt am Dateiende.
  So sieht man alle Zustände eines Bausteins an einer Stelle.
- **Kein `style="..."` im HTML.** Ohne Ausnahme — sonst weiß niemand mehr,
  warum eine Regel nicht greift.
- Dateiname und Hauptklasse heißen gleich: `program-card.css` → `.program`,
  `.program-content`. Dadurch findet man Regeln, ohne zu suchen.

## Breakpoint

Es gibt genau einen: **800 px**. Ein zweiter Breakpoint braucht einen guten
Grund und wird hier vermerkt.
