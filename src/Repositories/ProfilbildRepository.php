<?php
/**
 * @file        src/Repositories/ProfilbildRepository.php
 * @layer       5 – Repository (Datenzugriff)
 * @description Liest und schreibt die Profilbilder (Tabelle profilbilder).
 *
 *              Das Bild wandert als base64 hinein und heraus: decode() und
 *              encode() in PostgreSQL ersparen den Umweg über Datenströme,
 *              den PDO sonst für bytea verlangt.
 *
 *              WER DARF EIN BILD SEHEN - steht in bildFinden():
 *                - die Person selbst, immer
 *                - alle anderen nur, wenn das Bild freigegeben ist UND die
 *                  Person mindestens eine Bewertung geschrieben hat. Sonst
 *                  ließen sich über ?mitglied=1, 2, 3 ... die Bilder aller
 *                  Mitglieder abrufen, auch derer, die nirgends auftauchen.
 * @see         database/schema.sql
 * @see         api/profilbilder.php
 * @see         docs/features/profilbilder.md
 */

declare(strict_types=1);

namespace Repositories;

use Database;

final class ProfilbildRepository
{
    /**
     * Adresse eines Bildes, relativ zur BASE_URL - so, wie das Frontend sie
     * in <img src> einsetzt. Der Zeitstempel macht jede neue Fassung zu einer
     * neuen Adresse, damit der Browser nie ein altes Bild zeigt.
     *
     * @param int $mitgliedId
     * @param int $version     Unix-Zeitstempel aus geaendert_am
     * @return string          z. B. 'api/profilbilder.php?mitglied=7&v=1790776000'
     */
    public static function url(int $mitgliedId, int $version): string
    {
        return 'api/profilbilder.php?mitglied=' . $mitgliedId . '&v=' . $version;
    }

    /**
     * Stand des eigenen Bildes für "Mein Konto".
     *
     * @param int $mitgliedId  mitglieder.id, NICHT users.id
     * @return array{url: string, oeffentlich: bool}|null  null, wenn es kein Bild gibt
     */
    public function standFinden(int $mitgliedId): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT oeffentlich, extract(epoch FROM geaendert_am)::bigint AS version
               FROM profilbilder
              WHERE mitglied_id = ?'
        );
        $stmt->execute([$mitgliedId]);
        $zeile = $stmt->fetch();

        if ($zeile === false) {
            return null;
        }

        return [
            'url'         => self::url($mitgliedId, (int) $zeile['version']),
            'oeffentlich' => (bool) $zeile['oeffentlich'],
        ];
    }

    /**
     * Die Bilddaten, sofern die anfragende Person sie sehen darf.
     *
     * @param int  $mitgliedId  wessen Bild
     * @param bool $eigenes     true, wenn die anfragende Person es selbst ist
     * @return string|null      JPEG-Bytes, oder null, wenn es keins gibt
     *                          oder es nicht gezeigt werden darf
     */
    public function bildFinden(int $mitgliedId, bool $eigenes): ?string
    {
        $stmt = Database::connection()->prepare(
            "SELECT encode(p.bild, 'base64') AS bild
               FROM profilbilder p
              WHERE p.mitglied_id = ?
                AND (?::boolean OR (p.oeffentlich = 1
                                    AND EXISTS (SELECT 1 FROM bewertungen b WHERE b.mitglied_id = p.mitglied_id)))"
        );
        // Als Text 'true'/'false': Ein PHP-false kommt über PDO sonst als
        // leerer Text bei PostgreSQL an, und das ist kein gültiger boolean.
        $stmt->execute([$mitgliedId, $eigenes ? 'true' : 'false']);

        $base64 = $stmt->fetchColumn();

        // encode() bricht nach 76 Zeichen um; base64_decode() überspringt
        // die Zeilenumbrüche von selbst.
        return $base64 === false ? null : base64_decode($base64);
    }

    /**
     * Speichert ein neues Bild oder ersetzt das alte. Die Freigabe bleibt
     * beim Ersetzen, wie sie war.
     *
     * @param int    $mitgliedId
     * @param string $base64      bereits geprüftes JPEG als base64
     * @return array{url: string, oeffentlich: bool}  der neue Stand
     */
    public function speichern(int $mitgliedId, string $base64): array
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO profilbilder (mitglied_id, bild)
             VALUES (?, decode(?, 'base64'))
             ON CONFLICT (mitglied_id) DO UPDATE
                SET bild = EXCLUDED.bild,
                    geaendert_am = LOCALTIMESTAMP(0)"
        );
        $stmt->execute([$mitgliedId, $base64]);

        return $this->standFinden($mitgliedId);
    }

    /**
     * Schaltet die Anzeige bei den Bewertungen an oder aus.
     *
     * @param int  $mitgliedId
     * @param bool $oeffentlich
     * @return bool  false, wenn es gar kein Bild gibt
     */
    public function freigabeSetzen(int $mitgliedId, bool $oeffentlich): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE profilbilder SET oeffentlich = ? WHERE mitglied_id = ?'
        );
        $stmt->execute([$oeffentlich ? 1 : 0, $mitgliedId]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Entfernt das Bild. Gibt es keins, passiert nichts.
     *
     * @param int $mitgliedId
     * @return void
     */
    public function loeschen(int $mitgliedId): void
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM profilbilder WHERE mitglied_id = ?'
        );
        $stmt->execute([$mitgliedId]);
    }
}
