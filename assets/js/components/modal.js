/**
 * @file        assets/js/components/modal.js
 * @layer       2 – Komponente
 * @description Öffnen und Schließen des Overlay-Fensters.
 *
 *              Geschlossen wird auf drei Wegen, weil Nutzerinnen und Nutzer
 *              alle drei erwarten: über das X, über einen Klick neben das
 *              Fenster und über die Escape-Taste.
 *
 *              Die Komponente kennt den INHALT des Fensters nicht. Was im
 *              Formular passiert, steht in assets/js/pages/index.page.js.
 *              Dadurch lässt sich dieses Modal für jedes andere Fenster
 *              wiederverwenden.
 * @see         assets/css/components/modal.css
 * @see         partials/modal-anmeldung.php
 */

import { $ } from '../lib/dom.js';

/**
 * Aktiviert ein Overlay-Fenster.
 *
 * @param {object}  [options]
 * @param {string}  [options.modalId]    id des Fensters
 * @param {string}  [options.openId]     id des Buttons, der es öffnet
 * @param {string}  [options.closeId]    id des Buttons, der es schließt
 * @param {string}  [options.focusId]    id des Feldes, das beim Öffnen den Fokus bekommt
 * @returns {{open: () => void, close: () => void}|undefined}
 *          Beide Wege bleiben nutzbar: der Button oben und diese Rückgabe.
 *          Gebraucht wird sie, wenn sich das Fenster nach einer geglückten
 *          Aktion selbst schließen soll - siehe mitgliedschaft.page.js.
 *          undefined, wenn es das Fenster oder den Button nicht gibt.
 */
export function initModal({
  modalId = 'modal',
  openId = 'open-modal',
  closeId = 'close-modal',
  focusId = '',
} = {}) {
  const modal = $(`#${modalId}`);
  const openButton = $(`#${openId}`);

  if (!modal || !openButton) {
    return;
  }

  const closeButton = $(`#${closeId}`);

  const open = () => {
    modal.classList.add('open');

    // Fokus ins erste Feld, damit man sofort tippen kann - und damit
    // Tastaturnutzende nicht hinter dem Overlay hängen bleiben.
    const focusTarget = focusId ? $(`#${focusId}`) : null;
    if (focusTarget) {
      focusTarget.focus();
    }
  };

  const close = () => {
    modal.classList.remove('open');
    openButton.focus();
  };

  openButton.addEventListener('click', open);
  closeButton?.addEventListener('click', close);

  // Klick auf den abgedunkelten Hintergrund schließt das Fenster.
  // Die Prüfung auf event.target verhindert, dass auch ein Klick
  // INNERHALB der Karte als Klick daneben gewertet wird.
  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      close();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('open')) {
      close();
    }
  });

  return { open, close };
}
