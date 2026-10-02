# ADR-0021 — Ein Gutschein ist ein Gratiszeitraum am Mitglied, kein Vertrag

**Status:** angenommen
**Datum:** 2026-10-02

## Kontext

Mit „Freunde werben" bekommt ein Mitglied für jeden geworbenen Freund einen
Gutschein: drei Monate Basisplan gratis. Die Frage ist, wo „gratis" in der
Datenbank steht.

Die Verträge in `mitgliedschaften` folgen festen Regeln
([ADR-0009](ADR-0009-mitgliedschaften-datenmodell.md),
[ADR-0010](ADR-0010-tarifwechsel-zum-monatsersten.md)): höchstens ein laufender
Vertrag und höchstens ein vorgemerkter, der Preis wird beim Abschluss
eingefroren, ein Wechsel gilt zum nächsten Monatsersten und **löscht** eine
ältere Vormerkung. Darauf bauen `aktiveFinden()`, `geplanteFinden()`,
`wechselVormerken()` und alle Seiten auf, die den Tarifstand zeigen.

Abgerechnet wird im Projekt nichts. `preis_monatlich` ist eine Zahl, die
angezeigt wird, keine Zahlung.

## Entscheidung

**Der Gutschein hält einen Zeitraum am Mitglied fest und fasst keinen Vertrag
an.** Beim Einlösen trägt `GutscheinRepository::einloesen()` in der Zeile des
Gutscheins `gratis_von` und `gratis_bis` ein. In diesem Zeitraum kostet der
Basisplan nichts.

Daraus folgen diese Regeln:

- **`mitgliedschaften` bleibt, wie es ist.** Kein Vertrag mit Preis 0, keine
  neue Spalte. `preis_monatlich` zeigt weiter den eingefrorenen Preis.
- **Ob gerade gratis ist, ergibt sich aus zwei Angaben:** Der Zeitraum deckt
  den Tag, und an dem Tag läuft der Basisplan. `api/mitgliedschaften.php`
  liefert den Zeitraum als `gratis`, die Seite prüft den Tarif.
- **Einlösen geht nur mit Basisplan** – laufend oder zum Monatsersten
  vorgemerkt. Der Zeitraum beginnt heute bzw. am vorgemerkten Start.
- **Mehrere Gutscheine hängen sich hintereinander.** Der nächste beginnt am
  Tag nach dem Ende des vorigen.
- **Der Gutschein gehört dem Werber** (`gutscheine.mitglied_id`). Nur er kann
  ihn einlösen; der Code allein reicht nicht.
- **Ein Gutschein pro Einladung, eine Einladung pro E-Mail.** Beides sichern
  UNIQUE-Schlüssel, nicht der PHP-Code.

## Konsequenzen

**Positiv**

- Tarifwahl, Wechsel, Rücknahme und Kurskalender laufen unverändert. Niemand
  im Team muss eine bestehende Abfrage anpassen.
- Ein Tarifwechsel kann den Gutschein nicht versehentlich vernichten:
  `wechselVormerken()` löscht Vormerkungen, aber keine Gutscheine.
- Die Gratiszeit ist eine Abfrage auf eine Tabelle und lässt sich leicht
  anzeigen.

**Negativ**

- **Die Zeit läuft nach Kalender, nicht nach Nutzung.** Wer während der drei
  Monate vom Basisplan weg wechselt, verliert die Tage, in denen ein anderer
  Tarif läuft. Zurückgewechselt gilt nur noch der Rest.
- **„Gratis" steht an zwei Stellen verteilt:** der Zeitraum im Gutschein, der
  Tarif im Vertrag. Wer den tatsächlichen Monatsbeitrag ausrechnen will, muss
  beides zusammenführen. Für eine echte Abrechnung wäre das zu wenig.
- Der Vertragspreis in „Mein Konto" zeigt weiter den vollen Beitrag. Dass er
  gerade nicht anfällt, steht nur in der Karte „Freunde werben" und auf der
  Mitgliedschaftsseite.

## Verworfene Alternativen

**Ein eigener Vertrag über drei Monate mit Preis 0, danach ein Folgevertrag.**
Bildet „gratis" am ehrlichsten ab. Der Folgevertrag wäre aber ein zweiter
Vertrag in der Zukunft – genau das, was `geplanteFinden()` als vorgemerkten
Wechsel anzeigt und `wechselVormerken()` beim nächsten Wechsel löscht. Fast
jede Methode in `MitgliedschaftRepository` hätte angepasst werden müssen.

**Eine Spalte `gratis_bis` am Vertrag.**
Weniger Tabellen. Aber eine Vormerkung wird beim nächsten Wechsel gelöscht:
Wer den Gutschein auf einen vorgemerkten Basisplan einlöst und danach noch
einmal wechselt, hätte ihn verbraucht und nichts davon gehabt.

**Den Gutschein an den Code binden statt an das Mitglied.**
Dann könnte man ihn verschenken. Aber jeder, der einen Code errät oder
mitliest, könnte ihn einlösen, und der Werber stünde ohne da.
