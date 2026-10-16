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
 *              Ebenfalls hier: ob das Personal das Konto am Empfang mit dem
 *              Personalausweis bestätigt hat und bis wann das passieren muss,
 *              und die Liste aller noch unbestätigten Konten für den Empfang -
 *              siehe docs/features/empfang.md.
 *
 *              Und die Liste aller Konten mit der Rolle Personal für
 *              admin.php - siehe docs/features/personal-verwalten.md.
 * @see         database/schema.sql
 * @see         api/mitglieder.php
 * @see         api/sitzung.php
 * @see         api/empfang.php
 * @see         api/personal.php
 */

declare(strict_types=1);

namespace Repositories;

use Database;
use Delight\Auth\Role;
use Delight\Auth\Status;

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
     * Setzt die Frist, bis zu der ein Mitglied seinen Personalausweis am
     * Empfang zeigen muss - aufgerufen bei der ersten Tarifwahl.
     *
     * Ist schon eine Frist gesetzt, bleibt sie stehen und wird nie
     * verlängert, sonst ließe sich der Empfang umgehen. Wer schon bestätigt
     * ist, braucht keine.
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
     * Liefert alle Konten, die am Empfang noch nicht bestätigt sind - die
     * Liste für das Personal.
     *
     * Nur Konten im normalen Zustand der Login-Bibliothek. Gesperrte Konten,
     * etwa die Demo-Konten für die Auslastung, kann niemand vorzeigen.
     *
     * Wer einen Tarif hat, hat eine Frist (vorzeigen_bis) und steht oben, die
     * dringendste zuerst. Konten ohne Tarif folgen nach Namen - sie müssen
     * nicht, können aber bestätigt werden. Dazu je Konto die Art des gültigen
     * Ermäßigungsnachweises, falls es einen gibt: Verpasst so jemand die
     * Frist, ruht sein ermäßigter Preis.
     *
     * Liest users (E-Mail, Status) und nachweise nur mit, geschrieben wird
     * hier nur mitglieder.
     *
     * @return array<int, array<string, mixed>>  je Zeile mitglied_id,
     *         vorname, nachname, email, erstellt_am, vorzeigen_bis und
     *         ermaessigung ('schueler', 'student', 'senior' oder null)
     */
    public function unbestaetigteFinden(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.id AS mitglied_id, m.vorname, m.nachname, u.email,
                    m.erstellt_am, m.vorzeigen_bis, n.art AS ermaessigung
               FROM mitglieder m
               JOIN "users" u ON u.id = m.user_id
               LEFT JOIN nachweise n
                      ON n.mitglied_id = m.id
                     AND (n.gueltig_bis IS NULL OR n.gueltig_bis >= CURRENT_DATE)
              WHERE m.bestaetigt_am IS NULL
                AND u.status = ?
              ORDER BY m.vorzeigen_bis IS NULL, m.vorzeigen_bis, m.nachname, m.vorname'
        );
        // Ein Mitglied hat höchstens einen gültigen Nachweis (siehe
        // NachweisRepository::anlegen()), der LEFT JOIN verdoppelt also nichts.
        // Sortiert wird erst "mit Frist vor ohne", dann nach der Frist.
        $stmt->execute([Status::NORMAL]);

        return $stmt->fetchAll();
    }

    /**
     * Hält fest, dass das Personal das Konto am Empfang mit dem
     * Personalausweis bestätigt hat.
     *
     * Einmal gesetzt, wird das nie zurückgenommen - auch nicht, wenn ein
     * Nachweis später abläuft. Ab dann ist der Name fest. Die Bedingung auf
     * bestaetigt_am sorgt dafür, dass ein zweiter Klick den ersten Zeitpunkt
     * nicht überschreibt; die auf den Status, dass nur Konten aus der Liste
     * bestätigt werden.
     *
     * @param int $mitgliedId  mitglieder.id
     * @return bool  false, wenn es nichts zu bestätigen gab - schon bestätigt,
     *               gesperrt oder unbekannt
     */
    public function bestaetigen(int $mitgliedId): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE mitglieder
                SET bestaetigt_am = LOCALTIMESTAMP(0)
              WHERE id = ?
                AND bestaetigt_am IS NULL
                AND user_id IN (SELECT id FROM "users" WHERE status = ?)'
        );
        $stmt->execute([$mitgliedId, Status::NORMAL]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Alle Konten mit der Rolle Personal, nach Namen - für die Verwaltung auf
     * admin.php.
     *
     * Liest users nur mit. Vergeben und entzogen wird die Rolle über die
     * Login-Bibliothek in src/Auth.php. Gesperrte Konten stehen mit in der
     * Liste, damit man auch ihnen die Rolle entziehen kann.
     *
     * @return array<int, array{vorname: string, nachname: string, email: string}>
     */
    public function personalFinden(): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT m.vorname, m.nachname, u.email
               FROM mitglieder m
               JOIN "users" u ON u.id = m.user_id
              WHERE (u.roles_mask & ?) <> 0
              ORDER BY m.nachname, m.vorname'
        );
        $stmt->execute([Role::EMPLOYEE]);

        return $stmt->fetchAll();
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
