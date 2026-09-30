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
 * @typedef  {object} Kursmonat
 * @property {boolean} angemeldet
 * @property {string}  monat         der gezeigte Monat, 'JJJJ-MM'
 * @property {string}  ersterMonat   so weit zurück kann man blättern
 * @property {string}  letzterMonat  so weit vor
 * @property {string}  heute         'JJJJ-MM-TT', Tage davor sind vergangen
 * @property {string}  von           erster Tag des Monats
 * @property {string}  bis           letzter Tag des Monats
 * @property {{name: string, kurse: boolean}|null} tarif
 *           Der Tarif in diesem Monat. null = nicht angemeldet oder kein Vertrag
 * @property {string|null} wechselAb Ab diesem Tag gilt ein heute vorgemerkter Wechsel
 * @property {Kurstermin[]} termine  nur die, die noch nicht begonnen haben
 */

/**
 * Lädt die Termine eines Programms für einen Monat.
 * Geht auch ohne Anmeldung.
 *
 * @param {string} slug     'strength', 'move' oder 'fight'
 * @param {string} [monat]  'JJJJ-MM'; ohne Angabe der laufende Monat
 * @returns {Promise<Kursmonat>}
 * @throws {ApiError}  404, wenn es das Programm nicht gibt · 400 Monat außerhalb des Kalenders
 */
export async function termineLaden(slug, monat) {
  return getJson('api/kurstermine.php', monat ? { slug, monat } : { slug });
}

/**
 * Bucht einen Platz.
 *
 * @param {number} terminId
 * @param {string} datum  'JJJJ-MM-TT'
 * @param {string} stufe  'einsteiger', 'fortgeschritten' oder 'erfahren'
 * @returns {Promise<{gebucht: true}>}
 * @throws {ApiError}  401 nicht angemeldet · 403 Kurse nicht im Tarif · 409 ausgebucht oder schon gebucht · 400 ungültig
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
