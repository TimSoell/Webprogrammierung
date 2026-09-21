-- =============================================================================
-- @file        database/schema.sql
-- @layer       6 - Datenbank
-- @description Der Bauplan der Datenbank. Schicht 6 von 6.
--              Vorherige Schicht: src/Repositories/ (z. B. MitgliedRepository.php)
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
-- STAND        Tabellen für das Feature Mitglieder-Login (ganz unten):
--              die acht users-Tabellen der Login-Bibliothek delight-im/auth
--              und unsere eigene Tabelle mitglieder.
--              Die auskommentierte Tabelle in der Mitte zeigt die Konventionen.
--
--              Die Datei darf mehrfach eingespielt werden: CREATE ... IF NOT
--              EXISTS lässt vorhandene Tabellen und ihre Daten in Ruhe.
-- =============================================================================


-- -----------------------------------------------------------------------------
-- Datenbank anlegen
-- -----------------------------------------------------------------------------
-- utf8mb4 ist wichtig: nur damit werden Umlaute und Emojis korrekt
-- gespeichert. Mit dem älteren utf8 wird aus "Krafttraining für dich"
-- schnell "Krafttraining fÃ¼r dich".
--
-- Der Name muss zu 'name' in config/config.php passen.
CREATE DATABASE IF NOT EXISTS `schwitzkasten`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `schwitzkasten`;


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


-- -----------------------------------------------------------------------------
-- FEATURE MITGLIEDER-LOGIN - Tabellen der Login-Bibliothek
-- -----------------------------------------------------------------------------
-- Übernommen aus vendor/delight-im/auth/Database/MySQL.sql (Version 9).
-- Die Bibliothek erwartet genau diese Namen und Spalten. Deshalb weichen sie
-- von unseren Konventionen ab (englisch, keine erstellt_am-Spalte).
-- NICHT umbenennen und keine Spalten ändern, sonst bricht der Login.
--
-- Eine Abweichung vom Original ist Absicht: ENGINE = InnoDB statt MyISAM.
-- Nur InnoDB kennt Fremdschlüssel, und unsere Tabelle mitglieder verweist
-- auf users. Die Bibliothek funktioniert mit beiden Engines.
--
-- Wofür die Tabellen da sind:
--   users                Konto: E-Mail, Passwort-Hash, Status
--   users_resets         offene "Passwort vergessen"-Anfragen (Token-Hash)
--   users_throttling     Zähler gegen zu viele Fehlversuche
--   users_audit_log      Protokoll: Registrierung, Login, Logout ...
--   users_confirmations  E-Mail-Bestätigung      (von uns noch nicht genutzt)
--   users_remembered     "Angemeldet bleiben"     (von uns noch nicht genutzt)
--   users_2fa, users_otps  Zwei-Faktor-Anmeldung (von uns noch nicht genutzt)
-- Die ungenutzten Tabellen fragt die Bibliothek trotzdem ab - sie müssen da sein.
-- Siehe docs/decisions/ADR-0005-login-bibliothek.md
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '0',
  `verified` tinyint unsigned NOT NULL DEFAULT '0',
  `resettable` tinyint unsigned NOT NULL DEFAULT '1',
  `roles_mask` int unsigned NOT NULL DEFAULT '0',
  `registered` int unsigned NOT NULL,
  `last_login` int unsigned DEFAULT NULL,
  `force_logout` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_2fa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `mechanism` tinyint unsigned NOT NULL,
  `seed` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` int unsigned NOT NULL,
  `expires_at` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_mechanism` (`user_id`,`mechanism`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_audit_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `event_at` int unsigned NOT NULL,
  `event_type` varchar(128) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
  `admin_id` int unsigned DEFAULT NULL,
  `ip_address` varchar(49) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details_json` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_at` (`event_at`),
  KEY `user_id_event_at` (`user_id`,`event_at`),
  KEY `user_id_event_type_event_at` (`user_id`,`event_type`,`event_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_confirmations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `email` varchar(249) COLLATE utf8mb4_unicode_ci NOT NULL,
  `selector` varchar(16) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `email_expires` (`email`,`expires`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_otps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `mechanism` tinyint unsigned NOT NULL,
  `single_factor` tinyint unsigned NOT NULL DEFAULT '0',
  `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires_at` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_mechanism` (`user_id`,`mechanism`),
  KEY `selector_user_id` (`selector`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_remembered` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user` int unsigned NOT NULL,
  `selector` varchar(24) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `user` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_resets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user` int unsigned NOT NULL,
  `selector` varchar(20) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `token` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `expires` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `user_expires` (`user`,`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_throttling` (
  `bucket` varchar(44) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
  `tokens` float NOT NULL,
  `replenished_at` int unsigned NOT NULL,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`bucket`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- FEATURE MITGLIEDER-LOGIN - unsere eigenen Stammdaten
-- -----------------------------------------------------------------------------
-- Die Bibliothek kennt nur E-Mail und Passwort. Alles Fachliche über ein
-- Mitglied steht hier, verbunden über user_id. Spätere Features (Verträge,
-- Buchungen, Zahlungen) verweisen auf mitglieder.id, nicht auf users.
CREATE TABLE IF NOT EXISTS `mitglieder` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,

    -- Genau ein Mitglied pro Konto, deshalb UNIQUE.
    `user_id`     INT UNSIGNED  NOT NULL,

    `vorname`     VARCHAR(100)  NOT NULL,
    `nachname`    VARCHAR(100)  NOT NULL,
    `erstellt_am` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_user_id` (`user_id`),

    -- Wird ein Konto gelöscht, verschwinden seine Stammdaten mit.
    CONSTRAINT `fk_mitglieder_users`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
