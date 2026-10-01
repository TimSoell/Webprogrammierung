# ADR-0020 — Ein Nachweis je Mitglied, und er muss auf den Kontoinhaber lauten

**Status:** angenommen
**Datum:** 2026-10-01

**Ergänzt [ADR-0011](ADR-0011-ausweispruefung-mit-ki.md).** Dessen Kern gilt
unverändert: Das Bild wird nie gespeichert, die KI entscheidet nichts, die
Sperre sitzt im Endpunkt. Ersetzt wird ein Punkt: Den gespeicherten
Hinweissatz schreibt nicht mehr das Modell, sondern der Endpunkt.

## Kontext

Die Prüfung aus ADR-0011 las von einem Ausweis die Art und ein Datum. Damit
blieben vier Lücken offen:

- **Fremde Ausweise.** Niemand prüfte, auf wen der Ausweis ausgestellt ist.
  Ein Vater konnte den Schülerausweis seines Sohnes hochladen.
- **Mehrere Nachweise zugleich.** Ein Mitglied konnte Senior und Schüler
  gleichzeitig sein. Das passt nicht zusammen und deutet auf einen Ausweis,
  der jemand anderem gehört.
- **Beliebige Ablaufdaten.** Ein Ausweis „gültig bis 2099" wurde so
  übernommen — auch, wenn das Modell sich nur verlesen hatte.
- **Der gespeicherte Hinweissatz.** Das Modell formulierte ihn frei, er
  wurde ungefiltert gespeichert und enthielt Geburtsdatum oder Name —
  obwohl ADR-0011 zusagt, beides nicht zu speichern.

Dazu kam: Jeder Upload kostet eine Anfrage aus dem Tageskontingent der
kostenlosen Stufe, und nichts begrenzte die Zahl der Versuche.

## Entscheidung

- **Der Name auf dem Ausweis muss zum Konto passen.** Das Modell liest Vor-
  und Nachname, `api/nachweise.php` vergleicht: Der Nachname muss gleich
  sein, der Vorname aus dem Konto muss unter den Vornamen auf dem Ausweis
  stehen. Groß- und Kleinschreibung, Umlaute (`Müller` = `Mueller`), Akzente
  und Bindestriche zählen nicht. Der gelesene Name wird danach verworfen,
  wie das Geburtsdatum.
- **Der Name aus dem Konto geht nicht an das Modell.** Verglichen wird in
  PHP. So verlässt nichts zusätzlich den Server, und ein Bild kann das
  Modell nicht zu einem „passt" überreden.
- **Ein Mitglied hat höchstens einen gültigen Nachweis.** Ein neuer ersetzt
  den bisherigen, in einer Transaktion in `NachweisRepository::anlegen()`.
  Dieselbe Art wird nur ersetzt, wenn der neue länger gilt.
- **Nachweise lassen sich entfernen**, auch abgelaufene.
- **Befristete Nachweise gelten höchstens ein Jahr im Voraus** (Schüler:
  zwei). Steht auf dem Ausweis ein späteres Datum, wird gekürzt statt
  abgelehnt.
- **Drei Prüfversuche je Mitglied**, danach kommt alle acht Stunden einer
  dazu. Gezählt wird mit der Drosselung der Login-Bibliothek; wie beim
  Login ist sie mit `debug = true` aus.
- **Den Hinweissatz baut der Endpunkt** aus den geprüften Angaben. Das
  Modell liefert keinen freien Text mehr.

## Konsequenzen

**Positiv**

- Der naheliegende Missbrauch — fremder Ausweis im eigenen Konto — geht
  nicht mehr.
- Die Zusage „kein Name, kein Geburtsdatum in der Datenbank" stimmt jetzt
  auch für die Spalte `hinweis`.
- Keine neue Tabelle und keine neue Spalte.

**Negativ**

- **Der Name im Konto ist eine Selbstauskunft.** Wer ein Konto auf den
  Namen des Ausweisinhabers anlegt, kommt durch. Der Vertrag läuft dann
  aber auf diesen Namen; auffallen würde es erst bei einer Kontrolle am
  Empfang, die es im Projekt nicht gibt.
- **Wer sich mit einem Rufnamen registriert hat, scheitert.** „Max" passt
  nicht zu „Maximilian", und der Name im Konto lässt sich nicht ändern.
- **Derselbe Ausweis funktioniert in mehreren Konten**, solange der Name
  dort gleich ist.
- **Name und Datum müssen auf derselben Seite stehen.** Hochgeladen wird
  ein Bild.
- **Im Demo-Modus greift nichts davon.** Ohne Bild gibt es keinen Namen,
  und das Datum wird eingetippt.
- Die Versuchsgrenze ist ein Eimer, kein Kalendertag: In 24 Stunden sind
  im ungünstigsten Fall fünf Versuche möglich, nicht drei.

## Verworfene Alternativen

**Zweiten Nachweis ablehnen statt ersetzen.**
Wer vom Schüler zum Studenten wird, müsste erst entfernen und dann neu
hochladen. Dazwischen liegt kein Nachweis vor, und
`api/mitgliedschaften.php` merkt die Herabstufung auf den Standardpreis vor.

**Einen Fingerabdruck der Ausweisnummer speichern**, damit ein Ausweis nur
in einem Konto gilt. Schließt die Lücke mit den mehreren Konten, verlangt
aber, etwas vom Ausweis dauerhaft zu speichern — gegen den Kern von ADR-0011.

**Ähnliche Namen gelten lassen** (ein Buchstabe Abweichung). Würde
Lesefehler des Modells verzeihen, aber auch „Maier" für „Meier" durchlassen.
Ein unscharfes Foto lädt man neu hoch.

**Zu lange Gültigkeit ablehnen statt kürzen.**
Manche Hochschulen stellen den Ausweis für das ganze Studium aus. Diese
Mitglieder kämen dann nie an den ermäßigten Preis.

**Eigene Tabelle für die Versuche.**
Genauer („drei je Kalendertag"), aber eine Tabelle mehr, die auch in der
Produktionsdatenbank angelegt werden muss. Die Login-Bibliothek bringt die
Drosselung samt Tabelle schon mit.
