<?php
/**
 * @file        src/Repositories/NachweisRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest und schreibt die Nachweise für ermäßigte Preise
 *              (Tabelle nachweise).
 *
 *              Hier liegt die Zuordnung, welche Art von Nachweis zu welcher
 *              Preisgruppe berechtigt - siehe PREISGRUPPE_ARTEN. Die
 *              Bilder selbst kommen nie hier an: Sie werden in
 *              src/Ausweispruefung.php ausgelesen und sofort verworfen.
 * @see         database/schema.sql
 * @see         api/nachweise.php
 * @see         docs/features/nachweise.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;
use InvalidArgumentException;

final class NachweisRepository
{
    /**
     * Welche Nachweisarten zu welcher Preisgruppe berechtigen.
     *
     * 'standard' fehlt absichtlich: Der volle Preis braucht keinen Nachweis.
     * Wer hier eine Art ergänzt, muss auch das ENUM in database/schema.sql
     * erweitern.
     */
    public const PREISGRUPPE_ARTEN = [
        'ermaessigt' => ['schueler', 'student'],
        'senior'     => ['senior'],
    ];

    /**
     * Sucht den gültigen Nachweis, der zu einer Preisgruppe berechtigt.
     *
     * Gültig heißt: unbefristet (gueltig_bis IS NULL, nur bei Senioren) oder
     * das Ablaufdatum liegt heute oder später.
     *
     * @param int    $mitgliedId   mitglieder.id, NICHT users.id
     * @param string $preisgruppe  'ermaessigt' oder 'senior'
     * @return array<string, mixed>|null  null, wenn es keinen gültigen gibt
     * @throws InvalidArgumentException  bei einer Preisgruppe ohne Nachweispflicht
     */
    public function gueltigenFinden(int $mitgliedId, string $preisgruppe): ?array
    {
        $arten = self::PREISGRUPPE_ARTEN[$preisgruppe]
            ?? throw new InvalidArgumentException('Preisgruppe ohne Nachweispflicht: ' . $preisgruppe);

        // Die Fragezeichen entstehen aus der Anzahl der Arten, nicht aus einer
        // Eingabe - die Werte selbst gehen wie immer über execute() in die
        // Abfrage. Es steht also weiterhin kein Wert im SQL-Text.
        $platzhalter = implode(', ', array_fill(0, count($arten), '?'));

        $stmt = Database::connection()->prepare(
            'SELECT art, gueltig_bis, quelle, hinweis, geprueft_am
               FROM nachweise
              WHERE mitglied_id = ?
                AND art IN (' . $platzhalter . ')
                AND (gueltig_bis IS NULL OR gueltig_bis >= CURDATE())
              ORDER BY gueltig_bis IS NULL DESC, gueltig_bis DESC
              LIMIT 1'
        );
        $stmt->execute([$mitgliedId, ...$arten]);

        // Sortierung: unbefristet schlägt befristet, sonst gewinnt der, der
        // am längsten gilt. Wer zweimal hochgeladen hat, soll den besseren
        // seiner Nachweise behalten.
        return $stmt->fetch() ?: null;
    }

    /**
     * Liefert alle Nachweise eines Mitglieds, neueste zuerst.
     *
     * Auch abgelaufene: Im Konto soll sichtbar sein, dass ein Nachweis
     * einmal da war und wann er ausgelaufen ist.
     *
     * @param int $mitgliedId  mitglieder.id
     * @return array<int, array<string, mixed>>
     */
    public function alleFinden(int $mitgliedId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT art, gueltig_bis, quelle, ki_modell, hinweis, geprueft_am
               FROM nachweise
              WHERE mitglied_id = ?
              ORDER BY geprueft_am DESC, id DESC'
        );
        $stmt->execute([$mitgliedId]);

        return $stmt->fetchAll();
    }

    /**
     * Speichert das Ergebnis einer Prüfung.
     *
     * @param int         $mitgliedId   mitglieder.id
     * @param string      $art          'schueler', 'student' oder 'senior'
     * @param string|null $gueltigBis   'JJJJ-MM-TT', null = unbefristet (Senior)
     * @param string      $quelle       'ki' oder 'demo'
     * @param string|null $kiModell     Name des Modells, null im Demo-Modus
     * @param string      $hinweis      was beim Prüfen gelesen wurde
     * @return int                      id des neuen Nachweises
     */
    public function anlegen(
        int $mitgliedId,
        string $art,
        ?string $gueltigBis,
        string $quelle,
        ?string $kiModell,
        string $hinweis
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO nachweise
                 (mitglied_id, art, gueltig_bis, quelle, ki_modell, hinweis)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$mitgliedId, $art, $gueltigBis, $quelle, $kiModell, $hinweis]);

        return (int) Database::connection()->lastInsertId();
    }
}
