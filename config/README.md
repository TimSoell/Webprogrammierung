# config/ — Konfiguration

| Datei | Im Repository | Zweck |
|---|---|---|
| `config.example.php` | ja | Vorlage mit XAMPP-Standardwerten |
| `config.php` | **nein** | Die echten lokalen Werte |

## Einmalig einrichten

Nach dem Klonen des Repositories:

```bash
cp config/config.example.php config/config.php
```

Unter Windows im Explorer kopieren und umbenennen. Dann bei Bedarf die
MySQL-Zugangsdaten anpassen — bei einem frischen XAMPP passen die
Standardwerte meist unverändert.

Fehlt die Datei, zeigt jede Seite einen deutlichen Hinweis statt eines
kryptischen PHP-Fehlers.

## Warum config.php nicht im Repository liegt

Zwei Gründe:

1. **Zugangsdaten gehören nicht in Git.** Bei uns ist es nur ein leeres
   XAMPP-Passwort, aber die Gewohnheit ist die richtige. Was einmal in der
   Git-Historie steht, bekommt man kaum wieder heraus.
2. **Jede Person hat ein anderes Setup.** Läge die Datei im Repository, würde
   sie bei jedem Pull überschrieben.

## Neuer Konfigurationswert

1. In `config.example.php` mit sinnvollem Standardwert und Kommentar ergänzen.
2. Im Team ansagen — jede Person muss ihn in ihrer eigenen `config.php`
   nachtragen.
