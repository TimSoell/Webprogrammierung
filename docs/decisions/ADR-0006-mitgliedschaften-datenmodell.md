# ADR-0006 — Tarife im Katalog, Verträge mit eingefrorenem Preis

**Status:** angenommen
**Datum:** 2026-09-21

## Kontext

Das Studio vergibt vier Mitgliedschaften — Basisplan, Wellness-Plan,
Kurs-Plan und Premium — zu je drei Preisen: Standard, ermäßigt für
Schülerinnen, Schüler und Studierende, und Senioren. Zwölf Preise also.

Bis hierher kannte die Datenbank nur `mitglieder` mit Vor- und Nachnamen
([ADR-0005](ADR-0005-login-bibliothek.md)). Es ist die erste Tabelle mit
fachlichem Inhalt, und es ist die, an der später Kurse, Buchungen und
Zahlungen hängen werden. Ein Fehler hier wird teuer, sobald andere Features
darauf aufsetzen.

Drei Fragen mussten beantwortet werden:

1. Wo stehen die Preise — im HTML der Preisseite oder in der Datenbank?
2. Woran hängt der Tarif einer Person?
3. Was passiert mit laufenden Verträgen, wenn das Studio die Preise ändert?

## Entscheidung

**Die Preise stehen in der Datenbank**, in der Tabelle `tarife`, gepflegt
über `database/seed.sql`. Die Preisseite lädt sie über `api/tarife.php`.

**Angebot und Vertrag sind zwei Tabellen.** `tarife` ist der Katalog,
`mitgliedschaften` ist der abgeschlossene Vertrag. Ein Tarifwechsel beendet
die laufende Zeile (`endet_am`) und legt eine neue an, statt die alte zu
überschreiben.

**Der Preis steht in beiden Tabellen.** `mitgliedschaften.preis_monatlich`
ist eine Kopie aus dem Katalog zum Zeitpunkt des Abschlusses. Eine spätere
Preisänderung verändert laufende Verträge nicht.

Daraus ergeben sich drei feste Regeln:

- **Fachliche Tabellen verweisen auf `mitglieder.id`, nie auf `users.id`.**
  `users` gehört der Login-Bibliothek.
- **Was ein Tarif erlaubt, steht als eigene Spalte** (`zugang_geraete`,
  `zugang_wellness`, `zugang_kurse`) und wird nie über den Tarifnamen
  abgefragt. Ein späterer Kursplan prüft `zugang_kurse = 1` und muss nicht
  wissen, welche Tarife es gerade gibt.
- **Ein Tarif wird nie gelöscht**, sobald ein Vertrag auf ihn zeigt. Aus dem
  Angebot genommen wird er über `tarife.aktiv = 0`.

## Konsequenzen

**Positiv**

- Preise ändern heißt: eine Zeile in `seed.sql` ändern und die Datei erneut
  einspielen. Niemand fasst dafür HTML an, und niemand vergisst eine der
  zwölf Zahlen.
- „Seit wann ist diese Person Premium?" ist beantwortbar. Das ist die Frage,
  die bei einer Beschwerde am Empfang zuerst kommt.
- Eine Preiserhöhung kann laufende Verträge nicht rückwirkend verteuern —
  auch nicht versehentlich.
- Kurse und Wellness können später eine einzige Spalte abfragen, statt eine
  Liste von Tarifnamen zu pflegen.

**Negativ**

- Die Seite ist ohne eingespielte `seed.sql` leer. Wer das Projekt frisch
  klont und nur `schema.sql` einspielt, sieht keine Tarife und sucht den
  Fehler im JavaScript.
- Der Preis steht doppelt in der Datenbank. Das sieht wie ein Normalisierungs-
  fehler aus und muss jedem erklärt werden, der es zum ersten Mal sieht —
  deshalb steht der Grund als Kommentar an der Spalte.
- „Höchstens eine laufende Mitgliedschaft pro Mitglied" kann MySQL nicht
  erzwingen: Einen Unique-Index, der nur für `endet_am IS NULL` gilt, gibt es
  dort nicht. Die Regel hält `MitgliedschaftRepository::abschliessen()` ein.
  Wer per phpMyAdmin direkt einfügt, umgeht sie.
- Zwei Tabellen und zwei Repositories statt zwei Spalten an `mitglieder`.

## Verworfene Alternativen

**Preise fest im HTML der Preisseite.**
Am schnellsten gebaut und für eine reine Schaufensterseite völlig in Ordnung.
Fällt aber in dem Moment auseinander, in dem ein Mitglied einen Tarif
abschließen soll: Dann muss der Preis ohnehin in die Datenbank, weil er im
Vertrag steht. Zwei Wahrheiten über denselben Preis wären das Ergebnis.

**`tarif_id` und `preisgruppe` direkt an `mitglieder`.**
Zwei Spalten statt einer Tabelle, deutlich weniger Code. Ein Tarifwechsel
überschreibt damit aber die Historie, und der Preis müsste bei jeder Anzeige
frisch aus dem Katalog geholt werden — womit eine Preiserhöhung alle
bestehenden Verträge mitzieht. Beides ist fachlich falsch.

**Eigene Tabelle `tarif_preise` (Tarif × Preisgruppe, zwölf Zeilen).**
Sauberer normalisiert und die Antwort, die man in einer Datenbankklausur gibt.
Kostet hier einen JOIN und eine dritte Tabelle, ohne etwas zu ermöglichen:
Die drei Preisgruppen stehen fest, und eine Tarifzeile ist genau eine Karte
auf der Preisseite. Kommt später eine vierte Gruppe dazu (Firmenfitness),
ist das eine zusätzliche Spalte.

**Leistungen als m:n-Beziehung (`leistungen` + `tarif_leistungen`).**
Die flexible Lösung: neue Leistungen ohne Schemaänderung. Für drei
Leistungen, die sich in der Laufzeit der Studienarbeit nicht ändern, sind das
zwei Tabellen und ein JOIN für nichts. Drei Spalten sind lesbar und
ausreichend — [CLAUDE.md](../../CLAUDE.md), „Bau nichts auf Vorrat".

**Preise als `INT` in Cent.**
Der übliche Rat gegen Rundungsfehler, und für ein Abrechnungssystem richtig.
Hier wird nichts berechnet, nur angezeigt. `DECIMAL(6,2)` ist in MySQL exakt
und erspart die Umrechnung an jeder Ausgabe.
