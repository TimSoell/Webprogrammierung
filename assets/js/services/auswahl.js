/**
 * @file        assets/js/services/auswahl.js
 * @layer       3 – Service
 * @description Die am Konto gemerkte Level- und Format-Auswahl.
 *              Einzige Stelle im Frontend, die das Backend dafür anspricht.
 *
 *              Alle Funktionen setzen eine Anmeldung voraus und werfen sonst
 *              einen ApiError mit status 401. Wer nicht angemeldet ist,
 *              benutzt stattdessen den localStorage - siehe
 *              assets/js/components/auswahl-speicher.js.
 * @see         assets/js/services/api.js
 * @see         api/auswahl.php
 */

import { getJson, sendJson } from './api.js';

/**
 * @typedef  {object} GemerkteAuswahl
 * @property {string}      slug      'strength', 'move' oder 'fight'
 * @property {string}      programm  Anzeigename, z. B. 'Move'
 * @property {string|null} level     Titel des gemerkten Levels
 * @property {string|null} format    Titel des gemerkten Formats
 */

/**
 * Lädt alle gemerkten Auswahlen des angemeldeten Mitglieds.
 *
 * @returns {Promise<{auswahl: GemerkteAuswahl[]}>}
 * @throws {ApiError}  401, wenn niemand angemeldet ist
 */
export async function meineAuswahlLaden() {
  return getJson('api/auswahl.php');
}

/**
 * Lädt die gemerkte Auswahl zu einem einzelnen Programm.
 *
 * @param {string} slug
 * @returns {Promise<{auswahl: {level: string|null, format: string|null}|null}>}
 * @throws {ApiError}  401 · 404, wenn es das Programm nicht gibt
 */
export async function auswahlFuerProgrammLaden(slug) {
  return getJson('api/auswahl.php', { slug });
}

/**
 * Merkt eine Auswahl am Konto oder überschreibt die vorhandene.
 *
 * @param {string} slug
 * @param {string|null} level   Titel des Levels, null wenn das Programm keines hat
 * @param {string|null} format  Titel des Formats, null wenn das Programm keines hat
 * @returns {Promise<{gemerkt: true}>}
 * @throws {ApiError}  400 bei unbekanntem Titel · 401
 */
export async function auswahlMerken(slug, level, format) {
  return sendJson('PUT', 'api/auswahl.php', {
    slug,
    level: level ?? '',
    format: format ?? '',
  });
}

/**
 * Entfernt die gemerkte Auswahl zu einem Programm.
 *
 * Ist dort nichts gemerkt, gilt das trotzdem als Erfolg - das Ergebnis ist
 * dasselbe.
 *
 * @param {string} slug
 * @returns {Promise<{entfernt: true}>}
 * @throws {ApiError}  401 · 404, wenn es das Programm nicht gibt
 */
export async function auswahlEntfernen(slug) {
  return sendJson('DELETE', 'api/auswahl.php', { slug });
}
