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
--                  mysql -u root < database/seed.sql
--
--              Die Datei darf mehrfach laufen: Sie löscht die Programmdaten
--              vorher und legt sie neu an. Konten und Mitglieder bleiben
--              unangetastet.
--
-- ACHTUNG      Das DELETE unten löscht über ON DELETE CASCADE auch die
--              gemerkten Auswahlen der Mitglieder (Tabelle mitglied_auswahl).
--              Beim Vorführen also NICHT kurz vorher neu einspielen, sonst
--              ist der Mitgliedsbereich leer. Beim Entwickeln ist es egal,
--              da stehen ohnehin nur Testkonten drin.
--
-- STAND        Programm-Details: drei Programme, ihre Merkmale und je drei
--              Coaches. Konten für den Mitglieder-Login gehören nicht hierher,
--              weil sie ein Passwort brauchen - die legt man über die
--              Registrierung an.
-- =============================================================================

USE `baseline`;


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
