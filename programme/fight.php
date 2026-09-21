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
$pageScript      = 'fight.page.js';
$activeNav       = 'programme';

// Entscheidet nur, ob unten der Merken-Button oder der Hinweis zur Anmeldung
// erscheint. isLoggedIn() liest die Session, nicht die Datenbank.
$angemeldet = Auth::instanz()->isLoggedIn();

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

        <!-- Füllt assets/js/pages/fight.page.js aus der Datenbank. -->
        <div class="merkmale" id="merkmale">Merkmale werden geladen …</div>
        <div class="merken" id="merken" data-angemeldet="<?= $angemeldet ? '1' : '' ?>"></div>
      </section>

      <aside class="program-aside">
        <div class="schedule">
          <h3>Lust auf Punch?</h3>
          <p>Starte mit einem kostenlosen Kennenlerntermin und erlebe deine erste Fight-Session bei SCHWITZKASTEN.</p>
          <a class="button button--dark" href="<?= e(BASE_URL) ?>index.php#mitgliedschaft">Probetraining sichern</a>
        </div>

        <div class="coaches">
          <h3 class="coaches-titel">Deine Coaches</h3>
          <ul class="coach-liste" id="coaches"></ul>
        </div>
      </aside>
    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
