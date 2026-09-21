/**
 * @file        assets/js/pages/index.page.js
 * @layer       2 – Seitenskript
 * @description Verhalten, das es NUR auf der Startseite gibt: das
 *              scrollgesteuerte Video und das Absenden des
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

initScrollVideo();

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
