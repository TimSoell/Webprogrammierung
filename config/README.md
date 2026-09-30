# config/ — Konfiguration

| Datei | Im Repository | Zweck |
|---|---|---|
| `config.example.php` | ja | Vorlage für die lokale Konfiguration |
| `config.php` | **nein** | Die echten lokalen Werte, samt Datenbank-Passwort |
| `config.umgebung.php` | ja | Die Konfiguration auf Vercel – liest Umgebungsvariablen, enthält selbst keine Werte |
| `schluessel-setzen.php` | ja | Trägt den Gemini-Schlüssel lokal in `config.php` ein |

## Einmalig einrichten

Nach dem Klonen des Repositories:

```bash
cp config/config.example.php config/config.php
```

Unter Windows im Explorer kopieren und umbenennen. Dann in `config.php` bei
`'password'` das Passwort der Entwicklungsdatenbank `schwitzkasten-dev`
eintragen. Das gibt es nur im Team, per Passwortmanager – nicht per Chat.
Host und Benutzer stehen schon richtig in der Vorlage.

Fehlt die Datei, zeigt jede Seite einen deutlichen Hinweis statt eines
kryptischen PHP-Fehlers. Ebenso, wenn ihr ein Wert aus der Vorlage fehlt –
`src/bootstrap.php` vergleicht lokal bei jedem Aufruf beide Dateien.

## Auf Vercel

Dort gibt es keine `config.php`. `src/bootstrap.php` lädt stattdessen
`config.umgebung.php`, die dieselben Werte aus den Umgebungsvariablen des
Vercel-Projekts liest:

| Variable | Entspricht in `config.php` |
|---|---|
| `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` | `db` |
| `GEMINI_API_KEY`, `GEMINI_MODELL` | `ki` |
| `APP_DEBUG` (`1` = an) | `debug` |
| `DEMO_RESET_LINK` (`1` = an) | `demo_reset_link` |

Eingetragen werden sie im Vercel-Dashboard unter *Settings → Environment
Variables*, getrennt für *Production* und *Preview*. Passwort und
API-Schlüssel dort als *Sensitive*. Siehe
[ADR-0016](../docs/decisions/ADR-0016-hosting-auf-vercel.md).

## Warum config.php nicht im Repository liegt

Zwei Gründe:

1. **Zugangsdaten gehören nicht in Git.** Das Repository ist öffentlich –
   ein Passwort darin wäre sofort für alle lesbar. Was einmal in der
   Git-Historie steht, bekommt man kaum wieder heraus.
2. **Jede Person hat ein anderes Setup.** Läge die Datei im Repository, würde
   sie bei jedem Pull überschrieben.

## Neuer Konfigurationswert

1. In `config.example.php` mit sinnvollem Standardwert und Kommentar ergänzen.
2. In `config.umgebung.php` mit einer Umgebungsvariable ergänzen und die
   Variable in Vercel anlegen.
3. Im Team ansagen — jede Person muss ihn in ihrer eigenen `config.php`
   nachtragen. Wer es vergisst, bekommt beim nächsten Start einen Hinweis
   mit dem Namen des fehlenden Werts.

## Der Schlüssel für die Ausweisprüfung

`ki.api_key` steuert, wie Nachweise für ermäßigte Preise geprüft werden:

| Wert | Verhalten |
|---|---|
| leer (Standard) | **Demo-Modus** — das Ablaufdatum wird von Hand eingetragen |
| API-Schlüssel | das hochgeladene Ausweisfoto wird per Gemini ausgelesen |

Beides funktioniert. Wer keinen Schlüssel hat, kann trotzdem alles am
Projekt entwickeln und vorführen. Einzelheiten:
[`docs/features/nachweise.md`](../docs/features/nachweise.md).

Eingetragen wird der Schlüssel am sichersten per Skript statt von Hand:
`schluessel-setzen.bat` (Windows, Doppelklick) oder `./schluessel-setzen.sh`
(macOS, Linux).

**Der Schlüssel wird nie weitergegeben und nie committet.** Er steht nur in
der eigenen `config.php`, und die steht in `.gitignore`.
