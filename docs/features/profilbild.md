<!--
@file        docs/features/profilbild.md
@description Profilbild im Mitgliederkonto, dauerhaft am Mitglied gespeichert.
-->

# Feature: Profilbild im Mitgliederkonto

**Status:** in Arbeit  
**Verantwortlich:** Team  
**Zuletzt geprüft:** 2026-09-30

## Was kann man damit

Angemeldete Mitglieder sehen im Kopf der Kontoübersicht ihren vollständigen Namen und ein rundes Profilbild. Sie können ein eigenes Bild auswählen; es wird verkleinert und dauerhaft mit ihrem Mitgliedsprofil gespeichert.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| Seite | `mein-konto.php` |
| Seitenskript | `assets/js/pages/mein-konto.page.js` |
| Service | `assets/js/services/mitglieder.js` |
| Endpunkt | `api/sitzung.php` |
| Repository | `src/Repositories/MitgliedRepository.php` |
| Tabelle | `mitglieder.profilbild` in `database/schema.sql` |
| CSS | `assets/css/components/auth.css` |
| Bildverkleinerung | `assets/js/lib/bild.js` |

## Datenform

Das Bild wird im Browser zu einem JPEG mit höchstens 512 Pixeln Kantenlänge verkleinert und als Base64 übertragen. PostgreSQL speichert die dekodierten Bytes in `mitglieder.profilbild` (`bytea`). Die Sitzungs-API liefert das vorhandene Bild als Base64 zurück.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/sitzung.php` | Stammdaten inklusive optionalem Profilbild laden | `{ vorname, nachname, email, profilbild }` |
| PUT | `api/sitzung.php` | Profilbild des angemeldeten Mitglieds speichern | `{ gespeichert: true }` |

## Woher kommen die Daten aktuell

Vorname, Nachname und Bild kommen aus der Tabelle `mitglieder`; die E-Mail-Adresse kommt aus der Login-Bibliothek. Das Bild ist optional.

## Was fehlt noch

- Die Spalte muss in Supabase → SQL Editor zuerst in `schwitzkasten-dev` angelegt werden:

	```sql
	ALTER TABLE mitglieder ADD COLUMN IF NOT EXISTS profilbild bytea;
	```

- Vor dem Merge in Produktion muss dieselbe Schemaänderung dort eingespielt werden.
