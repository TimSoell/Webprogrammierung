# ADR-0025 — Ein Admin vergibt die Rolle Personal auf der Website

**Status:** angenommen
**Datum:** 2026-10-16

**Ersetzt einen Teil von [ADR-0023](ADR-0023-name-aendern-und-ausweis-am-empfang.md):**
dass die Rolle Personal „nur im Supabase-Dashboard, nie über die Website"
vergeben wird. Alles andere zur Rolle Personal gilt weiter.

## Kontext

Seit ADR-0023 gibt es die Rolle Personal für den Empfang. Vergeben ließ sie
sich nur per SQL im Supabase-Dashboard. Das hat zwei Nachteile: Es braucht
jemanden mit Zugang zum Dashboard, und ein Tippfehler im `UPDATE` trifft im
schlimmsten Fall alle Konten.

Ein Mitglied darf sich die Rolle aber weiterhin nicht selbst geben können.
Es braucht also eine zweite Rolle, die das Personal verwaltet. Randbedingung:
Die Datenstruktur bleibt, wie sie ist.

## Entscheidung

- **Es gibt eine Rolle „Admin".** Das ist die Rolle `ADMIN` (Wert 1) der
  Login-Bibliothek, in derselben Spalte `users.roles_mask` wie das Personal
  (`EMPLOYEE`, 2048). Keine neue Tabelle, keine neue Spalte.
- **Admin wird man nur im Supabase-Dashboard.** Auf der Website gibt es
  dafür keinen Weg.
- **Ein Admin hat eine eigene Seite `admin.php`.** Dort macht er ein
  bestehendes Konto per E-Mail-Adresse zum Personal und entzieht die Rolle
  wieder. **Kein Link führt dorthin**, wie beim Empfang. Seite
  (`Auth::nurFuerAdmins()`) und Endpunkt `api/personal.php` prüfen die Rolle
  selbst und antworten allen anderen, auch dem Personal, mit 403.
- **Admin und Personal sind zwei getrennte Rollen.** Ein Admin kommt nicht
  von selbst an den Empfang, kann sich aber selbst zum Personal machen.
- **Geschrieben wird die Rolle nur über die Login-Bibliothek**, in
  `src/Auth.php` (`personalErnennen()`, `personalEntziehen()`). Gelesen wird
  die Liste in `MitgliedRepository::personalFinden()`.

## Konsequenzen

**Positiv**

- Personal ernennen geht ohne Dashboard und ohne SQL.
- Eine Änderung trifft immer genau ein Konto.
- Keine Schemaänderung.

**Negativ**

- **Es wird nicht festgehalten, wer wann jemanden zum Personal gemacht hat.**
  Dafür bräuchte es eine neue Tabelle.
- **Die Rolle wirkt erst nach neuem Anmelden oder nach bis zu fünf
  Minuten**, weil die Bibliothek die Rollen in der Sitzung hält. Das gilt
  auch beim Entziehen.
- **Den ersten Admin muss trotzdem jemand im Dashboard eintragen.**

## Verworfene Alternativen

**Ein Bereich auf `empfang.php`.**
Den Empfang darf nur das Personal öffnen, ein Admin müsste dann auch Personal
sein. Außerdem steht der Bildschirm am Tresen, wo andere mitlesen.

**Admin über die Website vergeben.**
Dann könnte ein Admin weitere Admins ernennen, und ein einziges gekapertes
Konto reicht für alles. Admins sind wenige — der Weg übers Dashboard ist
hier zumutbar.

**Eigene Tabelle für Rollen.**
Die Bibliothek bringt Rollen schon mit, und die Anmeldung liest sie dort.
Eine zweite Stelle müsste man mit ihr abgleichen.
