# partials/ — Bausteine für Seiten

Teile, die auf mehreren Seiten vorkommen. Sie werden per `require` in eine
Seite eingebunden und existieren dadurch **genau einmal**.

| Datei | Zweck |
|---|---|
| `head.php` | `<head>` samt Titel, Schriften, CSS und JS. Öffnet `<body>` |
| `header.php` | Kopfzeile mit Logo, Navigation und Aktionsbutton |
| `footer.php` | Fußzeile. Schließt `</body>` und `</html>` |
| `modal-anmeldung.php` | Overlay-Fenster für die Interessenten-Anmeldung |
| `passwort-kriterien.php` | Liste der Passwort-Anforderungen unter einem Passwortfeld |
| `kurskalender.php` | Fenster mit den Kursterminen, auf allen drei Kursseiten |

## Reihenfolge in einer Seite

```php
require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
// ... eigener Inhalt ...
require ROOT_PATH . '/partials/footer.php';   // IMMER als letztes
```

`footer.php` schließt das HTML-Dokument. Steht danach noch etwas, landet es
außerhalb von `</html>`.

## Regeln

- Ein Baustein enthält **kein** CSS und **kein** JavaScript.
- Ein Baustein liest **nicht** aus der Datenbank. Braucht er Daten, setzt die
  Seite sie vorher in eine Variable.
- Jeder Baustein prüft oben, ob `BASE_URL` definiert ist. Das verhindert, dass
  er versehentlich direkt im Browser aufgerufen wird.
- Neuer Menüpunkt: nur das Array `$navItems` in `header.php` erweitern.
  Ausnahme ist der Konto-Button („Login / Registrierung“ bzw. „Mein Konto“),
  der vom Login-Zustand abhängt und deshalb darunter steht.
