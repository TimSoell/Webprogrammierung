# ADR-0006 — Programm-Details kommen aus der Datenbank

**Status:** angenommen
**Datum:** 2026-09-21

## Kontext

Die drei Programmseiten (`strength.php`, `move.php`, `fight.php`) zeigten unter
dem Einleitungstext drei feste Textblöcke: Fokus, Level und Format. Jeder
bestand aus einer Überschrift und einer Zeile Text, fest im PHP.

Daraus sollte mehr werden: Die Blöcke klappen auf und zeigen einen längeren
Text. Bei Level und Format gibt es mehrere Varianten, zwischen denen Besucher
wählen. Dazu kommen pro Programm mehrere Coaches mit Foto, Name und
Schwerpunkt.

Damit wächst der Inhalt von drei Zeilen auf rund zwanzig Einträge pro Seite —
sechzig insgesamt. Gleichzeitig braucht die Studienarbeit Features, die über
alle sechs Schichten gehen; bisher tat das nur der Mitglieder-Login.

[`ADR-0002`](ADR-0002-schichtenarchitektur.md) legt die Schichtenfolge fest,
`strength.php` kündigte diesen Schritt im Kopfkommentar bereits an.

## Entscheidung

Merkmale und Coaches der Programmseiten liegen in der Datenbank, in den drei
Tabellen `programme`, `merkmale` und `coaches`. Die Seiten laden sie über
`api/programme.php` nach.

Daraus folgen diese Regeln:

- **Die drei Seiten bleiben getrennte Dateien.** Sie werden nicht zu einer
  Seite mit Parameter zusammengelegt, obwohl der Kopfkommentar in
  `strength.php` das in Aussicht stellte. Grund siehe unten.
- **Der Inhalt wird in `database/seed.sql` gepflegt**, nicht im PHP und nicht
  über einen Pflegebereich im Browser. Wer Texte ändert, ändert sie dort und
  spielt die Datei neu ein.
- **`api/programme.php` kann nur lesen.** Kein POST, kein PUT.
- **Die Bilddateien der Coaches liegen in `assets/img/coaches/`**, in der
  Datenbank steht nur der Dateiname. Den Ordner hängt das Repository an, die
  vollständige URL baut erst der Browser mit `BASE_URL`.

## Konsequenzen

**Positiv**

- Texte ändern heißt jetzt: eine Zeile SQL. Vorher musste man wissen, in
  welcher der drei PHP-Dateien der Text steht.
- Ein zweites Feature geht durch alle sechs Schichten — genau das, was die
  Aufgabenstellung sehen will.
- Ein vierter Coach oder ein viertes Level ist eine INSERT-Zeile. Vorher wäre
  es HTML in drei Dateien gewesen.
- Die Seiten selbst wurden kürzer, nicht länger.

**Negativ**

- **Die Programmseiten brauchen jetzt MySQL.** Vorher liefen sie ohne. Wer nur
  am Aussehen arbeitet, muss trotzdem XAMPP starten.
- **Ohne JavaScript bleibt der untere Teil leer.** Der Inhalt kommt per
  `fetch`, nicht aus dem PHP. Für Suchmaschinen und Vorlesegeräte ist das
  schlechter als vorher. Die Schichtenfolge lässt es nicht anders zu: Eine
  Seite darf nicht direkt auf ein Repository zugreifen.
- Ein zusätzlicher HTTP-Aufruf pro Seitenaufruf.
- Texte stehen nicht mehr im Git-Verlauf der Seite, sondern in `seed.sql`.
  Wer wissen will, wann ein Satz geändert wurde, schaut an einer anderen Stelle.

## Verworfene Alternativen

**Alles im PHP der Seite lassen.**
Wäre schneller gebaut gewesen und hätte ohne MySQL funktioniert. Bei sechzig
Einträgen über drei Dateien wird das aber unübersichtlich, und das Feature
hätte nur Schicht 1 berührt — für die Studienarbeit zu wenig.

**Die drei Seiten zu einer `programm.php?slug=move` zusammenlegen.**
Technisch der nächste logische Schritt, und `strength.php` kündigt ihn an.
Dagegen sprachen zwei Dinge: Die bestehenden Links und Lesezeichen auf
`programme/move.php` hätten Weiterleitungen gebraucht, und die Begründung aus
dem ursprünglichen Kopfkommentar gilt weiter — getrennte Dateien lassen sich im
Fünfer-Team gleichzeitig bearbeiten. Der Einleitungstext und das Hero-Bild
bleiben ohnehin pro Seite verschieden. Die Zusammenlegung bleibt möglich, sie
ist durch dieses ADR nur nicht mehr nötig.

**Die Auswahl bei Level und Format serverseitig speichern.**
Hätte ein angemeldetes Konto vorausgesetzt. Die Auswahl ist reine
Anzeigehilfe, kein Geschäftsvorgang — sie liegt deshalb im `localStorage` des
Browsers und ist absichtlich nicht mit dem Mitgliedskonto verbunden.

**Eine Tabelle statt drei, mit Spalten wie `level_1`, `level_2`, `level_3`.**
Spart Fremdschlüssel, begrenzt aber die Anzahl fest auf drei und macht jede
Abfrage umständlich. Das ist genau der Fehler, den die Konventionen in
`database/schema.sql` verhindern sollen.
