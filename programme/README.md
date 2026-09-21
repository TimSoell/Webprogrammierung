# programme/ — Detailseiten der Trainingsprogramme

Je eine Seite pro Programm: `strength.php`, `move.php`, `fight.php`.

Alle drei nutzen dieselben CSS-Klassen aus
[`../assets/css/components/program-page.css`](../assets/css/components/program-page.css)
und unterscheiden sich im Einleitungstext und im Hintergrundbild.

**Der obere Teil steht fest im PHP, der untere kommt aus der Datenbank.**
Einleitungstext und Hero-Bild stehen in der Datei, die aufklappbaren Merkmale
und die Coaches lädt das Seitenskript über `api/programme.php` nach. Details:
[`../docs/features/programm-details.md`](../docs/features/programm-details.md)

## Neues Programm anlegen

1. Eine der drei Dateien kopieren und umbenennen.
2. Im Kopf `$pageTitle`, `$pageDescription` und `$pageScript` anpassen.
3. Die Modifier-Klasse am Hero ändern: `program-hero--strength` →
   `program-hero--<name>`.
4. In `program-page.css` eine passende Regel mit `--program-hero-image`
   ergänzen.
5. Auf der Startseite in `index.php` eine Kachel im Block `.programs` ergänzen.
6. Ein Seitenskript in `assets/js/pages/<name>.page.js` anlegen — eine Zeile,
   siehe `strength.page.js`.
7. In `database/seed.sql` das Programm, seine Merkmale und seine Coaches
   ergänzen und die Datei neu einspielen. **Ohne diesen Schritt bleibt der
   untere Teil der Seite leer.**

## Warum drei Dateien und keine Vorlage mit Parameter

Die Seiten unterscheiden sich im Einleitungstext und im Hintergrundbild.
Getrennte Dateien lassen sich im Team gleichzeitig bearbeiten, ohne Konflikte
in Git.

Seit die Merkmale aus der Datenbank kommen, wäre eine Seite mit Parameter
(`programm.php?slug=strength`) technisch möglich. Dagegen sprechen die
bestehenden Links auf `programme/move.php` und weiterhin die gleichzeitige
Bearbeitung im Team — abgewogen in
[`ADR-0007`](../docs/decisions/ADR-0007-programm-details-aus-der-datenbank.md).
