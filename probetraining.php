<?php
/**
 * @file        probetraining.php
 * @layer       1 – Seite
 * @description Probetraining buchen: Coach wählen, Stufe angeben, einen der
 *              freien Termine des Coaches aussuchen, buchen.
 *
 *              Ansehen kann die Seite jeder. Buchen nur, wer angemeldet ist -
 *              sonst steht an Stelle des Buttons ein Link zur Anmeldung.
 *              Gebuchte Probetrainings stehen danach in mein-konto.php.
 *
 *              Nur das Gerüst - Coaches und Termine trägt
 *              assets/js/pages/probetraining.page.js ein.
 * @see         assets/js/pages/probetraining.page.js
 * @see         assets/css/components/probetraining.css
 * @see         docs/features/terminkalender.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'Probetraining — SCHWITZKASTEN';
$pageDescription = 'Kostenloses Probetraining bei SCHWITZKASTEN: Coach wählen, Termin aussuchen, 60 Minuten ausprobieren.';
$pageScript      = 'probetraining.page.js';
$activeNav       = 'probetraining';

// Entscheidet nur, ob unten der Buchen-Button oder der Link zur Anmeldung
// steht. isLoggedIn() liest die Session, nicht die Datenbank.
$angemeldet = Auth::instanz()->isLoggedIn();

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="probetraining" id="probetraining-seite">
    <div class="wrap">

      <div class="probetraining-kopf">
        <p class="eyebrow">Kostenlos · 60 Minuten · ein Coach nur für dich</p>
        <h1 class="display probetraining-titel">Dein<br>Probetraining.</h1>
        <p class="probetraining-lead">Such dir aus, mit wem du trainieren willst, sag uns, wo du stehst, und nimm einen freien Termin.</p>
      </div>

      <div class="probetraining-schritt">
        <h2 class="probetraining-schritt-titel"><span>1</span> Coach</h2>
        <div class="coach-wahl" id="coach-wahl" role="group" aria-label="Coach wählen">Coaches werden geladen …</div>
      </div>

      <div class="probetraining-schritt">
        <h2 class="probetraining-schritt-titel"><span>2</span> Deine Stufe</h2>
        <div id="stufe-wahl"></div>
      </div>

      <div class="probetraining-schritt">
        <h2 class="probetraining-schritt-titel"><span>3</span> Termin</h2>
        <div class="kalender" id="probetermine" aria-live="polite">
          <p class="kalender-leer">Wähle zuerst einen Coach - dann erscheinen seine freien Termine.</p>
        </div>
      </div>

      <div class="probetraining-abschluss">
        <p class="probetraining-zusammenfassung" id="pt-zusammenfassung">Noch nicht alles gewählt.</p>

<?php if ($angemeldet): ?>
        <button class="button" id="pt-buchen" type="button">Buchen</button>
<?php else: ?>
        <p class="probetraining-hinweis">
          <a href="<?= e(BASE_URL) ?>anmelden.php">Melde dich an</a>, um dein Probetraining zu buchen.
          Aussuchen kannst du schon jetzt.
        </p>
<?php endif; ?>

        <p class="kalender-meldung" id="pt-meldung" role="status"></p>
      </div>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
