<?php
/**
 * @file        partials/cookie-banner.php
 * @layer       1 – Seite (Baustein)
 * @description Die Leiste am unteren Rand, in der Besucher Google Analytics
 *              zustimmen oder es ablehnen. Wird von footer.php auf jeder
 *              Seite eingebunden.
 *
 *              Die Leiste ist zunächst versteckt (hidden). Ob sie erscheint,
 *              entscheidet assets/js/components/cookie-banner.js - nur wenn
 *              im Browser noch keine Entscheidung gespeichert ist.
 * @see         assets/js/components/cookie-banner.js
 * @see         assets/css/components/cookie-banner.css
 */

declare(strict_types=1);

if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Dieser Baustein kann nicht einzeln aufgerufen werden.');
}
?>
  <section class="cookie-banner" data-cookie-banner role="dialog" aria-labelledby="cookie-banner-titel" aria-describedby="cookie-banner-text" hidden>
    <div class="wrap cookie-banner-inner">
      <div class="cookie-banner-copy">
        <h2 class="cookie-banner-title" id="cookie-banner-titel">Cookies &amp; Statistik</h2>
        <p class="cookie-banner-text" id="cookie-banner-text">
          Wir nutzen Google Analytics, um zu verstehen, wie unsere Website genutzt wird.
          Das passiert nur mit deiner Zustimmung. Du kannst sie jederzeit im Footer unter
          „Cookie-Einstellungen“ widerrufen. Mehr dazu in der
          <a href="<?= e(BASE_URL) ?>datenschutz.php">Datenschutzerklärung</a>.
        </p>
      </div>
      <div class="cookie-banner-actions">
        <button type="button" class="button button--ghost" data-cookie-ablehnen>Nur notwendige</button>
        <button type="button" class="button" data-cookie-zustimmen>Alle akzeptieren</button>
      </div>
    </div>
  </section>
