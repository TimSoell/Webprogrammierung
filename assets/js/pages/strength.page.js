/**
 * @file        assets/js/pages/strength.page.js
 * @layer       2 – Seitenskript
 * @description Startet die Programm-Details und den Kurskalender für Strength.
 *              Das Verhalten steckt in der Komponente, hier steht nur, um
 *              welches Programm es geht - move.page.js und fight.page.js
 *              sehen genauso aus.
 * @see         assets/js/components/programm-details.js
 * @see         assets/js/components/kurskalender.js
 * @see         programme/strength.php
 */

import { programmDetailsAufbauen } from '../components/programm-details.js';
import { kurskalenderAufbauen } from '../components/kurskalender.js';

programmDetailsAufbauen('strength');
kurskalenderAufbauen('strength');
