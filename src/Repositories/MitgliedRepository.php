<?php
/**
 * @file        src/Repositories/MitgliedRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest und schreibt die Stammdaten der Mitglieder (Tabelle
 *              mitglieder).
 *
 *              E-Mail-Adresse und Passwort stehen NICHT hier, sondern in der
 *              Tabelle users der Login-Bibliothek. Verbunden sind beide über
 *              user_id. Mehr dazu in docs/features/mitglieder-login.md.
 * @see         database/schema.sql
 * @see         api/mitglieder.php
 * @see         api/sitzung.php
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class MitgliedRepository
{
    /**
     * Legt die Stammdaten zu einem frisch registrierten Konto an.
     *
     * @param int    $userId    id des Kontos aus der Tabelle users
     * @param string $vorname   bereits geprüft, höchstens 100 Zeichen
     * @param string $nachname  bereits geprüft, höchstens 100 Zeichen
     * @return int              id des neuen Mitglieds
     */
    public function anlegen(int $userId, string $vorname, string $nachname): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO mitglieder (user_id, vorname, nachname) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $vorname, $nachname]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Sucht die Stammdaten zu einem Konto.
     *
     * Die id kommt mit, weil alle fachlichen Tabellen auf mitglieder.id
     * verweisen und nicht auf users.id - die Mitgliedschaften zum Beispiel.
     * Wer nur den Namen braucht, ignoriert sie.
     *
     * @param int $userId  id des Kontos aus der Tabelle users
     * @return array{id: int, vorname: string, nachname: string}|null  null, wenn es keine gibt
     */
    public function findenNachUserId(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, vorname, nachname FROM mitglieder WHERE user_id = ?'
        );
        $stmt->execute([$userId]);

        return $stmt->fetch() ?: null;
    }
}
