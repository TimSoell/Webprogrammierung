# src/ — PHP-Klassen und Infrastruktur

Dateien hier werden **nie direkt im Browser aufgerufen**. Sie werden von
Seiten und Endpunkten eingebunden.

| Datei | Zweck |
|---|---|
| `bootstrap.php` | Startpunkt jeder Seite: Konfiguration, Zeitzone, `ROOT_PATH`, `BASE_URL`, `e()`, Autoloader, Sitzung |
| `Database.php` | Die eine PDO-Verbindung zur Datenbank (PostgreSQL bei Supabase) |
| `SitzungsSpeicher.php` | Legt PHP-Sitzungen in der Datenbank ab statt als Datei – nötig für Vercel, siehe [ADR-0017](../docs/decisions/ADR-0017-postgresql-auf-supabase.md) |
| `Auth.php` | Die eine Instanz der Login-Bibliothek, Regeln für E-Mail und Passwort |
| `Api.php` | JSON-Rumpf lesen und antworten, für die Endpunkte in `api/` |
| `Terminplan.php` | Aus Wochenplänen konkrete Termine rechnen und angefragte Termine prüfen, ohne SQL |
| `Repositories/` | Datenzugriff. Der einzige Ort mit SQL |

## Autoloader

`bootstrap.php` registriert einen Autoloader und lädt danach `vendor/autoload.php`
für die Login-Bibliothek. Klassen werden automatisch
geladen, sobald sie benutzt werden — es braucht **kein `require`**.

Bedingung: Namespace und Ordner müssen übereinstimmen.

```
Klasse   Repositories\KursRepository
Datei    src/Repositories/KursRepository.php
```

## Die Werkzeuge aus bootstrap.php

| Name | Bedeutung | Beispiel |
|---|---|---|
| `ROOT_PATH` | Pfad im **Dateisystem** | `require ROOT_PATH . '/partials/head.php'` |
| `BASE_URL` | Pfad im **Browser** | `href="<?= e(BASE_URL) ?>index.php"` |
| `e()` | Ausgabe absichern | `<?= e($kurs['name']) ?>` |
| `CONFIG` | Die Konfiguration für Klassen | `CONFIG['db']['host']` |

Die häufigste Fehlerquelle ist, die beiden Pfade zu verwechseln.
Merkhilfe: `require` bekommt `ROOT_PATH`, `href` und `src` bekommen `BASE_URL`.

## Regeln

- Ein Dateiname entspricht dem Klassennamen: `KursRepository.php`.
- Klassen in `PascalCase`, Methoden in `camelCase`.
- `declare(strict_types=1);` in jeder PHP-Datei. Das lässt PHP meckern, wenn
  ein Text ankommt, wo eine Zahl erwartet wird — statt ihn stillschweigend
  umzuwandeln.
- Keine Ausgabe (`echo`, HTML) in Klassen. Klassen liefern Daten zurück.
