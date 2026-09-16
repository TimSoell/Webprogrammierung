/**
 * @file        assets/js/components/passwort-kriterien.js
 * @layer       2 – Komponente
 * @description Hakt beim Tippen ab, welche Passwort-Anforderungen schon
 *              erfüllt sind, und prüft vor dem Absenden Passwort und
 *              Wiederholung. Gebraucht bei der Registrierung und beim
 *              Zurücksetzen des Passworts.
 *
 *              Dieselben Regeln prüft der Server in src/Auth.php noch einmal.
 *              Die hier sind Komfort, die dort sind der Schutz.
 * @see         partials/passwort-kriterien.php
 * @see         src/Auth.php
 */

import { $$ } from '../lib/dom.js';

/**
 * Die Anforderungen aus Issue #8.
 * Schlüssel = data-kriterium in partials/passwort-kriterien.php.
 *
 * Sonderzeichen = alles außer Buchstaben und Ziffern. \p{L} zählt auch
 * Umlaute als Buchstaben - genauso wie auf dem Server.
 */
const KRITERIEN = {
  laenge: (passwort) => [...passwort].length >= 8,
  gross: (passwort) => /[A-Z]/.test(passwort),
  klein: (passwort) => /[a-z]/.test(passwort),
  zahl: (passwort) => /[0-9]/.test(passwort),
  sonderzeichen: (passwort) => /[^\p{L}\p{N}]/u.test(passwort),
};

/**
 * Verbindet ein Passwortfeld mit seiner Kriterienliste. Erfüllte
 * Einträge bekommen die Klasse .ok.
 *
 * @param {HTMLInputElement} feld   Das Passwortfeld
 * @param {Element}          liste  Das <ul> aus partials/passwort-kriterien.php
 * @returns {void}
 */
export function initPasswortKriterien(feld, liste) {
  const eintraege = $$('[data-kriterium]', liste);

  feld.addEventListener('input', () => {
    // trim(), weil der Server das Passwort vor dem Speichern genauso kürzt.
    const passwort = feld.value.trim();

    eintraege.forEach((eintrag) => {
      eintrag.classList.toggle('ok', KRITERIEN[eintrag.dataset.kriterium](passwort));
    });
  });
}

/**
 * Prüft Passwort und Wiederholung vor dem Absenden.
 *
 * @param {string} passwort
 * @param {string} wiederholung
 * @returns {string}  Meldung für das Formular, oder '' wenn alles passt
 */
export function passwortPruefen(passwort, wiederholung) {
  const alleErfuellt = Object.values(KRITERIEN).every((erfuellt) => erfuellt(passwort.trim()));

  if (!alleErfuellt) {
    return 'Dein Passwort erfüllt noch nicht alle Anforderungen.';
  }

  if (passwort !== wiederholung) {
    return 'Die beiden Passwörter stimmen nicht überein.';
  }

  return '';
}
