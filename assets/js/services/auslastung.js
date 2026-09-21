/**
 * @file        assets/js/services/auslastung.js
 * @layer       3 – Service
 * @description Studioauslastung und die eigenen angekündigten Besuche.
 *              Einzige Stelle im Frontend, die das Backend dafür anspricht.
 *
 *              auslastungLaden() geht ohne Anmeldung - das Diagramm steht auf
 *              der Startseite. Die beiden anderen Funktionen setzen eine
 *              Anmeldung voraus und werfen sonst einen ApiError mit status 401.
 * @see         assets/js/services/api.js
 * @see         api/auslastung.php
 */

import { getJson, sendJson } from './api.js';

/**
 * @typedef  {object} Stundenwert
 * @property {number} stunde   0 bis 23
 * @property {number} basis    Typische Auslastung aus der Datenbank
 * @property {number} gebucht  Aufschlag durch angekündigte Besuche
 * @property {number} gesamt   basis + gebucht
 */

/**
 * @typedef  {object} Besuch
 * @property {number} id
 * @property {string} beginn  'YYYY-MM-DD HH:MM:SS'
 * @property {string} ende    'YYYY-MM-DD HH:MM:SS'
 * @property {string} art     'jetzt' oder 'geplant'
 */

/**
 * @typedef  {object} Auslastung
 * @property {number}        kapazitaet  Wie viele Personen gleichzeitig passen
 * @property {string}        stand       Zeitpunkt der Auskunft, ISO-8601
 * @property {object}        jetzt       personen, prozent, stufe, stunde
 * @property {Stundenwert[]} verlauf     24 Werte, Stunde 0 bis 23
 * @property {Besuch[]}      [meine]     Nur wenn jemand angemeldet ist
 */

/**
 * Lädt die aktuelle Auslastung und die Kurve des heutigen Tages.
 *
 * @returns {Promise<Auslastung>}
 * @throws {ApiError}  Wenn der Server nicht erreichbar ist
 */
export async function auslastungLaden() {
  return getJson('api/auslastung.php');
}

/**
 * Trägt einen Besuch ein.
 *
 * @param {'jetzt'|'geplant'} art   'jetzt' checkt sofort ein, 'geplant' kündigt an
 * @param {string} [zeit]           Uhrzeit 'HH:MM'; nur bei 'geplant' nötig.
 *                                  Liegt sie heute schon in der Vergangenheit,
 *                                  versteht der Server sie als morgen.
 * @returns {Promise<{besuch: Besuch}>}
 * @throws {ApiError}  400 bei ungültiger Uhrzeit · 401 · 409 bei Überschneidung
 */
export async function besuchEintragen(art, zeit = '') {
  return sendJson('POST', 'api/auslastung.php', { art, zeit });
}

/**
 * Entfernt einen eigenen Besuch.
 *
 * @param {number} id
 * @returns {Promise<{entfernt: true}>}
 * @throws {ApiError}  401 · 404, wenn der Besuch nicht existiert oder
 *                     jemand anderem gehört
 */
export async function besuchEntfernen(id) {
  return sendJson('DELETE', 'api/auslastung.php', { id: String(id) });
}
