# ADR-0026 — Falsche Konten: Das Personal meldet, ein Admin löscht, und Gutscheine warten auf die Bestätigung

**Status:** angenommen
**Datum:** 2026-10-16

**Ersetzt einen Teil von [ADR-0024](ADR-0024-empfang-bestaetigt-konto-mit-personalausweis.md):**
„Es gibt nur noch Bestätigen. Passt der Personalausweis nicht, bestätigt das
Personal einfach nicht." Alles andere aus ADR-0024 gilt weiter.

## Kontext

Seit ADR-0024 bestätigt das Personal jedes Konto am Empfang mit dem
Personalausweis. Passte etwas nicht, blieb nur: nicht bestätigen. Das Konto
stand dann weiter in der Liste, ohne Hinweis, was war — und niemand konnte
es entfernen. Bei einem Betrugsversuch bleibt so Datenmüll zurück.

Dazu kommt „Freunde werben": Der Werber bekommt seinen Gutschein, sobald sich
jemand mit der eingeladenen E-Mail registriert. Mit erfundenen Konten ließe
sich so beliebig Gratiszeit sammeln.

Randbedingung wie immer: Die Datenstruktur wird nur ergänzt.

## Entscheidung

- **Das Personal meldet eine Unstimmigkeit.** Am Empfang steht neben
  „Bestätigen" ein Knopf „Unstimmigkeit". Das Personal schreibt kurz dazu,
  was nicht passt (Pflicht, höchstens 200 Zeichen). Gespeichert wird das in
  zwei neuen Spalten `mitglieder.unstimmigkeit_am` und
  `mitglieder.unstimmigkeit_grund`. Das Konto bleibt rot markiert in der
  Liste; eine spätere Bestätigung hebt die Meldung auf.
- **Löschen kann nur ein Admin, und nur gemeldete Konten.** Auf `admin.php`
  steht die Karte „Unstimmigkeiten" mit „Markierung aufheben" und „Konto
  löschen". Das eigene Konto kann ein Admin dort nicht löschen. Das Personal
  löscht nie.
- **Löschen heißt: alles weg, in einem Schritt.** Die Login-Bibliothek löscht
  das Konto in `users`; alles Fachliche hängt schon mit `ON DELETE CASCADE`
  daran. Nur die Einladung des Werbers würde bloß geleert — sie verschwindet
  vorher samt noch nicht eingelöstem Gutschein. Beides läuft in einer
  Transaktion.
- **Ein Gutschein lässt sich erst einlösen, wenn das Konto des Geworbenen am
  Empfang bestätigt ist.** Bis dahin steht er in „Mein Konto" auf „wartet".
  Dafür reicht die Spalte `mitglieder.bestaetigt_am`, die es schon gibt.

## Konsequenzen

**Positiv**

- Ein Betrugsversuch hinterlässt keine Daten, und der Admin sieht, warum
  gemeldet wurde.
- Erfundene Konten bringen beim Werben nichts mehr.
- Das Personal kann nichts Endgültiges tun; das bleibt bei wenigen Admins.

**Negativ**

- **Zwei neue Spalten** — vor dem Merge auch in der Produktion einspielen
  (Abschnitt „NACHTRÄGE" in `database/schema.sql`).
- **Löschen ist endgültig.** Ein Irrtum lässt sich nicht zurückholen; dagegen
  helfen nur die Rückfrage und dass nur gemeldete Konten gehen.
- **Ehrliche Werber warten länger** auf ihren Gutschein — bis der Freund zum
  ersten Training kommt.
- **Wer gemeldet oder gelöscht hat, wird nicht gespeichert.**

## Verworfene Alternativen

**Konto sperren statt löschen.**
Die Login-Bibliothek kann Konten sperren, die Daten blieben aber liegen.
Gewünscht ist, dass nach einem Betrugsversuch nichts zurückbleibt.

**Das Personal löscht direkt am Empfang.**
Ein Klick am Tresen, der nicht rückgängig zu machen ist, ist zu viel für
einen hektischen Moment. Melden und Löschen sind deshalb getrennt.

**Jedes Konto löschbar machen.**
Dann ließe sich auch ein echtes Mitglied aus Versehen löschen. Die Meldung
vom Empfang ist die zweite Person, die draufgeschaut hat.

**Gutschein beim Löschen immer behalten.**
Dann hätte sich der Betrug für den Werber trotzdem gelohnt.
