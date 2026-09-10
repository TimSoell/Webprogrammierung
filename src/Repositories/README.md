# src/Repositories/ — Datenzugriff

**Der einzige Ort im Projekt, an dem SQL stehen darf.**

Ein Repository pro Tabelle bzw. pro Thema. Es übersetzt zwischen
Datenbankzeilen und PHP-Arrays — mehr nicht.

Vollständiges Muster: [`BeispielFeatureRepository.php`](BeispielFeatureRepository.php)

## Namen

| Tabelle | Klasse | Datei |
|---|---|---|
| `kurse` | `KursRepository` | `KursRepository.php` |
| `produkte` | `ProduktRepository` | `ProduktRepository.php` |

Tabelle in Mehrzahl, Klasse in Einzahl.

## Übliche Methoden

```php
alleFinden(): array              // alle Datensätze
findenNachId(int $id): ?array    // einer, oder null
anlegen(array $daten): int       // gibt die neue id zurück
aktualisieren(int $id, array $daten): void
loeschen(int $id): void
```

Fachlich benennen, nicht technisch: `kurseAmTagFinden()` sagt mehr als
`selectByDay()`.

## Die wichtigste Regel

**Nie einen Wert in den SQL-Text schreiben.**

```php
// FALSCH - hierüber kann die Datenbank gelöscht werden
$sql = "SELECT * FROM kurse WHERE id = " . $id;

// RICHTIG
$stmt = Database::connection()->prepare('SELECT * FROM kurse WHERE id = ?');
$stmt->execute([$id]);
```

Das heißt *Prepared Statement* und verhindert SQL-Injection. In diesem
Projekt gibt es keinen Fall, in dem die erste Variante nötig wäre.

## Weitere Regeln

- Kein `echo`, kein HTML, kein `http_response_code()`. Das macht der Endpunkt.
- Kein Zugriff auf `$_GET` oder `$_POST`. Werte kommen als Parameter herein.
- Spalten im `SELECT` einzeln aufzählen statt `SELECT *`. Dann fällt beim
  Lesen sofort auf, welche Felder das Repository überhaupt liefert.
