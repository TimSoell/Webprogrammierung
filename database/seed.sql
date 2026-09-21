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
--              Einspielen wie schema.sql, aber DANACH.
--
-- STAND        Die vier Tarife des Studios. Ohne sie ist die Seite
--              mitgliedschaft.php leer.
--
--              Konten für den Mitglieder-Login gehören nicht hierher, weil sie
--              ein Passwort brauchen - die legt man über die Registrierung an.
--              Mitgliedschaften stehen deshalb auch nicht hier: Sie hängen an
--              einem Mitglied, und Mitglieder entstehen erst im Browser.
-- =============================================================================

USE `baseline`;


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
