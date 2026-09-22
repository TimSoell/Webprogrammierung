# ADR-0015 — Kurskalender monatsweise, Kurse nur mit passendem Tarif

**Status:** angenommen
**Datum:** 2026-09-22

## Kontext

Der Kurskalender aus [ADR-0014](ADR-0014-terminkalender-wochenplan.md) zeigte
die nächsten 14 Tage. Zwei Wünsche passten dazu nicht mehr:

- Man soll immer einen **ganzen Monat** sehen und in **spätere Monate
  blättern** können.
- Buchen soll nur, wer Kurse **im Tarif** hat. Laut
  [ADR-0010](ADR-0010-tarifwechsel-zum-monatsersten.md) gilt ein Wechsel aber
  erst zum nächsten Monatsersten. Ein Mitglied kann also heute den Basisplan
  haben und ab dem 1. des nächsten Monats den Kurs-Plan, oder umgekehrt.

## Entscheidung

**Der Kurskalender zeigt den laufenden Monat und `Terminplan::MONATE_VORAUS`
(= 2) weitere.** Der Browser lädt jeden Monat einzeln
(`api/kurstermine.php?monat=JJJJ-MM`). Welche Monate es gibt, sagt der Server.
Die Buchungsprüfung (`Terminplan::kursterminBuchbar`) nutzt denselben
Zeitraum. Was der Kalender nicht zeigt, lässt sich weiterhin nicht buchen.

**Maßgeblich ist der Tarif, der am Tag des Kurses gilt, nicht der heutige.**
`MitgliedschaftRepository::amTagFinden()` sucht den Vertrag für ein Datum,
auch einen vorgemerkten. Daraus folgt:

- `api/kursbuchungen.php` lehnt eine Buchung mit **403** ab, wenn der Vertrag
  an diesem Tag keine Kurse enthält. Das ist die eigentliche Sperre.
- `api/kurstermine.php` liefert pro Monat `tarif: { name, kurse }`. Das dient
  nur der Anzeige: Hinweis über dem Kalender, Link zur Mitgliedschaft, kein
  „Buchen"-Knopf.
- Geprüft wird pro Monat an einem Tag, weil sich der Tarif innerhalb eines
  Monats nie ändert (Wechsel nur zum Ersten, Erstwahl ab heute).

**Ein Tarifwechsel storniert Kurse, die der neue Tarif nicht mehr abdeckt.**
Enthält nach einem Wechsel oder einer Rücknahme der Tarif ab dem nächsten
Monatsersten keine Kurse, löscht `api/mitgliedschaften.php` alle Buchungen ab
diesem Tag (`KursbuchungRepository::abDatumStornieren()`). Vorher warnt das
Bestätigungsfenster mit der Anzahl (`kurseAbWechsel`), danach nennt die
Erfolgsmeldung, wie viele storniert wurden (`storniert`).

**Das Probetraining bleibt bei 14 Tagen** (`Terminplan::TAGE_VORAUS`). Es ist
ein Einzeltermin zum Kennenlernen, dafür braucht es keine Monate Vorlauf.

## Konsequenzen

**Positiv**

- Wer zum nächsten Monat auf einen Tarif mit Kursen wechselt, kann die Kurse
  ab dann sofort buchen und muss nicht bis zum Ersten warten.
- Wer vom Kurs-Plan weg wechselt, kann im Folgemonat nichts mehr buchen, was
  der neue Tarif nicht abdeckt.
- Der Hinweis im Kalender und die Sperre im Endpunkt fragen dieselbe
  Repository-Methode. Sie können sich nicht widersprechen.

**Negativ**

- **Stornierte Kurse kommen nicht zurück.** Nimmt jemand den Wechsel auf den
  Basisplan wieder zurück, sind die Buchungen weg und die Plätze womöglich
  vergeben. Die Warnung im Bestätigungsfenster sagt das vorher.
- Eine Anfrage pro Monat statt einer für alles. Beim Blättern gibt es eine
  kurze Ladezeit. Dafür werden die Monate bis zum Schließen des Fensters im
  Browser gemerkt.
- `seed.sql`-Stammplätze gelten jetzt für drei Monate. Die fest ausgebuchten
  Termine sind in jeder Woche voll.

## Verworfene Alternativen

**Gebuchte Kurse nach einem Wechsel stehen lassen.**
Weniger Code, aber dann säße jemand im Oktober in einem Kurs, den sein Tarif
nicht mehr enthält, und blockierte einen Platz.

**Alle drei Monate in einer Anfrage laden.**
Weniger Code im Browser, aber rund sechsmal so viele Termine bei jedem Öffnen,
obwohl die meisten nur den laufenden Monat ansehen.

**Den heutigen Tarif prüfen statt den am Kurstag.**
Einfacher, aber falsch in beide Richtungen: Wer schon auf den Kurs-Plan
gewechselt hat, könnte die Kurse im neuen Monat nicht buchen. Wer vom
Kurs-Plan weg wechselt, könnte Kurse in einem Monat buchen, für den er nicht
bezahlt.

**Unbegrenzt weit blättern.**
Der Wochenplan ließe sich beliebig weit ausrollen. Buchungen für Termine in
einem halben Jahr blockieren aber Plätze, deren Kurszeiten sich bis dahin
ändern können (siehe ADR-0014, „alte Buchungen zeigen die neue Uhrzeit").
