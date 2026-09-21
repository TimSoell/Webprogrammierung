<?php
/**
 * @file        src/Repositories/KursterminRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest den Wochenplan der Kurse (Tabelle kurstermine) und wie
 *              voll jeder Termin an einem bestimmten Tag ist.
 *
 *              Nur Lesen. Welche konkreten Tage aus dem Wochenplan werden,
 *              rechnet src/Terminplan.php aus - hier steht nur das SQL.
 * @see         database/schema.sql
 * @see         api/kurstermine.php
 * @see         src/Repositories/KursbuchungRepository.php
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class KursterminRepository
{
    /**
     * Lädt den Wochenplan eines Programms samt Format und Coach.
     *
     * @param int $programmId  id aus ProgrammRepository::findenNachSlug()
     * @return list<array{id: int, wochentag: int, beginn: string, dauer_minuten: int, max_teilnehmer: int, stammplaetze: int, format: string, format_beschreibung: string, coach: string, coach_schwerpunkt: string}>
     */
    public function wochenplanFuerProgramm(int $programmId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT k.id, k.wochentag, k.beginn, k.dauer_minuten, k.max_teilnehmer, k.stammplaetze,
                    m.titel        AS format,
                    m.beschreibung AS format_beschreibung,
                    c.name         AS coach,
                    c.schwerpunkt  AS coach_schwerpunkt
               FROM kurstermine k
               JOIN merkmale m ON m.id = k.format_id
               JOIN coaches  c ON c.id = k.coach_id
              WHERE k.programm_id = ?
              ORDER BY k.wochentag, k.beginn'
        );
        $stmt->execute([$programmId]);

        return $stmt->fetchAll();
    }

    /**
     * Sucht einen einzelnen Eintrag des Wochenplans.
     *
     * @param int $id  id aus kurstermine
     * @return array{id: int, wochentag: int, beginn: string, dauer_minuten: int}|null
     */
    public function findenNachId(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, wochentag, beginn, dauer_minuten FROM kurstermine WHERE id = ?'
        );
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Zählt die Buchungen je Termin und Tag in einem Zeitraum.
     *
     * Eine Abfrage für den ganzen Kalender statt einer pro Termin - bei
     * zwei Wochen wären das sonst rund zwölf Runden zur Datenbank.
     *
     * @param int    $programmId
     * @param string $von  'JJJJ-MM-TT', einschließlich
     * @param string $bis  'JJJJ-MM-TT', einschließlich
     * @return array<string, int>  Schlüssel "terminId|datum", z. B. "7|2026-09-22"
     */
    public function buchungenZaehlen(int $programmId, string $von, string $bis): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.kurstermin_id, b.datum, COUNT(*) AS anzahl
               FROM kursbuchungen b
               JOIN kurstermine k ON k.id = b.kurstermin_id
              WHERE k.programm_id = ? AND b.datum BETWEEN ? AND ?
              GROUP BY b.kurstermin_id, b.datum'
        );
        $stmt->execute([$programmId, $von, $bis]);

        $anzahl = [];

        foreach ($stmt->fetchAll() as $zeile) {
            $anzahl[$zeile['kurstermin_id'] . '|' . $zeile['datum']] = (int) $zeile['anzahl'];
        }

        return $anzahl;
    }

    /**
     * Welche dieser Termine hat ein Mitglied selbst gebucht?
     *
     * @param int    $mitgliedId
     * @param int    $programmId
     * @param string $von  'JJJJ-MM-TT', einschließlich
     * @param string $bis  'JJJJ-MM-TT', einschließlich
     * @return array<string, true>  Schlüssel "terminId|datum", wie bei buchungenZaehlen()
     */
    public function vonMitgliedGebucht(int $mitgliedId, int $programmId, string $von, string $bis): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.kurstermin_id, b.datum
               FROM kursbuchungen b
               JOIN kurstermine k ON k.id = b.kurstermin_id
              WHERE b.mitglied_id = ? AND k.programm_id = ? AND b.datum BETWEEN ? AND ?'
        );
        $stmt->execute([$mitgliedId, $programmId, $von, $bis]);

        $gebucht = [];

        foreach ($stmt->fetchAll() as $zeile) {
            $gebucht[$zeile['kurstermin_id'] . '|' . $zeile['datum']] = true;
        }

        return $gebucht;
    }
}
