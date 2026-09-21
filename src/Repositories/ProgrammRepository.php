<?php
/**
 * @file        src/Repositories/ProgrammRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest die Details der drei Programmseiten: die aufklappbaren
 *              Merkmale (Fokus, Level, Format) und die Coaches.
 *
 *              Nur Lesen. Gepflegt werden die Daten über database/seed.sql
 *              oder phpMyAdmin - ein Pflegebereich im Browser ist nicht Teil
 *              dieses Features.
 * @see         database/schema.sql
 * @see         api/programme.php
 * @see         docs/features/programm-details.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class ProgrammRepository
{
    /**
     * Die erlaubten Werte der Spalte merkmale.art, in Anzeigereihenfolge.
     *
     * Muss zum ENUM in database/schema.sql passen. Die Liste steht hier
     * zusätzlich, weil sie auch die Reihenfolge auf der Seite bestimmt -
     * das kann das ENUM allein nicht ausdrücken.
     */
    public const ARTEN = ['fokus', 'level', 'format'];

    /**
     * Sucht ein Programm anhand seines Kürzels aus der URL.
     *
     * @param string $slug  'strength', 'move' oder 'fight'
     * @return array{id: int, slug: string, name: string}|null  null, wenn es das Programm nicht gibt
     */
    public function findenNachSlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, slug, name FROM programme WHERE slug = ?'
        );
        $stmt->execute([$slug]);

        $programm = $stmt->fetch();

        if ($programm === false) {
            return null;
        }

        $programm['id'] = (int) $programm['id'];

        return $programm;
    }

    /**
     * Lädt alle Merkmale eines Programms, nach Art gruppiert.
     *
     * Eine Abfrage statt drei, danach im PHP sortiert. Bei neun Zeilen pro
     * Programm ist das schneller als drei Runden zur Datenbank.
     *
     * Jede Art aus ARTEN kommt im Ergebnis vor, notfalls als leeres Array.
     * Die Seite muss dadurch nicht prüfen, ob ein Schlüssel existiert.
     *
     * @param int $programmId  id aus findenNachSlug()
     * @return array<string, list<array{titel: string, beschreibung: string}>>
     */
    public function merkmaleFinden(int $programmId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT art, titel, beschreibung
               FROM merkmale
              WHERE programm_id = ?
              ORDER BY position, id'
        );
        $stmt->execute([$programmId]);

        $nachArt = array_fill_keys(self::ARTEN, []);

        foreach ($stmt->fetchAll() as $zeile) {
            // Unbekannte Art überspringen: Käme durch eine Schema-Änderung
            // ein vierter ENUM-Wert dazu, ohne dass ARTEN nachgezogen wurde,
            // fällt die Seite sonst über einen fehlenden Schlüssel.
            if (!isset($nachArt[$zeile['art']])) {
                continue;
            }

            $nachArt[$zeile['art']][] = [
                'titel'        => $zeile['titel'],
                'beschreibung' => $zeile['beschreibung'],
            ];
        }

        return $nachArt;
    }

    /**
     * Sucht die id eines Merkmals anhand seines Titels.
     *
     * Der Browser kennt die Merkmale nur über ihren Titel - so speichert sie
     * auch der localStorage. Beim Merken am Konto wird daraus hier wieder
     * eine id, damit in mitglied_auswahl ein echter Fremdschlüssel steht.
     *
     * Die Suche ist zugleich die Prüfung: Ein Titel, den es in diesem
     * Programm und dieser Art nicht gibt, liefert null. Damit kann niemand
     * über den Endpunkt ein fremdes Merkmal unterschieben.
     *
     * Sollten zwei Merkmale denselben Titel tragen, gewinnt das vordere -
     * nur damit das Ergebnis vorhersehbar bleibt.
     *
     * @param int    $programmId  id aus findenNachSlug()
     * @param string $art         'level' oder 'format'
     * @param string $titel       Titel aus dem Browser
     * @return int|null           null, wenn es den Titel dort nicht gibt
     */
    public function merkmalIdFinden(int $programmId, string $art, string $titel): ?int
    {
        $stmt = Database::connection()->prepare(
            'SELECT id
               FROM merkmale
              WHERE programm_id = ? AND art = ? AND titel = ?
              ORDER BY position, id
              LIMIT 1'
        );
        $stmt->execute([$programmId, $art, $titel]);

        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * Lädt die Coaches eines Programms in Anzeigereihenfolge.
     *
     * @param int $programmId  id aus findenNachSlug()
     * @return list<array{name: string, schwerpunkt: string, bild: string, bildAlt: string}>
     */
    public function coachesFinden(int $programmId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT name, schwerpunkt, bild, bild_alt
               FROM coaches
              WHERE programm_id = ?
              ORDER BY position, id'
        );
        $stmt->execute([$programmId]);

        return array_map(static fn (array $zeile): array => [
            'name'        => $zeile['name'],
            'schwerpunkt' => $zeile['schwerpunkt'],
            // Nur der Dateiname steht in der Datenbank. Den Ordner hängt
            // der Endpunkt an, die vollständige URL baut erst das Skript
            // im Browser mit BASE_URL.
            'bild'        => 'assets/img/coaches/' . $zeile['bild'],
            'bildAlt'     => $zeile['bild_alt'],
        ], $stmt->fetchAll());
    }
}
