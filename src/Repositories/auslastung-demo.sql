-- =============================================================================
-- @file        src/Repositories/auslastung-demo.sql
-- @layer       5 – Datenzugriff / manuelle Demo-Daten
-- @description Ergänzt heutige Demo-Besuche im ausgewählten Supabase-Projekt.
--              Ersetzt nur heutige Besuche der gesperrten Demo-Konten.
-- @see         docs/features/auslastung.md
-- =============================================================================

BEGIN;
SET LOCAL search_path TO public;
SET LOCAL TIME ZONE 'Europe/Berlin';

-- Gesperrte Demo-Konten ohne verwendbares Passwort; erneut ausführbar.
INSERT INTO users (email, password, status, verified, resettable, registered)
SELECT 'auslastung-demo-' || nummer || '@example.invalid',
       '!', 3, 0, 0, EXTRACT(EPOCH FROM NOW())::integer
FROM generate_series(1, 150) AS nummer
ON CONFLICT (email) DO NOTHING;

INSERT INTO mitglieder (user_id, vorname, nachname)
SELECT id, 'Auslastung-Demo', email
FROM users
WHERE email LIKE 'auslastung-demo-%@example.invalid' AND status = 3
ON CONFLICT (user_id) DO NOTHING;

-- Bestehende Benutzerbesuche bleiben erhalten.
DELETE FROM besuche
WHERE mitglied_id IN (
    SELECT m.id FROM mitglieder m JOIN users u ON u.id = m.user_id
    WHERE u.email LIKE 'auslastung-demo-%@example.invalid'
      AND u.status = 3 AND m.vorname = 'Auslastung-Demo'
)
AND beginn < CURRENT_DATE + INTERVAL '1 day'
AND ende > CURRENT_DATE;

DO $$
DECLARE
    gruppe record;
BEGIN
    -- Teilfüllungen, deutlich überdurchschnittliche Balken und Besuche jetzt.
    FOR gruppe IN
        SELECT * FROM (VALUES
            (CURRENT_DATE + TIME '08:00', 24),
            (CURRENT_DATE + TIME '11:00', 18),
            (CURRENT_DATE + TIME '16:00', 120),
            (CURRENT_DATE + TIME '19:00', 65),
            (LOCALTIMESTAMP(0), 45)
        ) AS gruppen(beginn, anzahl)
    LOOP
        INSERT INTO besuche (mitglied_id, beginn, ende, art)
        SELECT m.id, gruppe.beginn, gruppe.beginn + INTERVAL '90 minutes',
               CASE WHEN gruppe.beginn <= LOCALTIMESTAMP THEN 'jetzt' ELSE 'geplant' END
        FROM mitglieder m JOIN users u ON u.id = m.user_id
        WHERE u.email LIKE 'auslastung-demo-%@example.invalid'
          AND u.status = 3 AND m.vorname = 'Auslastung-Demo'
          AND NOT EXISTS (
              SELECT 1 FROM besuche b
              WHERE b.mitglied_id = m.id
                AND b.beginn < gruppe.beginn + INTERVAL '90 minutes'
                AND b.ende > gruppe.beginn
          )
        ORDER BY m.id
        LIMIT gruppe.anzahl;
    END LOOP;
END $$;

-- Ergebnis: Anzahl der heutigen Demo-Besuche im ausgewählten Projekt.
SELECT COUNT(*) AS demo_besuche_heute
FROM besuche b
JOIN mitglieder m ON m.id = b.mitglied_id
JOIN users u ON u.id = m.user_id
WHERE u.email LIKE 'auslastung-demo-%@example.invalid'
  AND u.status = 3 AND m.vorname = 'Auslastung-Demo'
  AND b.beginn >= CURRENT_DATE
  AND b.beginn < CURRENT_DATE + INTERVAL '1 day';

COMMIT;
