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
--                  mysql --default-character-set=utf8mb4 -u root < database/schema.sql
--              Der Schalter ist unter Windows Pflicht, sonst werden aus
--              Umlauten Zeichensalat. Siehe database/README.md.
--
-- STAND        Feature Mitglieder-Login: die acht users-Tabellen der
--              Login-Bibliothek delight-im/auth und unsere Tabelle mitglieder.
--              Feature Mitgliedschaften: tarife und mitgliedschaften.
--              Feature Nachweise: nachweise (Ausweisprüfung, ohne Bilder).
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


-- -----------------------------------------------------------------------------
-- FEATURE MITGLIEDSCHAFTEN - der Katalog
-- -----------------------------------------------------------------------------
-- Was das Studio anbietet und was es kostet. Vier Zeilen, gepflegt in
-- database/seed.sql. Diese Tabelle beschreibt das ANGEBOT, nicht die Verträge -
-- die stehen weiter unten in mitgliedschaften.
--
-- Siehe docs/decisions/ADR-0009-mitgliedschaften-datenmodell.md
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tarife` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,

    -- Stabiler Schlüssel für Code und Seed: 'basis', 'wellness', 'kurse',
    -- 'premium'. Die id ist auf jedem Rechner eine andere, die kennung nicht.
    `kennung`          VARCHAR(20)   NOT NULL,

    `name`             VARCHAR(60)   NOT NULL,
    `beschreibung`     VARCHAR(255)  NOT NULL,

    -- DECIMAL statt FLOAT: 44.90 ist als FLOAT nicht exakt 44.90, und bei
    -- Geldbeträgen summieren sich solche Ungenauigkeiten auf.
    -- NULL heißt: diesen Tarif gibt es für diese Preisgruppe nicht.
    `preis_standard`   DECIMAL(6,2)  NOT NULL,
    `preis_ermaessigt` DECIMAL(6,2)  NULL,
    `preis_senior`     DECIMAL(6,2)  NULL,

    -- Was der Tarif erlaubt. Absichtlich drei Spalten statt einer Stufe:
    -- Ein späteres Feature fragt "zugang_kurse = 1" ab und muss nicht wissen,
    -- welche Tarifnamen es gerade gibt.
    `zugang_geraete`   TINYINT(1)    NOT NULL DEFAULT 0,
    `zugang_wellness`  TINYINT(1)    NOT NULL DEFAULT 0,
    `zugang_kurse`     TINYINT(1)    NOT NULL DEFAULT 0,

    -- Reihenfolge der Karten auf der Seite. Ohne das ist sie zufällig.
    `sortierung`       INT           NOT NULL DEFAULT 0,

    -- Ein Tarif kann nicht gelöscht werden, sobald ein Vertrag auf ihn zeigt
    -- (Fremdschlüssel unten). Aus dem Angebot nehmen geht über diese Spalte.
    -- Gebraucht spätestens für den Eröffnungspreis der ersten 100 Mitglieder.
    `aktiv`            TINYINT(1)    NOT NULL DEFAULT 1,

    `erstellt_am`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_kennung` (`kennung`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- FEATURE MITGLIEDSCHAFTEN - die Verträge
-- -----------------------------------------------------------------------------
-- Eine Zeile je abgeschlossenem Tarif. Ein Wechsel beendet die alte Zeile
-- (endet_am wird gesetzt) und legt eine neue an - die Historie bleibt damit
-- erhalten.
--
-- BEIDE DATUMSSPALTEN SIND EINSCHLIESSLICH GEMEINT:
--   beginnt_am  erster Tag, an dem der Tarif gilt
--   endet_am    LETZTER Tag, an dem er gilt (nicht der erste Tag danach)
--
-- Daraus folgen die drei Zustände, die eine Zeile haben kann:
--   laufend     beginnt_am <= heute AND (endet_am IS NULL OR endet_am >= heute)
--   vorgemerkt  beginnt_am >  heute          (Wechsel zum nächsten Monatsersten)
--   vorbei      endet_am   <  heute
--
-- Eine Zeile mit beginnt_am in der ZUKUNFT ist also normal: Wechsel werden
-- zum Monatsersten vorgemerkt, nicht sofort wirksam. Wer laufende Verträge
-- sucht, darf deshalb nie nur auf endet_am IS NULL prüfen.
-- Siehe docs/decisions/ADR-0010-tarifwechsel-zum-monatsersten.md
--
-- "Nur eine laufende Mitgliedschaft pro Mitglied" lässt sich in MySQL nicht
-- als Constraint ausdrücken (es gibt keinen Unique-Index, der nur für
-- endet_am IS NULL gilt). Das stellt MitgliedschaftRepository sicher, in einer
-- Transaktion. Wer hier direkt per SQL einfügt, umgeht das.
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `mitgliedschaften` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,

    `mitglied_id`     INT UNSIGNED NOT NULL,
    `tarif_id`        INT UNSIGNED NOT NULL,

    -- Steht am Vertrag, nicht am Mitglied: Der Studentenstatus endet
    -- irgendwann, der Seniorenstatus beginnt irgendwann.
    `preisgruppe`     ENUM('standard','ermaessigt','senior') NOT NULL DEFAULT 'standard',

    -- Kopie aus tarife zum Zeitpunkt des Abschlusses, absichtlich redundant:
    -- Eine spätere Preisänderung im Katalog darf laufende Verträge nicht
    -- rückwirkend verteuern.
    `preis_monatlich` DECIMAL(6,2) NOT NULL,

    -- Erster Gültigkeitstag. Liegt in der Zukunft, solange ein Wechsel nur
    -- vorgemerkt ist.
    `beginnt_am`      DATE         NOT NULL,

    -- Letzter Gültigkeitstag, einschließlich. NULL = läuft unbefristet.
    `endet_am`        DATE         NULL,

    `erstellt_am`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Deckt beide Abfragen ab: "alle Verträge eines Mitglieds" und
    -- "der laufende Vertrag eines Mitglieds".
    KEY `idx_mitglied_laufzeit` (`mitglied_id`, `endet_am`),

    -- Wird ein Mitglied gelöscht, verschwinden seine Verträge mit.
    CONSTRAINT `fk_mitgliedschaften_mitglieder`
        FOREIGN KEY (`mitglied_id`) REFERENCES `mitglieder` (`id`)
        ON DELETE CASCADE,

    -- Hier ausdrücklich KEIN CASCADE: Ein Tarif, auf den Verträge zeigen,
    -- darf nicht löschbar sein. Aus dem Angebot nehmen geht über tarife.aktiv.
    CONSTRAINT `fk_mitgliedschaften_tarife`
        FOREIGN KEY (`tarif_id`) REFERENCES `tarife` (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


-- -----------------------------------------------------------------------------
-- FEATURE MITGLIEDSCHAFTEN - Nachweise für ermäßigte Preise
-- -----------------------------------------------------------------------------
-- Wer weniger zahlen will, muss es belegen: Schülerinnen und Schüler sowie
-- Studierende mit ihrem Ausweis, Seniorinnen und Senioren ab 65 mit einem
-- Lichtbildausweis.
--
-- HIER STEHT KEIN BILD UND KEIN PFAD ZU EINEM BILD. Das hochgeladene Foto
-- wird ausgelesen und sofort verworfen, es landet nie auf der Festplatte.
-- Gespeichert wird nur das Ergebnis der Prüfung. Das ist der Kern der
-- Datenschutz-Entscheidung, siehe
-- docs/decisions/ADR-0011-ausweispruefung-mit-ki.md
--
-- Ein Geburtsdatum wird ebenfalls nicht gespeichert: Wer einmal 65 ist,
-- bleibt es. Für Senioren genügt art = 'senior' mit gueltig_bis = NULL.
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `nachweise` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,

    `mitglied_id` INT UNSIGNED NOT NULL,

    -- Was belegt wurde. 'schueler' und 'student' berechtigen zum ermäßigten
    -- Preis, 'senior' zum Seniorenpreis.
    `art`         ENUM('schueler','student','senior') NOT NULL,

    -- Letzter Tag, an dem der Nachweis gilt - einschließlich, wie bei
    -- mitgliedschaften. NULL heißt unbefristet und kommt nur bei 'senior' vor.
    `gueltig_bis` DATE NULL,

    -- Wie geprüft wurde. 'ki' = ausgelesen von der Bilderkennung,
    -- 'demo' = im Demo-Modus von Hand eingetragen, weil kein API-Schlüssel
    -- hinterlegt ist. Der Unterschied muss sichtbar bleiben, sonst weiß
    -- später niemand mehr, welche Angaben geprüft wurden.
    `quelle`      ENUM('ki','demo') NOT NULL,

    -- Welches Modell geprüft hat, für die Nachvollziehbarkeit. NULL im Demo-Modus.
    `ki_modell`   VARCHAR(60) NULL,

    -- Was beim Prüfen gelesen wurde, ein Satz. Ersetzt das gelöschte Bild als
    -- Beleg, wenn jemand die Prüfung anzweifelt.
    `hinweis`     VARCHAR(255) NOT NULL,

    `geprueft_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Deckt die einzige häufige Abfrage ab: "hat dieses Mitglied einen
    -- gültigen Nachweis dieser Art?"
    KEY `idx_mitglied_art` (`mitglied_id`, `art`, `gueltig_bis`),

    CONSTRAINT `fk_nachweise_mitglieder`
        FOREIGN KEY (`mitglied_id`) REFERENCES `mitglieder` (`id`)
        ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
