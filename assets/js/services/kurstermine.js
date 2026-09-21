/**
 * @file        assets/js/services/kurstermine.js
 * @layer       3 – Service
 * @description Kurstermine laden, buchen und stornieren.
 *              Einzige Stelle im Frontend, die dafür das Backend anspricht.
 *
 *              Alle Funktionen werfen bei Fehlern einen ApiError, dessen
 *              message ein fertiger Satz ist - bei einem vollen Termin
 *              "Leider ist hier schon alles vollgeschwitzt."
 * @see         assets/js/services/api.js
 * @see         api/kurstermine.php
 * @see         api/kursbuchungen.php
 */

import { getJson, postJson, sendJson } from './api.js';

/**
 * @typedef  {object} Kurstermin
 * @property {number}  terminId            id des Wochenplan-Eintrags
 * @property {string}  datum               'JJJJ-MM-TT'
 * @property {string}  beginn              'HH:MM'
 * @property {string}  ende                'HH:MM'
 * @property {string}  format              nur zur Einordnung, z. B. 'Small Group'
 * @property {string}  formatBeschreibung
 * @property {string}  coach
 * @property {string}  coachSchwerpunkt
 * @property {number}  belegt              Stammplätze plus Buchungen
 * @property {number}  max                 höchstens so viele Teilnehmer
 * @property {boolean} gebucht             true, wenn man selbst gebucht hat
 */

/**
 * @typedef  {object} Kursbuchung
 * @property {number} id
 * @property {string} datum     'JJJJ-MM-TT'
 * @property {string} beginn    'HH:MM'
 * @property {string} ende      'HH:MM'
 * @property {string} stufe     'einsteiger', 'fortgeschritten' oder 'erfahren'
 * @property {string} programm  Anzeigename, z. B. 'Move'
 * @property {string} slug      für den Link zur Kursseite
 * @property {string} format
 * @property {string} coach
 */

/**
 * Lädt die Termine eines Programms für die nächsten zwei Wochen.
 * Geht auch ohne Anmeldung.
 *
 * von und bis sind erster und letzter Tag des Kalenders ('JJJJ-MM-TT'),
 * auch wenn an ihnen kein Termin liegt.
 *
 * @param {string} slug  'strength', 'move' oder 'fight'
 * @returns {Promise<{angemeldet: boolean, von: string, bis: string, termine: Kurstermin[]}>}
 * @throws {ApiError}  404, wenn es das Programm nicht gibt
 */
export async function termineLaden(slug) {
  return getJson('api/kurstermine.php', { slug });
}

/**
 * Bucht einen Platz.
 *
 * @param {number} terminId
 * @param {string} datum  'JJJJ-MM-TT'
 * @param {string} stufe  'einsteiger', 'fortgeschritten' oder 'erfahren'
 * @returns {Promise<{gebucht: true}>}
 * @throws {ApiError}  401 nicht angemeldet · 409 ausgebucht oder schon gebucht · 400 ungültig
 */
export async function buchen(terminId, datum, stufe) {
  return postJson('api/kursbuchungen.php', { terminId, datum, stufe });
}

/**
 * Lädt die kommenden eigenen Kursbuchungen.
 *
 * @returns {Promise<{buchungen: Kursbuchung[]}>}
 * @throws {ApiError}  401 nicht angemeldet
 */
export async function meineLaden() {
  return getJson('api/kursbuchungen.php');
}

/**
 * Storniert eine eigene Buchung. Der Platz wird wieder frei.
 *
 * @param {number} id  id aus meineLaden()
 * @returns {Promise<{storniert: true}>}
 * @throws {ApiError}  404 gibt es nicht · 409 hat schon begonnen
 */
export async function stornieren(id) {
  return sendJson('DELETE', 'api/kursbuchungen.php', { id });
}
