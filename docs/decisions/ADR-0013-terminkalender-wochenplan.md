# ADR-0013 — Termine als Wochenplan, Buchungen mit Datum

**Status:** angenommen
**Datum:** 2026-09-21

## Kontext

Kurse und Probetrainings sollen buchbar werden. Jedes Kursformat findet
zweimal pro Woche statt, mit höchstens 20 Teilnehmern. Probetrainings sind
Einzeltermine mit einem Coach, der nur zu bestimmten Zeiten kann.

Die Frage ist, wie Termine in der Datenbank stehen. Das bestimmt, wie viel
Pflege das Studio hat, ob die Vorführung in drei Wochen noch funktioniert und
wie sicher die Obergrenze von 20 Plätzen hält.

Eine Randbedingung macht die Frage dringlicher als sonst: Die Seite wird in
einigen Wochen vorgeführt, niemand im Team will bis dahin regelmäßig neue
Termine einspielen, und „ausgebucht" muss sich zeigen lassen, ohne vorher
zwanzig Testkonten anzulegen.

## Entscheidung

**Die Datenbank speichert Wochenmuster, keine Einzeltermine.**
`kurstermine` enthält „Move, Small Group, dienstags 18 Uhr, Sofia Lindqvist",
`verfuegbarkeiten` enthält „Sofia Lindqvist, montags 10 bis 13 Uhr". Welche
Tage das in den nächsten 14 Tagen sind, rechnet `src/Terminplan.php` aus.

**Gebucht wird dagegen ein konkreter Tag.** `kursbuchungen` verweist auf den
Wochenplan-Eintrag und hat zusätzlich ein Datum, `probetrainings` einen
genauen Zeitpunkt.

Daraus folgen diese Regeln:

- **Dieselbe Rechnung zeigt und prüft.** `Terminplan` liefert dem Kalender die
  Termine und prüft beim Buchen, ob es den angefragten Termin gibt. Was der
  Kalender nicht zeigen würde, lässt sich auch nicht buchen.
- **Die Obergrenze sichert eine Sperre, nicht eine Vorab-Zählung.** Das Buchen
  sperrt den Kurstermin (`SELECT … FOR UPDATE`), zählt und speichert in einer
  Transaktion.
- **Doppelte Probetrainings verhindert die Datenbank** über den UNIQUE-Schlüssel
  `(coach_id, beginnt_am)`, nicht der PHP-Code.
- **Stammplätze** (`kurstermine.stammplaetze`) sind Plätze, die jede Woche
  schon vergeben sind. Belegt = Stammplätze + Buchungen.
- **Die Obergrenze steht pro Termin** (`kurstermine.max_teilnehmer`): 20 für
  Gruppenformate, beim Einzelcoaching so viele wie das Programm Coaches hat.
  Ein Coach betreut dort eine Person.
- **Alle Zeiten sind Berliner Zeit**, gesetzt in `Terminplan::jetzt()`.
- **Gebucht wird nur mit Anmeldung**, den Kalender sieht jeder.

## Konsequenzen

**Positiv**

- Der Kalender ist nie veraltet. Er zeigt immer die nächsten 14 Tage, egal an
  welchem Tag die Seite geöffnet wird. Niemand muss Termine nachtragen.
- Eine Kurszeit ändern ist eine Zeile SQL und gilt sofort für alle kommenden
  Wochen.
- 18 Zeilen Wochenplan statt mehrerer Hundert Einzeltermine.
- Mit Stammplätzen lassen sich „ausgebucht" und „ein Platz frei" jederzeit
  vorführen.
- Die Obergrenze hält auch, wenn zwei Leute im selben Moment auf den letzten
  Platz klicken.

**Negativ**

- **Einzelne Ausnahmen gehen nicht.** „Am 3. Oktober fällt der Kurs aus" lässt
  sich nicht eintragen, ohne den ganzen Wochentag zu löschen. Dafür bräuchte
  es eine Tabelle mit Ausfällen.
- **Wird eine Kurszeit geändert, zeigen alte Buchungen die neue Uhrzeit.** Die
  Buchung kennt nur Tag und Wochenplan-Eintrag, die Uhrzeit kommt aus dem
  Wochenplan.
- **`seed.sql` neu einspielen löscht alle Buchungen**, weil sie über
  Fremdschlüssel an Programmen und Coaches hängen, die dabei neu entstehen.
- Stammplätze sind eine Vereinfachung. Echte Stammgäste hätten ein Konto und
  eine Buchung.
- Überschneiden sich ein Verfügbarkeitsfenster und ein Kurs desselben Coaches,
  merkt der Code das nicht. Die Testdaten sind so angelegt, dass das nicht
  vorkommt.

## Verworfene Alternativen

**Jeden Termin als eigene Zeile mit festem Datum.**
Die naheliegendste Lösung, und Ausnahmen wären einfach. Aber jemand müsste
laufend neue Termine einspielen, und eine Vorführung nach Ablauf der
eingespielten Wochen zeigt einen leeren Kalender.

**Termine per Skript im Voraus erzeugen (z. B. jeden Montag die nächsten vier
Wochen).**
Löst das Veralten, braucht aber einen Zeitplaner (Cron), den es unter XAMPP
nicht zuverlässig gibt, und verschiebt das Problem nur.

**Belegung vorab zählen, dann speichern, ohne Sperre.**
Weniger Code. Klicken zwei Leute gleichzeitig auf den letzten von 20 Plätzen,
sehen beide „19 von 20", und am Ende sind 21 im Kurs.

**Zwanzig Testkonten in seed.sql, um „ausgebucht" zu zeigen.**
Konten brauchen Passwörter, und die gehören laut Regel nicht in `seed.sql`.
Dazu würde jede Registrierung im Test die Belegung verschieben.
