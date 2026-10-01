# Feature: Nachweise für ermäßigte Preise

**Status:** in Arbeit
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-10-01

## Was kann man damit

Unter „Mein Konto" gibt es den Bereich **Nachweise**. Wer den ermäßigten
Preis für Schülerinnen, Schüler und Studierende oder den Seniorenpreis
nutzen will, fotografiert dort seinen Ausweis und lädt ihn hoch. Das Bild
wird ausgelesen, das Ergebnis gespeichert und **das Bild sofort verworfen**.
Der Ausweis muss auf die Person lauten, der das Konto gehört, und es gilt
immer nur ein Nachweis — siehe „Die Regeln".

Danach steht die passende Preisgruppe auf der Tarifseite zur Verfügung.
Ohne Nachweis ist die Preisgruppe abgedunkelt und gestrichelt umrandet: Man
sieht, was der Preis wäre, und darf die Gruppe auch anklicken — buchen lässt
sie sich aber nicht. Solange die gesperrte Gruppe gewählt ist, sind alle
vier Tarifkarten und der Button unten grau und gestrichelt statt
limefarben, und auf den Karten steht „Nachweis fehlt" statt „Auswählen".
Zwischen den Preisgruppen lässt sich frei hin- und herschalten. Erst wer
dann einen Tarif anklickt, bekommt das Fenster „Nachweis fehlt" mit einem
Knopf, der direkt zu „Mein Konto" führt. Die Aktionsleiste nennt den Grund
weiterhin im Klartext — limefarben, damit der Satz zwischen den grauen
Karten auffällt.

Damit lässt sich die Mitgliedschaft vollständig online abschließen — bei
anderen Studios muss man mit dem Ausweis an den Tresen. Das ist der Punkt
des Features.

## Die Regeln

| | |
|---|---|
| Name auf dem Ausweis | muss zum Namen im Konto passen |
| Schüler- und Studierendenausweis | gilt bis zum aufgedruckten Ablaufdatum, höchstens aber ein Jahr im Voraus (Schüler: zwei) |
| Senior | ab 65 Jahren, gilt unbefristet |
| Standardpreis | braucht nie einen Nachweis |
| Anzahl | **höchstens ein gültiger Nachweis** je Mitglied, ein neuer ersetzt den bisherigen |
| Neuer Nachweis derselben Art | nur, wenn er **länger** gilt als der vorhandene |
| Zweiter Seniorennachweis | gar nicht - der erste gilt unbefristet |
| Prüfversuche | drei, danach kommt alle acht Stunden einer dazu |

Die Gründe stehen in
[ADR-0020](../decisions/ADR-0020-nachweise-absichern.md).

**Der Namensabgleich.** Ohne ihn könnte ein Vater den Schülerausweis seines
Sohnes hochladen. Das Modell liest Vor- und Nachname, verglichen wird in
`api/nachweise.php`: Der Nachname muss gleich sein, der Vorname aus dem
Konto muss unter den Vornamen auf dem Ausweis stehen — dort stehen oft
mehrere, im Konto meist nur der Rufname. Groß- und Kleinschreibung, Umlaute
(`Müller` = `MUELLER`), Akzente und Bindestriche zählen nicht. Passt der
Name nicht oder ist keiner zu lesen, antwortet der Endpunkt mit **422**.

**Ein Nachweis je Mitglied.** Senior und Schüler zugleich gibt es nicht.
Wird ein neuer Nachweis gespeichert, löscht
`NachweisRepository::anlegen()` den bisher gültigen in derselben
Transaktion. Wer vom Schüler zum Studenten wird, lädt also einfach den
neuen Ausweis hoch. Abgelaufene Nachweise bleiben als Historie stehen.

Denselben Ausweis noch einmal hochzuladen lehnt der Endpunkt mit **409** ab:
Ein weiterer Nachweis derselben Art wird nur gespeichert, wenn sein
Ablaufdatum nach dem des vorhandenen liegt. Beim Senior greift die Sperre
schon, **bevor** das Bild an die Prüfung geht: Ein unbefristeter Nachweis
lässt sich durch nichts verbessern, also wird kein Kontingent der
kostenlosen Stufe dafür verbraucht.

**Die Höchstdauer.** Steht auf einem Studierendenausweis „gültig bis 2099",
gilt der Nachweis trotzdem nur ein Jahr ab heute, danach ist ein neuer
Upload fällig. Das fängt verlesene Jahreszahlen ab und Ausweise, die für
das ganze Studium ausgestellt sind. Gekürzt wird, nicht abgelehnt.

**Die Prüfversuche.** Jede Prüfung kostet eine Anfrage aus dem
Tageskontingent der kostenlosen Stufe. Nach drei Versuchen antwortet der
Endpunkt mit **429**. Gezählt wird mit der Drosselung der Login-Bibliothek —
wie beim Login ist sie mit `debug = true` aus, lokal gibt es also keine
Grenze.

**Entfernen.** Jeder Eintrag in der Liste hat einen Knopf „Entfernen", mit
Rückfrage. Wer einen gültigen Nachweis entfernt und einen ermäßigten Tarif
hat, zahlt ab dem nächsten Monatsersten den Standardpreis — derselbe Weg
wie beim Ablauf, siehe unten.

Im Konto steht unter der Auswahl, was ein Upload mit dem schon hinterlegten
Nachweis machen würde. Beim Senior ist der Button zusätzlich gesperrt. Das
ist Bequemlichkeit, keine Absicherung — verbindlich prüft der Endpunkt.

**Läuft ein Nachweis ab, wird der Vertrag zum nächsten Monatsersten auf den
Standardpreis gestellt** — nach derselben Regel wie jeder Tarifwechsel
([ADR-0010](../decisions/ADR-0010-tarifwechsel-zum-monatsersten.md)). Den
laufenden Monat behält man zum ermäßigten Preis.

Ein Seniorennachweis läuft nie ab: Wer einmal 65 ist, bleibt es.

## Was gespeichert wird — und was nicht

Das ist der wichtigste Abschnitt dieser Datei.

**Gespeichert wird:** Art des Nachweises, Gültigkeitsdatum, ob per KI oder im
Demo-Modus geprüft, welches Modell geprüft hat, und ein Satz, was geprüft
wurde.

**Nicht gespeichert wird:** das Bild, ein Pfad zu einem Bild, der Name,
die Ausweisnummer, das Geburtsdatum. Es gibt keinen Upload-Ordner. Das Bild
existiert nur für die Dauer einer Anfrage im Arbeitsspeicher.

Für das Alter heißt das: Das Geburtsdatum wird einmal gelesen, gegen 65
geprüft und verworfen. In der Tabelle steht danach nur noch
`art = 'senior'` mit `gueltig_bis = NULL`. Für den Namen gilt dasselbe: Er
wird gelesen, mit dem Konto verglichen und verworfen. Der Name aus dem
Konto geht dabei nicht an das Modell.

**Den gespeicherten Satz baut der Endpunkt selbst.** Das Modell liefert
keinen freien Text — sonst stünden Name oder Geburtsdatum darin, und die
Zusage oben wäre falsch.

**Ausweisdaten verlassen für die Prüfung den Server.** Zum Testen und
Vorführen werden ausschließlich erfundene Ausweise benutzt, keine echten.

## Zwei Betriebsarten

| | Wenn `ki.api_key` in `config/config.php` gesetzt ist | Wenn nicht |
|---|---|---|
| Formular | Feld für ein Foto | Feld für ein Datum |
| Prüfung | Modell liest Name und Datum vom Bild | Datum wird eingetippt |
| Namensabgleich, Prüfversuche | ja | nein — ohne Bild gibt es keinen Namen |
| `quelle` in der Tabelle | `'ki'` | `'demo'` |

Geprüft wird über die **Gemini-API von Google** auf der kostenlosen Stufe,
Modell einstellbar über `ki.modell` in `config/config.php`
([ADR-0012](../decisions/ADR-0012-gemini-statt-claude.md)).

> **Nur erfundene Ausweise hochladen.** Auf der kostenlosen Stufe darf Google
> die Eingaben zur Produktverbesserung verwenden, und menschliche Prüfer
> dürfen sie lesen. Für Testdokumente ist das unproblematisch, für echte
> Ausweise nicht — auch nicht für die eigenen.

Die kostenlose Stufe hat außerdem Kontingente pro Minute und pro Tag. Sind
sie erschöpft, antwortet die API mit 429 und das Hochladen scheitert mit
„Die Prüfung war gerade nicht erreichbar". Bei einer Vorführung mit mehreren
Uploads hintereinander ist das ein realistischer Fall.

Der Demo-Modus ist kein Notbehelf: Er hält das Projekt für alle im Team
lauffähig, die keinen Schlüssel haben, und für die Vorführung ohne Internet.
Dass er unsicher ist — jede Person kann sich ihr Datum aussuchen — ist der
Grund, warum er **nur** ohne hinterlegten Schlüssel greift und warum
`quelle` das in der Datenbank festhält.

## Was man während der Prüfung sieht

Die KI-Prüfung dauert spürbar - je nach Auslastung einige Sekunden. Damit in
dieser Zeit nicht nur ein gesperrter Button zu sehen ist, legt sich eine
Animation über die Seite: das gerade gewählte Foto, ein durchlaufender
Scanstrahl, ein Fortschrittswert und am Ende ein Haken mit Konfetti.

**Die Prozentzahl ist eine Schätzung, keine Messung.** Der Upload ist eine
einzige Anfrage an `api/nachweise.php`; dabei entsteht kein Zwischenstand,
den man anzeigen könnte. Der Wert wächst deshalb gebremst gegen 95 % und
springt erst auf 100 %, wenn die Antwort wirklich da ist. Damit kann die
Anzeige einen Abschluss nie vortäuschen - sie wirkt höchstens langsamer als
die Wirklichkeit, nie schneller.

**Das Bild verlässt den Browser nicht wegen der Animation.** Angezeigt wird
es über eine lokale Objekt-URL aus der gewählten Datei, die beim Ausblenden
wieder freigegeben wird. Die Zusagen im Abschnitt „Was gespeichert wird"
gelten unverändert.

Im Demo-Modus erscheint die Animation nicht: Dort ist die Antwort sofort da,
ein Scanner wäre reine Behauptung.

Wer im Betriebssystem „Bewegung reduzieren" eingestellt hat, bekommt
dieselbe Anzeige ohne wanderndes Licht und ohne Konfetti.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `mein-konto.php`, `mitgliedschaft.php` |
| 2 Seitenskript | `assets/js/pages/mein-konto.page.js`, `assets/js/pages/mitgliedschaft.page.js` |
| 2 Komponente | `assets/js/components/ausweis-scan.js` (Animation während der Prüfung), `assets/js/components/modal.js` (Fenster „Nachweis fehlt") |
| Hilfsmittel | `assets/js/lib/bild.js` (verkleinert das Foto vor dem Upload) |
| 3 Service | `assets/js/services/nachweise.js` |
| 4 Endpunkt | `api/nachweise.php` |
| Infrastruktur | `src/Ausweispruefung.php` (einzige Stelle mit einem KI-Aufruf) |
| 5 Repository | `src/Repositories/NachweisRepository.php` |
| 6 Tabelle | `nachweise` in `database/schema.sql` |
| CSS | `assets/css/components/auth.css`, `assets/css/components/tarifkarte.css`, `assets/css/components/ausweis-scan.css` |

## Datenform

```json
{
  "nachweise": [
    { "id": 7, "art": "student", "gueltigBis": "2027-03-31", "quelle": "ki",
      "hinweis": "Studierendenausweis geprüft: gültig bis 31.03.2027, Name stimmt mit dem Konto überein.",
      "geprueftAm": "2026-10-01 14:37:33" },
    { "id": 3, "art": "schueler", "gueltigBis": "2026-07-31", "quelle": "ki",
      "hinweis": "Schülerausweis geprüft: gültig bis 31.07.2026, Name stimmt mit dem Konto überein.",
      "geprueftAm": "2025-09-12 09:15:02" }
  ],
  "kiVerfuegbar": true
}
```

Höchstens einer der Einträge ist gültig, die übrigen sind abgelaufen.
`gueltigBis: null` heißt unbefristet und kommt nur bei `art: "senior"` vor.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/nachweise.php` | eigene Nachweise, auch abgelaufene | Nachweisstand |
| POST | `api/nachweise.php` | Bild prüfen lassen bzw. Datum eintragen, ersetzt den bisherigen Nachweis | 201, Nachweisstand |
| DELETE | `api/nachweise.php` | eigenen Nachweis entfernen, `{ id }` | 200, Nachweisstand |

Fehlerfälle des POST, alle mit deutscher Meldung für das Formular:

| Code | Wann |
|---|---|
| 400 | kein Bild, falsches Format, größer als 3 MB |
| 409 | es liegt schon ein gleich guter Nachweis dieser Art vor |
| 422 | Dokument passt nicht zur gewählten Art · kein Name lesbar · Name passt nicht zum Konto · kein Datum lesbar · Ausweis abgelaufen · noch keine 65 |
| 429 | Prüfversuche aufgebraucht |
| 502 | Prüfdienst nicht erreichbar |

Der Upload geht **als base64 im JSON**, nicht als `multipart/form-data` —
sonst müsste der CSRF-Schutz aus `src/Api.php` für diesen Endpunkt
ausgehebelt werden.

## Wo die Sperre wirklich sitzt

In `api/mitgliedschaften.php`. Wer eine ermäßigte Preisgruppe ohne gültigen
Nachweis buchen will, bekommt **403**. Die Tarifseite sperrt die Auswahl
zusätzlich und erklärt, was fehlt — das ist Bequemlichkeit, keine
Sicherheit. Alles, was aus dem Browser kommt, ist manipulierbar.

## Was fehlt noch

- **Fälschungen werden nicht erkannt.** Das Modell liest ab, was dasteht.
  Ein sauber gebautes Falsifikat kommt durch — auch eines mit geändertem
  Namen.
- **Der Name im Konto ist eine Selbstauskunft.** Wer ein Konto auf den
  Namen des Ausweisinhabers anlegt, kommt durch den Namensabgleich. Derselbe
  Ausweis funktioniert auch in mehreren Konten mit gleichem Namen. Sicher
  würde das erst mit einer Kontrolle am Empfang.
- **Der Name im Konto lässt sich nicht ändern.** Wer sich als „Max"
  registriert hat und „Maximilian" heißt, scheitert am Namensabgleich.
- **Name und Datum müssen auf derselben Seite des Ausweises stehen.**
  Hochgeladen wird ein Bild.
- **Im Demo-Modus greift keine dieser Prüfungen.** Jede Person tippt ihr
  Datum selbst ein. Laut Migrationsplan (Entscheidung E5 in
  [`migration-vercel-supabase.md`](../migration-vercel-supabase.md)) läuft
  die Produktion bewusst ohne Schlüssel, also im Demo-Modus — damit keine
  echten Ausweise Fremder bei Google landen.
- **Eine vorgemerkte Herabstufung bleibt stehen.** Ist der Wechsel auf den
  Standardpreis einmal vorgemerkt (Nachweis abgelaufen oder entfernt), nimmt
  ein danach hochgeladener Nachweis ihn nicht zurück. Man muss den Tarif auf
  der Tarifseite noch einmal wählen.
- **Niemand kann eine Prüfung überstimmen.** Liest das Modell ein Datum
  falsch, hilft nur ein neuer Upload oder ein Eingriff in der Datenbank.
  Ein Admin-Bereich wäre ein eigenes Feature.
- **Die Herabstufung läuft bei jedem Aufruf mit**, statt einmal nachts.
  Ohne Cronjob ist das der einzige Weg — siehe
  [ADR-0011](../decisions/ADR-0011-ausweispruefung-mit-ki.md), Konsequenzen.
- Es gibt keine Erinnerung, bevor ein Nachweis ausläuft. Man sieht das
  Datum im Konto, aber es schreibt niemand eine E-Mail.
