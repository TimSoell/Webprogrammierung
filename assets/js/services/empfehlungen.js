/**
 * @file        assets/js/services/empfehlungen.js
 * @layer       3 – Service
 * @description "Freunde werben": eigene Einladungen laden und einen Freund
 *              einladen. Einzige Stelle im Frontend, die dafür das Backend
 *              anspricht.
 *
 *              Beide Funktionen setzen eine Anmeldung voraus und werfen
 *              sonst einen ApiError mit status 401.
 * @see         assets/js/services/api.js
 * @see         api/empfehlungen.php
 */

import { getJson, postJson } from './api.js';

/**
 * @typedef  {object} Einladung
 * @property {string}  name         so hat man den Freund genannt
 * @property {string}  email
 * @property {boolean} registriert  true, sobald sich der Freund registriert hat
 * @property {string}  datum        Tag der Einladung, 'JJJJ-MM-TT'
 */

/**
 * Lädt die eigenen Einladungen, die neueste zuerst.
 *
 * @returns {Promise<{einladungen: Einladung[]}>}
 * @throws {ApiError}  401 ohne Anmeldung
 */
export async function einladungenLaden() {
  return getJson('api/empfehlungen.php');
}

/**
 * Trägt einen Freund als eingeladen ein. Eine E-Mail verschickt die Seite
 * dabei nicht - den Link gibt man selbst weiter.
 *
 * @param {string} name   Name des Freundes, höchstens 120 Zeichen
 * @param {string} email  E-Mail, mit der sich der Freund registrieren wird
 * @returns {Promise<{eingeladen: true}>}
 * @throws {ApiError}  400 bei fehlenden Angaben · 401 ohne Anmeldung ·
 *                     409 schon eingeladen, schon registriert oder zu viele offen
 */
export async function freundEinladen(name, email) {
  return postJson('api/empfehlungen.php', { name, email });
}
