<?php
/**
 * @file        programme/strength.php
 * @layer       1 – Seite
 * @description Detailseite zum Programm "Strength" (Krafttraining).
 *
 *              Die drei Programmseiten sind bewusst eigenständige Dateien und
 *              keine gemeinsame Vorlage mit Parametern. Grund: sie unterscheiden
 *              sich nur im Text, und getrennte Dateien lassen sich im Team
 *              gleichzeitig bearbeiten, ohne dass es Konflikte gibt.
 *
 *              Wenn daraus später echte Kursdaten aus der Datenbank werden,
 *              wird das hier zu EINER Seite mit Parameter - siehe
 *              docs/decisions/ADR-0002-schichtenarchitektur.md.
 * @see         assets/css/components/program-page.css
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$pageTitle       = 'Strength — BASELINE';
$pageDescription = 'Krafttraining bei BASELINE: saubere Technik, progressive Gewichte und Coaches, die dich voranbringen.';
$activeNav       = 'programme';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <section class="program-hero program-hero--strength">
    <div class="wrap">
      <a class="back" href="<?= e(BASE_URL) ?>index.php#programme">← Zurück zu den Programmen</a>
      <div class="program-hero-content">
        <p class="eyebrow">Programm 01 · Krafttraining</p>
        <h1 class="program-hero-title">Strength</h1>
        <p class="lead">Baue echte Kraft auf. Mit klaren Grundlagen, progressiven Gewichten und Coaches, die dich technisch sauber voranbringen.</p>
      </div>
    </div>
  </section>

  <main class="program-main">
    <div class="wrap program-layout">
      <section>
        <h2 class="program-heading">Deine<br>Basis.</h2>
        <p class="copy">Strength ist dein strukturierter Weg zu mehr Muskelkraft und Selbstvertrauen. Du trainierst die großen Bewegungsmuster und lernst, deinen Körper kontrolliert zu belasten.</p>

        <div class="attributes">
          <div class="attribute"><strong>Fokus</strong><span>Kraft, Technik und Muskelaufbau</span></div>
          <div class="attribute"><strong>Level</strong><span>Einsteiger bis Fortgeschrittene</span></div>
          <div class="attribute"><strong>Format</strong><span>Freies Training und Coaching</span></div>
        </div>
      </section>

      <aside class="schedule">
        <h3>Bereit für mehr?</h3>
        <p>Starte mit einem kostenlosen Kennenlerntermin und finde heraus, welches Strength-Programm zu dir passt.</p>
        <a class="button button--dark" href="<?= e(BASE_URL) ?>index.php#mitgliedschaft">Probetraining sichern</a>
      </aside>
    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
