/**
 * @file        assets/js/services/bewertungen.js
 * @layer       3 – Service
 * @description Bewertungen des Studios. Einzige Stelle im Frontend, die das
 *              Backend dafür anspricht.
 *
 *              bewertungenLaden() geht ohne Anmeldung. bewertungAbgeben()
 *              setzt eine Anmeldung voraus und wirft sonst einen ApiError
 *              mit status 401.
 * @see         assets/js/services/api.js
 * @see         api/bewertungen.php
 */

import { getJson, postJson } from './api.js';

/**
 * @typedef  {object} Bewertung
 * @property {number}      id
 * @property {string}      name      Anzeigename, z. B. 'Lea M.'
 * @property {boolean}     mitglied  true, wenn die Person beim Schreiben
 *                                   einen aktiven Vertrag hatte
 * @property {number}      sterne    Ganze Zahl von 1 bis 5
 * @property {string}      text
 * @property {string}      datum     'JJJJ-MM-TT'
 * @property {string|null} bild      Pfad ohne führenden Slash, relativ zur
 *                                   BASE_URL. null = kein Bild: Zitat-Kachel
 *                                   und Initialen im Fenster
 */

/**
 * Lädt alle Bewertungen, die neueste zuerst.
 *
 * @returns {Promise<Bewertung[]>}
 * @throws {ApiError}  Wenn der Server nicht erreichbar ist
 */
export async function bewertungenLaden() {
  return getJson('api/bewertungen.php');
}

/**
 * Gibt die eigene Bewertung ab. Name und Mitgliedsstatus ergänzt der
 * Server selbst.
 *
 * @param {number} sterne  1 bis 5
 * @param {string} text    höchstens 1000 Zeichen
 * @returns {Promise<{id: number}>}
 * @throws {ApiError}  400 bei fehlenden Angaben · 401 ohne Anmeldung ·
 *                     409, wenn das Konto schon bewertet hat
 */
export async function bewertungAbgeben(sterne, text) {
  return postJson('api/bewertungen.php', { sterne: String(sterne), text });
}
