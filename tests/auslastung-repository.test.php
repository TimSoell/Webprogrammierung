<?php
/**
 * @file        tests/auslastung-repository.test.php
 * @layer       Test
 * @description Prüft getrennte Check-in-Zählung und stündliche Besuchsgrenzen.
 * @see         src/Repositories/AuslastungRepository.php
 */
declare(strict_types=1);

require dirname(__DIR__) . '/src/Repositories/AuslastungRepository.php';

// Nur den externen Datenbankzugriff ersetzen; die Zähllogik läuft unverändert.
final class Database
{
    public static function connection(): object { return new self(); }
    public function prepare(string $sql): object { return $this; }
    public function execute(array $parameter): void {}
    public function fetchAll(): array
    {
        return [
            ['beginn' => '2026-10-16 09:00:00', 'ende' => '2026-10-16 10:30:00', 'art' => 'jetzt'],
            ['beginn' => '2026-10-16 09:30:00', 'ende' => '2026-10-16 11:00:00', 'art' => 'geplant'],
            ['beginn' => '2026-10-16 08:00:00', 'ende' => '2026-10-16 09:00:00', 'art' => 'jetzt'],
            ['beginn' => '2026-10-16 23:59:59', 'ende' => '2026-10-17 01:00:00', 'art' => 'jetzt'],
        ];
    }
}
$werte = (new Repositories\AuslastungRepository())->besucheProStundeZaehlen(new DateTimeImmutable('2026-10-16'));
foreach ([
    8 => ['gebucht' => 1, 'checkins' => 1],
    9 => ['gebucht' => 2, 'checkins' => 1],
    10 => ['gebucht' => 2, 'checkins' => 1],
    11 => ['gebucht' => 0, 'checkins' => 0],
    23 => ['gebucht' => 1, 'checkins' => 1],
] as $stunde => $erwartet) {
    if ($werte[$stunde] !== $erwartet) {
        throw new RuntimeException('Falsche Zählung in Stunde ' . $stunde . ': ' . json_encode($werte[$stunde]));
    }
}
echo "Besuchsarten und Stundengrenzen korrekt.\n";
