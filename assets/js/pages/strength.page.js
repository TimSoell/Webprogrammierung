/**
 * @file        assets/js/pages/strength.page.js
 * @layer       2 – Seitenskript
 * @description Startet die Programm-Details für Strength.
 *              Das Verhalten steckt in der Komponente, hier steht nur, um
 *              welches Programm es geht - move.page.js und fight.page.js
 *              sehen genauso aus.
 * @see         assets/js/components/programm-details.js
 * @see         programme/strength.php
 */

import { programmDetailsAufbauen } from '../components/programm-details.js';

programmDetailsAufbauen('strength');
