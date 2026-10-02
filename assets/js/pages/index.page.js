/**
 * @file        assets/js/pages/index.page.js
 * @layer       2 – Seitenskript
 * @description Verhalten, das es NUR auf der Startseite gibt: das
 *              Hero-Video, das beim Laden einmal abläuft, das
 *              Auslastungsdiagramm, die Bewertungen und das Fenster
 *              "Freund einladen".
 *
 *              Eingebunden wird die Datei über die Variable $pageScript
 *              in index.php - nicht über main.js.
 * @see         index.php
 */

import { $ } from '../lib/dom.js';
import { diagrammZeichnen } from '../components/auslastung-diagramm.js';
import { initBewertungen } from '../components/bewertungen.js';
import { initFreundeWerben } from '../components/freunde-werben.js';
import { auslastungLaden } from '../services/auslastung.js';

initBewertungen();
initFreundeWerben();

// --- Hero-Video --------------------------------------------------------------

const heroVideo = $('[data-hero-video] video');

// Bei "weniger Bewegung" bleibt das erste Einzelbild stehen.
if (heroVideo && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
  const preloader = $('[data-preloader]');

  // main.js läuft vor diesem Skript. Steht der Preloader noch, würde das
  // Video hinter ihm ablaufen - also erst starten, wenn er ausgeblendet ist.
  if (preloader) {
    preloader.addEventListener('transitionend', () => heroVideo.play(), { once: true });
  } else {
    heroVideo.play();
  }
}

// --- Auslastungsdiagramm -----------------------------------------------------

/** Abstand zwischen zwei Abfragen der Auslastung, in Millisekunden. */
const AKTUALISIEREN_ALLE = 20000;

const diagramm = $('#auslastung-diagramm');
const jetztKasten = $('#auslastung-jetzt');
const meldung = $('#auslastung-meldung');

if (diagramm && jetztKasten && meldung) {
  /**
   * Holt die Auslastung und zeichnet sie neu.
   *
   * @returns {Promise<void>}
   */
  const auslastungAnzeigen = async () => {
    try {
      const daten = await auslastungLaden();

      diagrammZeichnen(diagramm, daten);

      $('#auslastung-personen').textContent = String(daten.jetzt.personen);
      $('#auslastung-prozent').textContent = `von ${daten.kapazitaet} Plätzen · ${daten.jetzt.prozent} %`;
      $('#auslastung-stufe').textContent = daten.jetzt.stufe;

      // Die Stufe steckt zusätzlich in einem data-Attribut, damit das CSS
      // die Farbe wählen kann, ohne den Text auszuwerten.
      $('#auslastung-stufe').dataset.stufe = daten.jetzt.stufe;

      jetztKasten.hidden = false;
      meldung.textContent = '';
    } catch (fehler) {
      // Kein Grund, die Seite zu stören: das Diagramm ist Beiwerk. Eine
      // Zeile Text reicht, der Rest der Startseite bleibt benutzbar.
      meldung.textContent = 'Die Auslastung ist gerade nicht abrufbar.';
    }
  };

  auslastungAnzeigen();

  // Regelmäßig nachladen, damit ein Check-in aus einem anderen Tab hier
  // sichtbar wird - darum geht es bei der Vorführung.
  const uhr = window.setInterval(auslastungAnzeigen, AKTUALISIEREN_ALLE);

  // Im Hintergrundtab bringt das Nachladen nichts und kostet nur Anfragen.
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      auslastungAnzeigen();
    }
  });

  window.addEventListener('pagehide', () => window.clearInterval(uhr));
}
