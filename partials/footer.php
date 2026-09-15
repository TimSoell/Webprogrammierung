<?php
/**
 * @file        partials/footer.php
 * @layer       1 – Seite (Baustein)
 * @description Die Fußzeile plus der Abschluss des HTML-Dokuments.
 *
 *              ACHTUNG: Diese Datei schließt </body> und </html>. Sie gehört
 *              deshalb immer als LETZTES require in eine Seite.
 *
 *              Adresse und Öffnungszeiten stehen nur hier. Wenn das Studio
 *              umzieht, ist das genau eine Änderung an einer Stelle.
 * @see         assets/css/03-layout.css
 */

declare(strict_types=1);

if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Dieser Baustein kann nicht einzeln aufgerufen werden.');
}
?>
  <footer>
    <div class="wrap footer-inner">
      <img class="footer-logo" src="<?= e(BASE_URL) ?>assets/img/schwitzkasten-logo.png" alt="Schwitzkasten Athletic Club">
      <span>Venloer Straße 213 · 50823 Köln</span>
      <span>Mo–So · 24/7 für Mitglieder</span>
      <span>© <?= date('Y') ?> SCHWITZKASTEN Athletic Club</span>
    </div>
  </footer>
</body>
</html>
