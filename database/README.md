# database/ — Bauplan und Testdaten

| Datei | Zweck |
|---|---|
| `schema.sql` | Alle Tabellen. Die einzige Wahrheit über den Aufbau der Datenbank |
| `seed.sql` | Erfundene Testdaten zum Ausprobieren und Vorführen |

Getrennt, weil sie unterschiedlich oft laufen: Das Schema einmal, die
Testdaten beim Entwickeln immer wieder.

## Einspielen

1. In XAMPP **MySQL** starten — und **Apache** dazu, denn phpMyAdmin läuft
   darüber. Für die Projektseite selbst wird Apache nicht gebraucht, die
   startet `./start.sh` (Windows: `start.bat`).
2. `http://localhost/phpmyadmin` öffnen.
3. Reiter **Importieren** → `schema.sql` auswählen → **OK**.
4. Dasselbe mit `seed.sql`. Seit es Tarife gibt, ist das kein optionaler
   Schritt mehr.

Auf der Kommandozeile:

```bash
mysql --default-character-set=utf8mb4 -u root < database/schema.sql
```

**Der Schalter ist unter Windows Pflicht.** Ohne ihn liest der Client die
Dateien in der Zeichensatz-Einstellung der Konsole statt als UTF-8, und aus
`Alle Geräte` wird in der Datenbank `Alle Ger├ñte`. Über phpMyAdmin passiert
das nicht.

## Regeln

- **Jede Tabellenänderung kommt in `schema.sql`** und wird im Team angesagt.
  Sonst hat jede Person eine andere Datenbank, und der Fehler taucht erst
  bei der Vorführung auf.
- Tabellennamen kleingeschrieben, Mehrzahl, ohne Umlaute: `kurse`, `buchungen`
- Spaltennamen kleingeschrieben mit Unterstrich: `erstellt_am`, `kurs_id`
- Jede Tabelle hat `id` als Primärschlüssel und `erstellt_am` als Zeitstempel
- `ENGINE = InnoDB` und `utf8mb4` — beides steht als Beispiel in `schema.sql`
- In `seed.sql` **nur erfundene Daten**. Keine echten Namen, keine echten
  E-Mail-Adressen, keine Passwörter. Testkonten legt man deshalb über die
  Registrierung auf der Website an.

## Stand

| Tabelle | Feature | Herkunft |
|---|---|---|
| `mitglieder` | Mitglieder-Login | unsere Stammdaten |
| `users`, `users_*` (8 Tabellen) | Mitglieder-Login | Login-Bibliothek, siehe [ADR-0005](../docs/decisions/ADR-0005-login-bibliothek.md) |
| `tarife` | Mitgliedschaften | Katalog, Inhalt aus `seed.sql`, siehe [ADR-0006](../docs/decisions/ADR-0006-mitgliedschaften-datenmodell.md) |
| `mitgliedschaften` | Mitgliedschaften | abgeschlossene Verträge |
| `nachweise` | Nachweise | Ergebnis der Ausweisprüfung, **ohne Bild**, siehe [ADR-0008](../docs/decisions/ADR-0008-ausweispruefung-mit-ki.md) |

**`seed.sql` ist für die Tarife Pflicht, nicht optional.** Ohne sie ist
`mitgliedschaft.php` leer — das ist die häufigste Fehlersuche an dieser Stelle.

Die `users`-Tabellen sind die einzige Ausnahme von den Namensregeln oben: Die
Bibliothek erwartet genau diese Namen und Spalten. Nicht umbenennen.
