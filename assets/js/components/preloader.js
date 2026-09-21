/**
 * @file        assets/js/components/preloader.js
 * @layer       2 – Komponente
 * @description Blendet den globalen Preloader nach dem Laden der Seite aus.
 * @see         partials/head.php
 * @see         assets/css/components/preloader.css
 */

/**
 * Initialisiert den globalen Seiten-Preloader.
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
