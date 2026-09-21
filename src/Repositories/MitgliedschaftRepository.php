<?php
/**
 * @file        src/Repositories/MitgliedschaftRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest und schreibt die Verträge der Mitglieder
 *              (Tabelle mitgliedschaften).
 *
 *              DIE REGEL, die diese Klasse durchsetzt: Die erste Tarifwahl
 *              gilt sofort, jeder spätere Wechsel erst zum nächsten
 *              Monatsersten. Dadurch kann niemand beliebig oft hin und her
 *              springen - siehe
 *              docs/decisions/ADR-0007-tarifwechsel-zum-monatsersten.md
 *
 *              Beide Datumsspalten sind EINSCHLIESSLICH gemeint: beginnt_am
 *              ist der erste, endet_am der letzte Gültigkeitstag. Ein Vertrag
 *              läuft also, wenn beginnt_am <= heute und endet_am entweder
 *              NULL oder >= heute ist. Auf endet_am IS NULL allein zu prüfen
 *              wäre falsch, seit es vorgemerkte Wechsel gibt.
 *
 *              "Höchstens ein laufender Vertrag pro Mitglied" kann die
 *              Datenbank nicht erzwingen: Einen Unique-Index, der nur für
 *              endet_am IS NULL gilt, gibt es in MySQL nicht. Wer an dieser
 *              Klasse vorbei einfügt, umgeht die Regel.
 * @see         database/schema.sql
 * @see         api/mitgliedschaften.php
 * @see         docs/features/mitgliedschaften.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;
use Throwable;

final class MitgliedschaftRepository
{
    /**
     * Die Spalten, die Endpunkt und Seite brauchen. Name und Zugangsrechte
     * kommen aus tarife, Preis und Preisgruppe aus dem Vertrag selbst - der
     * Vertragspreis kann vom heutigen Katalogpreis abweichen.
     */
    private const SPALTEN = 't.kennung, t.name, t.beschreibung,
                    t.zugang_geraete, t.zugang_wellness, t.zugang_kurse,
                    m.preisgruppe, m.preis_monatlich, m.beginnt_am, m.endet_am';

    /**
     * Sucht den heute laufenden Vertrag eines Mitglieds samt Tarifdaten.
     *
     * @param int $mitgliedId  mitglieder.id, NICHT users.id
     * @return array<string, mixed>|null  null, wenn noch kein Tarif gewählt wurde
     */
    public function aktiveFinden(int $mitgliedId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SPALTEN . '
               FROM mitgliedschaften m
               JOIN tarife t ON t.id = m.tarif_id
              WHERE m.mitglied_id = ?
                AND m.beginnt_am <= CURDATE()
                AND (m.endet_am IS NULL OR m.endet_am >= CURDATE())
              ORDER BY m.beginnt_am DESC, m.id DESC
              LIMIT 1'
        );
        $stmt->execute([$mitgliedId]);

        // ORDER BY und LIMIT sind eine Absicherung: Sollte doch einmal mehr
        // als ein laufender Vertrag entstanden sein, gewinnt der neueste,
        // statt dass die Seite einen zufälligen anzeigt.
        return $stmt->fetch() ?: null;
    }

    /**
     * Sucht den vorgemerkten Wechsel eines Mitglieds - den Vertrag, der erst
     * in der Zukunft beginnt.
     *
     * @param int $mitgliedId  mitglieder.id
     * @return array<string, mixed>|null  null, wenn nichts vorgemerkt ist
     */
    public function geplanteFinden(int $mitgliedId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT ' . self::SPALTEN . '
               FROM mitgliedschaften m
               JOIN tarife t ON t.id = m.tarif_id
              WHERE m.mitglied_id = ?
                AND m.beginnt_am > CURDATE()
              ORDER BY m.beginnt_am, m.id
              LIMIT 1'
        );
        $stmt->execute([$mitgliedId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Der Tag, an dem ein heute vorgemerkter Wechsel wirksam wird: der erste
     * Tag des nächsten Monats.
     *
     * Kommt aus der Datenbank und nicht aus PHP, damit die Seite genau das
     * Datum anzeigt, das beim Speichern auch eingetragen wird. PHP und MySQL
     * können unterschiedliche Zeitzonen haben, und am Monatsletzten wäre das
     * ein Tag Unterschied.
     *
     * @return string  Datum als 'JJJJ-MM-TT'
     */
    public function naechsterWechseltermin(): string
    {
        $stmt = Database::connection()->query(
            'SELECT LAST_DAY(CURDATE()) + INTERVAL 1 DAY AS termin'
        );

        return (string) $stmt->fetch()['termin'];
    }

    /**
     * Speichert die allererste Tarifwahl. Sie gilt ab heute.
     *
     * Der Preis wird übergeben und nicht hier aus tarife gelesen: Er gehört
     * zum Vertrag und wird bewusst eingefroren, damit eine spätere
     * Preisänderung im Katalog laufende Verträge nicht verteuert.
     *
     * @param int    $mitgliedId      mitglieder.id
     * @param int    $tarifId         tarife.id
     * @param string $preisgruppe     'standard', 'ermaessigt' oder 'senior'
     * @param float  $preisMonatlich  Preis zum Zeitpunkt des Abschlusses
     * @return int                    id des neuen Vertrags
     */
    public function erstwahlSpeichern(
        int $mitgliedId,
        int $tarifId,
        string $preisgruppe,
        float $preisMonatlich
    ): int {
        $stmt = Database::connection()->prepare(
            'INSERT INTO mitgliedschaften
                 (mitglied_id, tarif_id, preisgruppe, preis_monatlich, beginnt_am)
             VALUES (?, ?, ?, ?, CURDATE())'
        );
        $stmt->execute([$mitgliedId, $tarifId, $preisgruppe, $preisMonatlich]);

        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Merkt einen Tarifwechsel zum nächsten Monatsersten vor.
     *
     * Drei Schritte in einer Transaktion, damit dazwischen kein Zustand
     * entstehen kann, in dem ein Mitglied zwei oder keinen Vertrag hat:
     *   1. eine ältere Vormerkung verwerfen (es gilt immer die letzte)
     *   2. den laufenden Vertrag auf das Monatsende befristen
     *   3. den neuen Vertrag ab dem Monatsersten anlegen
     *
     * Schritt 1 löscht wirklich. Eine Vormerkung ist noch kein Vertrag,
     * sondern eine Absicht - sie gehört nicht in die Historie.
     *
     * @param int    $mitgliedId      mitglieder.id
     * @param int    $tarifId         tarife.id des künftigen Tarifs
     * @param string $preisgruppe     'standard', 'ermaessigt' oder 'senior'
     * @param float  $preisMonatlich  künftiger Preis, eingefroren
     * @return int                    id des vorgemerkten Vertrags
     */
    public function wechselVormerken(
        int $mitgliedId,
        int $tarifId,
        string $preisgruppe,
        float $preisMonatlich
    ): int {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $alteVormerkung = $pdo->prepare(
                'DELETE FROM mitgliedschaften
                  WHERE mitglied_id = ? AND beginnt_am > CURDATE()'
            );
            $alteVormerkung->execute([$mitgliedId]);

            $befristen = $pdo->prepare(
                'UPDATE mitgliedschaften
                    SET endet_am = LAST_DAY(CURDATE())
                  WHERE mitglied_id = ?
                    AND beginnt_am <= CURDATE()
                    AND (endet_am IS NULL OR endet_am >= CURDATE())'
            );
            $befristen->execute([$mitgliedId]);

            $anlegen = $pdo->prepare(
                'INSERT INTO mitgliedschaften
                     (mitglied_id, tarif_id, preisgruppe, preis_monatlich, beginnt_am)
                 VALUES (?, ?, ?, ?, LAST_DAY(CURDATE()) + INTERVAL 1 DAY)'
            );
            $anlegen->execute([$mitgliedId, $tarifId, $preisgruppe, $preisMonatlich]);

            $id = (int) $pdo->lastInsertId();

            $pdo->commit();

            return $id;
        } catch (Throwable $fehler) {
            $pdo->rollBack();

            throw $fehler;
        }
    }

    /**
     * Nimmt einen vorgemerkten Wechsel zurück: Die Vormerkung verschwindet,
     * und der laufende Vertrag läuft wieder unbefristet weiter.
     *
     * Die Bedingung auf endet_am = LAST_DAY(CURDATE()) ist wichtig. Ohne sie
     * würde die Methode auch eine Befristung aufheben, die aus einem anderen
     * Grund gesetzt wurde.
     *
     * @param int $mitgliedId  mitglieder.id
     * @return void
     */
    public function vormerkungZuruecknehmen(int $mitgliedId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $loeschen = $pdo->prepare(
                'DELETE FROM mitgliedschaften
                  WHERE mitglied_id = ? AND beginnt_am > CURDATE()'
            );
            $loeschen->execute([$mitgliedId]);

            $entfristen = $pdo->prepare(
                'UPDATE mitgliedschaften
                    SET endet_am = NULL
                  WHERE mitglied_id = ?
                    AND beginnt_am <= CURDATE()
                    AND endet_am = LAST_DAY(CURDATE())'
            );
            $entfristen->execute([$mitgliedId]);

            $pdo->commit();
        } catch (Throwable $fehler) {
            $pdo->rollBack();

            throw $fehler;
        }
    }
}
