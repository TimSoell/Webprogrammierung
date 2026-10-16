<?php
/**
 * @file        src/Repositories/AuslastungDemoRepository.php
 * @layer       5 – Repository (Entwicklungsdaten)
 * @description Ergänzt erkennbare Demo-Mitglieder und heutige Besuche, ohne
 *              echte Konten oder Besuche zu verändern. Nur per CLI in der Dev-DB.
 * @see         docs/features/auslastung.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;
use DateTimeImmutable;
use LogicException;
use Throwable;

final class AuslastungDemoRepository
{
    /**
     * Ergänzt Demo-Besuche für heute; erneute Ausführung ersetzt nur deren Besuche.
     *
     * Die gesperrten Konten haben keine verwendbaren Zugangsdaten.
     *
     * @return int Anzahl neu eingetragener Demo-Besuche
     * @throws LogicException Außerhalb der CLI oder bei abweichender Datenbank
     */
    public function heuteEintragen(): int
    {
        $vorlage = require dirname(__DIR__, 2) . '/config/config.example.php';
        if (PHP_SAPI !== 'cli' || getenv('VERCEL') === '1'
            || CONFIG['db']['host'] !== $vorlage['db']['host']
            || CONFIG['db']['user'] !== $vorlage['db']['user']) {
            throw new LogicException('Demo-Daten sind nur per CLI in schwitzkasten-dev erlaubt.');
        }

        $db = Database::connection();
        $jetzt = new DateTimeImmutable();
        $datum = $jetzt->format('Y-m-d');
        $muster = 'auslastung-demo-%@example.invalid';
        $db->beginTransaction();

        try {
            $konten = $db->prepare(
                'INSERT INTO users (email, password, status, verified, resettable, registered)
                 SELECT ? || nummer || ?, ?, ?, ?, ?, ? FROM generate_series(1, 150) AS nummer
                 ON CONFLICT (email) DO NOTHING'
            );
            $konten->execute(['auslastung-demo-', '@example.invalid', '!', 3, 0, 0, time()]);

            $mitglieder = $db->prepare(
                'INSERT INTO mitglieder (user_id, vorname, nachname)
                 SELECT id, ?, email FROM users WHERE email LIKE ?
                 ON CONFLICT (user_id) DO NOTHING'
            );
            $mitglieder->execute(['Auslastung-Demo', $muster]);

            $loeschen = $db->prepare(
                'DELETE FROM besuche WHERE mitglied_id IN (
                    SELECT m.id FROM mitglieder m JOIN users u ON u.id = m.user_id
                    WHERE u.email LIKE ?
                 ) AND beginn < ? AND ende > ?'
            );
            $loeschen->execute([$muster, $jetzt->modify('tomorrow')->format('Y-m-d 00:00:00'), $datum . ' 00:00:00']);

            $besuche = $db->prepare(
                'INSERT INTO besuche (mitglied_id, beginn, ende, art)
                 SELECT m.id, CAST(? AS timestamp), CAST(? AS timestamp), ?
                   FROM mitglieder m JOIN users u ON u.id = m.user_id
                  WHERE u.email LIKE ? AND NOT EXISTS (
                    SELECT 1 FROM besuche b WHERE b.mitglied_id = m.id
                    AND b.beginn < CAST(? AS timestamp) AND b.ende > CAST(? AS timestamp)
                  ) ORDER BY m.id LIMIT CAST(? AS integer)'
            );

            $anzahl = 0;
            foreach ([['08:00', 24], ['11:00', 18], ['16:00', 120], ['19:00', 65], [$jetzt->format('H:i'), 45]] as [$zeit, $menge]) {
                $beginn = new DateTimeImmutable($datum . ' ' . $zeit);
                $ende = $beginn->modify('+' . AuslastungRepository::DAUER_MINUTEN . ' minutes');
                $von = $beginn->format('Y-m-d H:i:s');
                $bis = $ende->format('Y-m-d H:i:s');
                $besuche->execute([$von, $bis, $beginn <= $jetzt ? 'jetzt' : 'geplant', $muster, $bis, $von, $menge]);
                $anzahl += $besuche->rowCount();
            }

            $db->commit();
            return $anzahl;
        } catch (Throwable $fehler) {
            $db->rollBack();
            throw $fehler;
        }
    }
}
