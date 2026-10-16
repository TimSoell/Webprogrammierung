# ADR-0024 — Der Empfang bestätigt das Konto mit dem Personalausweis, nicht den Ermäßigungsnachweis

**Status:** angenommen
**Datum:** 2026-10-16

**Ersetzt einen Teil von [ADR-0023](ADR-0023-name-aendern-und-ausweis-am-empfang.md):**
wer in der Liste am Empfang steht, welcher Ausweis dort gezeigt wird und das
„Ablehnen". Alles andere aus ADR-0023 gilt weiter: die Rolle Personal, kein
Link zur Seite, Name fest nach der Bestätigung, die 14-Tage-Frist ab dem
ersten Nachweis und was beim Verpassen passiert.

## Kontext

ADR-0023 ließ offen, welchen Ausweis das Personal sich ansieht. Die Seite
listete nur Mitglieder mit Ermäßigungsnachweis und zeigte deren Schüler- oder
Studierendenausweis an. Mit „Ablehnen" konnte der Empfang diesen Nachweis
entfernen.

Das vermischt zwei Prüfungen. Ob jemand Student ist, prüft schon die
Ausweisprüfung online — dafür gibt es sie. Was online nicht geht, ist der
Abgleich der **Person** mit dem Konto: Gehört das Konto wirklich dem Menschen,
dessen Name darin steht? Das kann nur ein amtlicher Ausweis mit Foto
beantworten, den jemand mit der Person davor vergleicht.

## Entscheidung

- **Am Empfang wird nur das Konto bestätigt, mit dem Personalausweis** (oder
  einem anderen amtlichen Lichtbildausweis). Das Personal vergleicht das Foto
  mit der Person und den Namen mit dem Konto.
- **Schüler-, Studierenden- und Seniorennachweise sieht der Empfang nicht.**
  Die prüft allein die Ausweisprüfung in `api/nachweise.php`.
- **In der Liste stehen alle unbestätigten Konten**, nicht nur die mit
  Ermäßigung. Jedes Mitglied kann sich beim ersten Besuch bestätigen lassen.
  Gesperrte Konten (etwa die Demo-Konten der Auslastung) fehlen, das eigene
  Konto des Personals auch.
- **Wer einen Ermäßigungsnachweis hat, steht oben** und zeigt seine Frist:
  läuft (lime) oder verpasst (rot). Alle anderen folgen nach Namen, ohne
  Frist.
- **Es gibt nur noch „Bestätigen".** Passt der Personalausweis nicht,
  bestätigt das Personal einfach nicht. Ein „Ablehnen" fällt weg — der
  Empfang beurteilt keinen Ermäßigungsnachweis mehr, also entfernt er auch
  keinen.
- **Die Datenstruktur bleibt, wie sie ist.** `mitglieder.bestaetigt_am`
  heißt jetzt ausdrücklich „Konto mit Personalausweis bestätigt".

## Konsequenzen

**Positiv**

- Jede Prüfung hat einen klaren Ort: Studentenstatus online, Person am
  Empfang. Niemand muss am Tresen einen Studierendenausweis beurteilen.
- Auch Mitglieder ohne Ermäßigung haben ein bestätigtes Konto, sobald sie
  einmal da waren.
- Keine Schemaänderung, keine neue Spalte.

**Negativ**

- **Die Liste ist länger.** Dafür gibt es Suche und Filter
  („Mit Ermäßigung", „Frist verpasst").
- **Einen offensichtlich falschen Ermäßigungsnachweis kann der Empfang nicht
  mehr entfernen.** Er bestätigt das Konto dann nicht, und nach 14 Tagen
  ruht der Preis von selbst.
- **Im Demo-Modus prüft weiterhin niemand den Studentenstatus**, weil ohne
  Schlüssel keine Ausweisprüfung läuft. Das war vorher genauso.

## Verworfene Alternativen

**Nur Konten mit Ermäßigungsnachweis listen, wie bisher.**
Dann bliebe die Bestätigung an den Rabatt gebunden. Das Team will am Empfang
das Konto verifizieren, unabhängig vom Preis.

**„Ablehnen" behalten.**
Ablehnen hieß „Nachweis entfernen". Wenn der Empfang den Nachweis gar nicht
mehr ansieht, gibt es nichts abzulehnen. Ein Konto als „gefälscht" zu
markieren wäre ein eigenes Feature mit eigener Spalte.
