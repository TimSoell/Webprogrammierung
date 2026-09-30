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
import { initPreloader } from './components/preloader.js';
import { initCookieBanner } from './components/cookie-banner.js';

initNav();
initPreloader();
initCookieBanner();
