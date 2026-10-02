<?php
/**
 * @file        src/Repositories/EmpfehlungRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest und schreibt die Einladungen aus "Freunde werben"
 *              (Tabelle empfehlungen).
 *
 *              Eine Einladung ist OFFEN, solange registriert_am NULL ist.
 *              Registriert sich jemand mit der eingeladenen E-Mail, markiert
 *              registrierungVerbuchen() sie als erfüllt und legt in derselben
 *              Transaktion den Gutschein für den Werber an. Beides zusammen,
 *              damit es nie eine erfüllte Einladung ohne Gutschein gibt.
 * @see         database/schema.sql
 * @see         api/empfehlungen.php
 * @see         api/mitglieder.php
 * @see         docs/features/freunde-werben.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;
use Throwable;

final class EmpfehlungRepository
{
    /**
     * Alle Einladungen eines Mitglieds, die neueste zuerst.
     *
     * @param int $werberId  mitglieder.id, NICHT users.id
     * @return list<array{name: string, email: string, registriert: bool, datum: string}>
     */
    public function fuerWerberFinden(int $werberId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT freund_name, freund_email, registriert_am, erstellt_am::date AS datum
               FROM empfehlungen
              WHERE werber_id = ?
              ORDER BY erstellt_am DESC, id DESC'
        );
        $stmt->execute([$werberId]);

        return array_map(static fn (array $zeile): array => [
            'name'        => $zeile['freund_name'],
            'email'       => $zeile['freund_email'],
            'registriert' => $zeile['registriert_am'] !== null,
            'datum'       => $zeile['datum'],
        ], $stmt->fetchAll());
    }

    /**
     * Wie viele Einladungen eines Mitglieds noch offen sind. Der Endpunkt
     * begrenzt damit, wie viele Adressen jemand auf Vorrat eintragen kann.
     *
     * @param int $werberId  mitglieder.id
     * @return int
     */
    public function offeneZaehlen(int $werberId): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*) FROM empfehlungen
              WHERE werber_id = ? AND registriert_am IS NULL'
        );
        $stmt->execute([$werberId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Speichert eine neue Einladung.
     *
     * ON CONFLICT statt vorher nachzusehen: Tragen zwei Mitglieder im selben
     * Moment dieselbe Adresse ein, gewinnt genau eines - das andere bekommt
     * null statt eines Datenbankfehlers.
     *
     * @param int    $werberId     mitglieder.id
     * @param string $freundName   bereits geprüft, höchstens 120 Zeichen
     * @param string $freundEmail  bereits normalisiert (Auth::emailNormalisieren)
     * @return int|null            id der Einladung, oder null, wenn die
     *                             Adresse schon eingeladen wurde
     */
    public function anlegen(int $werberId, string $freundName, string $freundEmail): ?int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO empfehlungen (werber_id, freund_name, freund_email)
             VALUES (?, ?, ?)
             ON CONFLICT (freund_email) DO NOTHING
             RETURNING id'
        );
        $stmt->execute([$werberId, $freundName, $freundEmail]);

        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * Verbucht eine Registrierung: Gibt es zu dieser E-Mail eine offene
     * Einladung, wird sie erfüllt und der Werber bekommt seinen Gutschein.
     *
     * Die Bedingung registriert_am IS NULL im UPDATE sorgt dafür, dass eine
     * Einladung nur einmal zählt.
     *
     * @param string $email       E-Mail des neuen Kontos, normalisiert
     * @param int    $geworbenId  mitglieder.id des neuen Kontos
     * @param string $code        Code für den Gutschein, siehe GutscheinRepository::neuerCode()
     * @return bool               true, wenn eine Einladung erfüllt wurde
     */
    public function registrierungVerbuchen(string $email, int $geworbenId, string $code): bool
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $erfuellen = $pdo->prepare(
                'UPDATE empfehlungen
                    SET geworben_id = ?, registriert_am = LOCALTIMESTAMP(0)
                  WHERE freund_email = ? AND registriert_am IS NULL
              RETURNING id, werber_id'
            );
            $erfuellen->execute([$geworbenId, $email]);
            $einladung = $erfuellen->fetch();

            if ($einladung === false) {
                $pdo->commit();

                return false;
            }

            $gutschein = $pdo->prepare(
                'INSERT INTO gutscheine (code, mitglied_id, empfehlung_id)
                 VALUES (?, ?, ?)'
            );
            $gutschein->execute([$code, $einladung['werber_id'], $einladung['id']]);

            $pdo->commit();

            return true;
        } catch (Throwable $fehler) {
            $pdo->rollBack();

            throw $fehler;
        }
    }
}
