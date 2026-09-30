<?php
/**
 * @file        src/Repositories/AuslastungRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest die typische Auslastungskurve (Tabelle auslastung_basis)
 *              und verwaltet die angekündigten Besuche (Tabelle besuche).
 *
 *              Die angezeigte Auslastung ist immer eine SUMME aus zwei
 *              Quellen:
 *
 *                Basiswert  - die erfundene Kurve aus auslastung_basis
 *                Besuche    - echte Zeilen aus besuche
 *
 *              Nur der zweite Teil verändert sich, wenn jemand eincheckt.
 *              Genau das macht die Vorführung sichtbar: die Kurve steht,
 *              der Aufschlag wächst.
 *
 *              Alle Methoden, die auf besuche schreiben oder löschen,
 *              bekommen die mitglied_id als ERSTEN Parameter und filtern in
 *              jeder Abfrage danach - sonst könnte jemand über eine geratene
 *              id den Besuch eines anderen entfernen.
 * @see         database/schema.sql
 * @see         api/auslastung.php
 * @see         docs/features/auslastung.md
 */

declare(strict_types=1);

namespace Repositories;

use DateTimeImmutable;
use Database;

final class AuslastungRepository
{
    /**
     * Wie viele Personen gleichzeitig ins Studio passen.
     *
     * Bezugsgröße für die Prozentangabe und für die Einstufung in "entspannt",
     * "gut besucht" und "voll". Erfunden wie die Kurve selbst.
     */
    public const KAPAZITAET = 170;

    /**
     * Wie lange ein Besuch gezählt wird, in Minuten.
     *
     * Es gibt kein Auschecken. Ein Besuch verschwindet von selbst aus der
     * Zählung, sobald sein Zeitraum vorbei ist - deshalb braucht niemand
     * daran zu denken, und eine vergessene Zeile verfälscht nichts.
     */
    public const DAUER_MINUTEN = 90;

    /**
     * Lädt die typische Kurve eines Wochentags, eine Zahl je Stunde.
     *
     * @param int $wochentag  1 = Montag bis 7 = Sonntag (wie date('N'))
     * @return array<int, int>  Stunde (0-23) => Personen
     */
    public function basiskurveFinden(int $wochentag): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT stunde, personen
               FROM auslastung_basis
              WHERE wochentag = ?
              ORDER BY stunde'
        );
        $stmt->execute([$wochentag]);

        $kurve = [];

        foreach ($stmt->fetchAll() as $zeile) {
            $kurve[(int) $zeile['stunde']] = (int) $zeile['personen'];
        }

        return $kurve;
    }

    /**
     * Zählt die Besuche, deren Zeitraum einen bestimmten Moment enthält.
     *
     * @param DateTimeImmutable $zeitpunkt
     * @return int
     */
    public function besucheZaehlen(DateTimeImmutable $zeitpunkt): int
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*)
               FROM besuche
              WHERE beginn <= ?
                AND ende   >  ?'
        );
        $stmt->execute([
            $zeitpunkt->format('Y-m-d H:i:s'),
            $zeitpunkt->format('Y-m-d H:i:s'),
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Zählt je Stunde eines Tages, wie viele Besuche sie berühren.
     *
     * Ein Besuch von 17:50 bis 19:20 zählt in Stunde 17, 18 und 19 - er ist
     * in jeder davon zeitweise anwesend. Deshalb wird nicht nach beginn
     * gruppiert, sondern je Stunde geprüft, ob der Zeitraum sie überlappt.
     *
     * Die Abfrage holt nur die Besuche des Tages und verteilt sie in PHP auf
     * die Stunden. Eine reine SQL-Lösung bräuchte eine Hilfstabelle mit den
     * Zahlen 0 bis 23; das wäre für 24 Werte mehr Aufwand als Nutzen.
     *
     * @param DateTimeImmutable $tag  Irgendein Zeitpunkt des gesuchten Tages
     * @return array<int, int>  Stunde (0-23) => Anzahl Besuche
     */
    public function besucheProStundeZaehlen(DateTimeImmutable $tag): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT beginn, ende
               FROM besuche
              WHERE ende   >  ?
                AND beginn <  ?'
        );
        $stmt->execute([
            $tag->format('Y-m-d 00:00:00'),
            $tag->format('Y-m-d 23:59:59'),
        ]);

        $proStunde = array_fill(0, 24, 0);
        $datum     = $tag->format('Y-m-d');

        foreach ($stmt->fetchAll() as $zeile) {
            $beginn = new DateTimeImmutable((string) $zeile['beginn']);
            $ende   = new DateTimeImmutable((string) $zeile['ende']);

            for ($stunde = 0; $stunde < 24; $stunde++) {
                $von = new DateTimeImmutable(sprintf('%s %02d:00:00', $datum, $stunde));
                $bis = $von->modify('+1 hour');

                // Überlappung zweier Zeiträume: der eine beginnt vor dem Ende
                // des anderen und endet nach dessen Beginn.
                if ($beginn < $bis && $ende > $von) {
                    $proStunde[$stunde]++;
                }
            }
        }

        return $proStunde;
    }

    /**
     * Trägt einen Besuch ein.
     *
     * @param int               $mitgliedId  id aus mitglieder
     * @param DateTimeImmutable $beginn
     * @param DateTimeImmutable $ende
     * @param string            $art         'jetzt' oder 'geplant'
     * @return int  id des neuen Besuchs
     */
    public function besuchEintragen(
        int $mitgliedId,
        DateTimeImmutable $beginn,
        DateTimeImmutable $ende,
        string $art
    ): int {
        $verbindung = Database::connection();

        $stmt = $verbindung->prepare(
            'INSERT INTO besuche (mitglied_id, beginn, ende, art)
                  VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $mitgliedId,
            $beginn->format('Y-m-d H:i:s'),
            $ende->format('Y-m-d H:i:s'),
            $art,
        ]);

        return (int) $verbindung->lastInsertId();
    }

    /**
     * Prüft, ob sich ein Zeitraum mit einem schon eingetragenen Besuch
     * desselben Mitglieds überschneidet.
     *
     * Verhindert, dass jemand durch mehrfaches Klicken dieselbe Zeit zehnmal
     * anmeldet und die Auslastung im Alleingang hochtreibt.
     *
     * @param int               $mitgliedId
     * @param DateTimeImmutable $beginn
     * @param DateTimeImmutable $ende
     * @return bool
     */
    public function ueberschneidungFinden(
        int $mitgliedId,
        DateTimeImmutable $beginn,
        DateTimeImmutable $ende
    ): bool {
        $stmt = Database::connection()->prepare(
            'SELECT 1
               FROM besuche
              WHERE mitglied_id = ?
                AND beginn < ?
                AND ende   > ?
              LIMIT 1'
        );
        $stmt->execute([
            $mitgliedId,
            $ende->format('Y-m-d H:i:s'),
            $beginn->format('Y-m-d H:i:s'),
        ]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Lädt die noch nicht beendeten Besuche eines Mitglieds.
     *
     * Vergangene Besuche werden nicht angezeigt - der Mitgliedsbereich ist
     * kein Trainingstagebuch, sondern zeigt, was noch ansteht.
     *
     * @param int $mitgliedId  id aus mitglieder
     * @return list<array{id: int, beginn: string, ende: string, art: string}>
     */
    public function offeneBesucheFinden(int $mitgliedId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT id, beginn, ende, art
               FROM besuche
              WHERE mitglied_id = ?
                AND ende > LOCALTIMESTAMP
              ORDER BY beginn'
        );
        $stmt->execute([$mitgliedId]);

        return array_map(
            static fn (array $zeile): array => [
                'id'     => (int) $zeile['id'],
                'beginn' => (string) $zeile['beginn'],
                'ende'   => (string) $zeile['ende'],
                'art'    => (string) $zeile['art'],
            ],
            $stmt->fetchAll()
        );
    }

    /**
     * Löscht einen Besuch, aber nur den eines bestimmten Mitglieds.
     *
     * @param int $mitgliedId  id aus mitglieder
     * @param int $besuchId    id aus besuche
     * @return bool  true, wenn eine Zeile entfernt wurde
     */
    public function besuchLoeschen(int $mitgliedId, int $besuchId): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM besuche
                   WHERE id = ?
                     AND mitglied_id = ?'
        );
        $stmt->execute([$besuchId, $mitgliedId]);

        return $stmt->rowCount() > 0;
    }
}
