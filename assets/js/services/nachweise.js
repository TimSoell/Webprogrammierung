/**
 * @file        assets/js/services/nachweise.js
 * @layer       3 – Service
 * @description Nachweise für ermäßigte Preise lesen, hochladen und entfernen.
 *              Einzige Stelle im Frontend, die api/nachweise.php anspricht.
 *
 *              Das Bild geht als base64 im JSON an den Server und nicht als
 *              Datei-Upload. Grund steht in api/nachweise.php: Der
 *              Content-Type application/json ist unser CSRF-Schutz, und den
 *              wollen wir für diesen einen Endpunkt nicht aufgeben.
 * @see         assets/js/services/api.js
 * @see         assets/js/lib/bild.js
 * @see         api/nachweise.php
 */

import { getJson, postJson, sendJson } from './api.js';
import { verkleinern } from '../lib/bild.js';

/**
 * @typedef {object} Nachweis
 * @property {number} id               zum Entfernen
 * @property {'schueler'|'student'|'senior'} art
 * @property {string|null} gueltigBis  'JJJJ-MM-TT', null = unbefristet (Senior)
 * @property {'ki'|'demo'} quelle      wie geprüft wurde
 * @property {string} hinweis          was geprüft wurde, ohne persönliche Angaben
 * @property {string} geprueftAm       Zeitstempel der Prüfung
 */

/**
 * @typedef {object} Nachweisstand
 * @property {Array<Nachweis>} nachweise  höchstens ein gültiger, dazu die
 *                                        abgelaufenen, neueste zuerst
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
 * sondern liest nur Name und Datum heraus. Ein gespeicherter Nachweis
 * ersetzt den bisherigen - es gilt immer nur einer.
 *
 * @param {'schueler'|'student'|'senior'} art  was nachgewiesen werden soll
 * @param {File} datei                         das Foto des Ausweises
 * @returns {Promise<Nachweisstand>}           der Stand nach der Prüfung
 * @throws {ApiError}  400 bei einem unbrauchbaren Bild,
 *                     409 wenn schon ein gleich guter Nachweis vorliegt,
 *                     422 wenn das Dokument nicht passt, nichts zu lesen war,
 *                     der Name nicht zum Konto passt, der Ausweis abgelaufen
 *                     ist oder das Alter nicht reicht,
 *                     429 wenn die Prüfversuche aufgebraucht sind,
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

/**
 * Entfernt einen eigenen Nachweis.
 *
 * Läuft ein Vertrag zum ermäßigten Preis, gilt für ihn ohne Nachweis ab dem
 * nächsten Monatsersten der Standardpreis.
 *
 * @param {number} id  id des Nachweises
 * @returns {Promise<Nachweisstand>}  der Stand danach
 * @throws {ApiError}  401, wenn niemand angemeldet ist
 */
export async function entfernen(id) {
  return sendJson('DELETE', 'api/nachweise.php', { id: String(id) });
}
