/**
 * @file        assets/js/services/profilbilder.js
 * @layer       3 – Service
 * @description Das eigene Profilbild speichern, freigeben und entfernen.
 *              Einzige Stelle im Frontend, die dafür das Backend anspricht.
 *
 *              Das Bild ANZEIGEN braucht keinen Service: Die Adresse kommt
 *              aus api/sitzung.php bzw. api/bewertungen.php und steht direkt
 *              in <img src>.
 *
 *              Alle Funktionen setzen eine Anmeldung voraus und werfen sonst
 *              einen ApiError mit status 401.
 * @see         assets/js/services/api.js
 * @see         api/profilbilder.php
 */

import { sendJson } from './api.js';

/**
 * Speichert ein neues Profilbild oder ersetzt das alte.
 *
 * @param {string} daten  JPEG als base64, ohne "data:..."-Vorspann -
 *                        so wie lib/bild.js es liefert
 * @returns {Promise<{profilbild: string, oeffentlich: boolean}>}
 *          profilbild ist die Adresse relativ zur BASE_URL
 * @throws {ApiError}  400, wenn das Bild nicht passt
 */
export async function profilbildSpeichern(daten) {
  return sendJson('PUT', 'api/profilbilder.php', { bild: daten });
}

/**
 * Legt fest, ob das Profilbild bei den eigenen Bewertungen erscheint.
 *
 * @param {boolean} oeffentlich
 * @returns {Promise<{oeffentlich: boolean}>}
 * @throws {ApiError}  404, wenn es noch kein Profilbild gibt
 */
export async function profilbildFreigeben(oeffentlich) {
  return sendJson('PUT', 'api/profilbilder.php', { oeffentlich: oeffentlich ? '1' : '0' });
}

/**
 * Entfernt das eigene Profilbild.
 *
 * @returns {Promise<{profilbild: null}>}
 * @throws {ApiError}
 */
export async function profilbildEntfernen() {
  return sendJson('DELETE', 'api/profilbilder.php');
}
