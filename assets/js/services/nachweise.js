/**
 * @file        assets/js/services/nachweise.js
 * @layer       3 – Service
 * @description Nachweise für ermäßigte Preise lesen und hochladen. Einzige
 *              Stelle im Frontend, die api/nachweise.php anspricht.
 *
 *              Das Bild geht als base64 im JSON an den Server und nicht als
 *              Datei-Upload. Grund steht in api/nachweise.php: Der
 *              Content-Type application/json ist unser CSRF-Schutz, und den
 *              wollen wir für diesen einen Endpunkt nicht aufgeben.
 * @see         assets/js/services/api.js
 * @see         assets/js/lib/bild.js
 * @see         api/nachweise.php
 */

import { getJson, postJson } from './api.js';
import { verkleinern } from '../lib/bild.js';

/**
 * @typedef {object} Nachweis
 * @property {'schueler'|'student'|'senior'} art
 * @property {string|null} gueltigBis  'JJJJ-MM-TT', null = unbefristet (Senior)
 * @property {'ki'|'demo'} quelle      wie geprüft wurde
 * @property {string} hinweis          was beim Prüfen gelesen wurde
 * @property {string} geprueftAm       Zeitstempel der Prüfung
 */

/**
 * @typedef {object} Nachweisstand
 * @property {Array<Nachweis>} nachweise  alle, auch abgelaufene, neueste zuerst
 * @property {boolean} kiVerfuegbar       false = Demo-Modus, es wird ein
 *                                        Datum statt eines Bildes erwartet
 */

/**
 * Lädt die eigenen Nachweise.
 *
 * @returns {Promise<Nachweisstand>}
 * @throws {ApiError}  401, wenn niemand angemeldet ist
 */
export async function alleLaden() {
  return getJson('api/nachweise.php');
}

/**
 * Lädt ein Ausweisfoto hoch und lässt es prüfen.
 *
 * Das Bild wird vorher im Browser verkleinert. Der Server speichert es nicht,
 * sondern liest nur das Datum heraus.
 *
 * @param {'schueler'|'student'|'senior'} art  was nachgewiesen werden soll
 * @param {File} datei                         das Foto des Ausweises
 * @returns {Promise<Nachweisstand>}           der Stand nach der Prüfung
 * @throws {ApiError}  400 bei einem unbrauchbaren Bild,
 *                     422 wenn das Dokument nicht passt, nichts zu lesen war,
 *                     der Ausweis abgelaufen ist oder das Alter nicht reicht,
 *                     502 wenn die Prüfung nicht erreichbar war
 * @throws {Error}     wenn die Datei sich nicht als Bild lesen lässt
 */
export async function hochladen(art, datei) {
  const { daten, mimeTyp } = await verkleinern(datei);

  return postJson('api/nachweise.php', { art, bild: daten, mimeTyp });
}

/**
 * Trägt einen Nachweis im Demo-Modus von Hand ein.
 *
 * Nur möglich, solange kein API-Schlüssel hinterlegt ist - sonst weist der
 * Endpunkt das zurück und verlangt ein Bild. Das ist wichtig: Sonst könnte
 * sich jede Person ihr Ablaufdatum selbst aussuchen.
 *
 * @param {'schueler'|'student'|'senior'} art
 * @param {string} datum  bei 'senior' das Geburtsdatum, sonst das
 *                        Ablaufdatum des Ausweises, als 'JJJJ-MM-TT'
 * @returns {Promise<Nachweisstand>}
 * @throws {ApiError}  422, wenn das Datum die Regeln nicht erfüllt
 */
export async function demoEintragen(art, datum) {
  return postJson('api/nachweise.php', { art, datum });
}
