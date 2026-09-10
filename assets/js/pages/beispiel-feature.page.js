/**
 * @file        assets/js/pages/beispiel-feature.page.js
 * @layer       2 – Seitenskript
 * @description VORLAGE, Schicht 2 von 6. Enthält absichtlich keinen Code.
 *              Vorherige Schicht: beispiel-feature.php
 *              Nächste Schicht:   assets/js/services/beispiel-feature.js
 *
 * ============================================================================
 * WAS GEHÖRT IN DIESE SCHICHT
 * ============================================================================
 *
 *   Alles, was mit der SEITE zu tun hat:
 *
 *   - Elemente auf der Seite suchen ($ und $$ aus ../lib/dom.js)
 *   - Auf Ereignisse reagieren: click, submit, input, change
 *   - Den zuständigen Service aufrufen und auf das Ergebnis warten
 *   - Aus den erhaltenen Daten HTML bauen und einsetzen
 *   - Lade- und Fehlerzustände anzeigen
 *
 * ============================================================================
 * WAS HIER NICHTS ZU SUCHEN HAT
 * ============================================================================
 *
 *   - fetch()                     -> gehört in den Service (Schicht 3)
 *   - Feste URLs wie 'api/x.php'  -> gehört in den Service
 *   - element.style.farbe = ...   -> stattdessen eine CSS-Klasse setzen
 *   - Code, den auch andere Seiten brauchen -> gehört nach js/components/
 *
 *   Der Grund für das fetch-Verbot: wenn sich der Weg zu den Daten ändert
 *   (andere URL, plötzlich mit Login, erst Mock dann echte Datenbank),
 *   soll genau EINE Datei angefasst werden müssen - der Service.
 *
 * ============================================================================
 * TYPISCHER AUFBAU
 * ============================================================================
 *
 *   import { $ } from '../lib/dom.js';
 *   import { alleLaden } from '../services/beispiel-feature.js';
 *
 *   const liste = $('#kursliste');
 *
 *   // if (!liste) verhindert Fehler, falls das Skript auf einer Seite
 *   // landet, auf der es den Container nicht gibt.
 *   if (liste) {
 *     try {
 *       const kurse = await alleLaden();
 *       liste.innerHTML = kurse.map(zuHtml).join('');
 *     } catch (fehler) {
 *       liste.textContent = fehler.message;
 *     }
 *   }
 *
 *   ACHTUNG bei innerHTML: niemals ungeprüften Text aus der Datenbank
 *   direkt hineinschreiben. Entweder textContent benutzen oder den Text
 *   vorher escapen - sonst ist die Seite offen für Cross-Site-Scripting.
 *
 * @see         docs/ARCHITECTURE.md
 * @see         assets/js/pages/index.page.js
 */
