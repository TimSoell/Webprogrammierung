/**
 * @file        assets/js/components/auth-formular.js
 * @layer       2 – Komponente
 * @description Was alle Formulare rund um den Login gemeinsam haben:
 *              Meldungen anzeigen und den Absende-Button sperren, solange
 *              die Anfrage läuft.
 *
 *              Die Meldung steht in einem <p class="auth-message">, das immer
 *              im HTML steht. Ist es leer, blendet es das CSS aus - deshalb
 *              genügt hier textContent, ohne hidden oder style.
 * @see         assets/css/components/auth.css
 * @see         anmelden.php
 */

import { $ } from '../lib/dom.js';

/**
 * Zeigt eine Meldung an oder leert sie.
 *
 * textContent statt innerHTML: Die Meldung kommt teils vom Server und
 * darf nie als HTML ausgeführt werden.
 *
 * @param {Element} element        Das <p class="auth-message">
 * @param {string}  text           Meldung, '' blendet sie aus
 * @param {boolean} [erfolg=false] true = grün statt rot
 * @returns {void}
 */
export function meldungZeigen(element, text, erfolg = false) {
  element.textContent = text;
  element.classList.toggle('auth-message--success', erfolg);
}

/**
 * Führt eine Anfrage für ein Formular aus.
 *
 * Leert vorher die Meldung, sperrt den Absende-Button gegen Doppelklicks
 * und zeigt einen Fehler (ApiError) direkt im Formular an.
 *
 * @param {HTMLFormElement}     form     Das Formular
 * @param {Element}             meldung  Das <p class="auth-message"> darin
 * @param {() => Promise<void>} aktion   Ruft den Service auf und reagiert auf Erfolg
 * @returns {Promise<void>}
 */
export async function formularAbsenden(form, meldung, aktion) {
  const button = $('button[type="submit"]', form);

  meldungZeigen(meldung, '');
  button.disabled = true;

  try {
    await aktion();
  } catch (fehler) {
    meldungZeigen(meldung, fehler.message);
  } finally {
    button.disabled = false;
  }
}
