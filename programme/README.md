# programme/ — Detailseiten der Trainingsprogramme

Je eine Seite pro Programm: `strength.php`, `move.php`, `fight.php`.

Alle drei nutzen dieselben CSS-Klassen aus
[`../assets/css/components/program-page.css`](../assets/css/components/program-page.css)
und unterscheiden sich nur im Text und im Hintergrundbild.

## Neues Programm anlegen

1. Eine der drei Dateien kopieren und umbenennen.
2. Im Kopf `$pageTitle` und `$pageDescription` anpassen.
3. Die Modifier-Klasse am Hero ändern: `program-hero--strength` →
   `program-hero--<name>`.
4. In `program-page.css` eine passende Regel mit `--program-hero-image`
   ergänzen.
5. Auf der Startseite in `index.php` eine Kachel im Block `.programs` ergänzen.

## Warum drei Dateien und keine Vorlage mit Parameter

Die Seiten unterscheiden sich derzeit nur im Text. Getrennte Dateien lassen
sich im Team gleichzeitig bearbeiten, ohne Konflikte in Git.

Sobald Programme aus der Datenbank kommen, wird daraus sinnvollerweise eine
Seite mit Parameter (`programm.php?name=strength`). Bis dahin wäre das
Mehraufwand ohne Nutzen.
