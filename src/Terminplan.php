<?php
/**
 * @file        src/Terminplan.php
 * @layer       Infrastruktur (Unterbau für Schicht 4 – Endpunkte)
 * @description Rechnet aus Wochenplänen konkrete Termine und prüft, ob ein
 *              angefragter Termin überhaupt existiert.
 *
 *              In der Datenbank stehen nur Wochenmuster: "dienstags 18 Uhr"
 *              (kurstermine) und "montags 10 bis 13 Uhr" (verfuegbarkeiten).
 *              Welche Tage das in den nächsten zwei Wochen sind, rechnet
 *              diese Klasse aus. Kein SQL - das bleibt in den Repositories.
 *
 *              ZWEI AUFGABEN, EINE QUELLE
 *              Dieselben Methoden, die dem Browser die Termine liefern,
 *              prüfen später die Buchung. Dadurch kann niemand einen Termin
 *              buchen, den der Kalender nie angezeigt hätte - etwa einen
 *              Dienstag, an dem der Kurs gar nicht stattfindet, oder einen
 *              Tag in drei Monaten.
 *
 *              Alle Zeiten sind Berliner Zeit. Die Datenbank speichert sie
 *              ohne Zeitzone, deshalb wird die Zone hier ausdrücklich gesetzt
 *              statt sich auf die php.ini zu verlassen.
 * @see         docs/decisions/ADR-0014-terminkalender-wochenplan.md
 * @see         api/kurstermine.php
 * @see         api/verfuegbarkeiten.php
 */

declare(strict_types=1);

final class Terminplan
{
    /** So viele Tage im Voraus zeigt der Kalender und nimmt Buchungen an. */
    public const TAGE_VORAUS = 14;

    /** Länge eines Probetrainings. Die Fenster werden in diese Stücke geschnitten. */
    public const PROBETRAINING_MINUTEN = 60;

    /**
     * Die drei Stufen, die man bei jeder Buchung angibt. Muss zum ENUM in
     * kursbuchungen.stufe und probetrainings.stufe passen.
     */
    public const STUFEN = ['einsteiger', 'fortgeschritten', 'erfahren'];

    /** Diese Klasse wird nie instanziiert - sie hat nur statische Methoden. */
    private function __construct()
    {
    }

    /**
     * Der aktuelle Zeitpunkt in Berliner Zeit.
     *
     * @return DateTimeImmutable
     */
    public static function jetzt(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('Europe/Berlin'));
    }

    /**
     * Erster und letzter Tag des Zeitraums, den der Kalender zeigt.
     *
     * @param DateTimeImmutable $jetzt
     * @return array{0: string, 1: string}  ['JJJJ-MM-TT', 'JJJJ-MM-TT'], beide einschließlich
     */
    public static function zeitraum(DateTimeImmutable $jetzt): array
    {
        return [
            $jetzt->format('Y-m-d'),
            $jetzt->modify('+' . (self::TAGE_VORAUS - 1) . ' days')->format('Y-m-d'),
        ];
    }

    /**
     * Rollt einen Kurs-Wochenplan auf die konkreten Tage der nächsten zwei
     * Wochen aus. Termine, die schon begonnen haben, fallen weg.
     *
     * @param list<array{id: int|string, wochentag: int|string, beginn: string, dauer_minuten: int|string}> $wochenplan
     * @param DateTimeImmutable $jetzt
     * @return list<array{terminId: int, datum: string, beginn: string, ende: string}>
     *         Nach Datum und Uhrzeit sortiert. beginn/ende als 'HH:MM'.
     */
    public static function kursterminAusrollen(array $wochenplan, DateTimeImmutable $jetzt): array
    {
        $termine = [];

        for ($i = 0; $i < self::TAGE_VORAUS; $i++) {
            $tag = $jetzt->setTime(0, 0)->modify("+{$i} days");

            foreach ($wochenplan as $zeile) {
                if ((int) $zeile['wochentag'] !== (int) $tag->format('N')) {
                    continue;
                }

                $beginn = self::aufZeitSetzen($tag, $zeile['beginn']);

                if ($beginn <= $jetzt) {
                    continue;
                }

                $termine[] = [
                    'terminId' => (int) $zeile['id'],
                    'datum'    => $beginn->format('Y-m-d'),
                    'beginn'   => $beginn->format('H:i'),
                    'ende'     => $beginn->modify('+' . (int) $zeile['dauer_minuten'] . ' minutes')->format('H:i'),
                ];
            }
        }

        usort($termine, static fn (array $a, array $b): int
            => [$a['datum'], $a['beginn']] <=> [$b['datum'], $b['beginn']]);

        return $termine;
    }

    /**
     * Prüft, ob ein Kurstermin an diesem Tag stattfindet und noch buchbar ist.
     *
     * @param array{id: int|string, wochentag: int|string, beginn: string, dauer_minuten: int|string} $zeile
     * @param string            $datum  'JJJJ-MM-TT' aus der Anfrage
     * @param DateTimeImmutable $jetzt
     * @return bool
     */
    public static function kursterminBuchbar(array $zeile, string $datum, DateTimeImmutable $jetzt): bool
    {
        foreach (self::kursterminAusrollen([$zeile], $jetzt) as $termin) {
            if ($termin['datum'] === $datum) {
                return true;
            }
        }

        return false;
    }

    /**
     * Schneidet die Verfügbarkeitsfenster eines Coaches in Termine zu je
     * 60 Minuten und lässt vergangene und schon gebuchte weg.
     *
     * Ein Fenster von 10:00 bis 13:00 ergibt 10:00, 11:00 und 12:00 - der
     * letzte Termin muss vor dem Ende des Fensters fertig sein.
     *
     * @param list<array{wochentag: int|string, von: string, bis: string}> $fenster
     * @param list<string>      $belegt  Schon gebuchte Beginne, 'JJJJ-MM-TT HH:MM:SS'
     * @param DateTimeImmutable $jetzt
     * @return list<array{beginntAm: string, datum: string, beginn: string, ende: string}>
     *         beginntAm als 'JJJJ-MM-TT HH:MM' - genau so kommt er beim Buchen zurück.
     */
    public static function probetrainingsAusrollen(array $fenster, array $belegt, DateTimeImmutable $jetzt): array
    {
        // Als Schlüssel, damit die Prüfung unten nicht jedes Mal die ganze
        // Liste durchsucht.
        $belegt = array_flip(array_map(static fn (string $zeit): string => substr($zeit, 0, 16), $belegt));
        $termine = [];

        for ($i = 0; $i < self::TAGE_VORAUS; $i++) {
            $tag = $jetzt->setTime(0, 0)->modify("+{$i} days");

            foreach ($fenster as $zeile) {
                if ((int) $zeile['wochentag'] !== (int) $tag->format('N')) {
                    continue;
                }

                $beginn = self::aufZeitSetzen($tag, $zeile['von']);
                $schluss = self::aufZeitSetzen($tag, $zeile['bis']);

                while ($beginn->modify('+' . self::PROBETRAINING_MINUTEN . ' minutes') <= $schluss) {
                    $schluessel = $beginn->format('Y-m-d H:i');

                    if ($beginn > $jetzt && !isset($belegt[$schluessel])) {
                        $termine[] = [
                            'beginntAm' => $schluessel,
                            'datum'     => $beginn->format('Y-m-d'),
                            'beginn'    => $beginn->format('H:i'),
                            'ende'      => $beginn->modify('+' . self::PROBETRAINING_MINUTEN . ' minutes')->format('H:i'),
                        ];
                    }

                    $beginn = $beginn->modify('+' . self::PROBETRAINING_MINUTEN . ' minutes');
                }
            }
        }

        usort($termine, static fn (array $a, array $b): int => $a['beginntAm'] <=> $b['beginntAm']);

        return $termine;
    }

    /**
     * Prüft, ob ein angefragter Probetraining-Beginn in eines der Fenster
     * fällt. Ob er schon vergeben ist, prüft die Datenbank beim Speichern.
     *
     * @param list<array{wochentag: int|string, von: string, bis: string}> $fenster
     * @param string            $beginntAm  'JJJJ-MM-TT HH:MM' aus der Anfrage
     * @param DateTimeImmutable $jetzt
     * @return bool
     */
    public static function probetrainingBuchbar(array $fenster, string $beginntAm, DateTimeImmutable $jetzt): bool
    {
        foreach (self::probetrainingsAusrollen($fenster, [], $jetzt) as $termin) {
            if ($termin['beginntAm'] === $beginntAm) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hat ein Termin schon begonnen? Danach kann er nicht mehr storniert werden.
     *
     * @param string            $beginn  'JJJJ-MM-TT HH:MM:SS' oder 'JJJJ-MM-TT HH:MM'
     * @param DateTimeImmutable $jetzt
     * @return bool
     */
    public static function hatBegonnen(string $beginn, DateTimeImmutable $jetzt): bool
    {
        return new DateTimeImmutable($beginn, $jetzt->getTimezone()) <= $jetzt;
    }

    /**
     * Setzt einen Tag auf eine Uhrzeit aus der Datenbank ('HH:MM:SS').
     *
     * @param DateTimeImmutable $tag
     * @param string            $uhrzeit
     * @return DateTimeImmutable
     */
    private static function aufZeitSetzen(DateTimeImmutable $tag, string $uhrzeit): DateTimeImmutable
    {
        [$stunde, $minute] = array_map('intval', explode(':', $uhrzeit));

        return $tag->setTime($stunde, $minute);
    }
}
