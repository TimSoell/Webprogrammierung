# Feature: Personal verwalten

**Status:** in Arbeit
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-10-16

## Was kann man damit

Ein **Admin** macht auf einer eigenen Seite ein bestehendes Konto zum
**Personal** — oder nimmt ihm die Rolle wieder weg. Dazu gibt er die
E-Mail-Adresse ein und klickt **„Zum Personal machen"**. Darunter steht eine
Tabelle mit allen, die gerade Personal sind, je Zeile ein Knopf **„Rolle
entziehen"**.

Personal heißt: Die Person kann den [Empfang](empfang.md) öffnen und dort
Konten bestätigen. Vorher musste man dafür SQL im Supabase-Dashboard
ausführen.

## Die Regeln

| | |
|---|---|
| Wer darf | nur Admins (Rolle `ADMIN` der Login-Bibliothek) |
| Wie wird man Admin | nur im Supabase-Dashboard (SQL unten), nie über die Website |
| Wer kann Personal werden | jedes Konto, das es schon gibt — die Person registriert sich vorher ganz normal |
| Admin und Personal | zwei getrennte Rollen. Ein Admin ist nicht von selbst Personal, kann sich aber selbst eintragen |
| Wann wirkt es | nach dem nächsten Anmelden der Person, sonst spätestens nach fünf Minuten |
| Wer sonst die Seite aufruft | landet in „Mein Konto"; der Endpunkt antwortet mit **403**, auch dem Personal |

## So kommt man auf die Seite

**Kein Link auf der Website führt dorthin** — kein Menüpunkt, kein Knopf in
„Mein Konto". Der Admin ruft die Adresse direkt auf:

| | |
|---|---|
| Live | `https://schwitzkasten-gym.de/admin.php` |
| Lokal | `http://localhost:8000/admin.php` |

Geheim ist die Adresse nicht. Geschützt ist die Seite über die Rolle, und die
Sperre sitzt auf dem Server (`Auth::nurFuerAdmins()`, `api/personal.php`).

## So wird jemand Admin

Im SQL-Editor des Supabase-Dashboards, für genau ein Konto:

```sql
-- Rolle Admin vergeben (1 = Role::ADMIN der Bibliothek)
UPDATE users SET roles_mask = roles_mask | 1 WHERE email = 'name@beispiel.de';

-- Rolle Admin wieder wegnehmen
UPDATE users SET roles_mask = roles_mask & ~1 WHERE email = 'name@beispiel.de';
```

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `admin.php` |
| 2 Seitenskript | `assets/js/pages/admin.page.js` |
| 3 Service | `assets/js/services/personal.js` |
| 4 Endpunkt | `api/personal.php` |
| Infrastruktur | `src/Auth.php` (`istAdmin()`, `nurFuerAdmins()`, `personalErnennen()`, `personalEntziehen()`) |
| 5 Repository | `src/Repositories/MitgliedRepository.php` (`personalFinden()`, nur lesend) |
| 6 Tabelle | keine neue — Rollen in `users.roles_mask` |
| CSS | `assets/css/components/admin.css`, die Tabelle mit den Klassen aus `empfang.css` |

## Datenform

Ein Eintrag der Liste:

```json
{ "vorname": "Maximilian", "nachname": "Testmann", "email": "max@beispiel.de" }
```

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/personal.php` | alle mit der Rolle Personal | `{ "personal": [...] }` · 401 · 403 |
| POST | `api/personal.php` | Konto zum Personal machen, `{ email }` | `{ "personal": [...] }` · 400 · 404 kein Konto · 409 schon Personal |
| DELETE | `api/personal.php` | Rolle entziehen, `{ email }` | `{ "personal": [...] }` · 400 · 409 nicht (mehr) Personal |

## Woher kommen die Daten aktuell

Aus PostgreSQL bei Supabase. Die Rollen stehen in `users.roles_mask`, der
Spalte der Login-Bibliothek: `1` = Admin, `2048` = Personal. Geschrieben wird
sie nur über die Bibliothek in `src/Auth.php`, gelesen für die Liste in
`MitgliedRepository::personalFinden()` zusammen mit dem Namen aus
`mitglieder`. Neue Tabellen oder Spalten gibt es nicht.

## Was fehlt noch

- **Gespeichert wird nicht, wer wann jemanden zum Personal gemacht hat.**
- **Admin lässt sich nur im Dashboard vergeben.** Mit Absicht.
- **Keine Kennzahlen.** Die Admin-Seite zeigt keine Zahlen zu Mitgliedern,
  Buchungen oder Besuchen.

Auf derselben Seite steht die zweite Karte für Admins:
[Unstimmigkeiten und Konto löschen](unstimmigkeiten.md).
