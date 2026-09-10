/**
 * @file        assets/js/components/nav.js
 * @layer       2 – Komponente
 * @description Auf- und Zuklappen der Navigation auf schmalen Bildschirmen.
 *
 *              Die Komponente ändert ausschließlich CSS-Klassen und
 *              ARIA-Attribute. Wie das Menü dann aussieht, steht komplett in
 *              assets/css/03-layout.css. Diese Trennung ist Absicht: das
 *              Aussehen im CSS, das Verhalten im JavaScript.
 * @see         assets/css/03-layout.css
 * @see         partials/header.php
 */

import { $, $$ } from '../lib/dom.js';

/**
 * Aktiviert den Menü-Button in der Kopfzeile.
 *
 * Tut nichts, wenn die Kopfzeile auf der aktuellen Seite fehlt - so kann
 * die Funktion bedenkenlos auf jeder Seite aufgerufen werden.
 *
 * @returns {void}
 */
export function initNav() {
  const menuButton = $('#menu-button');
  const navLinks = $('#nav-links');

  if (!menuButton || !navLinks) {
    return;
  }

  menuButton.addEventListener('click', () => {
    const isOpen = navLinks.classList.toggle('open');

    // aria-expanded teilt Screenreadern mit, ob das Menü offen ist.
    menuButton.setAttribute('aria-expanded', String(isOpen));
    menuButton.setAttribute('aria-label', isOpen ? 'Menü schließen' : 'Menü öffnen');
    menuButton.textContent = isOpen ? 'CLOSE' : 'MENU';
  });

  // Nach einem Klick auf einen Link soll das Menü wieder zugehen.
  $$('a', navLinks).forEach((link) => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('open');
      menuButton.setAttribute('aria-expanded', 'false');
      menuButton.setAttribute('aria-label', 'Menü öffnen');
      menuButton.textContent = 'MENU';
    });
  });
}
