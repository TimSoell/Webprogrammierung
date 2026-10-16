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
 *
 *              Ebenfalls hier: ob das Personal die Person am Empfang
 *              bestätigt hat und bis wann das passieren muss - siehe
 *              docs/features/empfang.md.
 * @see         database/schema.sql
 * @see         api/mitglieder.php
 * @see         api/sitzung.php
 * @see         api/empfang.php
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
     * @return array{vorname: string, nachname: string}|null  null, wenn es keine gibt
     */
    public function findenNachUserId(int $userId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT vorname, nachname FROM mitglieder WHERE user_id = ?'
        );
        $stmt->execute([$userId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Ändert Vor- und Nachname eines Mitglieds.
     *
     * Ob das gerade erlaubt ist, prüft api/mitglieder.php: Nach der
     * Bestätigung am Empfang nie mehr, davor nicht, solange ein gültiger
     * Nachweis hinterlegt ist.
     *
     * @param int    $mitgliedId  mitglieder.id, NICHT users.id
     * @param string $vorname     bereits geprüft, höchstens 100 Zeichen
     * @param string $nachname    bereits geprüft, höchstens 100 Zeichen
     * @return void
     */
    public function nameAendern(int $mitgliedId, string $vorname, string $nachname): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE mitglieder SET vorname = ?, nachname = ? WHERE id = ?'
        );
        $stmt->execute([$vorname, $nachname, $mitgliedId]);
    }

    /**
     * Liest, wie weit ein Mitglied mit dem Empfang ist: schon bestätigt, und
     * wenn nicht, bis wann es den Ausweis zeigen muss.
     *
     * @param int $mitgliedId  mitglieder.id
     * @return array{bestaetigt_am: string|null, vorzeigen_bis: string|null, frist_verpasst: bool}
     *         frist_verpasst rechnet die Datenbank, damit Browser und Server
     *         nicht mit verschiedenen Uhren zählen
     */
    public function empfangsstandFinden(int $mitgliedId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT bestaetigt_am, vorzeigen_bis,
                    (bestaetigt_am IS NULL AND vorzeigen_bis < CURRENT_DATE) AS frist_verpasst
               FROM mitglieder
              WHERE id = ?'
        );
        $stmt->execute([$mitgliedId]);

        $stand = $stmt->fetch();

        return [
            'bestaetigt_am'  => $stand['bestaetigt_am'] ?? null,
            'vorzeigen_bis'  => $stand['vorzeigen_bis'] ?? null,
            'frist_verpasst' => (bool) ($stand['frist_verpasst'] ?? false),
        ];
    }

    /**
     * Setzt die Frist, bis zu der ein Mitglied den Ausweis am Empfang zeigen
     * muss - aber nur beim ersten Nachweis.
     *
     * Ist schon eine Frist gesetzt, bleibt sie stehen: Ein neuer Upload darf
     * sie nicht verlängern, sonst ließe sich der Empfang umgehen. Wer schon
     * bestätigt ist, braucht keine.
     *
     * @param int    $mitgliedId  mitglieder.id
     * @param string $bis         letzter Tag, 'JJJJ-MM-TT'
     * @return void
     */
    public function fristSetzen(int $mitgliedId, string $bis): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE mitglieder
                SET vorzeigen_bis = ?
              WHERE id = ?
                AND vorzeigen_bis IS NULL
                AND bestaetigt_am IS NULL'
        );
        $stmt->execute([$bis, $mitgliedId]);
    }

    /**
     * Hält fest, dass das Personal die Person am Empfang bestätigt hat.
     *
     * Einmal gesetzt, wird das nie zurückgenommen - auch nicht, wenn der
     * Nachweis später abläuft. Ab dann ist der Name fest. Die Bedingung auf
     * bestaetigt_am sorgt dafür, dass ein zweiter Klick den ersten Zeitpunkt
     * nicht überschreibt.
     *
     * @param int $mitgliedId  mitglieder.id
     * @return void
     */
    public function bestaetigen(int $mitgliedId): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE mitglieder
                SET bestaetigt_am = LOCALTIMESTAMP(0)
              WHERE id = ?
                AND bestaetigt_am IS NULL'
        );
        $stmt->execute([$mitgliedId]);
    }

    /**
     * Gibt es zu dieser E-Mail schon ein Konto? "Freunde werben" fragt damit,
     * ob eine Einladung überhaupt noch zu einer Registrierung führen kann.
     *
     * Liest users, die Tabelle der Login-Bibliothek - nur lesend. Geschrieben
     * wird dort ausschließlich über src/Auth.php.
     *
     * @param string $email  normalisiert (Auth::emailNormalisieren)
     * @return bool
     */
    public function emailVergeben(string $email): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM "users" WHERE email = ?'
        );
        $stmt->execute([$email]);

        return $stmt->fetchColumn() !== false;
    }
}
