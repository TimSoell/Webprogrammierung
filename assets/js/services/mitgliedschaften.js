/**
 * @file        assets/js/services/mitgliedschaften.js
 * @layer       3 – Service
 * @description Den eigenen Tarifstand lesen und ändern. Einzige Stelle im
 *              Frontend, die api/mitgliedschaften.php anspricht.
 *
 *              Beide Funktionen setzen eine Anmeldung voraus und werfen sonst
 *              einen ApiError mit status 401.
 * @see         assets/js/services/api.js
 * @see         api/mitgliedschaften.php
 */

import { getJson, postJson } from './api.js';

/**
 * @typedef {object} Mitgliedschaft
 * @property {string} tarif           Kennung des Tarifs, z. B. 'premium'
 * @property {string} name            Anzeigename des Tarifs
 * @property {string} beschreibung    Ein Satz zum Tarif
 * @property {'standard'|'ermaessigt'|'senior'} preisgruppe
 * @property {number} preisMonatlich  Preis beim Abschluss, kann vom heutigen
 *                                    Katalogpreis abweichen
 * @property {string} beginntAm       Erster Gültigkeitstag, 'JJJJ-MM-TT'
 * @property {string|null} endetAm    Letzter Gültigkeitstag, null = unbefristet
 * @property {{geraete: boolean, wellness: boolean, kurse: boolean}} zugang
 */

/**
 * @typedef {object} Tarifstand
 * @property {Mitgliedschaft|null} mitgliedschaft  was heute gilt
 * @property {Mitgliedschaft|null} geplant         zum Monatsersten vorgemerkt
 * @property {string} wechselAb  Tag, ab dem ein jetzt vorgemerkter Wechsel
 *                               gelten würde, 'JJJJ-MM-TT'. Kommt vom Server,
 *                               damit Anzeige und Eintrag dasselbe Datum haben
 * @property {number} kurseAbWechsel  gebuchte Kurse ab wechselAb. So viele
 *                               würde ein Wechsel auf einen Tarif ohne Kurse
 *                               automatisch stornieren
 * @property {number} storniert  nach anpassen(): so viele Kurse wurden
 *                               dabei storniert, sonst 0
 * @property {{von: string, bis: string}|null} gratis  eingelöste Gutscheine
 *                               aus "Freunde werben": in diesem Zeitraum
 *                               (beide Tage einschließlich) kostet der
 *                               Basisplan nichts. null = keiner läuft
 */

/**
 * Lädt den Tarifstand des angemeldeten Mitglieds.
 *
 * @returns {Promise<Tarifstand>}  mitgliedschaft ist null, wenn noch kein
 *                                 Tarif gewählt wurde - das ist kein Fehler
 * @throws {ApiError}  401, wenn niemand (mehr) angemeldet ist
 */
export async function standLaden() {
  return getJson('api/mitgliedschaften.php');
}

/**
 * Übernimmt eine Tarifauswahl. Was dabei passiert, entscheidet der Server
 * anhand des bisherigen Stands:
 *
 *   - noch kein Tarif       -> gilt ab heute
 *   - anderer Tarif gewählt -> wird zum nächsten Monatsersten vorgemerkt
 *   - laufender Tarif       -> eine bestehende Vormerkung wird zurückgenommen
 *
 * @param {string} tarif  Kennung, z. B. 'wellness'
 * @param {'standard'|'ermaessigt'|'senior'} preisgruppe
 * @returns {Promise<Tarifstand>}  der Stand nach der Änderung
 * @throws {ApiError}  400 bei unbekanntem Tarif oder unpassender Preisgruppe,
 *                     401 ohne Anmeldung,
 *                     409 wenn es nichts zu ändern gibt
 */
export async function anpassen(tarif, preisgruppe) {
  return postJson('api/mitgliedschaften.php', { tarif, preisgruppe });
}
