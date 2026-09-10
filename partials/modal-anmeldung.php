<?php
/**
 * @file        partials/modal-anmeldung.php
 * @layer       1 – Seite (Baustein)
 * @description Das Overlay-Fenster für die Interessenten-Anmeldung.
 *
 *              Liegt als eigener Baustein vor, weil es später auch von
 *              anderen Seiten aus geöffnet werden soll (z. B. von einer
 *              künftigen Kursseite). Aktuell bindet es nur index.php ein.
 *
 *              Das Verhalten steckt in zwei getrennten Dateien:
 *                Öffnen/Schließen -> assets/js/components/modal.js
 *                Formular absenden -> assets/js/pages/index.page.js
 * @see         assets/css/components/modal.css
 */

declare(strict_types=1);

if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Dieser Baustein kann nicht einzeln aufgerufen werden.');
}
?>
  <div class="modal" id="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-card">
      <button class="modal-close" id="close-modal" type="button" aria-label="Fenster schließen">×</button>

      <h2 id="modal-title">Dein Platz.</h2>
      <p>Trag dich ein und wir melden uns mit allen Infos zum Eröffnungspreis bei dir.</p>

      <form class="signup-form" id="signup-form">
        <label for="name">Dein Name</label>
        <input id="name" name="name" required autocomplete="name" placeholder="Vor- und Nachname">

        <label for="email">E-Mail</label>
        <input id="email" name="email" type="email" required autocomplete="email" placeholder="du@beispiel.de">

        <button class="button" type="submit">Interesse anmelden</button>
      </form>

      <div class="form-success" id="signup-success" role="status">Danke! Wir melden uns bald bei dir.</div>
    </div>
  </div>
