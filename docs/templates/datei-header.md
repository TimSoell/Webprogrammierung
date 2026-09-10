# Vorlage: Datei-Kopfkommentar

Jede Datei im Projekt beginnt mit einem Kopfkommentar. Er beantwortet drei
Fragen, ohne dass man den Code lesen muss: **Was ist das, wo gehört es hin,
was hängt damit zusammen.**

## Felder

| Feld | Pflicht | Inhalt |
|---|---|---|
| `@file` | ja | Pfad ab Projektwurzel, z. B. `assets/js/services/kurse.js` |
| `@layer` | ja | Schicht 1–6, oder `Komponente`, `Infrastruktur`, `Hilfsfunktionen` |
| `@description` | ja | Was macht die Datei. Bei wichtigen Dateien auch: warum so |
| `@see` | nein | Verwandte Dateien, eine pro Zeile |

`@file` wird mitgeschrieben, obwohl der Pfad auch oben im Editor steht — beim
Ausdrucken oder in einem PDF der Abgabe fehlt diese Information sonst.

## PHP

```php
<?php
/**
 * @file        api/kurse.php
 * @layer       4 – API-Endpunkt
 * @description Liefert alle Kurse als JSON und nimmt neue Kurse entgegen.
 * @see         src/Repositories/KursRepository.php
 */

declare(strict_types=1);
```

## JavaScript

```js
/**
 * @file        assets/js/services/kurse.js
 * @layer       3 – Service
 * @description Holt Kursdaten. Einzige Stelle, die dafür das Backend anspricht.
 * @see         assets/js/services/api.js
 */
```

## CSS

```css
/**
 * @file        assets/css/components/kursliste.css
 * @layer       Komponente
 * @description Darstellung der Kursliste inklusive schmaler Bildschirme.
 * @see         kurse.php
 */
```

## SQL

```sql
-- =============================================================================
-- @file        database/schema.sql
-- @layer       6 - Datenbank
-- @description Bauplan aller Tabellen.
-- =============================================================================
```

## Für Funktionen zusätzlich

Öffentliche Funktionen bekommen zusätzlich einen eigenen Kommentar mit
`@param` und `@returns`. Das ist kein Selbstzweck: VS Code liest diese Angaben
und zeigt sie beim Tippen als Hilfe an.

```js
/**
 * Lädt alle Kurse eines Wochentags.
 *
 * @param {string} tag  Wochentag, z. B. 'Montag'
 * @returns {Promise<Array<{id: number, name: string}>>}
 * @throws {ApiError}   Wenn der Server nicht erreichbar ist
 */
```
