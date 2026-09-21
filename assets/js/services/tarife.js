/**
 * @file        assets/js/services/tarife.js
 * @layer       3 – Service
 * @description Holt den Tarifkatalog vom Server. Einzige Stelle im Frontend,
 *              die api/tarife.php anspricht.
 *
 *              Nur lesend: Tarife werden über database/seed.sql gepflegt,
 *              nicht über die Website.
 * @see         assets/js/services/api.js
 * @see         api/tarife.php
 */

import { getJson } from './api.js';

/**
 * @typedef {object} Tarif
 * @property {string} kennung       'basis', 'wellness', 'kurse' oder 'premium'
 * @property {string} name          Anzeigename, z. B. 'Wellness-Plan'
 * @property {string} beschreibung  Ein Satz für die Karte
 * @property {{standard: number, ermaessigt: number|null, senior: number|null}} preise
 *           Monatspreis je Preisgruppe. null = für diese Gruppe nicht angeboten
 * @property {{geraete: boolean, wellness: boolean, kurse: boolean}} zugang
 *           Was der Tarif erlaubt
 */

/**
 * Lädt alle Tarife, die das Studio aktuell anbietet.
 *
 * Die Reihenfolge kommt aus der Datenbank und ist die, in der die Karten
 * auf der Seite stehen sollen - nicht selbst sortieren.
 *
 * @returns {Promise<Array<Tarif>>}  leer, wenn seed.sql nie eingespielt wurde
 * @throws {ApiError}                wenn der Server nicht erreichbar ist
 */
export async function alleLaden() {
  const antwort = await getJson('api/tarife.php');

  return antwort.tarife;
}
