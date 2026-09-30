<?php
/**
 * @file        partials/kurskalender.php
 * @layer       1 – Seite (Baustein)
 * @description Das Fenster mit den Kursterminen, auf allen drei Kursseiten
 *              gleich. Geöffnet wird es über den Button
 *              #kurskalender-oeffnen in der jeweiligen Seite.
 *
 *              Nur das Gerüst - die Termine trägt
 *              assets/js/components/kurskalender.js beim Öffnen ein.
 *              Öffnen und Schließen übernimmt modal.js, deshalb dieselben
 *              Klassen wie beim Anmeldefenster.
 * @see         assets/js/components/kurskalender.js
 * @see         assets/css/components/kalender.css
 */

declare(strict_types=1);

if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Dieser Baustein kann nicht einzeln aufgerufen werden.');
}
?>
  <div class="modal" id="kurskalender" role="dialog" aria-modal="true" aria-labelledby="kurskalender-titel">
    <div class="modal-card modal-card--kalender">
      <button class="modal-close" id="kurskalender-schliessen" type="button" aria-label="Fenster schließen">×</button>

      <h2 id="kurskalender-titel">Termine.</h2>
      <p>Fahr über einen Tag, um zu sehen, was ansteht. Klick ihn an und wähl einen Termin zum Buchen. Mit den Pfeilen oder per Wischen geht es in die nächsten Monate.</p>

      <!-- Kein aria-live hier: Angesagt wird nur die Tagesansicht darin,
           nicht bei jedem Neuzeichnen alle Kreise des Monats. -->
      <div class="kalender" id="kurskalender-liste"></div>
    </div>
  </div>
