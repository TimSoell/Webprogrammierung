/**
 * @file        assets/js/services/personal.js
 * @layer       3 – Service
 * @description Die Liste aller Konten mit der Rolle Personal lesen, ein Konto
 *              zum Personal machen und ihm die Rolle wieder entziehen.
 *              Einzige Stelle im Frontend, die api/personal.php anspricht.
 *
 *              Alle drei Funktionen antworten mit der Liste, wie sie danach
 *              ist. Wer kein Admin ist, bekommt vom Server 403.
 * @see         assets/js/services/api.js
 * @see         api/personal.php
 */

import { getJson, sendJson } from './api.js';

/**
 * @typedef {object} PersonalKonto
 * @property {string} vorname
 * @property {string} nachname
 * @property {string} email     zum Entziehen der Rolle
 */

/**
 * Lädt alle Konten, die gerade zum Personal gehören.
 *
 * @returns {Promise<{personal: Array<PersonalKonto>}>}
 * @throws {ApiError}  401 ohne Anmeldung, 403 ohne die Rolle Admin
 */
export async function personalLaden() {
  return getJson('api/personal.php');
}

/**
 * Macht ein bestehendes Konto zum Personal.
 *
 * @param {string} email  E-Mail-Adresse des Kontos
 * @returns {Promise<{personal: Array<PersonalKonto>}>}  die Liste danach
 * @throws {ApiError}  400 ungültige Adresse, 404 kein Konto, 409 schon Personal
 */
export async function zumPersonalMachen(email) {
  return sendJson('POST', 'api/personal.php', { email });
}

/**
 * Nimmt einem Konto die Rolle Personal wieder weg.
 *
 * @param {string} email  E-Mail-Adresse des Kontos
 * @returns {Promise<{personal: Array<PersonalKonto>}>}  die Liste danach
 * @throws {ApiError}  409, wenn das Konto schon kein Personal mehr ist
 */
export async function rolleEntziehen(email) {
  return sendJson('DELETE', 'api/personal.php', { email });
}
