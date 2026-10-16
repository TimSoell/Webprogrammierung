/**
 * @file        assets/js/services/empfang.js
 * @layer       3 – Service
 * @description Die Liste für das Personal am Empfang lesen, eine Person
 *              bestätigen oder ihren Nachweis ablehnen. Einzige Stelle im
 *              Frontend, die api/empfang.php anspricht.
 *
 *              Alle Funktionen antworten mit der Liste, wie sie danach ist.
 *              Ohne die Rolle des Personals antwortet der Server mit 403.
 * @see         assets/js/services/api.js
 * @see         api/empfang.php
 */

import { getJson, sendJson } from './api.js';

/**
 * @typedef {object} OffenerNachweis
 * @property {number} mitgliedId          zum Bestätigen und Ablehnen
 * @property {string} vorname             so, wie er im Konto steht
 * @property {string} nachname
 * @property {string} email               unterscheidet Personen gleichen Namens
 * @property {'schueler'|'student'|'senior'} art
 * @property {string|null} gueltigBis     'JJJJ-MM-TT', null = unbefristet (Senior)
 * @property {string} geprueftAm          Zeitstempel des Uploads
 * @property {string|null} vorzeigenBis   letzter Tag der Frist, 'JJJJ-MM-TT'
 * @property {boolean} fristVerpasst      true = der Preis gilt gerade nicht
 */

/**
 * Lädt alle Nachweise, die am Empfang noch gezeigt werden müssen.
 *
 * @returns {Promise<{offen: Array<OffenerNachweis>}>}
 * @throws {ApiError}  401 ohne Anmeldung, 403 ohne die Rolle des Personals
 */
export async function offeneLaden() {
  return getJson('api/empfang.php');
}

/**
 * Bestätigt eine Person: Ausweis gesehen, Foto und Name passen. Danach ist
 * ihr Name für immer fest.
 *
 * @param {number} mitgliedId
 * @returns {Promise<{offen: Array<OffenerNachweis>}>}  die Liste danach
 * @throws {ApiError}  403 beim eigenen Konto, 409 wenn nichts mehr offen ist
 */
export async function bestaetigen(mitgliedId) {
  return sendJson('PUT', 'api/empfang.php', { mitgliedId: String(mitgliedId) });
}

/**
 * Lehnt den Nachweis einer Person ab und entfernt ihn.
 *
 * @param {number} mitgliedId
 * @returns {Promise<{offen: Array<OffenerNachweis>}>}  die Liste danach
 * @throws {ApiError}  403 beim eigenen Konto, 409 wenn nichts mehr offen ist
 */
export async function ablehnen(mitgliedId) {
  return sendJson('DELETE', 'api/empfang.php', { mitgliedId: String(mitgliedId) });
}
