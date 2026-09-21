# ADR-0007 — Gemerkte Auswahl liegt am Konto, nicht nur im Browser

**Status:** angenommen
**Datum:** 2026-09-21

## Kontext

[`ADR-0006`](ADR-0006-programm-details-aus-der-datenbank.md) hat die Level- und
Format-Auswahl auf den Programmseiten im `localStorage` abgelegt und die
serverseitige Speicherung ausdrücklich verworfen — mit der Begründung, die
Auswahl sei „reine Anzeigehilfe, kein Geschäftsvorgang".

Diese Einschätzung ist überholt. Der Mitgliedsbereich soll zeigen, welche
Programme sich ein Mitglied gemerkt hat und mit welchem Level und Format.
Das geht mit `localStorage` grundsätzlich nicht: Der Server sieht ihn nie,
und auf einem zweiten Gerät ist die Auswahl weg.

Damit wird aus der Anzeigehilfe etwas anderes — eine Angabe, die das Mitglied
über sich macht und später wiederfinden will.

**Dieses ADR ersetzt ADR-0006 nicht.** Dessen Kernentscheidung, dass die
Programm-Details aus der Datenbank kommen, gilt unverändert. Revidiert wird
nur eine der dort verworfenen Alternativen.

## Entscheidung

Die gewählte Kombination aus Level und Format wird für angemeldete Mitglieder
in der Tabelle `mitglied_auswahl` gespeichert. Daraus folgen diese Regeln:

- **Gespeichert wird erst auf Knopfdruck.** Ein Klick auf eine Level- oder
  Format-Schaltfläche ändert weiterhin nur die Anzeige. Erst der Button
  „Auswahl merken" schreibt sie ans Konto.
- **Nicht angemeldete Besucher behalten den `localStorage`.** Dort ändert sich
  nichts: Jeder Klick wird sofort lokal gemerkt.
- **Beim Öffnen einer Programmseite gewinnt das Konto.** Steht dort etwas,
  überschreibt es die lokale Auswahl in der Anzeige.
- **Beim Anmelden wird eine lokale Auswahl übernommen**, aber nur für
  Programme, zu denen am Konto noch nichts steht.
- **`mitglied_auswahl` verweist auf `mitglieder.id`**, nicht auf `users.id` —
  dieselbe Regel wie für alle fachlichen Tabellen.
- **Der Endpunkt nimmt Titel entgegen, keine ids.** Die Umwandlung und die
  Prüfung passieren im Backend.

## Konsequenzen

**Positiv**

- Die Auswahl ist auf jedem Gerät dieselbe und überlebt einen neuen Browser.
- Der Mitgliedsbereich hat einen echten Inhalt über die Stammdaten hinaus.
- Ein zweites Feature verbindet Login und Programmdaten — bisher standen beide
  Bereiche unverbunden nebeneinander.
- Der Knopfdruck macht die Absicht eindeutig. Wer sich nur umsieht, sammelt
  keine Einträge.

**Negativ**

- **Zwei Speicherorte für dieselbe Sache.** Wer den Code liest, muss beide
  kennen und wissen, welcher wann gewinnt. Das ist der Preis dafür, dass die
  Seite auch ohne Anmeldung nützlich bleibt.
- **Ein Zustand mehr in der Oberfläche.** Der Button hat drei Beschriftungen
  (merken / aktualisieren / gemerkt), die zur Auswahl passen müssen.
- **Ein zusätzlicher HTTP-Aufruf** beim Öffnen einer Programmseite, sobald
  jemand angemeldet ist.
- **`seed.sql` löscht die gemerkten Auswahlen mit.** Das `DELETE FROM programme`
  greift über `ON DELETE CASCADE` bis in `mitglied_auswahl` durch. Vor einer
  Vorführung also nicht neu einspielen.
- Titel als Schnittstelle: Wird ein Merkmaltitel in `seed.sql` geändert, findet
  der Endpunkt ihn nicht mehr und antwortet mit 400. Bei einem kompletten
  Neueinspielen ist die Auswahl ohnehin weg, insofern kein neuer Nachteil.

## Verworfene Alternativen

**Jeder Klick speichert sofort ans Konto.**
Hätte den Button gespart und wäre am wenigsten Code gewesen. Dann füllt aber
schon neugieriges Herumklicken den Mitgliedsbereich, und aus „das habe ich mir
gemerkt" wird „da war ich mal". Der Knopfdruck trennt beides.

**Den `localStorage` ganz abschaffen.**
Wäre der eine klare Weg gewesen. Für nicht angemeldete Besucher — die
Mehrheit — wäre die Auswahl damit aber gar nicht mehr haltbar, und das ist
gegenüber heute eine Verschlechterung.

**Ändern auch im Mitgliedsbereich erlauben.**
Zwei Oberflächen für dieselbe Sache, die zusammenpassen müssen. Der
erklärende Text zu jedem Level steht auf der Programmseite; eine Auswahl ohne
diesen Text wäre ein Dropdown ohne Grundlage. Im Konto gibt es deshalb nur
Anzeigen und Entfernen, geändert wird dort, wo die Erklärung steht.

**Eine Zeile je Mitglied, Programm und Art statt zweier Spalten.**
Wäre die lehrbuchmäßig normalisierte Form. Sie bräuchte aber `programm_id` und
`art` zusätzlich in jeder Zeile, nur damit der UNIQUE-Schlüssel greift — und
die Arten liegen durch das ENUM in `merkmale.art` ohnehin fest. Zwei Spalten
sind hier die einfachere Lösung, ohne etwas zu verbauen.
