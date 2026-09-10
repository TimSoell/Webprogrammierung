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
4. Optional dasselbe mit `seed.sql`.

Auf der Kommandozeile:

```bash
mysql -u root < database/schema.sql
```

## Regeln

- **Jede Tabellenänderung kommt in `schema.sql`** und wird im Team angesagt.
  Sonst hat jede Person eine andere Datenbank, und der Fehler taucht erst
  bei der Vorführung auf.
- Tabellennamen kleingeschrieben, Mehrzahl, ohne Umlaute: `kurse`, `buchungen`
- Spaltennamen kleingeschrieben mit Unterstrich: `erstellt_am`, `kurs_id`
- Jede Tabelle hat `id` als Primärschlüssel und `erstellt_am` als Zeitstempel
- `ENGINE = InnoDB` und `utf8mb4` — beides steht als Beispiel in `schema.sql`
- In `seed.sql` **nur erfundene Daten**. Keine echten Namen, keine echten
  E-Mail-Adressen, keine Passwörter.

## Stand

Es gibt noch keine Tabellen, weil es noch keine Features gibt. `schema.sql`
legt bisher nur die leere Datenbank an und zeigt die Konventionen an einer
auskommentierten Beispieltabelle.
