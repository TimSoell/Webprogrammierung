# ADR-0010 — Tarifwechsel gelten zum Monatsersten und brauchen eine Bestätigung

**Status:** angenommen
**Datum:** 2026-09-21

## Kontext

Mit [ADR-0009](ADR-0009-mitgliedschaften-datenmodell.md) konnte jedes Mitglied
einen Tarif wählen — und zwar beliebig oft. Ein Klick auf „Hierhin wechseln"
war sofort wirksam, ohne Rückfrage und ohne Grenze. Man konnte an einem
Nachmittag zwanzigmal zwischen Basisplan und Premium springen.

Fachlich ist das falsch. Eine Mitgliedschaft ist ein Vertrag mit einem
Monatsbeitrag. Wer heute auf Premium wechselt, den Sauna-Zugang nutzt und
morgen zurück auf den Basisplan geht, hat das teure Angebot benutzt und den
billigen Preis bezahlt. Dazu kommt: Ein Wechsel ändert den Beitrag und die
Zugangsrechte, ohne dass die Seite das vorher sagt. Man klickt und weiß erst
danach, was es kostet.

## Entscheidung

**Die erste Tarifwahl gilt sofort. Jeder spätere Wechsel gilt erst ab dem
ersten Tag des nächsten Monats.**

Ein Wechsel wird dafür *vorgemerkt*: Der laufende Vertrag wird auf den
Monatsletzten befristet, der neue Vertrag bekommt den Monatsersten als
`beginnt_am` und liegt bis dahin in der Zukunft.

Daraus ergeben sich fünf feste Regeln:

- **`beginnt_am` und `endet_am` sind einschließlich gemeint.** `endet_am` ist
  der letzte Gültigkeitstag, nicht der erste Tag danach. Ein Vertrag läuft,
  wenn `beginnt_am <= heute AND (endet_am IS NULL OR endet_am >= heute)`.
  **Auf `endet_am IS NULL` allein zu prüfen ist seitdem falsch**, weil auch
  vorgemerkte Verträge kein Ende haben.
- **Eine Vormerkung ist ersetzbar.** Bis zum Stichtag darf sie überschrieben
  oder zurückgenommen werden — es ist noch nichts passiert. Die Barriere
  bleibt trotzdem: Wirksam wird höchstens eine Änderung pro Monat.
- **Eine verworfene Vormerkung wird gelöscht, nicht archiviert.** Sie war nie
  ein Vertrag, sondern eine Absicht. In der Historie stehen nur Verträge, die
  tatsächlich gegolten haben.
- **Die Preisgruppe zu wechseln ist ein Wechsel.** Sie steht im Vertrag und
  bestimmt den Beitrag; sie folgt denselben Regeln wie ein Tarifwechsel.
- **Keine Änderung ohne Bestätigung.** Das Anklicken einer Karte ändert nur
  die Auswahl. Verbindlich wird sie über den Button unten und die Rückfrage,
  die Beitragsdifferenz und die gewonnenen wie verlorenen Zugangsrechte
  benennt.

Welcher der drei Fälle vorliegt — Erstwahl, Wechsel oder Rücknahme —
entscheidet `api/mitgliedschaften.php` anhand des gespeicherten Stands, nicht
der Browser. Alles, was aus dem Browser kommt, ist manipulierbar.

## Konsequenzen

**Positiv**

- Der Beitrag lässt sich nicht mehr durch Hin- und Herwechseln umgehen.
- Vor jeder Änderung steht schwarz auf weiß, was sie kostet und welche Rechte
  sie bringt oder nimmt. Das ist der Teil, der beim alten Stand komplett
  fehlte.
- Die Historie wird ehrlicher: Verträge haben jetzt ein echtes Enddatum am
  Monatsletzten statt „beendet am Tag des Klicks".
- Ein Abrechnungslauf könnte später einfach nach Monat gruppieren, weil
  Wechsel auf Monatsgrenzen fallen.

**Negativ**

- Die Abfrage „welcher Vertrag gilt?" ist keine einfache Prüfung auf
  `endet_am IS NULL` mehr. Wer das übersieht, findet vorgemerkte Verträge als
  vermeintlich laufende. Deshalb steht die Bedingung als Kommentar an der
  Tabelle und im Repository.
- Die Seite hat einen Zustand mehr: angeklickt, aber nicht übernommen. Genau
  daher kommt die Rückfrage beim Verlassen der Seite — und die ist eine
  Browser-Rückfrage, deren Wortlaut wir nicht bestimmen können.
- Wer am 2. eines Monats wechseln will, wartet 29 Tage. Fachlich gewollt, am
  Empfang aber erklärungsbedürftig.
- Es gibt keinen Weg, eine Vormerkung nachträglich zu belegen: Sie wird beim
  Ersetzen gelöscht. Für ein Studioprojekt ist das in Ordnung, für ein echtes
  Abrechnungssystem wäre ein Protokoll nötig.

## Verworfene Alternativen

**Sperre bis zum Stichtag.**
Wer einmal gewechselt hat, kann bis zum Monatsersten gar nichts mehr ändern.
Die härteste Barriere und am einfachsten zu implementieren. Verworfen, weil
sie einen Tippfehler bei der Tarifwahl für bis zu vier Wochen einbetoniert
und die Seite dann erklären muss, warum alle Buttons tot sind. Die Barriere
soll gegen Ausnutzen wirken, nicht gegen Versehen.

**Upgrade sofort, Downgrade erst zum Monatsersten.**
Marktüblich und für das Studio finanziell die beste Variante. Verworfen,
weil „Upgrade" ohne eine Rangfolge der Tarife nicht definiert ist: Basisplan
und Kurs-Plan sind weder über- noch untergeordnet, sie enthalten
verschiedene Dinge. Eine Rangfolge künstlich einzuführen, nur um diese Frage
zu beantworten, wäre der falsche Weg herum.

**Sofortiger Wechsel mit anteiliger Abrechnung.**
Die sauberste Lösung, wenn Geld tatsächlich flösse. Setzt aber eine
Abrechnung voraus, die es hier nicht gibt und die als Feature um ein
Vielfaches größer wäre als die Mitgliedschaften selbst.

**Nur eine Bestätigung, ohne Monatsregel.**
Hätte die zweite Beschwerde gelöst (man weiß nicht, was es kostet), aber
nicht die erste: Mit genug Klicks bleibt beliebiges Hin und Her möglich. Die
Rückfrage ist eine Information, keine Grenze.
