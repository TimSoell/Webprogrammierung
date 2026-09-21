<?php
/**
 * @file        programme/move.php
 * @layer       1 – Seite
 * @description Detailseite zum Programm "Move" (Beweglichkeit).
 *              Aufbau identisch zu strength.php - dort steht die ausführliche
 *              Erklärung zur Struktur.
 * @see         programme/strength.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$pageTitle       = 'Move — SCHWITZKASTEN';
$pageDescription = 'Funktionelles Training bei SCHWITZKASTEN: Mobilität, Stabilität und Ausdauer für mehr Energie im Alltag.';
$pageScript      = 'move.page.js';
$activeNav       = 'programme';

// Entscheidet nur, ob unten der Merken-Button oder der Hinweis zur Anmeldung
// erscheint. isLoggedIn() liest die Session, nicht die Datenbank.
$angemeldet = Auth::instanz()->isLoggedIn();

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <section class="program-hero program-hero--move">
    <div class="wrap">
      <a class="back" href="<?= e(BASE_URL) ?>index.php#programme">← Zurück zu den Programmen</a>
      <div class="program-hero-content">
        <p class="eyebrow">Programm 02 · Beweglichkeit</p>
        <h1 class="program-hero-title">Move</h1>
        <p class="lead">Bewege dich freier, stabiler und mit mehr Energie. Funktionelles Training, das dich im Studio und im Alltag leichter macht.</p>
      </div>
    </div>
  </section>

  <main class="program-main">
    <div class="wrap program-layout">
      <section>
        <h2 class="program-heading">Bewegung<br>die bleibt.</h2>
        <p class="copy">Move verbindet Mobilität, Stabilität und Ausdauer in fließenden Workouts. Du verbesserst deine Bewegungsqualität und entwickelst ein gutes Gefühl für deinen Körper.</p>

        <!-- Füllt assets/js/pages/move.page.js aus der Datenbank. -->
        <div class="merkmale" id="merkmale">Merkmale werden geladen …</div>
        <div class="merken" id="merken" data-angemeldet="<?= $angemeldet ? '1' : '' ?>"></div>

        <button class="button kurskalender-aufruf" id="kurskalender-oeffnen" type="button">Termin buchen</button>
      </section>

      <aside class="program-aside">
        <div class="schedule">
          <h3>In Bewegung kommen?</h3>
          <p>Starte mit einem kostenlosen Kennenlerntermin und entdecke deine neue Bewegungsroutine.</p>
          <a class="button button--dark" href="<?= e(BASE_URL) ?>probetraining.php">Probetraining sichern</a>
        </div>

        <div class="coaches">
          <h3 class="coaches-titel">Deine Coaches</h3>
          <ul class="coach-liste" id="coaches"></ul>
        </div>
      </aside>
    </div>
  </main>

<?php
require ROOT_PATH . '/partials/kurskalender.php';
require ROOT_PATH . '/partials/footer.php';
