/**
 * @file        assets/js/services/programme.js
 * @layer       3 – Service
 * @description Lädt die Details einer Programmseite (Merkmale und Coaches).
 *              Einzige Stelle im Frontend, die das Backend dafür anspricht.
 * @see         assets/js/services/api.js
 * @see         api/programme.php
 * @see         assets/js/components/programm-details.js
 */

import { getJson } from './api.js';

/**
 * @typedef  {object} Merkmal
 * @property {string} titel         Überschrift der Schaltfläche, z. B. 'Small Group'
 * @property {string} beschreibung  Text, der nach dem Aufklappen erscheint
 */

/**
 * @typedef  {object} Coach
 * @property {string} name         Voller Name
 * @property {string} schwerpunkt  Art des Trainings, erscheint kursiv unter dem Namen
 * @property {string} bild         Pfad ohne führenden Slash, relativ zur BASE_URL
 * @property {string} bildAlt      Beschreibung des Bildes für das alt-Attribut
 */

/**
 * Lädt Merkmale und Coaches eines Programms.
 *
 * Jede Art in `merkmale` ist immer vorhanden, notfalls als leeres Array -
 * der Aufrufer muss also nicht auf fehlende Schlüssel prüfen.
 *
 * @param {string} slug  'strength', 'move' oder 'fight'
 * @returns {Promise<{name: string, merkmale: {fokus: Merkmal[], level: Merkmal[], format: Merkmal[]}, coaches: Coach[]}>}
 * @throws {ApiError}    404, wenn es das Programm nicht gibt
 */
export async function programmDetailsLaden(slug) {
  return getJson('api/programme.php', { slug });
}
