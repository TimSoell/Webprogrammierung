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
     * Sucht die mitglieder.id zu einem Konto.
     *
     * Gebraucht von allen Endpunkten, die etwas Fachliches am Mitglied
     * speichern: Die Login-Bibliothek kennt nur users.id, fachliche Tabellen
     * verweisen aber auf mitglieder.id.
     *
     * @param int $userId  id des Kontos aus der Tabelle users
     * @return int|null    null, wenn es zu dem Konto keine Stammdaten gibt
     */
    public function idFindenNachUserId(int $userId): ?int
    {
        $stmt = Database::connection()->prepare(
            'SELECT id FROM mitglieder WHERE user_id = ?'
        );
        $stmt->execute([$userId]);

        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * Sucht die Stammdaten zu einem Konto.
     *
     * @param int $userId  id des Kontos aus der Tabelle users
    * @return array{vorname: string, nachname: string, profilbild: string|null}|null
    *         null, wenn es keine Stammdaten gibt
     */
    public function findenNachUserId(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT vorname, nachname, replace(encode(profilbild, 'base64'), E'\\n', '') AS profilbild "
            . 'FROM mitglieder WHERE user_id = ?'
        );
        $stmt->execute([$userId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Speichert das bereits geprüfte Profilbild eines Kontos.
     *
     * @param int    $userId     id des Kontos aus der Tabelle users
     * @param string $profilbild JPEG-Bilddaten als base64
     */
    public function profilbildSpeichern(int $userId, string $profilbild): void
    {
        $stmt = Database::connection()->prepare(
            "UPDATE mitglieder SET profilbild = decode(?, 'base64') WHERE user_id = ?"
        );
        $stmt->execute([$profilbild, $userId]);
    }
}
