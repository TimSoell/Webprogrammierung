/**
 * @file        assets/js/pages/move.page.js
 * @layer       2 – Seitenskript
 * @description Startet die Programm-Details und den Kurskalender für Move.
 *              Erklärung zum Aufbau steht in strength.page.js.
 * @see         assets/js/components/programm-details.js
 * @see         assets/js/components/kurskalender.js
 * @see         programme/move.php
 */

import { programmDetailsAufbauen } from '../components/programm-details.js';
import { kurskalenderAufbauen } from '../components/kurskalender.js';

programmDetailsAufbauen('move');
kurskalenderAufbauen('move');
