/**
 * @file        assets/js/services/empfang.js
 * @layer       3 – Service
 * @description Die Liste der Konten lesen, die am Empfang noch nicht mit
 *              dem Personalausweis bestätigt sind, ein Konto bestätigen und
 *              eine Unstimmigkeit melden.
 *              Einzige Stelle im Frontend, die api/empfang.php anspricht.
 *
 *              Alle Funktionen antworten mit der Liste, wie sie danach ist.
 *              Ohne die Rolle des Personals antwortet der Server mit 403.
 * @see         assets/js/services/api.js
 * @see         api/empfang.php
 */

import { getJson, sendJson } from './api.js';

/**
 * @typedef {object} OffenesKonto
 * @property {number} mitgliedId          zum Bestätigen
 * @property {string} vorname             so, wie er im Konto steht
 * @property {string} nachname
 * @property {string} email               unterscheidet Personen gleichen Namens
 * @property {string} registriertAm       'JJJJ-MM-TT'
 * @property {'schueler'|'student'|'senior'|null} ermaessigung
 *                                        Art des gültigen Ermäßigungsnachweises,
 *                                        null ohne
 * @property {string|null} vorzeigenBis   letzter Tag der Frist, 'JJJJ-MM-TT';
 *                                        null = noch kein Tarif, keine Frist
 * @property {boolean} fristVerpasst      true = Frist vorbei; mit Ermäßigung
 *                                        ruht dann der günstige Preis
 * @property {{am: string, grund: string}|null} unstimmigkeit
 *                                        vom Personal gemeldet, am = 'JJJJ-MM-TT';
 *                                        null = nichts gemeldet
 */

/**
 * Lädt alle Konten, die am Empfang noch nicht bestätigt sind.
 *
 * @returns {Promise<{offen: Array<OffenesKonto>}>}
 * @throws {ApiError}  401 ohne Anmeldung, 403 ohne die Rolle des Personals
 */
export async function offeneLaden() {
  return getJson('api/empfang.php');
}

/**
 * Bestätigt ein Konto: Personalausweis gesehen, Foto und Name passen.
 * Danach ist der Name für immer fest.
 *
 * @param {number} mitgliedId
 * @returns {Promise<{offen: Array<OffenesKonto>}>}  die Liste danach
 * @throws {ApiError}  403 beim eigenen Konto, 409 wenn es schon bestätigt ist
 */
export async function bestaetigen(mitgliedId) {
  return sendJson('PUT', 'api/empfang.php', { mitgliedId: String(mitgliedId) });
}

/**
 * Meldet eine Unstimmigkeit: Der Personalausweis ist falsch, oder die
 * Person passt nicht zum Konto. Ein Admin entscheidet danach, ob das Konto
 * gelöscht wird.
 *
 * @param {number} mitgliedId
 * @param {string} grund  was nicht passt, höchstens 200 Zeichen
 * @returns {Promise<{offen: Array<OffenesKonto>}>}  die Liste danach
 * @throws {ApiError}  400 ohne Grund, 403 beim eigenen Konto, 409 schon gemeldet
 */
export async function unstimmigkeitMelden(mitgliedId, grund) {
  return sendJson('POST', 'api/empfang.php', { mitgliedId: String(mitgliedId), grund });
}
