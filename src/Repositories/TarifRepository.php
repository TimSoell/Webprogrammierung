<?php
/**
 * @file        src/Repositories/TarifRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest den Tarifkatalog (Tabelle tarife).
 *
 *              Nur lesend: Die vier Tarife werden über database/seed.sql
 *              gepflegt, nicht über die Website. Ein Tarifeditor wäre ein
 *              eigenes Feature und ist keiner Aufgabe zugeordnet.
 *
 *              Wer welchen Tarif abgeschlossen hat, steht NICHT hier,
 *              sondern in MitgliedschaftRepository.
 * @see         database/schema.sql
 * @see         api/tarife.php
 * @see         docs/features/mitgliedschaften.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class TarifRepository
{
    /**
     * Die Spalten, die Seite und Endpunkt brauchen.
     *
     * id steht bewusst dabei: mitgliedschaften.tarif_id verweist darauf.
     * aktiv und erstellt_am fehlen bewusst - die gehen die Oberfläche
     * nichts an.
     */
    private const SPALTEN = 'id, kennung, name, beschreibung, '
        . 'preis_standard, preis_ermaessigt, preis_senior, '
        . 'zugang_geraete, zugang_wellness, zugang_kurse';

    /**
     * Liefert alle Tarife, die das Studio aktuell anbietet, in der
     * Reihenfolge, in der sie auf der Seite stehen sollen.
     *
     * Tarife mit aktiv = 0 fehlen absichtlich: Sie existieren nur noch,
     * weil alte Verträge auf sie zeigen.
     *
     * @return array<int, array<string, mixed>>  leer, wenn seed.sql nie lief
     */
    public function alleAngebotenenFinden(): array
    {
        $stmt = Database::connection()->query(
            'SELECT ' . self::SPALTEN . ' FROM tarife WHERE aktiv = 1 ORDER BY sortierung'
        );

        return $stmt->fetchAll();
    }

    /**
     * Sucht einen angebotenen Tarif über seine Kennung.
     *
     * Über die Kennung und nicht über die id, weil die id auf jedem Rechner
     * eine andere ist, 'premium' aber überall 'premium'.
     *
     * @param string $kennung  'basis', 'wellness', 'kurse' oder 'premium'
     * @return array<string, mixed>|null  null, wenn es die Kennung nicht gibt
     *                                    oder der Tarif nicht mehr angeboten wird
     */
    public function angebotenenFindenNachKennung(string $kennung): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SPALTEN . ' FROM tarife WHERE kennung = ? AND aktiv = 1'
        );
        $stmt->execute([$kennung]);

        return $stmt->fetch() ?: null;
    }
}
