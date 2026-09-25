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
    <div class="wrap footer-meta">
      <div class="footer-app-boxes" aria-label="App-Links">
        <a class="store-badge badge-google" href="https://play.google.com/store/apps" target="_blank" rel="noopener noreferrer" aria-label="Google Play">
          <span class="badge-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3 2.8v18.4c0 .5.6.8 1 .4l9.8-9.2-9.8-9.2c-.4-.4-1-.1-1 .4zm12.8 10.2 2.8 2.6-7.9 4.5 5.1-7.1zm3.8-3.5 2.9 1.7c1 .6 1 1.9 0 2.5l-2.9 1.7-3.3-3.8 3.3-3.8zm-3.8-1.8-2.8 2.6-5.1-7.1 7.9 4.5z" fill="currentColor"/></svg>
          </span>
          <span class="badge-copy">
            <small>ERHÄLTLICH BEI</small>
            <strong>Google Play</strong>
          </span>
        </a>

        <a class="store-badge badge-apple" href="https://www.apple.com/de/app-store/" target="_blank" rel="noopener noreferrer" aria-label="Apple App Store">
          <span class="badge-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M15.5 12.3c0-2.2 1.8-3.2 1.9-3.3-1-1.5-2.6-1.7-3.2-1.7-1.4-.1-2.7.8-3.4.8-.7 0-1.8-.8-2.9-.8-1.5 0-2.9.9-3.7 2.2-1.6 2.8-.4 6.9 1.1 9.2.8 1.1 1.6 2.3 2.8 2.3 1.1 0 1.5-.7 2.8-.7 1.3 0 1.7.7 2.8.7 1.2 0 1.9-1.1 2.7-2.2.8-1.3 1.2-2.5 1.2-2.6-.1-.1-2.3-.9-3.9-3.2zm-2.6-6.6c.6-.7 1-1.7.9-2.7-.9.1-1.9.6-2.5 1.3-.6.7-1.1 1.7-1 2.7 1 .1 1.9-.5 2.6-1.3z" fill="currentColor"/></svg>
          </span>
          <span class="badge-copy">
            <small>Download on the</small>
            <strong>App Store</strong>
          </span>
        </a>
      </div>

      <div class="footer-social-boxes" aria-label="Soziale Medien">
        <a class="social-link" href="https://www.facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.2c0-.9.3-1.5 1.6-1.5H17V2.8c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H8v3.1h2.8v8h2.7z" fill="currentColor"/></svg>
        </a>
        <a class="social-link" href="https://www.instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm5 3.2A5.8 5.8 0 1 1 6.2 13 5.8 5.8 0 0 1 12 7.2zm0 2A3.8 3.8 0 1 0 15.8 13 3.8 3.8 0 0 0 12 9.2zm6.1-3.2a1.4 1.4 0 1 1-1.4-1.4 1.4 1.4 0 0 1 1.4 1.4z" fill="currentColor"/></svg>
        </a>
      </div>
    </div>

    <div class="wrap footer-inner">
      <img class="footer-logo" src="<?= e(BASE_URL) ?>assets/img/schwitzkasten-logo.png" alt="Schwitzkasten Athletic Club">
      <span>Venloer Straße 213 · 50823 Köln</span>
      <span>Mo–So · 24/7 für Mitglieder</span>
      <span>© <?= date('Y') ?> SCHWITZKASTEN Athletic Club</span>
    </div>
  </footer>
</body>
</html>
