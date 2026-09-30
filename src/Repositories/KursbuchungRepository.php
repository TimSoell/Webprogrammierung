<?php
/**
 * @file        src/Repositories/KursbuchungRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Bucht, liest und storniert Kurstermine eines Mitglieds
 *              (Tabelle kursbuchungen).
 *
 *              Jede Methode bekommt die mitglied_id und filtert danach.
 *              Ohne das könnte jemand über eine erratene id die Buchung
 *              eines anderen lesen oder stornieren.
 * @see         database/schema.sql
 * @see         api/kursbuchungen.php
 */

declare(strict_types=1);

namespace Repositories;

use Database;
use PDOException;

final class KursbuchungRepository
{
    /** Ergebnis von buchen(): Platz ist sicher. */
    public const GEBUCHT = 'gebucht';

    /** Ergebnis von buchen(): kein Platz mehr frei. */
    public const VOLL = 'voll';

    /** Ergebnis von buchen(): dieses Mitglied hat den Termin schon. */
    public const DOPPELT = 'doppelt';

    /**
     * Bucht einen Platz - aber nur, wenn noch einer frei ist.
     *
     * Die Prüfung "ist noch Platz?" und das Speichern laufen in EINER
     * Transaktion, und der Kurstermin ist währenddessen gesperrt
     * (SELECT ... FOR UPDATE). Klicken zwei Leute gleichzeitig auf den
     * letzten Platz, wartet der zweite, bis der erste fertig ist, und zählt
     * dann richtig. Ohne Sperre sähen beide "19 von 20" und am Ende stünden
     * 21 im Kurs.
     *
     * @param int    $mitgliedId
     * @param int    $kursterminId  id aus kurstermine, vorher über Terminplan geprüft
     * @param string $datum         'JJJJ-MM-TT', vorher über Terminplan geprüft
     * @param string $stufe         einer der Werte aus Terminplan::STUFEN
     * @return string               self::GEBUCHT, self::VOLL oder self::DOPPELT
     */
    public function buchen(int $mitgliedId, int $kursterminId, string $datum, string $stufe): string
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT max_teilnehmer, stammplaetze FROM kurstermine WHERE id = ? FOR UPDATE'
            );
            $stmt->execute([$kursterminId]);
            $termin = $stmt->fetch();

            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM kursbuchungen WHERE kurstermin_id = ? AND datum = ?'
            );
            $stmt->execute([$kursterminId, $datum]);
            $belegt = (int) $termin['stammplaetze'] + (int) $stmt->fetchColumn();

            if ($belegt >= (int) $termin['max_teilnehmer']) {
                $pdo->rollBack();
                return self::VOLL;
            }

            $stmt = $pdo->prepare(
                'INSERT INTO kursbuchungen (kurstermin_id, datum, mitglied_id, stufe) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$kursterminId, $datum, $mitgliedId, $stufe]);

            $pdo->commit();
            return self::GEBUCHT;

        } catch (PDOException $fehler) {
            $pdo->rollBack();

            // 23505 = Verletzung eines UNIQUE-Schlüssels. Hier heißt das:
            // uniq_termin_datum_mitglied - das Mitglied hat den Termin schon.
            if ($fehler->getCode() === '23505') {
                return self::DOPPELT;
            }

            throw $fehler;
        }
    }

    /**
     * Lädt die kommenden Kursbuchungen eines Mitglieds, für den Profilkalender.
     *
     * @param int    $mitgliedId
     * @param string $abDatum  'JJJJ-MM-TT' - frühere Buchungen fallen weg
     * @return list<array{id: int, datum: string, beginn: string, dauer_minuten: int, stufe: string, programm: string, slug: string, format: string, coach: string}>
     */
    public function fuerMitgliedFinden(int $mitgliedId, string $abDatum): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.id, b.datum, k.beginn, k.dauer_minuten, b.stufe,
                    p.name  AS programm,
                    p.slug,
                    m.titel AS format,
                    c.name  AS coach
               FROM kursbuchungen b
               JOIN kurstermine k ON k.id = b.kurstermin_id
               JOIN programme   p ON p.id = k.programm_id
               JOIN merkmale    m ON m.id = k.format_id
               JOIN coaches     c ON c.id = k.coach_id
              WHERE b.mitglied_id = ? AND b.datum >= ?
              ORDER BY b.datum, k.beginn'
        );
        $stmt->execute([$mitgliedId, $abDatum]);

        return $stmt->fetchAll();
    }

    /**
     * Sucht eine Buchung, aber nur, wenn sie diesem Mitglied gehört.
     *
     * @param int $id          id aus kursbuchungen
     * @param int $mitgliedId
     * @return array{datum: string, beginn: string}|null  null, wenn es sie nicht gibt oder sie jemand anderem gehört
     */
    public function findenFuerMitglied(int $id, int $mitgliedId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.datum, k.beginn
               FROM kursbuchungen b
               JOIN kurstermine k ON k.id = b.kurstermin_id
              WHERE b.id = ? AND b.mitglied_id = ?'
        );
        $stmt->execute([$id, $mitgliedId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Löscht eine Buchung. Der Platz ist danach wieder frei.
     *
     * @param int $id          id aus kursbuchungen
     * @param int $mitgliedId
     * @return void
     */
    public function stornieren(int $id, int $mitgliedId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM kursbuchungen WHERE id = ? AND mitglied_id = ?'
        );
        $stmt->execute([$id, $mitgliedId]);
    }

    /**
     * Löscht alle Buchungen eines Mitglieds ab einem Tag. Gebraucht, wenn ein
     * Tarifwechsel die Kurse ab dann nicht mehr abdeckt - siehe
     * api/mitgliedschaften.php.
     *
     * @param int    $mitgliedId
     * @param string $abDatum  'JJJJ-MM-TT', einschließlich
     * @return int             so viele Buchungen wurden storniert
     */
    public function abDatumStornieren(int $mitgliedId, string $abDatum): int
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM kursbuchungen WHERE mitglied_id = ? AND datum >= ?'
        );
        $stmt->execute([$mitgliedId, $abDatum]);

        return $stmt->rowCount();
    }
}
