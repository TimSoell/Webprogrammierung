# assets/js/ — Das Verhalten

```
main.js          Einstieg. Läuft auf JEDER Seite
components/      Verhalten, das mehrere Seiten brauchen
pages/           Verhalten genau einer Seite
services/        Datenbeschaffung  ← die Naht zur Datenbank
lib/             Allgemeine Helfer ohne Feature-Bezug
```

## Welcher Ordner

| Frage | Antwort |
|---|---|
| Brauchen das mehrere Seiten? | `components/` |
| Nur eine bestimmte Seite? | `pages/` |
| Holt oder schickt es Daten? | `services/` |
| Wäre es auch in einem anderen Projekt nützlich? | `lib/` |

## Einbinden

`main.js` wird von `partials/head.php` auf jeder Seite geladen.

Ein Seitenskript wird über eine Variable in der Seite eingebunden:

```php
$pageScript = 'kurse.page.js';   // sucht in assets/js/pages/
```

Alle Skripte laufen als `type="module"`. Module werden automatisch erst nach
dem Aufbau der Seite ausgeführt — `DOMContentLoaded` ist also nicht nötig.

## Regeln

- **`fetch()` nur in `services/`.** Nirgendwo sonst. Der Grund steht in
  [`../../docs/decisions/ADR-0002-schichtenarchitektur.md`](../../docs/decisions/ADR-0002-schichtenarchitektur.md).
- **Kein `element.style.xyz = ...`.** Stattdessen eine CSS-Klasse setzen oder
  entfernen. Das Aussehen bleibt dadurch vollständig im CSS.
- **Komponenten prüfen, ob es ihre Elemente gibt**, und beenden sich sonst
  ohne Fehler. Nur so darf `main.js` sie auf jeder Seite aufrufen.
- **Kein `innerHTML` mit ungeprüftem Text** aus Datenbank oder Formular.
  `textContent` benutzen oder vorher escapen.
- Dateien in `pages/` heißen wie ihre Seite plus `.page.js`:
  `kurse.php` → `kurse.page.js`.
