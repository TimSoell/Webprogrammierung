<!--
@file        docs/decisions/ADR-0022-auslastung-als-fuellung.md
@layer       Dokumentation
@description Ersetzt die additive Auslastung aus ADR-0013 durch eine Füllung.
-->
# ADR-0022 — Besuche füllen die typische Auslastung

**Status:** angenommen
**Datum:** 2026-10-16

## Kontext

ADR-0013 addiert angekündigte Besuche zum Durchschnitt. Dadurch erscheinen
Besuche als zusätzlicher Aufsatz, obwohl sie die typische Belegung ausfüllen
sollen. Die gewünschte Darstellung ersetzt diese Addition.

## Entscheidung

Die Balkenhöhe und die aktuelle Auslastung verwenden das Maximum aus
Durchschnitt und erfassten Besuchen. Der Durchschnitt bleibt grau im
Hintergrund; angekündigte Besuche und Check-ins füllen von derselben
Grundlinie aus grün. Übersteigen Besuche den Durchschnitt, wächst Grün darüber
hinaus. Stunden erscheinen als Uhrzeiten, etwa `9:00`.

Die Besuchstabelle, beide Besuchsarten, die Dauer von 90 Minuten und die
stündliche Überlappungszählung aus ADR-0013 bleiben bestehen. Die aktuelle
Zahl zählt Besuche zum aktuellen Moment. Demo-Daten stehen in derselben
Besuchstabelle und werden nur für die Entwicklungsdatenbank ergänzt.

## Konsequenzen

**Positiv**

- Besuche werden nicht zusätzlich zum Durchschnitt gezählt.
- Jeder Check-in verändert die grüne Füllung über denselben Datenweg.
- Diagramm und API verwenden dieselbe Regel für den Gesamtwert.

**Negativ**

- Unterhalb des Durchschnitts ändert ein Check-in die grüne Füllung, aber
  nicht die aktuelle Gesamtzahl.
- Stundenwerte können höher sein als die minutengenaue aktuelle Belegung,
  weil sie alle Besuche berücksichtigen, die eine Stunde berühren.

## Verworfene Alternativen

**Weiterhin addieren und nur Grün verschieben.** Die Balkenhöhe und die
aktuelle Zahl würden der gewünschten Füllung widersprechen.

**Nur erfasste Besuche darstellen.** Der gewünschte graue Vergleich mit der
typischen Belegung würde entfallen.
