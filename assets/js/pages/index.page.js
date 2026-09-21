/**
 * @file        assets/js/pages/index.page.js
 * @layer       2 – Seitenskript
 * @description Verhalten, das es NUR auf der Startseite gibt: das
 *              scrollgesteuerte Hero-Video und das Absenden des
 *              Interessenten-Formulars im Anmeldefenster.
 *
 *              Eingebunden wird die Datei über die Variable $pageScript
 *              in index.php - nicht über main.js.
 *
 *              AKTUELLER STAND: Das Formular wird noch nicht verschickt,
 *              sondern zeigt nur die Erfolgsmeldung an. Der Weg zur echten
 *              Speicherung ist unten im Kommentar beschrieben.
 * @see         index.php
 * @see         partials/modal-anmeldung.php
 * @see         assets/js/components/scroll-video.js
 */

import { $ } from '../lib/dom.js';
import { initScrollVideo } from '../components/scroll-video.js';
import { diagrammZeichnen } from '../components/auslastung-diagramm.js';
import { auslastungLaden } from '../services/auslastung.js';

initScrollVideo('[data-scroll-video]');

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

// --- Interessenten-Formular --------------------------------------------------

const form = $('#signup-form');
const success = $('#signup-success');

if (form && success) {
  form.addEventListener('submit', (event) => {
    // Verhindert, dass der Browser die Seite neu lädt und die Eingaben
    // an die URL hängt. Wir wollen das Absenden selbst steuern.
    event.preventDefault();

    // -------------------------------------------------------------------
    // SPÄTER: Statt direkt die Erfolgsmeldung zu zeigen, wird hier der
    // zuständige Service aufgerufen - zum Beispiel:
    //
    //     import { anmeldungSenden } from '../services/anmeldung.js';
    //     await anmeldungSenden({ name: form.name.value, email: form.email.value });
    //
    // Das Seitenskript ruft NIE selbst fetch() auf. Warum, steht in
    // docs/ARCHITECTURE.md unter "Schicht 3".
    // -------------------------------------------------------------------

    form.hidden = true;
    success.classList.add('show');
  });
}
