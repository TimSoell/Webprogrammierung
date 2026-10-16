# Feature: Empfang

**Status:** in Arbeit
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-10-16

## Was kann man damit

Das Personal am Empfang sieht auf einer eigenen Seite, wer einen Nachweis
für einen ermäßigten Preis hochgeladen hat und den Ausweis noch einmal
zeigen muss. Steht die Person vor ihm, vergleicht es Foto und Name auf dem
Ausweis mit ihr und mit dem Namen in der Liste und klickt **„Bestätigen"**
oder **„Ablehnen"**.

Bestätigt wird jede Person **einmal**. Danach ist ihr Name für immer fest,
und spätere Nachweise — etwa der Ausweis fürs nächste Semester — prüft
allein die Ausweisprüfung. Warum: [ADR-0023](../decisions/ADR-0023-name-aendern-und-ausweis-am-empfang.md).

Mitglieder sehen von alldem nur ihren Stand unter „Mein Konto": bis wann sie
den Ausweis zeigen müssen, ob die Frist verpasst ist, oder dass ihr Name
bestätigt ist.

## So entsteht ein Konto für das Personal

1. Die Person **registriert sich ganz normal** auf der Website, wie jedes
   Mitglied. Ein eigenes Login gibt es nicht.
2. Jemand mit Zugang zum **Supabase-Dashboard** gibt diesem Konto die Rolle
   `EMPLOYEE` der Login-Bibliothek (SQL unten). Zugang zum Dashboard hat nur
   das Team, nicht die Mitglieder.

Auf der Website gibt es dafür **keinen Knopf und kein Formular**. Ein
Mitglied kann sich die Rolle also nicht selbst geben, und niemand kann sie
über die Seite vergeben.

```sql
-- Rolle vergeben (2048 = Role::EMPLOYEE der Bibliothek)
UPDATE users SET roles_mask = roles_mask | 2048 WHERE email = 'name@beispiel.de';

-- Rolle wieder wegnehmen
UPDATE users SET roles_mask = roles_mask & ~2048 WHERE email = 'name@beispiel.de';
```

Die Bibliothek gleicht die Rolle alle fünf Minuten mit der Datenbank ab. Sie
wirkt also nach dem nächsten Anmelden sofort, sonst spätestens nach fünf
Minuten. Das ist die einzige Stelle, an der `users` von Hand geschrieben
wird — im Code passiert das weiterhin nur über `src/Auth.php`.

## So kommt das Personal auf die Seite

**Kein Link auf der Website führt zum Empfang** — kein Menüpunkt, kein Knopf
in „Mein Konto". Das Personal ruft die Adresse direkt auf, am besten als
Lesezeichen am Rechner des Empfangs:

| | |
|---|---|
| Live | `https://schwitzkasten-gym.de/empfang.php` |
| Lokal | `http://localhost:8000/empfang.php` |

Wer dort nicht angemeldet ist, landet auf der Anmeldung. Nach dem Anmelden
geht es wie immer nach „Mein Konto" — die Adresse dann noch einmal aufrufen.

Geheim ist die Adresse nicht, und sie muss es auch nicht sein: Geschützt ist
die Seite über die Rolle. Ein Mitglied, das sie eintippt, kommt zurück in
sein Konto (`Auth::nurFuerPersonal()`), und `api/empfang.php` antwortet mit
**403**. Die Sperre sitzt auf dem Server.

## Die Regeln

| | |
|---|---|
| Wer bestätigt | nur das Personal, und nie sich selbst |
| Was bestätigt wird | die Person: Ausweis gesehen, Foto und Name passen zu ihr und zum Konto |
| Wie oft | einmal je Person, für immer |
| Frist | 14 Tage ab dem **ersten** Nachweis, beginnt nie neu |
| Bis zur Bestätigung | Preis gilt, solange die Frist läuft |
| Frist verpasst | kein ermäßigter Preis, bis das Personal bestätigt; ein laufender Vertrag wird zum Monatsersten herabgestuft |
| Bestätigen nach verpasster Frist | Preis gilt wieder, eine noch nicht wirksame Herabstufung wird zurückgenommen |
| Ablehnen | entfernt den Nachweis, die Frist läuft weiter |
| Name | bis zur Bestätigung änderbar (nicht, solange ein Nachweis gilt), danach für immer fest |
| Spätere Nachweise | prüft allein die Ausweisprüfung, kein Gang zum Empfang |

In der Liste stehen alle, die noch nicht bestätigt sind und einen Nachweis
haben, der nach Datum gilt — auch die mit verpasster Frist. Die stehen oben
und sind rot markiert. Sortiert wird nach Frist, dann nach Namen.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `empfang.php` |
| 2 Seitenskript | `assets/js/pages/empfang.page.js` |
| 3 Service | `assets/js/services/empfang.js` |
| 4 Endpunkt | `api/empfang.php` |
| Infrastruktur | `src/Auth.php` (`istPersonal()`, `nurFuerPersonal()`) |
| 5 Repository | `src/Repositories/NachweisRepository.php` (Liste, Gültigkeit), `src/Repositories/MitgliedRepository.php` (bestätigen, Frist) |
| 6 Tabelle | Spalten `bestaetigt_am` und `vorzeigen_bis` in `mitglieder`, Rolle in `users.roles_mask` |
| CSS | `assets/css/components/empfang.css` |

Was sich am Nachweis selbst ändert, steht in [Nachweise](nachweise.md).

## Datenform

Ein Eintrag der Liste:

```json
{ "mitgliedId": 12, "vorname": "Maximilian", "nachname": "Testmann",
  "art": "student", "gueltigBis": "2027-03-31", "geprueftAm": "2026-10-16 10:02:11",
  "vorzeigenBis": "2026-10-30", "fristVerpasst": false }
```

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/empfang.php` | alle offenen Nachweise | `{ "offen": [...] }` · 401 · 403 |
| PUT | `api/empfang.php` | Person bestätigen, `{ mitgliedId }` | `{ "offen": [...] }` · 400 · 403 eigenes Konto · 409 nichts mehr offen |
| DELETE | `api/empfang.php` | Nachweis ablehnen und entfernen, `{ mitgliedId }` | `{ "offen": [...] }` · 400 · 403 · 409 |

**409** heißt meist: Jemand anderes am Empfang hat gerade schneller
geklickt. Die Seite zeigt die Meldung, die Liste lädt man neu.

## Woher kommen die Daten aktuell

Aus PostgreSQL bei Supabase, Tabellen `mitglieder`, `nachweise` und `users`.
Neue Tabellen gibt es nicht. Die zwei Spalten kommen über den Abschnitt
„NACHTRÄGE" am Ende von `database/schema.sql` in vorhandene Datenbanken;
wer dort schon einen Nachweis hat, bekommt seine 14 Tage ab dem Einspielen.

## Was fehlt noch

- **Gespeichert wird nur, wann bestätigt wurde, nicht von wem.**
- **Eine Bestätigung lässt sich nicht zurücknehmen** — nur von Hand im
  Dashboard (`bestaetigt_am` auf `NULL`). Mit Absicht kein Knopf dafür.
- **Keine Suche in der Liste.** Bei vielen offenen Einträgen müsste man
  scrollen.
- **Im Demo-Modus prüft niemand spätere Nachweise.** Wer bestätigt ist, kann
  sich ohne Schlüssel jedes Ablaufdatum eintippen — der Name bleibt aber fest.
- **Die Tarifseite sagt bei verpasster Frist „Nachweis fehlt".** Den genauen
  Grund nennt nur „Mein Konto".
