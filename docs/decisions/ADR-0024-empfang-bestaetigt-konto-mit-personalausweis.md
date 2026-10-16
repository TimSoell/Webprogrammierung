# ADR-0024 — Jedes Mitglied mit Tarif bestätigt sein Konto einmal mit dem Personalausweis am Empfang

**Status:** angenommen, „nur Bestätigen" ersetzt durch [ADR-0026](ADR-0026-schutz-vor-falschen-konten.md)
**Datum:** 2026-10-16

**Ersetzt einen Teil von [ADR-0023](ADR-0023-name-aendern-und-ausweis-am-empfang.md):**
wer zum Empfang muss, wann die Frist beginnt, welcher Ausweis dort gezeigt
wird, wer in der Liste steht und das „Ablehnen". Alles andere aus ADR-0023
gilt weiter: die Rolle Personal, kein Link zur Seite, Name fest nach der
Bestätigung, 14 Tage Frist, die nie neu beginnt, und dass ein ermäßigter
Preis bei verpasster Frist ruht.

## Kontext

ADR-0023 band den Empfang an den Ermäßigungsnachweis: Nur wer einen
Schüler-, Studierenden- oder Seniorennachweis hochlud, musste hin, und die
Frist begann mit diesem Upload. Gezeigt wurde „der Ausweis", gemeint war
meist derselbe Schülerausweis. Mit „Ablehnen" konnte der Empfang den
Nachweis entfernen.

Das vermischt zwei Prüfungen. Ob jemand Schüler oder Student ist, prüft
schon die Ausweisprüfung online. Was online nicht geht, ist der Abgleich der
**Person** mit dem Konto: Gehört das Konto wirklich dem Menschen, dessen Name
darin steht? Das beantwortet nur ein amtlicher Ausweis mit Foto, den jemand
mit der Person davor vergleicht — und diese Frage stellt sich für jedes
Mitglied, nicht nur für die mit Rabatt.

## Entscheidung

- **Jedes Mitglied, das einen Tarif wählt, bestätigt sein Konto einmal am
  Empfang mit dem Personalausweis** (oder einem anderen amtlichen
  Lichtbildausweis), am besten beim ersten Training. Das gilt für alle
  Preisgruppen und auch für einen Tarif, der dank Gutschein gratis beginnt.
- **Die Frist beginnt mit der ersten Tarifwahl** (`api/mitgliedschaften.php`,
  Erstwahl) und beträgt 14 Tage. Sie steht weiter in
  `mitglieder.vorzeigen_bis` und beginnt nie neu.
- **Ohne Tarif gibt es keine Frist.** Solche Konten müssen nicht zum Empfang,
  können dort aber bestätigt werden.
- **Schüler-, Studierenden- und Seniorennachweise sieht der Empfang nicht.**
  Sie werden wie bisher hochgeladen und von der Ausweisprüfung geprüft und
  freigegeben.
- **Frist verpasst — die lockere Variante:** Mit Ermäßigung ruht der günstige
  Preis, bis das Konto bestätigt ist (wie in ADR-0023). Ohne Ermäßigung
  passiert nichts weiter; das Konto ist in „Mein Konto" und am Empfang rot
  markiert.
- **In der Liste am Empfang stehen alle unbestätigten Konten**, oben die mit
  Tarif nach Frist, darunter die ohne Tarif nach Namen. Gesperrte Konten (etwa
  die Demo-Konten der Auslastung) fehlen, das eigene Konto des Personals auch.
- **Es gibt nur noch „Bestätigen".** Passt der Personalausweis nicht,
  bestätigt das Personal einfach nicht.
- **Die Datenstruktur bleibt, wie sie ist.** Nur der Zeitpunkt, zu dem
  `vorzeigen_bis` gesetzt wird, ändert sich. Ein Nachtrag in
  `database/schema.sql` gibt Mitgliedern, die schon einen Tarif haben, ihre 14
  Tage ab dem Einspielen und leert Fristen, die nach der alten Regel ohne
  Tarif entstanden sind.

## Konsequenzen

**Positiv**

- Jede Prüfung hat einen klaren Ort: Studentenstatus online, Person am
  Empfang.
- Jedes trainierende Mitglied hat nach dem ersten Besuch ein bestätigtes
  Konto, nicht nur die mit Rabatt.
- Keine Schemaänderung.
- Hochladen und Buchen gehen weiter in einem Zug: Ein Nachweis zählt sofort,
  auch bevor ein Tarif gewählt ist.

**Negativ**

- **Ohne Ermäßigung hat eine verpasste Frist keine Folge.** Das ist bewusst so
  gewählt; eine Sperre (etwa für Kursbuchungen) wäre die strengere Variante.
- **Die Liste ist länger.** Dafür gibt es Suche und Filter („Mit Tarif",
  „Frist verpasst").
- **Einen offensichtlich falschen Ermäßigungsnachweis kann der Empfang nicht
  entfernen.** Er bestätigt das Konto dann nicht, und nach 14 Tagen ruht der
  Preis von selbst.
- **Im Demo-Modus prüft weiterhin niemand den Studentenstatus**, weil ohne
  Schlüssel keine Ausweisprüfung läuft.

## Verworfene Alternativen

**Frist ab dem ersten Ermäßigungsnachweis, wie in ADR-0023.**
Dann müssten nur Mitglieder mit Rabatt zum Empfang. Das Team will aber jedes
Konto einmal verifizieren.

**Bei verpasster Frist Kurse sperren.**
Wirksamer, aber mehr Umbau und für die Studienarbeit nicht nötig. Am Empfang
fällt die Person ohnehin auf, sobald sie trainieren will.

**„Ablehnen" behalten.**
Ablehnen hieß „Nachweis entfernen". Wenn der Empfang den Nachweis gar nicht
mehr ansieht, gibt es nichts abzulehnen.
