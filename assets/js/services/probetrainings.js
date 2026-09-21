/**
 * @file        assets/js/services/probetrainings.js
 * @layer       3 – Service
 * @description Coaches und freie Termine fürs Probetraining laden, buchen
 *              und stornieren. Einzige Stelle im Frontend, die dafür das
 *              Backend anspricht.
 * @see         assets/js/services/api.js
 * @see         api/verfuegbarkeiten.php
 * @see         api/probetrainings.php
 */

import { getJson, postJson, sendJson } from './api.js';

/**
 * @typedef  {object} ProbetrainingCoach
 * @property {number} id
 * @property {string} name
 * @property {string} schwerpunkt
 * @property {string} bild      Pfad ohne führenden Slash, relativ zur BASE_URL
 * @property {string} bildAlt
 * @property {string} programm  z. B. 'Move' - nur zur Einordnung
 */

/**
 * @typedef  {object} FreierTermin
 * @property {string} beginntAm  'JJJJ-MM-TT HH:MM' - so geht er beim Buchen zurück
 * @property {string} datum      'JJJJ-MM-TT'
 * @property {string} beginn     'HH:MM'
 * @property {string} ende       'HH:MM'
 */

/**
 * @typedef  {object} Probetraining
 * @property {number} id
 * @property {string} datum     'JJJJ-MM-TT'
 * @property {string} beginn    'HH:MM'
 * @property {string} ende      'HH:MM'
 * @property {string} stufe
 * @property {string} coach
 * @property {string} programm
 */

/**
 * Lädt alle Coaches, die Probetrainings anbieten. Geht auch ohne Anmeldung.
 *
 * @returns {Promise<{coaches: ProbetrainingCoach[]}>}
 * @throws {ApiError}
 */
export async function coachesLaden() {
  return getJson('api/verfuegbarkeiten.php');
}

/**
 * Lädt die freien Termine eines Coaches für die nächsten zwei Wochen.
 *
 * @param {number} coachId
 * @returns {Promise<{termine: FreierTermin[]}>}
 * @throws {ApiError}  404, wenn es den Coach nicht gibt
 */
export async function freieTermineLaden(coachId) {
  return getJson('api/verfuegbarkeiten.php', { coach: coachId });
}

/**
 * Bucht ein Probetraining.
 *
 * @param {number} coachId
 * @param {string} beginntAm  aus freieTermineLaden()
 * @param {string} stufe      'einsteiger', 'fortgeschritten' oder 'erfahren'
 * @returns {Promise<{gebucht: true}>}
 * @throws {ApiError}  401 nicht angemeldet · 409 inzwischen vergeben · 400 ungültig
 */
export async function buchen(coachId, beginntAm, stufe) {
  return postJson('api/probetrainings.php', { coachId, beginntAm, stufe });
}

/**
 * Lädt die kommenden eigenen Probetrainings.
 *
 * @returns {Promise<{probetrainings: Probetraining[]}>}
 * @throws {ApiError}  401 nicht angemeldet
 */
export async function meineLaden() {
  return getJson('api/probetrainings.php');
}

/**
 * Storniert ein eigenes Probetraining. Der Termin wird wieder frei.
 *
 * @param {number} id  id aus meineLaden()
 * @returns {Promise<{storniert: true}>}
 * @throws {ApiError}  404 gibt es nicht · 409 hat schon begonnen
 */
export async function stornieren(id) {
  return sendJson('DELETE', 'api/probetrainings.php', { id });
}
