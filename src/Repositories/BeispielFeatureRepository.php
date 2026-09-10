<?php
/**
 * @file        src/Repositories/BeispielFeatureRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description VORLAGE, Schicht 5 von 6. Enthält absichtlich keinen Code.
 *              Vorherige Schicht: api/beispiel-feature.php
 *              Nächste Schicht:   database/schema.sql
 *
 *              Ein Repository ist die EINZIGE Stelle im Projekt, in der SQL
 *              stehen darf. Pro Tabelle bzw. pro Thema gibt es genau eines.
 *
 *              Warum so streng? Wenn SQL überall verstreut ist, findet
 *              niemand mehr alle Stellen, die eine Tabelle benutzen - und
 *              eine Spaltenumbenennung wird zur Suchaktion. So ist es
 *              eine Datei.
 *
 * ============================================================================
 * WAS GEHÖRT IN DIESE SCHICHT
 * ============================================================================
 *
 *   - SELECT, INSERT, UPDATE, DELETE
 *   - Je eine Methode pro Vorgang, fachlich benannt:
 *         alleFinden(), findenNachId($id), anlegen($daten), loeschen($id)
 *   - Umwandlung zwischen Datenbankzeile und PHP-Array
 *
 * ============================================================================
 * WAS HIER NICHTS ZU SUCHEN HAT
 * ============================================================================
 *
 *   - echo, print, HTML          -> ein Repository gibt Daten zurück
 *   - http_response_code()       -> gehört in den Endpunkt (Schicht 4)
 *   - Zugriff auf $_GET / $_POST -> der Endpunkt reicht Werte als Parameter
 *
 * ============================================================================
 * DIE WICHTIGSTE REGEL: NIE WERTE IN DEN SQL-STRING SCHREIBEN
 * ============================================================================
 *
 *   FALSCH - hierüber kann die ganze Datenbank gelöscht werden:
 *
 *       $sql = "SELECT * FROM kurse WHERE id = " . $id;
 *
 *   RICHTIG - der Wert wird getrennt übergeben:
 *
 *       $stmt = $pdo->prepare('SELECT * FROM kurse WHERE id = ?');
 *       $stmt->execute([$id]);
 *
 *   Das nennt sich Prepared Statement und verhindert SQL-Injection.
 *   Es gibt in diesem Projekt keinen Fall, in dem die erste Variante
 *   nötig wäre.
 *
 * ============================================================================
 * TYPISCHER AUFBAU
 * ============================================================================
 *
 *   declare(strict_types=1);
 *
 *   namespace Repositories;
 *
 *   use Database;
 *   use PDO;
 *
 *   final class BeispielFeatureRepository
 *   {
 *       public function alleFinden(): array
 *       {
 *           $stmt = Database::connection()->query(
 *               'SELECT id, name, tag, uhrzeit FROM beispiel_eintraege ORDER BY name'
 *           );
 *
 *           return $stmt->fetchAll();
 *       }
 *
 *       public function findenNachId(int $id): ?array
 *       {
 *           $stmt = Database::connection()->prepare(
 *               'SELECT id, name FROM beispiel_eintraege WHERE id = ?'
 *           );
 *           $stmt->execute([$id]);
 *
 *           // fetch() liefert false, wenn nichts gefunden wurde.
 *           // Wir machen daraus null, weil sich das in PHP besser prüfen lässt.
 *           return $stmt->fetch() ?: null;
 *       }
 *
 *       public function anlegen(string $name): int
 *       {
 *           $stmt = Database::connection()->prepare(
 *               'INSERT INTO beispiel_eintraege (name) VALUES (?)'
 *           );
 *           $stmt->execute([$name]);
 *
 *           return (int) Database::connection()->lastInsertId();
 *       }
 *   }
 *
 *   Der Autoloader in src/bootstrap.php findet die Klasse automatisch,
 *   weil Namespace und Ordnername übereinstimmen (Repositories/).
 *   Es ist kein require nötig.
 *
 * @see         src/Database.php
 * @see         database/schema.sql
 */
