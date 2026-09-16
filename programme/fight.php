<?php
/**
 * @file        programme/fight.php
 * @layer       1 – Seite
 * @description Detailseite zum Programm "Fight" (Kampfsport).
 *              Aufbau identisch zu strength.php - dort steht die ausführliche
 *              Erklärung zur Struktur.
 * @see         programme/strength.php
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$pageTitle       = 'Fight — SCHWITZKASTEN';
$pageDescription = 'Kampfsport bei SCHWITZKASTEN: Boxtechnik, Intervalltraining und Teamenergie für Kondition und einen klaren Kopf.';
$activeNav       = 'programme';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <section class="program-hero program-hero--fight">
    <div class="wrap">
      <a class="back" href="<?= e(BASE_URL) ?>index.php#programme">← Zurück zu den Programmen</a>
      <div class="program-hero-content">
        <p class="eyebrow">Programm 03 · Kampfsport</p>
        <h1 class="program-hero-title">Fight</h1>
        <p class="lead">Fokus, Kondition und ein kontrolliertes Ventil. Fight bringt Energie in deinen Körper und Klarheit in deinen Kopf.</p>
      </div>
    </div>
  </section>

  <main class="program-main">
    <div class="wrap program-layout">
      <section>
        <h2 class="program-heading">Dein<br>Ausgleich.</h2>
        <p class="copy">Fight kombiniert Boxtechnik, Intervalltraining und Teamenergie. Du trainierst intensiv, lernst neue Skills und gehst jedes Mal mit einem klaren Kopf nach Hause.</p>

        <div class="attributes">
          <div class="attribute"><strong>Fokus</strong><span>Technik, Reaktion und Kondition</span></div>
          <div class="attribute"><strong>Level</strong><span>Einsteiger bis Fortgeschrittene</span></div>
          <div class="attribute"><strong>Format</strong><span>Coach-geführte Gruppenkurse</span></div>
        </div>
      </section>

      <aside class="schedule">
        <h3>Lust auf Punch?</h3>
        <p>Starte mit einem kostenlosen Kennenlerntermin und erlebe deine erste Fight-Session bei SCHWITZKASTEN.</p>
        <a class="button button--dark" href="<?= e(BASE_URL) ?>index.php#mitgliedschaft">Probetraining sichern</a>
      </aside>
    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
