# database/ — Bauplan und Testdaten

| Datei | Zweck |
|---|---|
| `schema.sql` | Alle Tabellen. Die einzige Wahrheit über den Aufbau der Datenbank |
| `seed.sql` | Erfundene Testdaten zum Ausprobieren und Vorführen |

Getrennt, weil sie unterschiedlich oft laufen: Das Schema einmal, die
Testdaten beim Entwickeln immer wieder.

Die Datenbank ist **PostgreSQL bei Supabase**, siehe
[ADR-0017](../docs/decisions/ADR-0017-postgresql-auf-supabase.md).

| Supabase-Projekt | Wofür |
|---|---|
| `schwitzkasten-dev` | lokal (`start.bat` / `start.sh`) und Preview-Deployments |
| Produktion | nur die veröffentlichte Seite auf `main` |

## Einspielen

1. [supabase.com/dashboard](https://supabase.com/dashboard) öffnen, Projekt
   **`schwitzkasten-dev`** auswählen.
2. Links **SQL Editor** → **New query**.
3. Den Inhalt von `schema.sql` einfügen → **Run**.
4. Dasselbe mit `seed.sql`. Seit es Tarife gibt, ist das kein optionaler
   Schritt mehr.

Beide Dateien sind UTF-8, Umlaute kommen so ohne Umweg richtig an.

In die Produktionsdatenbank kommt `seed.sql` genau **einmal** beim
Einrichten – danach nie wieder, siehe unten.

## Ansehen und von Hand ändern

Im Supabase-Dashboard unter **Table Editor**. Das ersetzt phpMyAdmin.

## Regeln

- **Jede Tabellenänderung kommt in `schema.sql`** und wird im Team angesagt.
  `CREATE TABLE IF NOT EXISTS` legt nur neue Tabellen an – eine geänderte
  Spalte in einer vorhandenen Tabelle braucht zusätzlich ein `ALTER TABLE`.
- Tabellennamen kleingeschrieben, Mehrzahl, ohne Umlaute: `kurse`, `buchungen`
- Spaltennamen kleingeschrieben mit Unterstrich: `erstellt_am`, `kurs_id`
- Jede Tabelle hat `id` als Primärschlüssel und `erstellt_am` als Zeitstempel
- **Jede neue Tabelle bekommt `ENABLE ROW LEVEL SECURITY`** am Ende von
  `schema.sql`. Sonst ist sie über die öffentliche Schnittstelle von Supabase
  lesbar.
- **Fremdschlüssel brauchen einen eigenen Index** – PostgreSQL legt ihn nicht
  selbst an.
- Wie MySQL-Typen übersetzt werden, steht in
  [ADR-0017](../docs/decisions/ADR-0017-postgresql-auf-supabase.md). Das
  auskommentierte Beispiel oben in `schema.sql` zeigt die Konventionen.
- In `seed.sql` **nur erfundene Daten**. Keine echten Namen, keine echten
  E-Mail-Adressen, keine Passwörter. Testkonten legt man deshalb über die
  Registrierung auf der Website an.

## Stand

| Tabelle | Feature | Herkunft |
|---|---|---|
| `mitglieder` | Mitglieder-Login | unsere Stammdaten |
| `users`, `users_*` (8 Tabellen) | Mitglieder-Login | Login-Bibliothek, siehe [ADR-0005](../docs/decisions/ADR-0005-login-bibliothek.md) |
| `tarife` | Mitgliedschaften | Katalog, Inhalt aus `seed.sql`, siehe [ADR-0009](../docs/decisions/ADR-0009-mitgliedschaften-datenmodell.md) |
| `mitgliedschaften` | Mitgliedschaften | abgeschlossene Verträge |
| `nachweise` | Nachweise | Ergebnis der Ausweisprüfung, **ohne Bild**, siehe [ADR-0011](../docs/decisions/ADR-0011-ausweispruefung-mit-ki.md) |
| `kurstermine` | Terminkalender | Wochenplan der Kurse, Inhalt aus `seed.sql`, siehe [ADR-0014](../docs/decisions/ADR-0014-terminkalender-wochenplan.md) |
| `kursbuchungen` | Terminkalender | gebuchte Kurstermine, mit konkretem Datum |
| `verfuegbarkeiten` | Terminkalender | Wochenfenster der Coaches fürs Probetraining, Inhalt aus `seed.sql` |
| `probetrainings` | Terminkalender | gebuchte Probetrainings |
| `sitzungen` | Hosting auf Vercel | PHP-Sitzungen, siehe [ADR-0017](../docs/decisions/ADR-0017-postgresql-auf-supabase.md) |
| `bewertungen` | Bewertungen | Bewertungen der Konten, dazu Beispiele ohne Konto aus `seed.sql` |
| `empfehlungen` | Freunde werben | wer wen mit welcher E-Mail eingeladen hat |
| `gutscheine` | Freunde werben | Gutscheine der Werber samt Gratiszeitraum, siehe [ADR-0021](../docs/decisions/ADR-0021-gutschein-als-gratiszeitraum.md) |

**`seed.sql` neu einspielen löscht alle Buchungen.** Kursbuchungen und
Probetrainings hängen über Fremdschlüssel an Programmen und Coaches, die
`seed.sql` neu anlegt. Die Entwicklungsdatenbank teilt sich das ganze Team –
vorher Bescheid sagen. Und vor einer Vorführung nicht neu einspielen.

**`seed.sql` ist für die Tarife Pflicht, nicht optional.** Ohne sie ist
`mitgliedschaft.php` leer — das ist die häufigste Fehlersuche an dieser Stelle.

Die `users`-Tabellen sind die einzige Ausnahme von den Namensregeln oben: Die
Bibliothek erwartet genau diese Namen und Spalten. Nicht umbenennen.
