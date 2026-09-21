<?php
/**
 * @file        mein-konto.php
 * @layer       1 – Seite
 * @description Startpunkt des Mitgliederbereichs. Zeigt die Stammdaten und
 *              bietet das Abmelden an.
 *
 *              Nur für angemeldete Mitglieder: Wer nicht angemeldet ist, wird
 *              von Auth::nurFuerMitglieder() zur Anmeldeseite geschickt,
 *              bevor irgendetwas ausgegeben wird.
 *
 *              Die Daten selbst stehen NICHT im HTML, sondern kommen über
 *              assets/js/pages/mein-konto.page.js aus api/sitzung.php -
 *              der gewohnte Weg durch die Schichten.
 * @see         assets/js/pages/mein-konto.page.js
 * @see         docs/features/mitglieder-login.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

Auth::nurFuerMitglieder();

$pageTitle  = 'Mein Konto — SCHWITZKASTEN';
$pageScript = 'mein-konto.page.js';
$activeNav  = 'konto';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="auth" id="konto-seite" data-abgemeldet="<?= e(BASE_URL) ?>index.php" data-login="<?= e(BASE_URL) ?>anmelden.php">
    <div class="wrap auth-inner">

      <div class="auth-intro">
        <p class="eyebrow">Mitgliederbereich</p>
        <h1 class="display auth-title">Mein<br>Konto.</h1>
      </div>

      <div class="auth-cards">
        <div class="auth-card">
          <h2 class="auth-heading">Stammdaten</h2>

          <dl class="auth-data">
            <div>
              <dt>Vorname</dt>
              <dd id="konto-vorname">…</dd>
            </div>
            <div>
              <dt>Nachname</dt>
              <dd id="konto-nachname">…</dd>
            </div>
            <div>
              <dt>E-Mail-Adresse</dt>
              <dd id="konto-email">…</dd>
            </div>
          </dl>

          <p class="auth-message" id="konto-meldung" role="alert"></p>

          <button class="button button--ghost" id="abmelden" type="button">Abmelden</button>
        </div>

        <div class="auth-card">
          <h2 class="auth-heading">Meine Auswahl</h2>
          <p class="auth-hint">Was du dir auf den Programmseiten gemerkt hast.</p>

          <!-- Füllt assets/js/pages/mein-konto.page.js aus api/auswahl.php. -->
          <ul class="auswahl-liste" id="auswahl-liste"></ul>

          <p class="auth-message" id="auswahl-meldung" role="alert"></p>
        </div>
      </div>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
