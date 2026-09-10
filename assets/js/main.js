/**
 * @file        assets/js/main.js
 * @layer       Einstiegspunkt
 * @description Wird von partials/head.php auf JEDER Seite geladen und startet
 *              die Komponenten, die es überall gibt.
 *
 *              Hier gehört nur hinein, was auf allen Seiten gebraucht wird.
 *              Alles, was nur eine bestimmte Seite betrifft, kommt in eine
 *              eigene Datei unter pages/ - siehe assets/js/README.md.
 *
 *              Das Skript wird als type="module" eingebunden. Module werden
 *              automatisch erst nach dem Aufbau der Seite ausgeführt, deshalb
 *              ist hier kein Warten auf DOMContentLoaded nötig.
 * @see         assets/js/README.md
 */

import { initNav } from './components/nav.js';
import { initModal } from './components/modal.js';

initNav();

// Das Anmeldefenster gibt es aktuell nur auf der Startseite. initModal()
// beendet sich von selbst, wenn die Elemente auf der Seite fehlen - deshalb
// kann der Aufruf hier trotzdem für alle Seiten stehen.
initModal({
  modalId: 'modal',
  openId: 'open-modal',
  closeId: 'close-modal',
  focusId: 'name',
});
