-- =============================================================================
-- @file        database/schema.sql
-- @layer       6 - Datenbank
-- @description Der Bauplan der Datenbank. Schicht 6 von 6 des Beispiel-Features.
--              Vorherige Schicht: src/Repositories/BeispielFeatureRepository.php
--
--              Diese Datei ist die einzige Wahrheit darüber, wie die Datenbank
--              aussieht. Wer eine Tabelle ändert, ändert sie HIER und sagt es
--              im Team - sonst hat jede Person eine andere Datenbank.
--
-- SO WIRD SIE EINGESPIELT
--              1. XAMPP starten, Apache und MySQL müssen grün sein
--              2. http://localhost/phpmyadmin aufrufen
--              3. Reiter "Importieren", diese Datei auswählen, ausführen
--
--              Alternativ auf der Kommandozeile:
--                  mysql -u root < database/schema.sql
--
-- STAND        Es gibt noch keine echten Tabellen, weil es noch keine echten
--              Features gibt. Angelegt wird bisher nur die leere Datenbank,
--              damit die Verbindung aus src/Database.php funktioniert.
--              Die auskommentierte Tabelle unten zeigt die Konventionen.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- Datenbank anlegen
-- -----------------------------------------------------------------------------
-- utf8mb4 ist wichtig: nur damit werden Umlaute und Emojis korrekt
-- gespeichert. Mit dem älteren utf8 wird aus "Krafttraining für dich"
-- schnell "Krafttraining fÃ¼r dich".
--
-- Der Name muss zu 'name' in config/config.php passen.
CREATE DATABASE IF NOT EXISTS `baseline`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `baseline`;


-- -----------------------------------------------------------------------------
-- KONVENTIONEN FÜR NEUE TABELLEN
-- -----------------------------------------------------------------------------
-- Tabellennamen    kleingeschrieben, Mehrzahl, deutsch:  kurse, produkte, buchungen
-- Spaltennamen     kleingeschrieben, mit Unterstrich:    erstellt_am, kurs_id
-- Keine Umlaute    in Tabellen- und Spaltennamen (aber gerne in den Daten)
-- Immer dabei      id als Primärschlüssel, erstellt_am als Zeitstempel
-- Fremdschlüssel   heißen <tabelle_einzahl>_id, z. B. kurs_id
-- Engine           InnoDB, damit Fremdschlüssel überhaupt funktionieren
--
-- Die folgende Tabelle ist ein VOLLSTÄNDIGES BEISPIEL und absichtlich
-- auskommentiert. Zum Anlegen eines echten Features: kopieren, umbenennen,
-- Kommentarzeichen entfernen.
-- -----------------------------------------------------------------------------

-- CREATE TABLE IF NOT EXISTS `beispiel_eintraege` (
--     `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
--
--     -- VARCHAR mit Längenangabe statt TEXT, solange die Länge absehbar ist.
--     -- Das erlaubt der Datenbank, sinnvoll zu sortieren und zu indizieren.
--     `name`        VARCHAR(120)  NOT NULL,
--     `tag`         VARCHAR(20)   NOT NULL,
--     `uhrzeit`     TIME          NOT NULL,
--
--     -- NULL erlaubt heißt: "diese Angabe ist freiwillig".
--     `beschreibung` TEXT         NULL,
--
--     -- Wird beim Einfügen automatisch gesetzt, muss nie mitgeschickt werden.
--     `erstellt_am` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
--
--     PRIMARY KEY (`id`),
--
--     -- Ein Index beschleunigt genau die Abfragen, nach denen oft gesucht wird.
--     KEY `idx_tag` (`tag`)
-- ) ENGINE = InnoDB
--   DEFAULT CHARSET = utf8mb4
--   COLLATE = utf8mb4_unicode_ci;
