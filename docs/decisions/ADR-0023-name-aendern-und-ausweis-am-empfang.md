# ADR-0023 — Der Name ist bis zur Bestätigung am Empfang änderbar, und das Personal bestätigt jede Person einmal

**Status:** angenommen, wer zum Empfang muss, Beginn der Frist, gezeigter Ausweis, Liste und „Ablehnen" ersetzt durch [ADR-0024](ADR-0024-empfang-bestaetigt-konto-mit-personalausweis.md); Vergabe der Rolle Personal nur im Dashboard ersetzt durch [ADR-0025](ADR-0025-admin-verwaltet-personal.md)
**Datum:** 2026-10-16

**Ergänzt [ADR-0020](ADR-0020-nachweise-absichern.md).** Dessen Regeln gelten
unverändert: Namensabgleich, ein Nachweis je Mitglied, Höchstdauer,
Prüfversuche. Ersetzt werden zwei seiner negativen Konsequenzen — „der Name
im Konto lässt sich nicht ändern" und „eine Kontrolle am Empfang gibt es im
Projekt nicht".

## Kontext

Der Namensabgleich aus ADR-0020 hat zwei Lücken hinterlassen, die dort schon
benannt sind:

- **Wer sich mit einem Rufnamen registriert hat, kam nie an den ermäßigten
  Preis.** „Max" passt nicht zu „Maximilian", und der Name im Konto ließ sich
  nicht ändern. Das trifft ehrliche Mitglieder.
- **Der Name im Konto ist eine Selbstauskunft.** Wer ein Konto auf den Namen
  des Ausweisinhabers anlegt, kommt durch. Online lässt sich das nur mit
  einer echten Identitätsprüfung schließen (Video-Ident, Online-Ausweis) —
  für eine Studienarbeit zu groß.

Dazu kommt: In Produktion läuft die Seite bewusst im Demo-Modus (Entscheidung
E5 in [`migration-vercel-supabase.md`](../migration-vercel-supabase.md)).
Dort wird kein Bild gelesen, also greift online gar keine Prüfung.

Ein erster Entwurf ließ das Mitglied mit seinem eigenen Check-in bestätigen,
dass es den Ausweis gezeigt hat. Das hat das Team verworfen: Ein Klick, den
jeder selbst setzen kann, prüft nichts. Bestätigen muss ein Mensch, der den
Ausweis gesehen hat.

Randbedingungen: Hochladen und den ermäßigten Tarif wählen muss weiterhin in
einem Zug gehen, ohne Wartezeit. Und die Datenstruktur darf dafür nur
ergänzt, nicht umgebaut werden.

## Entscheidung

- **Es gibt eine Rolle „Personal".** Das ist die Rolle `EMPLOYEE` der
  Login-Bibliothek, gespeichert in ihrer eigenen Spalte `users.roles_mask`.
  Vergeben wird sie nur im Supabase-Dashboard, nie über die Website. Das
  Personal meldet sich über dieselbe Anmeldung an wie alle anderen.
- **Das Personal hat eine eigene Seite `empfang.php`.** Sie listet alle
  Personen, die ihren Ausweis noch zeigen müssen, mit „Bestätigen" und
  „Ablehnen". **Kein Link auf der Website führt dorthin**, auch nicht für
  das Personal: Es ruft die Adresse direkt auf. Geschützt ist die Seite über
  die Rolle, nicht über eine geheime Adresse — Seite und Endpunkt
  `api/empfang.php` prüfen sie selbst.
- **Bestätigt wird die Person, nicht der Nachweis — und nur einmal.** Das
  Personal vergleicht Foto und Name auf dem Ausweis mit der Person und mit
  dem Namen im Konto. Danach steht `mitglieder.bestaetigt_am`, und das wird
  nie zurückgenommen.
- **Nach der Bestätigung ist der Name für immer fest**, auch wenn der
  Nachweis später abläuft. Das Konto gehört dann dieser einen Person.
- **Davor ist der Name änderbar**, aber nicht, solange ein gültiger Nachweis
  hinterlegt ist. Sonst könnte sich ein Vater kurz den Namen seines Sohnes
  geben, dessen Schülerausweis hochladen und sich wieder umbenennen.
- **Spätere Nachweise prüft allein die Ausweisprüfung.** Wer einmal bestätigt
  ist, lädt den neuen Studierendenausweis hoch, und die KI gleicht ihn mit
  dem festen Namen ab. Zum Empfang muss er dafür nicht mehr.
- **Der erste Nachweis gilt sofort, aber nur 14 Tage ohne Bestätigung.** Die
  Frist steht in `mitglieder.vorzeigen_bis`. Sie beginnt mit dem ersten
  Upload und **beginnt nie neu** — sonst ließe sich der Empfang mit einem
  neuen Upload alle zwei Wochen umgehen.
- **Wer die Frist verpasst, verliert den ermäßigten Preis**, bis das Personal
  ihn bestätigt. Ein laufender Vertrag wird zum Monatsersten herabgestuft,
  derselbe Weg wie bei einem abgelaufenen Nachweis. Die Bestätigung nimmt
  diese Herabstufung wieder zurück, solange sie noch nicht gilt.
- **Ablehnen entfernt den Nachweis.** Die Frist läuft weiter.
- **Den eigenen Ausweis bestätigt jemand anderes.** Der Endpunkt lehnt es ab,
  wenn das Personal sich selbst bestätigen will.

## Konsequenzen

**Positiv**

- Wer sich mit einem Rufnamen registriert hat, kommt an seinen Preis.
- Die Lücke „Konto auf fremden Namen" schließt ein Mensch, der Person und
  Ausweis vor sich hat — so, wie es ein echtes Studio täte.
- Im Demo-Modus, also in Produktion, ist das die einzige echte Kontrolle.
- **Nur zwei neue Spalten, beide in `mitglieder`, beide dürfen leer sein.**
  Keine neue Tabelle, `nachweise` bleibt unverändert, und die Rolle nutzt
  eine Spalte, die die Login-Bibliothek schon mitbringt. Vorhandene Abfragen
  nennen ihre Spalten beim Namen und merken nichts davon.
- Vorhandene Nachweise verlieren ihren Preis nicht über Nacht: Der Nachtrag
  in `schema.sql` gibt ihnen 14 Tage ab dem Einspielen.
- Die Vorführung läuft wie bisher: hochladen, Tarif wählen, fertig.

**Negativ**

- **Vor dem Merge müssen die zwei Spalten in die Produktionsdatenbank.**
  Fehlen sie, brechen Kontoseite und Tarifseite.
- **Wer im Demo-Modus einmal bestätigt ist, kann sich später jedes Datum
  eintippen.** Ohne Schlüssel gibt es keine Ausweisprüfung, die einen
  späteren Nachweis kontrolliert. Der Name bleibt aber fest — es geht nur
  noch um das Ablaufdatum des eigenen Ausweises.
- **Ein falsch bestätigter Name lässt sich nur im Dashboard korrigieren.**
  Es gibt keinen Knopf zum Zurücknehmen — mit Absicht, sonst wäre die
  Bestätigung nichts wert.
- **Gespeichert wird nicht, wer bestätigt hat**, nur wann.
- **Die Namensänderung mit Nachweis kostet vor der Bestätigung zwei
  Schritte** (entfernen, neu hochladen) und einen Prüfversuch.
- `NachweisRepository` liest für die Gültigkeit jetzt `mitglieder` mit.
  Geschrieben wird `mitglieder` weiterhin nur von `MitgliedRepository`.

## Verworfene Alternativen

**Der Check-in spielt den Empfang.**
Der erste Klick auf „Jetzt einchecken" hätte als Vorzeigen gegolten. Das
Mitglied bestätigt sich damit selbst — Sicherheit entsteht so nicht.

**Jeden Nachweis am Empfang bestätigen, nicht nur die Person.**
Dafür bräuchte `nachweise` eigene Spalten, und jedes Mitglied müsste mit
jedem neuen Semesterausweis wieder an den Tresen. Die Person steht nach der
ersten Bestätigung fest; ob ein späterer Ausweis echt und aktuell ist, prüft
die KI.

**Frist je Nachweis statt je Person.**
Mit jedem neuen Upload begänne die Frist neu. Wer nie zum Empfang will, lädt
alle zwei Wochen neu hoch.

**Preis erst nach der Freigabe am Empfang.**
Am strengsten, aber dann geht Hochladen und Buchen nicht mehr in einem Zug,
und Buchen, „ein Nachweis je Mitglied" und Herabstufung hingen alle am neuen
Stand. Zu viel Umbau für denselben Schutz, den die Frist schon bietet.

**Eigene Tabelle für Rollen.**
Die Login-Bibliothek bringt Rollen mit, samt Abgleich mit der Session. Eine
zweite Stelle dafür wäre doppelte Wahrheit.

**Fingerabdruck der Ausweisnummer speichern.**
Schon in ADR-0020 verworfen, der Grund gilt weiter: Es bliebe dauerhaft
etwas vom Ausweis in der Datenbank.
