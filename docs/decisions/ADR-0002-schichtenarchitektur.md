# ADR-0002 — Sechs Schichten mit einem Service als Naht zur Datenbank

**Status:** angenommen
**Datum:** 2026-09-09

## Kontext

Geplant sind Features, die Daten brauchen: Terminanfragen, Kursplanung und
möglicherweise ein kleiner Onlineshop. Zum jetzigen Zeitpunkt steht aber noch
nicht fest, wann eine MySQL-Datenbank angebunden wird und wie ihre Tabellen
aussehen.

Gesucht war ein Aufbau, der beides erlaubt: sofort mit erfundenen Daten
loslegen und später auf eine echte Datenbank umstellen, **ohne die schon
gebauten Seiten erneut anzufassen**.

Zusätzlich soll ein Außenstehender — konkret der bewertende Dozent — schnell
erkennen können, wo welche Art von Code liegt.

## Entscheidung

Das Projekt wird in sechs Schichten aufgeteilt (siehe
[`../ARCHITECTURE.md`](../ARCHITECTURE.md)). Jede Schicht spricht nur mit der
direkt darunterliegenden.

Die entscheidende Schicht ist **Schicht 3, der Service** unter
`assets/js/services/`. Sie ist die einzige Stelle im Frontend, die `fetch()`
aufruft. Nach oben bietet sie einen festen Vertrag an
(`alleLaden()`, `anlegen()`), nach unten kann sie ihre Daten beliebig
beschaffen.

Daraus folgen zwei harte Regeln:

- **Kein `fetch()` außerhalb von `assets/js/services/`.**
- **Kein SQL außerhalb von `src/Repositories/`.**

## Konsequenzen

**Positiv**

- Der Umstieg von erfundenen Daten auf MySQL betrifft genau eine Datei pro
  Feature — den Service. Seiten und Seitenskripte bleiben unverändert.
- Man kann mit der sichtbaren Oberfläche anfangen, bevor die Datenbank steht.
  Das ist bei einem Semesterprojekt mit fester Abgabefrist wertvoll.
- Ein Fehler lässt sich schnell einkreisen: Sieht die Seite falsch aus, ist es
  Schicht 1 oder 2. Kommen falsche Daten an, ist es Schicht 3 bis 6.
- SQL steht an einem einzigen Ort pro Thema. Eine Spaltenumbenennung ist
  dadurch eine überschaubare Änderung.

**Negativ**

- Für ein sehr kleines Feature bedeutet es mehr Dateien, als unbedingt nötig
  wären. Das wird bewusst in Kauf genommen: Der Aufbau soll bei allen
  Features gleich sein, damit man ihn nicht jedes Mal neu erraten muss.
- Die Regeln müssen eingehalten werden, sonst ist der Nutzen weg. Deshalb
  stehen sie in `CLAUDE.md` und in jeder Vorlage-Datei des Beispiel-Features.

## Verworfene Alternativen

**Direkt `fetch()` in den Seitenskripten aufrufen.**
Weniger Dateien, aber die URL und die Fehlerbehandlung wären über das ganze
Projekt verstreut. Beim Umstieg auf echte Daten hätte jede Seite angefasst
werden müssen — genau das sollte vermieden werden.

**PHP rendert alles serverseitig, ohne JavaScript-Schicht.**
Für die reinen Inhaltsseiten wäre das einfacher. Für Warenkorb und
Terminauswahl braucht es aber ohnehin JavaScript, und zwei parallele Wege zu
den Daten wären verwirrender als einer.

**Erst ohne Struktur bauen, später aufräumen.**
Erfahrungsgemäß findet das „später" vor einer Abgabefrist nicht statt.
