/**
 * @file        assets/js/services/beispiel-feature.js
 * @layer       3 – Service
 * @description VORLAGE, Schicht 3 von 6. Enthält absichtlich keinen Code.
 *              Vorherige Schicht: assets/js/pages/beispiel-feature.page.js
 *              Nächste Schicht:   api/beispiel-feature.php
 *
 *              DAS IST DIE WICHTIGSTE SCHICHT DES PROJEKTS.
 *
 *              Sie ist die Naht zwischen Oberfläche und Datenhaltung.
 *              Solange die Funktionen hier gleich heißen und gleich
 *              aussehende Daten zurückgeben, ist es der Seite völlig egal,
 *              WOHER die Daten kommen. Genau deshalb könnt ihr heute mit
 *              erfundenen Daten arbeiten und später auf MySQL umstellen,
 *              ohne eine einzige Seite anzufassen.
 *
 * ============================================================================
 * WAS GEHÖRT IN DIESE SCHICHT
 * ============================================================================
 *
 *   - Je eine Funktion pro fachlichem Vorgang, benannt nach dem Vorgang:
 *         alleLaden(), einzelnesLaden(id), anlegen(daten), loeschen(id)
 *   - Die Aufrufe von getJson() / postJson() aus ./api.js
 *   - Umformen der Serverantwort in die Form, die die Seite braucht
 *
 * ============================================================================
 * WAS HIER NICHTS ZU SUCHEN HAT
 * ============================================================================
 *
 *   - document.querySelector oder sonstiger Zugriff auf die Seite
 *   - innerHTML, textContent, classList
 *   - fetch() direkt -> immer über ./api.js
 *
 *   Ein Service darf die Seite nicht kennen. Er liefert Daten, sonst nichts.
 *
 * ============================================================================
 * SCHRITT 1 - HEUTE, OHNE DATENBANK
 * ============================================================================
 *
 *   const BEISPIELDATEN = [
 *     { id: 1, name: 'Kraftzirkel', tag: 'Montag', uhrzeit: '18:00' },
 *     { id: 2, name: 'Boxen',       tag: 'Mittwoch', uhrzeit: '19:30' },
 *   ];
 *
 *   export async function alleLaden() {
 *     return BEISPIELDATEN;
 *   }
 *
 *   Wichtig: schon jetzt "async" schreiben, obwohl noch nichts geladen wird.
 *   Dann bleibt die Signatur beim Umstieg unverändert und die Seite muss
 *   nicht angepasst werden.
 *
 * ============================================================================
 * SCHRITT 2 - SPÄTER, MIT DATENBANK
 * ============================================================================
 *
 *   import { getJson, postJson } from './api.js';
 *
 *   export async function alleLaden() {
 *     return getJson('api/beispiel-feature.php');
 *   }
 *
 *   export async function anlegen(daten) {
 *     return postJson('api/beispiel-feature.php', daten);
 *   }
 *
 *   Das ist der gesamte Umbau. Die Seite merkt davon nichts.
 *
 * @see         assets/js/services/api.js
 * @see         docs/ARCHITECTURE.md
 */
