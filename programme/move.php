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
$activeNav       = 'programme';

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

        <div class="attributes">
          <div class="attribute"><strong>Fokus</strong><span>Mobilität, Balance und Ausdauer</span></div>
          <div class="attribute"><strong>Level</strong><span>Für jedes Fitnesslevel</span></div>
          <div class="attribute"><strong>Format</strong><span>Small Group und freie Workouts</span></div>
        </div>
      </section>

      <aside class="schedule">
        <h3>In Bewegung kommen?</h3>
        <p>Starte mit einem kostenlosen Kennenlerntermin und entdecke deine neue Bewegungsroutine.</p>
        <a class="button button--dark" href="<?= e(BASE_URL) ?>index.php#mitgliedschaft">Probetraining sichern</a>
      </aside>
    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
