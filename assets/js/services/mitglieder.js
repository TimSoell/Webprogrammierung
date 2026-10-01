/**
 * @file        assets/js/services/mitglieder.js
 * @layer       3 – Service
 * @description Registrieren, Anmelden, Abmelden und Passwort zurücksetzen.
 *              Einzige Stelle im Frontend, die dafür das Backend anspricht.
 *
 *              Alle Funktionen werfen bei Fehlern einen ApiError. Dessen
 *              message ist bereits ein fertiger Satz für das Formular,
 *              z. B. "E-Mail-Adresse oder Passwort ungültig."
 * @see         assets/js/services/api.js
 * @see         api/mitglieder.php
 * @see         api/sitzung.php
 * @see         api/passwort-reset.php
 */

import { getJson, postJson, sendJson } from './api.js';

/**
 * Legt ein neues Konto an. Danach ist das Mitglied direkt angemeldet.
 *
 * @param {{vorname: string, nachname: string, email: string, passwort: string, passwortWiederholung: string}} daten
 * @returns {Promise<{id: number}>}
 * @throws {ApiError}  400 bei ungültigen Eingaben, 409 wenn die E-Mail-Adresse schon registriert ist
 */
export async function registrieren(daten) {
  return postJson('api/mitglieder.php', daten);
}

/**
 * Meldet ein Mitglied an.
 *
 * @param {string} email
 * @param {string} passwort
 * @returns {Promise<{angemeldet: true}>}
 * @throws {ApiError}  401 bei falschen Anmeldedaten, 429 nach zu vielen Fehlversuchen
 */
export async function anmelden(email, passwort) {
  return postJson('api/sitzung.php', { email, passwort });
}

/**
 * Meldet das aktuelle Mitglied ab.
 *
 * @returns {Promise<{angemeldet: false}>}
 * @throws {ApiError}
 */
export async function abmelden() {
  return sendJson('DELETE', 'api/sitzung.php');
}

/**
 * Lädt die Stammdaten des angemeldeten Mitglieds.
 *
 * profilbild ist die Adresse des eigenen Profilbilds relativ zur BASE_URL,
 * oder null, wenn es keins gibt. profilbildOeffentlich sagt, ob es bei den
 * eigenen Bewertungen erscheint.
 *
 * @returns {Promise<{vorname: string, nachname: string, email: string, profilbild: string|null, profilbildOeffentlich: boolean}>}
 * @throws {ApiError}  401, wenn niemand (mehr) angemeldet ist
 */
export async function angemeldetesMitgliedLaden() {
  return getJson('api/sitzung.php');
}

/**
 * Fordert einen Link zum Zurücksetzen des Passworts an.
 *
 * Die Antwort ist absichtlich dieselbe, egal ob es die Adresse gibt.
 * Nur mit 'demo_reset_link' => true in der Konfiguration und nur für
 * bestehende Konten enthält sie zusätzlich den Link, weil das Projekt keine
 * E-Mails verschickt.
 *
 * @param {string} email
 * @returns {Promise<{nachricht: string, demoLink?: string}>}
 * @throws {ApiError}  400 bei ungültiger Adresse, 429 bei zu vielen Anfragen
 */
export async function passwortResetAnfordern(email) {
  return postJson('api/passwort-reset.php', { email });
}

/**
 * Setzt ein neues Passwort mit den Angaben aus dem Link.
 *
 * @param {{selector: string, token: string, passwort: string, passwortWiederholung: string}} daten
 * @returns {Promise<{geaendert: true}>}
 * @throws {ApiError}  400 bei ungültigem Passwort oder abgelaufenem Link
 */
export async function passwortZuruecksetzen(daten) {
  return sendJson('PUT', 'api/passwort-reset.php', daten);
}
