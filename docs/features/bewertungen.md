# Feature: Bewertungen

**Status:** fertig
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-10-01

## Was kann man damit

Auf der Startseite steht vor „Komm rein." der Abschnitt **„Was andere
sagen."**: ein **Karussell** aus dunklen Kacheln, jede mit einem großen
Anführungszeichen, Sternen, Text, Name und der Angabe **Mitglied** oder
**Kein Mitglied**. Drei Kacheln sind zu sehen (auf dem Handy eine). Die Pfeile
stehen am Rechner links und rechts neben den Kacheln, so hoch wie diese und
mit demselben Abstand wie die Kacheln untereinander; auf dem Handy stehen
sie klein über den Kacheln. Sie sind in Glas-Optik fast unsichtbar (leicht
gerundete Ecken wie die Balken der Auslastung) und werden beim Überfahren
mit der Maus gelb mit schwarzem Pfeil. Auf Geräten ohne Maus sind sie von
Anfang an deutlicher zu sehen. Sie drehen um
eine Kachel weiter, am Ende geht es von vorn los.
Wischen und Touchpad funktionieren ebenfalls. Ein Bild steht **klein und
rund vor dem Namen** (56 px, so groß wie in „Mein Konto" und im Fenster) –
es füllt nicht die Kachel. Ohne Bild steht dort nur der Name, keine
Initialen. Darunter stehen der Durchschnitt und der Button
**„Alle Bewertungen"**.

Der Button öffnet ein Fenster über der Startseite (wie der Kurskalender) mit
dem Durchschnitt und allen Bewertungen. Wer angemeldet ist, vergibt dort über
ein Formular 1 bis 5 Sterne und schreibt einen Text dazu. Ohne Anmeldung
steht an Stelle des Formulars ein Link zur Anmeldung.

Pro Konto gibt es eine Bewertung. Wer schon bewertet hat, sieht im Fenster
statt des Formulars **„Deine Bewertung"** mit dem Link **„Bewertung
löschen"**. Nach dem Löschen (mit Rückfrage) ist das Formular wieder da.

## Entscheidungen (Team, 2026-09-30 und 2026-10-01)

- **Bilder klein und rund, nicht über die ganze Kachel.** Zuerst füllte das
  Bild die Kachel wie bei „Dein Programm". Mit echten Profilbildern war das
  dem Team zu groß (2026-10-01).
- **Keine Freigabe durch das Team.** Bewertungen und freigegebene
  Profilbilder erscheinen sofort. Eine Freigabe bräuchte eine eigene
  Verwaltungsseite samt Rollen – für die Studienarbeit bewusst weggelassen.
  Für einen echten Betrieb wäre das der erste Nachtrag.
- **Löschen statt Ändern.** Wer seine Bewertung ändern will, löscht sie und
  schreibt eine neue. Datum und Mitgliedsstatus gelten dann für die neue.

- **Kein Menüpunkt und keine eigene Seite.** Die Bewertungen stehen auf der
  Startseite, alle weiteren im Fenster.
- **Bewerten nur mit Konto.** Ein Konto hat jede Person, die sich
  registriert — auch ohne Vertrag.
- **Mitglied heißt: ein aktiver Vertrag.** Der Vertrag hat begonnen und ist
  nicht abgelaufen — dieselbe Bedingung wie in
  `MitgliedschaftRepository::aktiveFinden()`. Wer nur ein Konto hat, ist
  kein Mitglied.
- **Der Status gilt beim Schreiben.** `api/bewertungen.php` prüft ihn einmal
  und speichert ihn in `bewertungen.war_mitglied`. Kündigt jemand später,
  bleibt sichtbar, dass die Bewertung als Mitglied entstand.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `index.php` — Abschnitt `#bewertungen` und Fenster `#bewertungen-modal` |
| 2 Seitenskript | `assets/js/pages/index.page.js` ruft `initBewertungen()` auf |
| 2 Komponente | `assets/js/components/bewertungen.js` — Kacheln, Fenster, Formular |
| 3 Service | `assets/js/services/bewertungen.js` |
| 4 Endpunkt | `api/bewertungen.php` |
| 5 Repository | `src/Repositories/BewertungRepository.php` |
| 6 Tabelle | `bewertungen` in `database/schema.sql`, Beispiele in `database/seed.sql` |
| CSS | `assets/css/components/bewertungen.css` |
| Bilder | `assets/img/bewertungen/`, siehe die [README dort](../../assets/img/bewertungen/README.md) |

## Das Karussell

Im Karussell stehen **alle** Bewertungen: zuerst die mit Bild, danach die
ohne, jeweils die neueste zuerst. Ein Bild haben die Beispiele aus
`seed.sql` und alle, deren Verfasser ein freigegebenes Profilbild haben
(siehe [Profilbilder](profilbilder.md)).

Technisch ist es eine quer scrollende Liste mit `scroll-snap` in
`bewertungen.css`; Wischen kommt dadurch vom Browser. Die Pfeile erscheinen
nur, wenn nicht alle Kacheln auf einmal zu sehen sind. Es dreht sich nicht
von selbst — nur auf Klick oder Wischen, damit niemandem der Text beim
Lesen davonläuft.

## Datenform

```json
{
  "id": 6,
  "name": "Lea M.",
  "mitglied": true,
  "sterne": 5,
  "text": "Seit einem halben Jahr im Strength-Programm. …",
  "datum": "2026-09-24",
  "bild": null,
  "eigene": false
}
```

- `name` ist Vorname und erster Buchstabe des Nachnamens — die Seite ist
  öffentlich.
- `mitglied` kommt aus der Spalte `war_mitglied`.
- `bild` ist bei Bewertungen echter Konten die Adresse des freigegebenen
  Profilbilds (`api/profilbilder.php?mitglied=…&v=…`), sonst `null`. Bei
  den Beispielen steht hier der Pfad aus der Spalte `bild`, sobald das Team
  die KI-Bilder geliefert hat. Ohne Bild steht auf der Kachel nur der Name
  und im Fenster ein kleiner Kreis mit den Initialen — ebenso, wenn die
  Datei fehlt.
- `eigene` ist `true` bei der Bewertung der angemeldeten Person, ohne
  Anmeldung immer `false`. Daran entscheidet die Seite, ob sie das Formular
  oder „Deine Bewertung" zeigt.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/bewertungen.php` | alle Bewertungen, neueste zuerst | Array von Bewertungen |
| POST | `api/bewertungen.php` | eigene Bewertung, `{ "sterne": "5", "text": "…" }` | `{ "id": 7 }`, 201 |
| DELETE | `api/bewertungen.php` | eigene Bewertung löschen | `{ "geloescht": true }` |

POST antwortet mit 401 ohne Anmeldung, 400 bei fehlenden Sternen, leerem
oder zu langem Text (über 1000 Zeichen) und 409, wenn das Konto schon
bewertet hat. Name und Mitgliedsstatus ergänzt der Endpunkt selbst.

DELETE antwortet mit 401 ohne Anmeldung und 404, wenn das Konto keine
Bewertung hat. Welche Bewertung gelöscht wird, bestimmt die Sitzung – eine
fremde zu löschen ist nicht möglich.

## Woher kommen die Daten aktuell

**PostgreSQL**, Tabelle `bewertungen`. Die sechs Beispielbewertungen kommen
aus `database/seed.sql` und haben keine `mitglied_id`. Nur sie löscht
`seed.sql` beim erneuten Einspielen — Bewertungen echter Konten bleiben.

Tabelle und Beispiele stehen seit 2026-09-30 in `schwitzkasten-dev` und in
der Produktionsdatenbank. Eingespielt wurde jeweils nur der Abschnitt
„FEATURE BEWERTUNGEN" aus `schema.sql` (samt der RLS-Zeile) und aus
`seed.sql`. Fehlt die Tabelle, zeigt die Startseite an der Stelle nur
„Die Bewertungen sind gerade nicht abrufbar."

## Was fehlt noch

- Die sechs KI-Bilder für die Beispiele (Team). Bis dahin steht in der
  Spalte `bild` der Beispiele `NULL`, damit die Seite keine fehlenden
  Dateien sucht. Wie die Bilder eingetragen werden, steht in
  `assets/img/bewertungen/README.md`.
