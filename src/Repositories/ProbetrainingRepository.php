<?php
/**
 * @file        src/Repositories/ProbetrainingRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Alles zum Probetraining: welche Coaches es anbieten, wann sie
 *              können (verfuegbarkeiten), was schon vergeben ist, und die
 *              Buchungen selbst (probetrainings).
 *
 *              Die Methoden für Buchungen bekommen die mitglied_id und
 *              filtern danach, genau wie in KursbuchungRepository.
 * @see         database/schema.sql
 * @see         api/verfuegbarkeiten.php
 * @see         api/probetrainings.php
 */

declare(strict_types=1);

namespace Repositories;

use Database;
use PDOException;

final class ProbetrainingRepository
{
    /**
     * Lädt alle Coaches, die mindestens ein Verfügbarkeitsfenster haben.
     *
     * Wer kein Fenster hat, bietet kein Probetraining an und taucht in der
     * Auswahl gar nicht erst auf.
     *
     * @return list<array{id: int, name: string, schwerpunkt: string, bild: string, bildAlt: string, programm: string}>
     */
    public function coachesFinden(): array
    {
        $stmt = Database::connection()->query(
            'SELECT c.id, c.name, c.schwerpunkt, c.bild, c.bild_alt, p.name AS programm
               FROM coaches c
               JOIN programme p ON p.id = c.programm_id
              WHERE EXISTS (SELECT 1 FROM verfuegbarkeiten v WHERE v.coach_id = c.id)
              ORDER BY p.id, c.position, c.id'
        );

        return array_map(static fn (array $zeile): array => [
            'id'          => (int) $zeile['id'],
            'name'        => $zeile['name'],
            'schwerpunkt' => $zeile['schwerpunkt'],
            // Wie in ProgrammRepository::coachesFinden(): In der Datenbank
            // steht nur der Dateiname.
            'bild'        => 'assets/img/coaches/' . $zeile['bild'],
            'bildAlt'     => $zeile['bild_alt'],
            'programm'    => $zeile['programm'],
        ], $stmt->fetchAll());
    }

    /**
     * Sucht einen Coach. Gebraucht, um eine angefragte coachId zu prüfen.
     *
     * @param int $coachId
     * @return array{id: int, name: string}|null
     */
    public function coachFinden(int $coachId): ?array
    {
        $stmt = Database::connection()->prepare('SELECT id, name FROM coaches WHERE id = ?');
        $stmt->execute([$coachId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Die Wochenfenster eines Coaches, z. B. "montags 10 bis 13 Uhr".
     *
     * @param int $coachId
     * @return list<array{wochentag: int, von: string, bis: string}>
     */
    public function fensterFinden(int $coachId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT wochentag, von, bis FROM verfuegbarkeiten WHERE coach_id = ? ORDER BY wochentag, von'
        );
        $stmt->execute([$coachId]);

        return $stmt->fetchAll();
    }

    /**
     * Welche Termine dieses Coaches sind im Zeitraum schon vergeben?
     *
     * @param int    $coachId
     * @param string $von  'JJJJ-MM-TT', einschließlich
     * @param string $bis  'JJJJ-MM-TT', einschließlich
     * @return list<string>  Beginne als 'JJJJ-MM-TT HH:MM:SS'
     */
    public function belegtFinden(int $coachId, string $von, string $bis): array
    {
        // beginnt_am::date BETWEEN: Der letzte Tag soll ganz dazugehören,
        // nicht nur bis 00:00 Uhr.
        $stmt = Database::connection()->prepare(
            'SELECT beginnt_am FROM probetrainings WHERE coach_id = ? AND beginnt_am::date BETWEEN ? AND ?'
        );
        $stmt->execute([$coachId, $von, $bis]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Bucht ein Probetraining.
     *
     * Ob der Termin noch frei ist, prüft nicht dieser Code, sondern der
     * UNIQUE-Schlüssel uniq_coach_beginn in der Datenbank. Nur so ist es
     * auch dann sicher, wenn zwei Leute im selben Augenblick klicken.
     *
     * @param int    $mitgliedId
     * @param int    $coachId
     * @param string $beginntAm  'JJJJ-MM-TT HH:MM', vorher über Terminplan geprüft
     * @param string $stufe      einer der Werte aus Terminplan::STUFEN
     * @return bool              false, wenn der Termin inzwischen vergeben ist
     */
    public function buchen(int $mitgliedId, int $coachId, string $beginntAm, string $stufe): bool
    {
        try {
            $stmt = Database::connection()->prepare(
                'INSERT INTO probetrainings (mitglied_id, coach_id, beginnt_am, stufe) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$mitgliedId, $coachId, $beginntAm . ':00', $stufe]);

            return true;
        } catch (PDOException $fehler) {
            // 23505 = Verletzung eines UNIQUE-Schlüssels, hier uniq_coach_beginn.
            if ($fehler->getCode() === '23505') {
                return false;
            }

            throw $fehler;
        }
    }

    /**
     * Die kommenden Probetrainings eines Mitglieds, für den Profilkalender.
     *
     * @param int    $mitgliedId
     * @param string $abDatum  'JJJJ-MM-TT' - frühere fallen weg
     * @return list<array{id: int, beginnt_am: string, stufe: string, coach: string, programm: string}>
     */
    public function fuerMitgliedFinden(int $mitgliedId, string $abDatum): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT t.id, t.beginnt_am, t.stufe, c.name AS coach, p.name AS programm
               FROM probetrainings t
               JOIN coaches   c ON c.id = t.coach_id
               JOIN programme p ON p.id = c.programm_id
              WHERE t.mitglied_id = ? AND t.beginnt_am::date >= ?
              ORDER BY t.beginnt_am'
        );
        $stmt->execute([$mitgliedId, $abDatum]);

        return $stmt->fetchAll();
    }

    /**
     * Sucht ein Probetraining, aber nur, wenn es diesem Mitglied gehört.
     *
     * @param int $id          id aus probetrainings
     * @param int $mitgliedId
     * @return array{beginnt_am: string}|null
     */
    public function findenFuerMitglied(int $id, int $mitgliedId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT beginnt_am FROM probetrainings WHERE id = ? AND mitglied_id = ?'
        );
        $stmt->execute([$id, $mitgliedId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Löscht ein Probetraining. Der Termin ist danach wieder frei.
     *
     * @param int $id
     * @param int $mitgliedId
     * @return void
     */
    public function stornieren(int $id, int $mitgliedId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM probetrainings WHERE id = ? AND mitglied_id = ?'
        );
        $stmt->execute([$id, $mitgliedId]);
    }
}
