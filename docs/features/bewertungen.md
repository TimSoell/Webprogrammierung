# Feature: Bewertungen

**Status:** in Arbeit
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-09-30

## Was kann man damit

Auf der Startseite steht vor „Komm rein." der Abschnitt **„Was andere
sagen."**: drei Bildkacheln wie bei „Dein Programm", jede mit Bild, Sternen,
Text, Name und der Angabe **Mitglied** oder **Kein Mitglied**. Ohne Bild
wird die Kachel zur **Zitat-Kachel**: ein großes Anführungszeichen statt
des Fotos, keine Initialen. Darunter stehen der Durchschnitt und der Button
**„Alle Bewertungen"**.

Der Button öffnet ein Fenster über der Startseite (wie der Kurskalender) mit
dem Durchschnitt und allen Bewertungen. Wer angemeldet ist, vergibt dort über
ein Formular 1 bis 5 Sterne und schreibt einen Text dazu. Ohne Anmeldung
steht an Stelle des Formulars ein Link zur Anmeldung. Pro Konto gibt es eine
Bewertung.

## Entscheidungen (Team, 2026-09-30)

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

## Welche Bewertungen als Kachel erscheinen

Die drei neuesten **mit Bild**, erst danach die ohne. Bilder haben bisher
nur die Beispiele aus `seed.sql` — neue Bewertungen erscheinen deshalb im
Fenster, aber nicht auf der Startseite. Das ändert sich mit den
Profilbildern. Die Zahl steht als `KACHELN` oben in
`assets/js/components/bewertungen.js`.

## Datenform

```json
{
  "id": 6,
  "name": "Lea M.",
  "mitglied": true,
  "sterne": 5,
  "text": "Seit einem halben Jahr im Strength-Programm. …",
  "datum": "2026-09-24",
  "bild": "assets/img/bewertungen/lea-m.jpg"
}
```

- `name` ist Vorname und erster Buchstabe des Nachnamens — die Seite ist
  öffentlich.
- `mitglied` kommt aus der Spalte `war_mitglied`.
- `bild` ist `null` bei Bewertungen echter Konten, bis es Profilbilder
  gibt. Dann zeigt die Startseite eine Zitat-Kachel und das Fenster einen
  kleinen Kreis mit den Initialen — ebenso, wenn die Datei fehlt.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/bewertungen.php` | alle Bewertungen, neueste zuerst | Array von Bewertungen |
| POST | `api/bewertungen.php` | eigene Bewertung, `{ "sterne": "5", "text": "…" }` | `{ "id": 7 }`, 201 |

POST antwortet mit 401 ohne Anmeldung, 400 bei fehlenden Sternen, leerem
oder zu langem Text (über 1000 Zeichen) und 409, wenn das Konto schon
bewertet hat. Name und Mitgliedsstatus ergänzt der Endpunkt selbst.

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

- **Profilbilder** (Feature von Philipp, noch nicht im Repository): Jede
  Person legt ein Profilbild an und bestimmt selbst, ob es freigegeben
  ist. Sobald es das gibt, liefert `BewertungRepository::alleFinden()` für
  Bewertungen mit Konto das freigegebene Profilbild als `bild`. Komponente,
  Service und Seite bleiben dabei unverändert — sie kennen nur `bild`.
- Die sechs KI-Bilder für die Beispiele in `assets/img/bewertungen/`
- Bewertungen erscheinen sofort, ohne Freigabe. Für eine öffentliche
  Domain wäre eine Freigabe durch das Team zu überlegen.
- Eigene Bewertung ändern oder löschen gibt es nicht
