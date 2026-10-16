<?php
/**
 * @file        src/Repositories/GutscheinRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest die Gutscheine eines Mitglieds und löst sie ein
 *              (Tabelle gutscheine).
 *
 *              Ein Gutschein ändert keinen Vertrag. Beim Einlösen wird nur
 *              ein Zeitraum eingetragen (gratis_von bis gratis_bis, beide
 *              einschließlich), in dem der Basisplan nichts kostet. Ob in
 *              diesem Zeitraum wirklich der Basisplan läuft, entscheidet
 *              api/mitgliedschaften.php beim Anzeigen - siehe
 *              docs/decisions/ADR-0021-gutschein-als-gratiszeitraum.md
 *
 *              Angelegt werden Gutscheine nicht hier, sondern zusammen mit
 *              der erfüllten Einladung in
 *              EmpfehlungRepository::registrierungVerbuchen().
 *
 *              FREIGEGEBEN ist ein Gutschein erst, wenn das Personal das
 *              Konto des Geworbenen am Empfang bestätigt hat
 *              (mitglieder.bestaetigt_am). Sonst ließe sich mit erfundenen
 *              Konten Gratiszeit sammeln.
 * @see         database/schema.sql
 * @see         api/gutscheine.php
 * @see         docs/features/freunde-werben.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class GutscheinRepository
{
    /** So viele Monate ist der Basisplan mit einem Gutschein gratis. */
    public const GRATIS_MONATE = 3;

    /**
     * Zeichen für den Code: Großbuchstaben und Ziffern ohne 0, O, 1 und I,
     * weil man die beim Abtippen verwechselt.
     */
    private const ZEICHEN = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Erzeugt einen neuen Code, z. B. 'SK-7F3K9QXM'.
     *
     * random_int() und nicht mt_rand(): Der Code soll sich nicht erraten
     * lassen. Acht Zeichen aus 32 ergeben rund eine Billion Möglichkeiten.
     *
     * @return string
     */
    public static function neuerCode(): string
    {
        $code = 'SK-';

        for ($i = 0; $i < 8; $i++) {
            $code .= self::ZEICHEN[random_int(0, strlen(self::ZEICHEN) - 1)];
        }

        return $code;
    }

    /**
     * Alle Gutscheine eines Mitglieds, der neueste zuerst.
     *
     * Dazu je Gutschein, ob er freigegeben ist, und der Name, unter dem der
     * Werber den Freund eingeladen hat - für den Hinweis, auf wen er wartet.
     *
     * @param int $mitgliedId  mitglieder.id, NICHT users.id
     * @return list<array{code: string, eingeloest: bool, freigegeben: bool, freund: string, gratisVon: string|null, gratisBis: string|null}>
     */
    public function fuerMitgliedFinden(int $mitgliedId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT g.code, g.eingeloest_am, g.gratis_von, g.gratis_bis,
                    e.freund_name, m.bestaetigt_am
               FROM gutscheine g
               JOIN empfehlungen e ON e.id = g.empfehlung_id
               LEFT JOIN mitglieder m ON m.id = e.geworben_id
              WHERE g.mitglied_id = ?
              ORDER BY g.erstellt_am DESC, g.id DESC'
        );
        $stmt->execute([$mitgliedId]);

        return array_map(static fn (array $zeile): array => [
            'code'        => $zeile['code'],
            'eingeloest'  => $zeile['eingeloest_am'] !== null,
            'freigegeben' => $zeile['bestaetigt_am'] !== null,
            'freund'      => $zeile['freund_name'],
            'gratisVon'   => $zeile['gratis_von'],
            'gratisBis'   => $zeile['gratis_bis'],
        ], $stmt->fetchAll());
    }

    /**
     * Sucht einen Gutschein, aber nur, wenn er diesem Mitglied gehört.
     *
     * Freigegeben ist er, sobald das Konto des Geworbenen am Empfang
     * bestätigt ist. LEFT JOIN, weil geworben_id nach dem Löschen dieses
     * Kontos leer ist - dann bleibt der Gutschein gesperrt.
     *
     * @param string $code        wie eingetippt, bereits in Großbuchstaben
     * @param int    $mitgliedId  mitglieder.id
     * @return array{id: int, eingeloest: bool, freigegeben: bool, freund: string}|null
     *         null, wenn es ihn nicht gibt oder er jemand anderem gehört
     */
    public function findenFuerMitglied(string $code, int $mitgliedId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT g.id, g.eingeloest_am, e.freund_name, m.bestaetigt_am
               FROM gutscheine g
               JOIN empfehlungen e ON e.id = g.empfehlung_id
               LEFT JOIN mitglieder m ON m.id = e.geworben_id
              WHERE g.code = ? AND g.mitglied_id = ?'
        );
        $stmt->execute([$code, $mitgliedId]);
        $zeile = $stmt->fetch();

        return $zeile === false ? null : [
            'id'          => (int) $zeile['id'],
            'eingeloest'  => $zeile['eingeloest_am'] !== null,
            'freigegeben' => $zeile['bestaetigt_am'] !== null,
            'freund'      => $zeile['freund_name'],
        ];
    }

    /**
     * Löst einen Gutschein ein und trägt den Gratiszeitraum ein.
     *
     * Der Zeitraum beginnt am gewünschten Tag - oder, falls ein früher
     * eingelöster Gutschein dann noch läuft, am Tag nach dessen Ende. So
     * hängen sich mehrere Gutscheine hintereinander, statt sich zu
     * überlappen und Monate zu verschenken.
     *
     * Die Bedingung eingeloest_am IS NULL im UPDATE verhindert, dass ein
     * Gutschein durch zwei schnelle Klicks zweimal eingelöst wird.
     *
     * @param int         $id          gutscheine.id
     * @param int         $mitgliedId  mitglieder.id des Besitzers
     * @param string|null $abDatum     'JJJJ-MM-TT', frühester erster Gratistag; null = heute
     * @return array{gratisVon: string, gratisBis: string}|null  null, wenn er schon eingelöst war
     */
    public function einloesen(int $id, int $mitgliedId, ?string $abDatum): ?array
    {
        $stmt = Database::connection()->prepare(
            "WITH beginn AS (
                 SELECT GREATEST(
                            COALESCE(?::date, CURRENT_DATE),
                            COALESCE(MAX(gratis_bis) + 1, CURRENT_DATE)
                        ) AS tag
                   FROM gutscheine
                  WHERE mitglied_id = ?
             )
             UPDATE gutscheine g
                SET eingeloest_am = LOCALTIMESTAMP(0),
                    gratis_von    = beginn.tag,
                    gratis_bis    = (beginn.tag + make_interval(months => ?) - interval '1 day')::date
               FROM beginn
              WHERE g.id = ? AND g.mitglied_id = ? AND g.eingeloest_am IS NULL
          RETURNING g.gratis_von, g.gratis_bis"
        );
        $stmt->execute([$abDatum, $mitgliedId, self::GRATIS_MONATE, $id, $mitgliedId]);
        $zeile = $stmt->fetch();

        return $zeile === false ? null : [
            'gratisVon' => $zeile['gratis_von'],
            'gratisBis' => $zeile['gratis_bis'],
        ];
    }

    /**
     * Der Gratiszeitraum, der heute läuft oder noch kommt - über alle
     * eingelösten Gutscheine eines Mitglieds zusammen.
     *
     * @param int $mitgliedId  mitglieder.id
     * @return array{von: string, bis: string}|null  null, wenn keiner (mehr) läuft
     */
    public function gratiszeitFinden(int $mitgliedId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT MIN(gratis_von) AS von, MAX(gratis_bis) AS bis
               FROM gutscheine
              WHERE mitglied_id = ? AND gratis_bis >= CURRENT_DATE'
        );
        $stmt->execute([$mitgliedId]);
        $zeile = $stmt->fetch();

        // MIN und MAX liefern auch ohne Treffer eine Zeile - mit NULL darin.
        return $zeile['von'] === null ? null : [
            'von' => $zeile['von'],
            'bis' => $zeile['bis'],
        ];
    }
}
