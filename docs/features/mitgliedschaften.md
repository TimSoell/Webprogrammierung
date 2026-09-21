# Feature: Mitgliedschaften

**Status:** in Arbeit
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-09-21

## Was kann man damit

Auf der Seite „Mitgliedschaft" sieht man die vier Tarife des Studios
nebeneinander: Basisplan, Wellness-Plan, Kurs-Plan und Premium. Zu jedem
steht, was er kostet und was man damit darf. Über eine Auswahl oben schaltet
man zwischen den drei Preisgruppen um — Standard, ermäßigt (Schülerinnen,
Schüler und Studierende) und Senioren.

Wer angemeldet ist, klickt sich einen Tarif zusammen. **Das ändert noch
nichts.** Verbindlich wird die Auswahl erst über den Button unten und die
Rückfrage, die auftauchen: Sie zeigt den neuen Beitrag, den Unterschied zum
bisherigen und welche Zugangsrechte dazukommen oder wegfallen. Wer die Seite
mit einer offenen Auswahl verlässt, wird vom Browser gefragt.

Unter „Mein Konto" steht danach, welcher Tarif läuft, zu welchem Preis, seit
wann — und ob ein Wechsel vorgemerkt ist.

## Die Regel

**Die erste Tarifwahl gilt sofort. Jeder spätere Wechsel gilt erst ab dem
ersten Tag des nächsten Monats.**

Sonst könnte man an einem Nachmittag zwanzigmal zwischen Basisplan und
Premium wechseln, die Sauna benutzen und den günstigen Beitrag zahlen.
Begründung und verworfene Alternativen:
[ADR-0007](../decisions/ADR-0007-tarifwechsel-zum-monatsersten.md).

Ein vorgemerkter Wechsel ist bis zum Stichtag ersetzbar. Wer zurück auf den
laufenden Tarif klickt und bestätigt, nimmt die Vormerkung zurück.

Die Preisgruppe zu wechseln zählt als Wechsel: Sie steht im Vertrag und
bestimmt den Beitrag. Für die beiden ermäßigten Preisgruppen braucht es
zusätzlich einen gültigen Nachweis — siehe [Nachweise](nachweise.md). Läuft
der ab, stellt das System den Vertrag zum nächsten Monatsersten auf den
Standardpreis um.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `mitgliedschaft.php`, `mein-konto.php` |
| 2 Seitenskript | `assets/js/pages/mitgliedschaft.page.js`, `assets/js/pages/mein-konto.page.js` |
| 2 Komponente | `assets/js/components/modal.js` (für die Rückfrage mitbenutzt) |
| 3 Service | `assets/js/services/tarife.js`, `assets/js/services/mitgliedschaften.js` |
| 4 Endpunkt | `api/tarife.php`, `api/mitgliedschaften.php` |
| 5 Repository | `src/Repositories/TarifRepository.php`, `src/Repositories/MitgliedschaftRepository.php` |
| 6 Tabelle | `tarife`, `mitgliedschaften` in `database/schema.sql` |
| CSS | `assets/css/components/tarifkarte.css` |

## Die zwei Tabellen

Der Unterschied ist der wichtigste Punkt an diesem Feature:

- **`tarife` ist der Katalog.** Was das Studio anbietet und was es kostet.
- **`mitgliedschaften` ist der Vertrag.** Wer hat wann welchen Tarif zu
  welchem Preis.

Der Preis steht **in beiden**. Das ist Absicht und keine vergessene
Normalisierung: `mitgliedschaften.preis_monatlich` ist eine Kopie aus dem
Katalog zum Zeitpunkt des Abschlusses. Ändert das Studio später seine Preise,
bleiben laufende Verträge davon unberührt. Begründung in
[ADR-0006](../decisions/ADR-0006-mitgliedschaften-datenmodell.md).

### Die drei Zustände einer Vertragszeile

Beide Datumsspalten sind **einschließlich** gemeint: `beginnt_am` ist der
erste, `endet_am` der letzte Gültigkeitstag.

| Zustand | Bedingung |
|---|---|
| laufend | `beginnt_am <= heute AND (endet_am IS NULL OR endet_am >= heute)` |
| vorgemerkt | `beginnt_am > heute` |
| vorbei | `endet_am < heute` |

**Nur auf `endet_am IS NULL` zu prüfen ist falsch** — auch ein vorgemerkter
Vertrag hat kein Ende. Das ist die Falle an diesem Datenmodell.

Ein Wechsel sieht in der Tabelle so aus (Beispiel, Wechsel am 21.09.):

```
id  kennung   preisgruppe  preis   beginnt_am  endet_am
1   wellness  standard     44.90   2026-09-21  2026-09-30   <- befristet
2   premium   senior       49.90   2026-10-01  NULL         <- vorgemerkt
```

## Was die Tarife erlauben

| Tarif (`kennung`) | Geräte | Wellness | Kurse |
|---|:---:|:---:|:---:|
| Basisplan (`basis`) | ✓ | | |
| Wellness-Plan (`wellness`) | ✓ | ✓ | |
| Kurs-Plan (`kurse`) | | | ✓ |
| Premium (`premium`) | ✓ | ✓ | ✓ |

Wer Geräte **und** Kurse will, nimmt Premium — der Kurs-Plan ist bewusst nur
für Leute, die ausschließlich Kurse besuchen.

Die drei Rechte stehen als eigene Spalten in `tarife`
(`zugang_geraete`, `zugang_wellness`, `zugang_kurse`), **nicht** als
Fallunterscheidung über den Tarifnamen. Ein späterer Kursplan fragt also
`zugang_kurse` ab und nicht `kennung === 'premium' || kennung === 'kurse'`.
Aus denselben Spalten baut das Bestätigungsfenster die Listen „Das kommt
dazu" und „Das fällt weg".

## Datenform

Ein Tarif, wie ihn `services/tarife.js` liefert:

```json
{
  "kennung": "wellness",
  "name": "Wellness-Plan",
  "beschreibung": "Geräte, Sauna und Sonnenbank.",
  "preise": { "standard": 44.90, "ermaessigt": 34.90, "senior": 37.90 },
  "zugang": { "geraete": true, "wellness": true, "kurse": false }
}
```

Der Tarifstand, wie ihn `services/mitgliedschaften.js` liefert:

```json
{
  "mitgliedschaft": { "tarif": "wellness", "name": "Wellness-Plan", "preisgruppe": "standard",
                      "preisMonatlich": 44.90, "beginntAm": "2026-09-21", "endetAm": "2026-09-30",
                      "zugang": { "geraete": true, "wellness": true, "kurse": false } },
  "geplant":        { "tarif": "premium", "name": "Premium", "beginntAm": "2026-10-01", "…": "…" },
  "wechselAb":      "2026-10-01"
}
```

`mitgliedschaft` und `geplant` sind einzeln `null`, wenn es sie nicht gibt.
Kein Tarif gewählt zu haben ist kein Fehler: Man registriert sich zuerst und
wählt später.

`wechselAb` kommt vom Server und nicht aus dem Browser, damit die angezeigte
und die gespeicherte Datumsangabe garantiert übereinstimmen.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/tarife.php` | Katalog, öffentlich | `{ "tarife": [ ... ] }` |
| GET | `api/mitgliedschaften.php` | eigener Tarifstand | Tarifstand |
| POST | `api/mitgliedschaften.php` | Auswahl übernehmen | Tarifstand danach |

Die beiden Mitgliedschafts-Aufrufe verlangen eine Anmeldung und antworten
sonst mit 401.

Ein POST bedeutet je nach Lage etwas anderes. **Welcher Fall vorliegt,
entscheidet der Server**, nicht der Browser:

| Ausgangslage | Ergebnis | Status |
|---|---|---|
| kein Vertrag | Erstwahl, gilt ab heute | 201 |
| anderer Tarif oder andere Preisgruppe gewählt | Wechsel zum Monatsersten vorgemerkt | 201 |
| laufender Tarif gewählt, Vormerkung vorhanden | Vormerkung zurückgenommen | 200 |
| laufender Tarif gewählt, keine Vormerkung | nichts zu tun | 409 |

## Woher kommen die Daten aktuell

Aus MySQL. Die vier Tarife und ihre zwölf Preise stehen in
`database/seed.sql` und müssen einmal eingespielt werden — ohne das ist die
Seite leer.

## Was fehlt noch

- **Kündigen** gibt es nicht. Ein Wechsel beendet den alten Vertrag, aber
  „ganz aufhören" ist kein Anwendungsfall, den die Oberfläche anbietet.
- ~~Der Nachweis für ermäßigte Preise wird nicht geprüft.~~ **Erledigt:**
  Ermäßigte Preisgruppen sind ohne gültigen Nachweis gesperrt, der Endpunkt
  antwortet mit 403. Siehe [Nachweise](nachweise.md) und
  [ADR-0008](../decisions/ADR-0008-ausweispruefung-mit-ki.md).
- **Es wird nichts abgerechnet.** `preis_monatlich` ist eine Zahl, keine
  Zahlung. Zahlungen wären ein eigenes Feature mit eigener Tabelle.
- **Die Rückfrage beim Verlassen der Seite ist die des Browsers.** Ihren
  Wortlaut können wir nicht bestimmen, das erlauben Browser seit Jahren nicht
  mehr. Sie erscheint außerdem nur, wenn auf der Seite vorher geklickt wurde.
- Die Zugangsrechte werden noch von niemandem abgefragt. Das passiert erst,
  wenn es Kurse (`zugang_kurse`) oder einen Wellness-Bereich gibt.
- **Eine verworfene Vormerkung ist nicht nachvollziehbar** — sie wird beim
  Ersetzen gelöscht. Für ein Abrechnungssystem bräuchte es ein Protokoll.
