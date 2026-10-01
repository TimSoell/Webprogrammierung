<?php
/**
 * @file        src/Repositories/BewertungRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest und schreibt die Bewertungen des Studios (Tabelle
 *              bewertungen).
 *
 *              Ob jemand beim Schreiben Mitglied war, entscheidet nicht diese
 *              Klasse, sondern api/bewertungen.php über
 *              MitgliedschaftRepository::aktiveFinden(). Hier wird das
 *              Ergebnis nur gespeichert.
 * @see         database/schema.sql
 * @see         api/bewertungen.php
 * @see         docs/features/bewertungen.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class BewertungRepository
{
    /**
     * Alle Bewertungen, die neueste zuerst - in der Form, die
     * assets/js/services/bewertungen.js beschreibt.
     *
     * Das Bild ist das freigegebene Profilbild der Person, sonst das Bild
     * aus der Spalte bild (nur bei den Beispielen aus seed.sql), sonst null.
     *
     * @return list<array{id: int, name: string, mitglied: bool, sterne: int, text: string, datum: string, bild: string|null}>
     */
    public function alleFinden(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT b.id, b.name, b.war_mitglied, b.sterne, b.text, b.erstellt_am::date AS datum, b.bild,
                    p.mitglied_id AS profilbild_von,
                    extract(epoch FROM p.geaendert_am)::bigint AS profilbild_version
               FROM bewertungen b
               LEFT JOIN profilbilder p ON p.mitglied_id = b.mitglied_id AND p.oeffentlich = 1
              ORDER BY b.erstellt_am DESC, b.id DESC'
        );
        $stmt->execute();

        return array_map(static fn (array $zeile): array => [
            'id'       => (int) $zeile['id'],
            'name'     => $zeile['name'],
            'mitglied' => (bool) $zeile['war_mitglied'],
            'sterne'   => (int) $zeile['sterne'],
            'text'     => $zeile['text'],
            'datum'    => $zeile['datum'],
            'bild'     => $zeile['profilbild_von'] !== null
                ? ProfilbildRepository::url((int) $zeile['profilbild_von'], (int) $zeile['profilbild_version'])
                : $zeile['bild'],
        ], $stmt->fetchAll());
    }

    /**
     * Hat dieses Mitglied schon bewertet? Pro Konto gibt es eine Bewertung.
     *
     * @param int $mitgliedId  mitglieder.id, NICHT users.id
     * @return bool
     */
    public function vorhanden(int $mitgliedId): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM bewertungen WHERE mitglied_id = ?'
        );
        $stmt->execute([$mitgliedId]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Speichert eine neue Bewertung.
     *
     * @param int    $mitgliedId   mitglieder.id, NICHT users.id
     * @param string $name         Anzeigename, z. B. 'Lea M.'
     * @param bool   $warMitglied  true, wenn gerade ein aktiver Vertrag läuft
     * @param int    $sterne       1 bis 5, bereits geprüft
     * @param string $text         bereits geprüft, höchstens 1000 Zeichen
     * @return int                 id der neuen Bewertung
     */
    public function anlegen(int $mitgliedId, string $name, bool $warMitglied, int $sterne, string $text): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO bewertungen (mitglied_id, name, war_mitglied, sterne, text)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$mitgliedId, $name, $warMitglied ? 1 : 0, $sterne, $text]);

        return (int) Database::connection()->lastInsertId();
    }
}
