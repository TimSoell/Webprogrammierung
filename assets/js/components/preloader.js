/**
 * @file        assets/js/components/preloader.js
 * @layer       2 – Komponente
 * @description Blendet den Preloader der Startseite nach dem Laden aus.
 *              Gezeigt wird er nur beim ersten Aufruf pro Browser-Sitzung;
 *              danach wird er sofort entfernt.
 * @see         partials/head.php
 * @see         assets/css/components/preloader.css
 */

/** Merker im sessionStorage: Der Preloader wurde in dieser Sitzung schon gezeigt. */
const MERKER = 'schwitzkasten:preloader-gezeigt';

/**
 * Prüft, ob der Preloader in dieser Browser-Sitzung schon lief, und merkt
 * sich beim ersten Mal, dass er lief. Bei blockiertem Speicher (privates
 * Fenster) wirft sessionStorage - dann gilt jeder Aufruf als der erste.
 *
 * @returns {boolean}
 */
function schonGezeigt() {
  try {
    if (window.sessionStorage.getItem(MERKER)) {
      return true;
    }
    window.sessionStorage.setItem(MERKER, '1');
  } catch {
    // Kein Speicher verfügbar - der Preloader läuft dann bei jedem Aufruf.
  }
  return false;
}

/**
 * Initialisiert den Preloader der Startseite. Auf allen anderen Seiten gibt
 * es kein Preloader-Element, dort passiert nichts.
 *
 * @returns {void}
 */
export function initPreloader() {
  const preloader = document.querySelector('[data-preloader]');
  const startzeit = performance.now();
  const mindestdauer = 1400;

  if (!preloader) {
    return;
  }

  if (schonGezeigt()) {
    preloader.remove();
    return;
  }

  const ausblenden = () => {
    const verbleibendeZeit = Math.max(0, mindestdauer - (performance.now() - startzeit));

    window.setTimeout(() => {
      preloader.classList.add('is-hidden');
      preloader.addEventListener('transitionend', () => {
        preloader.remove();
      }, { once: true });
    }, verbleibendeZeit);
  };

  if (document.readyState === 'complete') {
    ausblenden();
  } else {
    window.addEventListener('load', ausblenden, { once: true });
  }
}
