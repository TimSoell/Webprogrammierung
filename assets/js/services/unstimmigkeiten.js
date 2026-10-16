/**
 * @file        assets/js/services/unstimmigkeiten.js
 * @layer       3 – Service
 * @description Die Konten lesen, bei denen das Personal am Empfang eine
 *              Unstimmigkeit gemeldet hat, die Markierung aufheben und ein
 *              solches Konto löschen. Einzige Stelle im Frontend, die
 *              api/unstimmigkeiten.php anspricht.
 *
 *              Alle drei Funktionen antworten mit der Liste, wie sie danach
 *              ist. Wer kein Admin ist, bekommt vom Server 403.
 * @see         assets/js/services/api.js
 * @see         api/unstimmigkeiten.php
 */

import { getJson, sendJson } from './api.js';

/**
 * @typedef {object} GemeldetesKonto
 * @property {number} mitgliedId
 * @property {string} vorname
 * @property {string} nachname
 * @property {string} email
 * @property {string} registriertAm  'JJJJ-MM-TT'
 * @property {string} gemeldetAm     'JJJJ-MM-TT'
 * @property {string} grund          was das Personal dazu geschrieben hat
 */

/**
 * Lädt alle gemeldeten Konten, die älteste Meldung zuerst.
 *
 * @returns {Promise<{unstimmigkeiten: Array<GemeldetesKonto>}>}
 * @throws {ApiError}  401 ohne Anmeldung, 403 ohne die Rolle Admin
 */
export async function unstimmigkeitenLaden() {
  return getJson('api/unstimmigkeiten.php');
}

/**
 * Hebt die Markierung auf, weil sich alles aufgeklärt hat.
 *
 * @param {number} mitgliedId
 * @returns {Promise<{unstimmigkeiten: Array<GemeldetesKonto>}>}  die Liste danach
 * @throws {ApiError}  409, wenn nichts mehr gemeldet ist
 */
export async function markierungAufheben(mitgliedId) {
  return sendJson('PUT', 'api/unstimmigkeiten.php', { mitgliedId: String(mitgliedId) });
}

/**
 * Löscht ein gemeldetes Konto mit allem, was dazugehört. Nicht rückgängig
 * zu machen.
 *
 * @param {number} mitgliedId
 * @returns {Promise<{unstimmigkeiten: Array<GemeldetesKonto>}>}  die Liste danach
 * @throws {ApiError}  403 beim eigenen Konto, 409 wenn nicht mehr gemeldet
 */
export async function kontoLoeschen(mitgliedId) {
  return sendJson('DELETE', 'api/unstimmigkeiten.php', { mitgliedId: String(mitgliedId) });
}
