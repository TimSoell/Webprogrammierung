/**
 * @file        assets/js/pages/fight.page.js
 * @layer       2 – Seitenskript
 * @description Startet die Programm-Details und den Kurskalender für Fight.
 *              Erklärung zum Aufbau steht in strength.page.js.
 * @see         assets/js/components/programm-details.js
 * @see         assets/js/components/kurskalender.js
 * @see         programme/fight.php
 */

import { programmDetailsAufbauen } from '../components/programm-details.js';
import { kurskalenderAufbauen } from '../components/kurskalender.js';

programmDetailsAufbauen('fight');
kurskalenderAufbauen('fight');
