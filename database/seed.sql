-- =============================================================================
-- @file        database/seed.sql
-- @layer       6 - Datenbank
-- @description Testdaten zum Ausprobieren und Vorführen.
--
--              Getrennt von schema.sql, weil beide unterschiedlich oft
--              gebraucht werden: das Schema läuft einmal, die Testdaten
--              werden beim Entwickeln immer wieder neu eingespielt.
--
--              REGEL: Hier stehen ausschließlich erfundene Daten. Keine
--              echten Namen, keine echten E-Mail-Adressen, keine Passwörter.
--
--              Einspielen wie schema.sql, aber DANACH:
--                  mysql --default-character-set=utf8mb4 -u root < database/seed.sql
--
--              Die Datei darf mehrfach laufen: Sie löscht die Programmdaten
--              vorher und legt sie neu an. Die Tarife werden nicht gelöscht,
--              sondern aktualisiert. Konten, Mitglieder und ihre Verträge
--              bleiben unangetastet.
--
-- ACHTUNG      Das DELETE unten löscht über ON DELETE CASCADE auch die
--              gemerkten Auswahlen der Mitglieder (Tabelle mitglied_auswahl)
--              und alle gebuchten Kurse und Probetrainings.
--              Beim Vorführen also NICHT kurz vorher neu einspielen, sonst
--              ist der Mitgliedsbereich leer. Beim Entwickeln ist es egal,
--              da stehen ohnehin nur Testkonten drin.
--
-- STAND        Programm-Details: drei Programme, ihre Merkmale und je drei
--              Coaches. Mitgliedschaften: die vier Tarife des Studios - ohne
--              sie ist die Seite mitgliedschaft.php leer. Terminkalender:
--              der Wochenplan der Kurse und die Verfügbarkeiten der Coaches.
--
--              Konten für den Mitglieder-Login gehören nicht hierher, weil sie
--              ein Passwort brauchen - die legt man über die Registrierung an.
--              Mitgliedschaften stehen deshalb auch nicht hier: Sie hängen an
--              einem Mitglied, und Mitglieder entstehen erst im Browser.
-- =============================================================================

USE `schwitzkasten`;


-- -----------------------------------------------------------------------------
-- FEATURE PROGRAMM-DETAILS
-- -----------------------------------------------------------------------------
-- DELETE statt TRUNCATE, weil TRUNCATE bei Fremdschlüsseln scheitert.
-- merkmale und coaches verschwinden durch ON DELETE CASCADE von selbst.
DELETE FROM `programme`;

INSERT INTO `programme` (`slug`, `name`) VALUES
    ('strength', 'Strength'),
    ('move',     'Move'),
    ('fight',    'Fight');


-- --- Merkmale ----------------------------------------------------------------
-- 'fokus' hat pro Programm genau einen Eintrag (reine Information),
-- 'level' und 'format' mehrere (daraus werden die Schaltflächen zur Auswahl).
--
-- Die programm_id wird nicht fest eingetragen, sondern über den slug
-- nachgeschlagen. Sonst müsste man nach jedem Neuanlegen die Zahlen anpassen.
INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'fokus', 'Kraft, Technik und Muskelaufbau',
       'Du trainierst die großen Grundübungen: Kniebeuge, Kreuzheben, Bankdrücken und Überkopfdrücken. Jede Einheit beginnt mit Technik bei leichtem Gewicht und steigert sich erst danach. Deine Fortschritte hältst du im Trainingsbuch fest, sodass du schwarz auf weiß siehst, wo du stehst.',
       0
FROM `programme` WHERE `slug` = 'strength';

INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'level', 'Einsteiger', 'Du hast noch nie mit Langhantel trainiert oder warst lange weg. Die ersten vier Wochen laufen mit leichtem Gewicht, dafür mit viel Korrektur. Ein Coach steht die ganze Zeit daneben.', 0
FROM `programme` WHERE `slug` = 'strength'
UNION ALL
SELECT `id`, 'level', 'Fortgeschritten', 'Die Grundübungen sitzen und du kennst deine Arbeitsgewichte. Der Plan wird auf dein Ziel zugeschnitten und alle sechs Wochen angepasst.', 1
FROM `programme` WHERE `slug` = 'strength'
UNION ALL
SELECT `id`, 'level', 'Wettkampf', 'Du bereitest dich auf einen Kraftdreikampf vor. Periodisierung, Attemptplanung und Technikvideos gehören dazu, Betreuung bis an den Wettkampftag.', 2
FROM `programme` WHERE `slug` = 'strength';

INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'format', 'Freies Training', 'Du trainierst nach deinem Plan, wann du willst. Auf der Fläche ist immer ein Coach ansprechbar, ohne festen Termin.', 0
FROM `programme` WHERE `slug` = 'strength'
UNION ALL
SELECT `id`, 'format', 'Einzelcoaching', 'Sechzig Minuten allein mit einem Coach. Für Technikaufbau, nach einer Verletzung oder wenn du seit Monaten auf demselben Gewicht feststeckst.', 1
FROM `programme` WHERE `slug` = 'strength'
UNION ALL
SELECT `id`, 'format', 'Small Group', 'Höchstens sechs Personen an einer Station. Günstiger als Einzelcoaching, und die Gruppe zieht an den Tagen mit, an denen die eigene Motivation fehlt.', 2
FROM `programme` WHERE `slug` = 'strength';


INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'fokus', 'Mobilität, Balance und Ausdauer',
       'Move arbeitet an Bewegungsqualität statt an Maximalgewicht. Du trainierst Hüfte, Schultern und Wirbelsäule durch ihren vollen Bewegungsraum und verbindest das mit Gleichgewicht und ruhiger Atmung. Wer viel sitzt, merkt den Unterschied meist nach drei Wochen.',
       0
FROM `programme` WHERE `slug` = 'move';

INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'level', 'Sanfter Einstieg', 'Ruhiges Tempo, alles am Boden oder im Stand, jede Übung mit leichterer Variante. Geeignet nach langer Pause und bei Rückenbeschwerden.', 0
FROM `programme` WHERE `slug` = 'move'
UNION ALL
SELECT `id`, 'level', 'Fließend', 'Die Übungen gehen ineinander über, der Puls bleibt oben. Du solltest dich dreißig Minuten am Stück bewegen können.', 1
FROM `programme` WHERE `slug` = 'move'
UNION ALL
SELECT `id`, 'level', 'Athletisch', 'Sprünge, einbeinige Landungen und Halteübungen kommen dazu. Für alle, die daneben eine Sportart betreiben und ihre Beweglichkeit dort einsetzen wollen.', 2
FROM `programme` WHERE `slug` = 'move';

INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'format', 'Small Group', 'Acht Personen, fester Termin in der Woche. Der Coach korrigiert einzeln, die Gruppe bleibt über den Kurs hinweg dieselbe.', 0
FROM `programme` WHERE `slug` = 'move'
UNION ALL
SELECT `id`, 'format', 'Freies Workout', 'Der Mobility-Bereich steht dir zu allen Öffnungszeiten offen. An der Wand hängen vier Abläufe von zwanzig Minuten zum Nachmachen.', 1
FROM `programme` WHERE `slug` = 'move'
UNION ALL
SELECT `id`, 'format', 'Morgenroutine', 'Dreißig Minuten vor der Arbeit, dienstags und donnerstags ab sieben. Kurz, wach machend, ohne Duschstress danach.', 2
FROM `programme` WHERE `slug` = 'move';


INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'fokus', 'Technik, Reaktion und Kondition',
       'Fight verbindet saubere Boxtechnik mit Intervalltraining. Du lernst Schlagfolgen, Beinarbeit und Deckung am Sandsack und an den Pratzen. Gesparrt wird nur, wer es ausdrücklich will - alles andere läuft ohne Körperkontakt.',
       0
FROM `programme` WHERE `slug` = 'fight';

INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'level', 'Erste Handschuhe', 'Grundstellung, Führhand, Deckung. Sechs Wochen am Sandsack und an den Pratzen, ohne jeden Partnerkontakt. Handschuhe und Bandagen leihst du im Club.', 0
FROM `programme` WHERE `slug` = 'fight'
UNION ALL
SELECT `id`, 'level', 'Kombinationen', 'Schlagfolgen, Beinarbeit und Konterübungen mit Partner. Leichter Kontakt mit Schutzausrüstung, immer unter Aufsicht.', 1
FROM `programme` WHERE `slug` = 'fight'
UNION ALL
SELECT `id`, 'level', 'Sparring', 'Kontrolliertes Sparring mit Kopfschutz nach vorheriger Freigabe durch einen Coach. Wer nicht sparren möchte, trainiert die Einheit an den Pratzen weiter.', 2
FROM `programme` WHERE `slug` = 'fight';

INSERT INTO `merkmale` (`programm_id`, `art`, `titel`, `beschreibung`, `position`)
SELECT `id`, 'format', 'Gruppenkurs', 'Sechzig Minuten mit festem Aufbau: Aufwärmen, Technik, Runden, Ausklang. Zwölf bis achtzehn Personen, dreimal wöchentlich.', 0
FROM `programme` WHERE `slug` = 'fight'
UNION ALL
SELECT `id`, 'format', 'Pratzentraining', 'Zwei zu eins mit einem Coach. Dreißig Minuten am Stück an den Pratzen - die ehrlichste Rückmeldung zu deiner Technik.', 1
FROM `programme` WHERE `slug` = 'fight'
UNION ALL
SELECT `id`, 'format', 'Konditionsrunde', 'Boxtechnik im Intervall mit Seilspringen und Körpergewichtsübungen. Kein Partner, kein Kontakt, maximaler Puls.', 2
FROM `programme` WHERE `slug` = 'fight';


-- --- Coaches -----------------------------------------------------------------
-- Alle Namen erfunden. Die Bilddateien liegen in assets/img/coaches/ -
-- siehe die README dort. Fehlt eine Datei, zeigt die Seite die Initialen
-- des Namens statt eines kaputten Bildes.
INSERT INTO `coaches` (`programm_id`, `name`, `schwerpunkt`, `bild`, `bild_alt`, `position`)
SELECT `id`, 'Lena Brandt',  'Kraftdreikampf und Technikaufbau an der Langhantel', 'lena-brandt.jpg',  'Porträtfoto von Coach Lena Brandt',  0 FROM `programme` WHERE `slug` = 'strength'
UNION ALL
SELECT `id`, 'Mika Özdemir', 'Hypertrophie und Trainingsplanung für Fortgeschrittene', 'mika-oezdemir.jpg', 'Porträtfoto von Coach Mika Özdemir', 1 FROM `programme` WHERE `slug` = 'strength'
UNION ALL
SELECT `id`, 'Jonas Reiter', 'Wiedereinstieg nach Verletzung, ruhige Progression', 'jonas-reiter.jpg', 'Porträtfoto von Coach Jonas Reiter', 2 FROM `programme` WHERE `slug` = 'strength';

INSERT INTO `coaches` (`programm_id`, `name`, `schwerpunkt`, `bild`, `bild_alt`, `position`)
SELECT `id`, 'Sofia Lindqvist', 'Mobilität für Schulter und Hüfte, Atemarbeit', 'sofia-lindqvist.jpg', 'Porträtfoto von Coach Sofia Lindqvist', 0 FROM `programme` WHERE `slug` = 'move'
UNION ALL
SELECT `id`, 'Tobias Krüger', 'Fließende Workouts und Gleichgewichtstraining', 'tobias-krueger.jpg', 'Porträtfoto von Coach Tobias Krüger', 1 FROM `programme` WHERE `slug` = 'move'
UNION ALL
SELECT `id`, 'Amelie Fuchs', 'Rückengesundheit und Training für Vielsitzer', 'amelie-fuchs.jpg', 'Porträtfoto von Coach Amelie Fuchs', 2 FROM `programme` WHERE `slug` = 'move';

INSERT INTO `coaches` (`programm_id`, `name`, `schwerpunkt`, `bild`, `bild_alt`, `position`)
SELECT `id`, 'Nuri Yilmaz', 'Boxtechnik und Beinarbeit, Einsteigergruppen', 'nuri-yilmaz.jpg', 'Porträtfoto von Coach Nuri Yilmaz', 0 FROM `programme` WHERE `slug` = 'fight'
UNION ALL
SELECT `id`, 'Clara Vogt', 'Pratzentraining und kontrolliertes Sparring', 'clara-vogt.jpg', 'Porträtfoto von Coach Clara Vogt', 1 FROM `programme` WHERE `slug` = 'fight'
UNION ALL
SELECT `id`, 'David Ostermann', 'Konditionsrunden und Intervallsteuerung', 'david-ostermann.jpg', 'Porträtfoto von Coach David Ostermann', 2 FROM `programme` WHERE `slug` = 'fight';


-- -----------------------------------------------------------------------------
-- TARIFE
-- -----------------------------------------------------------------------------
-- Die Preise sind erfunden und dürfen jederzeit geändert werden. Zwei Dinge
-- sollten dabei stimmen bleiben:
--
--   1. Premium muss günstiger sein als Basisplan + Kurs-Plan zusammen,
--      sonst hat der Tarif keinen Grund zu existieren.
--   2. Wer Geräte UND Kurse will, kommt nur über Premium dorthin. Der
--      Kurs-Plan ist absichtlich nur für Kurse.
--
-- ON DUPLICATE KEY UPDATE macht die Datei wiederholbar: Beim zweiten
-- Einspielen entstehen keine Dubletten (kennung ist UNIQUE), sondern
-- geänderte Preise werden übernommen. Laufende Verträge bleiben davon
-- unberührt - die haben ihren Preis eingefroren.
INSERT INTO `tarife`
    (`kennung`, `name`, `beschreibung`,
     `preis_standard`, `preis_ermaessigt`, `preis_senior`,
     `zugang_geraete`, `zugang_wellness`, `zugang_kurse`, `sortierung`)
VALUES
    ('basis', 'Basisplan',
     'Alle Geräte, alle Öffnungszeiten. Trainieren, wann du willst.',
     29.90, 22.90, 24.90,   1, 0, 0,   10),

    ('wellness', 'Wellness-Plan',
     'Alles aus dem Basisplan, dazu Sauna und Sonnenbank.',
     44.90, 34.90, 37.90,   1, 1, 0,   20),

    ('kurse', 'Kurs-Plan',
     'Alle Kurse im Wochenplan. Ohne Gerätetraining - für alle, die lieber in der Gruppe schwitzen.',
     34.90, 26.90, 28.90,   0, 0, 1,   30),

    ('premium', 'Premium',
     'Geräte, Sauna, Sonnenbank und alle Kurse. Das ganze SCHWITZKASTEN.',
     59.90, 44.90, 49.90,   1, 1, 1,   40)

ON DUPLICATE KEY UPDATE
    `name`             = VALUES(`name`),
    `beschreibung`     = VALUES(`beschreibung`),
    `preis_standard`   = VALUES(`preis_standard`),
    `preis_ermaessigt` = VALUES(`preis_ermaessigt`),
    `preis_senior`     = VALUES(`preis_senior`),
    `zugang_geraete`   = VALUES(`zugang_geraete`),
    `zugang_wellness`  = VALUES(`zugang_wellness`),
    `zugang_kurse`     = VALUES(`zugang_kurse`),
    `sortierung`       = VALUES(`sortierung`);


-- -----------------------------------------------------------------------------
-- FEATURE TERMINKALENDER
-- -----------------------------------------------------------------------------
-- Kein eigenes DELETE nötig: Das DELETE FROM programme oben löscht über
-- ON DELETE CASCADE auch kurstermine, kursbuchungen, verfuegbarkeiten und
-- probetrainings. ACHTUNG, das heißt auch: Neu einspielen löscht alle
-- gebuchten Kurse und Probetrainings der Mitglieder.
--
-- Programm, Format und Coach werden über ihre Namen nachgeschlagen, nicht
-- über ids - die ändern sich bei jedem Neueinspielen.
-- COLLATE in den Vergleichen, weil die Spalten der Hilfstabelle sonst eine
-- andere Sortierung haben als die Tabellen und MySQL den Vergleich ablehnt.

-- --- Kurstermine: jedes Format zweimal pro Woche -----------------------------
-- wochentag: 1 = Montag ... 7 = Sonntag
-- stammplaetze: jede Woche schon vergeben. Drei Termine sind damit immer
-- ausgebucht, zwei haben genau noch einen Platz frei - beides lässt sich so
-- jederzeit vorführen.
--
-- max_teilnehmer: 20 - außer beim Einzelcoaching. Dort betreut ein Coach eine
-- Person, es gibt also höchstens so viele Plätze wie das Programm Coaches hat.
-- Die Zahl wird gezählt, nicht fest eingetragen: Kommt ein Coach dazu, stimmt
-- sie nach dem nächsten Einspielen von selbst.
INSERT INTO `kurstermine`
    (`programm_id`, `format_id`, `coach_id`, `wochentag`, `beginn`, `dauer_minuten`,
     `max_teilnehmer`, `stammplaetze`)
SELECT p.`id`, m.`id`, c.`id`, t.wochentag, t.beginn, t.dauer,
       CASE WHEN t.format = 'Einzelcoaching'
            THEN (SELECT COUNT(*) FROM `coaches` alle WHERE alle.`programm_id` = p.`id`)
            ELSE 20
       END,
       t.stamm
FROM (
              SELECT 'strength' AS slug, 'Freies Training' AS format, 'Lena Brandt'     AS coach, 1 AS wochentag, '07:00:00' AS beginn, 60 AS dauer,  8 AS stamm
    UNION ALL SELECT 'strength', 'Freies Training', 'Lena Brandt',     3, '07:00:00', 60,  6
    UNION ALL SELECT 'strength', 'Einzelcoaching',  'Mika Özdemir',    2, '17:00:00', 60,  1
    UNION ALL SELECT 'strength', 'Einzelcoaching',  'Mika Özdemir',    4, '17:00:00', 60,  3
    UNION ALL SELECT 'strength', 'Small Group',     'Jonas Reiter',    2, '19:00:00', 60, 12
    UNION ALL SELECT 'strength', 'Small Group',     'Jonas Reiter',    5, '18:00:00', 60, 19

    UNION ALL SELECT 'move',     'Small Group',     'Sofia Lindqvist', 1, '18:00:00', 60, 14
    UNION ALL SELECT 'move',     'Small Group',     'Sofia Lindqvist', 4, '18:00:00', 60,  9
    UNION ALL SELECT 'move',     'Freies Workout',  'Tobias Krüger',   3, '12:00:00', 60,  5
    UNION ALL SELECT 'move',     'Freies Workout',  'Tobias Krüger',   6, '10:00:00', 60, 11
    UNION ALL SELECT 'move',     'Morgenroutine',   'Amelie Fuchs',    2, '07:00:00', 30, 16
    UNION ALL SELECT 'move',     'Morgenroutine',   'Amelie Fuchs',    4, '07:00:00', 30, 20

    UNION ALL SELECT 'fight',    'Gruppenkurs',     'Nuri Yilmaz',     1, '19:30:00', 60, 17
    UNION ALL SELECT 'fight',    'Gruppenkurs',     'Nuri Yilmaz',     3, '19:30:00', 60, 20
    UNION ALL SELECT 'fight',    'Pratzentraining', 'Clara Vogt',      2, '18:00:00', 30,  7
    UNION ALL SELECT 'fight',    'Pratzentraining', 'Clara Vogt',      5, '17:00:00', 30, 10
    UNION ALL SELECT 'fight',    'Konditionsrunde', 'David Ostermann', 4, '19:00:00', 60, 13
    UNION ALL SELECT 'fight',    'Konditionsrunde', 'David Ostermann', 6, '11:00:00', 60, 19
) AS t
JOIN `programme` p ON p.`slug` = t.slug COLLATE utf8mb4_unicode_ci
JOIN `merkmale`  m ON m.`programm_id` = p.`id` AND m.`art` = 'format'
                  AND m.`titel` = t.format COLLATE utf8mb4_unicode_ci
JOIN `coaches`   c ON c.`programm_id` = p.`id`
                  AND c.`name` = t.coach COLLATE utf8mb4_unicode_ci;


-- --- Verfügbarkeiten der Coaches für Probetrainings --------------------------
-- Aus jedem Fenster werden Termine zu je 60 Minuten: 09:00-12:00 ergibt
-- 09:00, 10:00 und 11:00. Die Fenster überschneiden sich absichtlich nicht
-- mit den Kursen desselben Coaches - das prüft der Code nicht.
INSERT INTO `verfuegbarkeiten` (`coach_id`, `wochentag`, `von`, `bis`)
SELECT c.`id`, t.wochentag, t.von, t.bis
FROM (
              SELECT 'Lena Brandt' AS coach, 1 AS wochentag, '09:00:00' AS von, '12:00:00' AS bis
    UNION ALL SELECT 'Lena Brandt',     4, '15:00:00', '18:00:00'
    UNION ALL SELECT 'Mika Özdemir',    1, '14:00:00', '17:00:00'
    UNION ALL SELECT 'Mika Özdemir',    3, '10:00:00', '13:00:00'
    UNION ALL SELECT 'Jonas Reiter',    3, '16:00:00', '19:00:00'
    UNION ALL SELECT 'Jonas Reiter',    6, '10:00:00', '13:00:00'
    UNION ALL SELECT 'Sofia Lindqvist', 2, '10:00:00', '13:00:00'
    UNION ALL SELECT 'Sofia Lindqvist', 5, '14:00:00', '17:00:00'
    UNION ALL SELECT 'Tobias Krüger',   1, '10:00:00', '12:00:00'
    UNION ALL SELECT 'Tobias Krüger',   4, '13:00:00', '16:00:00'
    UNION ALL SELECT 'Amelie Fuchs',    3, '08:00:00', '11:00:00'
    UNION ALL SELECT 'Amelie Fuchs',    5, '09:00:00', '12:00:00'
    UNION ALL SELECT 'Nuri Yilmaz',     2, '14:00:00', '17:00:00'
    UNION ALL SELECT 'Nuri Yilmaz',     6, '12:00:00', '15:00:00'
    UNION ALL SELECT 'Clara Vogt',      1, '15:00:00', '18:00:00'
    UNION ALL SELECT 'Clara Vogt',      4, '10:00:00', '13:00:00'
    UNION ALL SELECT 'David Ostermann', 3, '14:00:00', '17:00:00'
    UNION ALL SELECT 'David Ostermann', 5, '10:00:00', '13:00:00'
) AS t
JOIN `coaches` c ON c.`name` = t.coach COLLATE utf8mb4_unicode_ci;
