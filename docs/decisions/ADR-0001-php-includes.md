# ADR-0001 — PHP-Includes statt vier eigenständiger HTML-Dateien

**Status:** angenommen
**Datum:** 2026-09-09

## Kontext

Der Ausgangsstand bestand aus vier eigenständigen HTML-Dateien
(`index.html`, `strength.html`, `move.html`, `fight.html`). Jede enthielt ihr
komplettes CSS in einem `<style>`-Block, dazu eine eigene Kopie von Kopf- und
Fußzeile.

Daraus ergaben sich zwei konkrete Probleme:

1. **Eine Änderung an fünf Stellen.** Eine neue Farbe oder ein neuer
   Menüpunkt hätte jede Datei einzeln betroffen. Vergisst jemand eine,
   fällt es erst beim Betrachten auf.
2. **Fünf Personen, eine Datei.** Wenn mehrere Teammitglieder gleichzeitig an
   `index.html` arbeiten, kollidieren die Änderungen in Git ständig, weil
   Inhalt, Aussehen und Verhalten in derselben Datei liegen.

Die Aufgabenstellung gibt HTML, CSS und JavaScript vor. Der Dozent empfiehlt
für die Umgebung XAMPP mit Apache, MySQL und PHP.

## Entscheidung

Die Seiten werden `.php`-Dateien. Kopfzeile, Fußzeile und `<head>` liegen als
Bausteine in `partials/` und werden per `include` eingebunden.

PHP wird zunächst **ausschließlich** für diese Bausteine benutzt — nicht für
Logik, nicht für Datenbankzugriffe. Der Anteil beschränkt sich auf
`require`-Aufrufe und Variablen wie `$pageTitle`.

## Konsequenzen

**Positiv**

- Kopf- und Fußzeile existieren genau einmal. Ein neuer Menüpunkt ist eine
  Zeile in `partials/header.php` und erscheint sofort auf allen Seiten.
- Neue Seiten sind rund 20 Zeilen HTML statt 250.
- Die Dateien sind klein und thematisch getrennt, dadurch entstehen deutlich
  weniger Git-Konflikte.
- Der Weg zu Datenbank und Formularverarbeitung ist bereits geebnet, weil
  PHP ohnehin läuft.

**Negativ**

- Die Seiten sind nicht mehr per Doppelklick zu öffnen. Es braucht einen
  laufenden Apache. Da der Dozent XAMPP ausdrücklich empfiehlt, ist das
  eine akzeptierte Voraussetzung und in der `README.md` dokumentiert.
- Wer nur HTML kennt, sieht in den Seiten zunächst ungewohnte
  `<?php require ... ?>`-Zeilen. Deshalb ist der Aufbau jeder Seite im
  Kopfkommentar von `index.php` beschrieben.

## Verworfene Alternativen

**Bei reinem HTML bleiben und Kopf/Fußzeile duplizieren.**
Verworfen, weil bei fünf Personen und wachsender Seitenzahl garantiert
Fassungen auseinanderlaufen. Der Aufwand steigt mit jeder neuen Seite.

**Kopf- und Fußzeile per JavaScript nachladen.**
Verworfen: Die Navigation erscheint dann erst nach dem Laden der Seite und
flackert sichtbar. Außerdem funktioniert die Seite dann ohne JavaScript
überhaupt nicht mehr.

**Ein Build-Werkzeug (z. B. Vite oder ein Static-Site-Generator).**
Verworfen, weil es einen Installationsschritt einführt, der weder in der
Vorlesung behandelt wurde noch für die Bewertung nötig ist. Der Nutzen
gegenüber `include` wäre in diesem Projekt gering.
