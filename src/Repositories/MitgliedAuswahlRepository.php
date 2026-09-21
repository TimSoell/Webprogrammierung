<?php
/**
 * @file        src/Repositories/MitgliedAuswahlRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest und schreibt, welches Level und Format sich ein Mitglied
 *              zu einem Programm gemerkt hat (Tabelle mitglied_auswahl).
 *
 *              Alle Methoden bekommen die mitglied_id als ERSTEN Parameter
 *              und filtern in jeder Abfrage danach. Ohne das könnte ein
 *              Mitglied über eine erratene Programm-Kennung die Auswahl
 *              eines anderen lesen oder löschen.
 * @see         database/schema.sql
 * @see         api/auswahl.php
 * @see         docs/features/gemerkte-auswahl.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class MitgliedAuswahlRepository
{
    /**
     * Legt die gemerkte Auswahl an oder überschreibt die vorhandene.
     *
     * INSERT ... ON DUPLICATE KEY UPDATE statt "erst SELECT, dann INSERT
     * oder UPDATE": eine Anweisung, kein Zeitfenster dazwischen, und der
     * UNIQUE-Schlüssel uniq_mitglied_programm erledigt die Unterscheidung.
     *
     * @param int      $mitgliedId  id aus mitglieder
     * @param int      $programmId  id aus programme
     * @param int|null $levelId     id aus merkmale, oder null
     * @param int|null $formatId    id aus merkmale, oder null
     * @return void
     */
    public function merken(int $mitgliedId, int $programmId, ?int $levelId, ?int $formatId): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO mitglied_auswahl (mitglied_id, programm_id, level_id, format_id)
                  VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE level_id = VALUES(level_id),
                                     format_id = VALUES(format_id)'
        );
        $stmt->execute([$mitgliedId, $programmId, $levelId, $formatId]);
    }

    /**
     * Lädt alle gemerkten Auswahlen eines Mitglieds, für den Mitgliedsbereich.
     *
     * LEFT JOIN auf merkmale, weil level_id und format_id null sein dürfen -
     * mit einem normalen JOIN verschwände die ganze Zeile.
     *
     * @param int $mitgliedId  id aus mitglieder
     * @return list<array{slug: string, programm: string, level: string|null, format: string|null}>
     */
    public function fuerMitgliedFinden(int $mitgliedId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.slug,
                    p.name   AS programm,
                    lvl.titel AS level,
                    fmt.titel AS format
               FROM mitglied_auswahl a
               JOIN programme p   ON p.id   = a.programm_id
          LEFT JOIN merkmale  lvl ON lvl.id = a.level_id
          LEFT JOIN merkmale  fmt ON fmt.id = a.format_id
              WHERE a.mitglied_id = ?
              ORDER BY p.id'
        );
        $stmt->execute([$mitgliedId]);

        return $stmt->fetchAll();
    }

    /**
     * Lädt die gemerkte Auswahl zu EINEM Programm.
     *
     * Braucht die Programmseite, um die gemerkte Auswahl beim Öffnen
     * vorauszuwählen und den Merken-Button richtig zu beschriften.
     *
     * @param int $mitgliedId  id aus mitglieder
     * @param int $programmId  id aus programme
     * @return array{level: string|null, format: string|null}|null  null, wenn nichts gemerkt ist
     */
    public function fuerProgrammFinden(int $mitgliedId, int $programmId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT lvl.titel AS level, fmt.titel AS format
               FROM mitglied_auswahl a
          LEFT JOIN merkmale lvl ON lvl.id = a.level_id
          LEFT JOIN merkmale fmt ON fmt.id = a.format_id
              WHERE a.mitglied_id = ? AND a.programm_id = ?'
        );
        $stmt->execute([$mitgliedId, $programmId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Entfernt die gemerkte Auswahl zu einem Programm.
     *
     * @param int $mitgliedId  id aus mitglieder
     * @param int $programmId  id aus programme
     * @return bool            false, wenn es dort nichts zu entfernen gab
     */
    public function entfernen(int $mitgliedId, int $programmId): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM mitglied_auswahl WHERE mitglied_id = ? AND programm_id = ?'
        );
        $stmt->execute([$mitgliedId, $programmId]);

        return $stmt->rowCount() > 0;
    }
}
