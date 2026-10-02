/**
 * @file        assets/js/services/gutscheine.js
 * @layer       3 – Service
 * @description Gutscheine aus "Freunde werben": die eigenen laden und einen
 *              einlösen. Einzige Stelle im Frontend, die dafür das Backend
 *              anspricht.
 *
 *              Beide Funktionen setzen eine Anmeldung voraus und werfen
 *              sonst einen ApiError mit status 401.
 * @see         assets/js/services/api.js
 * @see         api/gutscheine.php
 */

import { getJson, postJson } from './api.js';

/**
 * @typedef  {object} Gutschein
 * @property {string}      code        z. B. 'SK-7F3K9QXM'
 * @property {boolean}     eingeloest
 * @property {string|null} gratisVon   erster Gratistag, 'JJJJ-MM-TT'; null = nicht eingelöst
 * @property {string|null} gratisBis   letzter Gratistag, einschließlich
 */

/**
 * Lädt die eigenen Gutscheine, den neuesten zuerst.
 *
 * @returns {Promise<{gutscheine: Gutschein[]}>}
 * @throws {ApiError}  401 ohne Anmeldung
 */
export async function gutscheineLaden() {
  return getJson('api/gutscheine.php');
}

/**
 * Löst einen eigenen Gutschein ein: Der Basisplan ist danach drei Monate
 * gratis. Geht nur, wenn der Basisplan läuft oder vorgemerkt ist.
 *
 * @param {string} code  wie unter "Mein Konto" angezeigt; Groß- und Kleinschreibung egal
 * @returns {Promise<{gratisVon: string, gratisBis: string}>}  beide 'JJJJ-MM-TT', einschließlich
 * @throws {ApiError}  404 gibt es nicht · 409 schon eingelöst oder kein Basisplan
 */
export async function gutscheinEinloesen(code) {
  return postJson('api/gutscheine.php', { code });
}
