<?php
/**
 * @file        index.php
 * @layer       1 – Seite
 * @description Die Startseite. Hero, Studio-Vorstellung, die drei Programme,
 *              Philosophie und der Aufruf zur Mitgliedschaft.
 *
 *              Diese Datei enthält bewusst NUR Inhalt und Struktur.
 *              Kein CSS, kein JavaScript, kein SQL. Das Aussehen liegt in
 *              assets/css/, das Verhalten in assets/js/.
 *
 *              Aufbau jeder Seite in diesem Projekt:
 *                1. bootstrap.php einbinden
 *                2. Seiten-Variablen setzen ($pageTitle ...)
 *                3. head.php und header.php einbinden
 *                4. eigener Inhalt
 *                5. footer.php als letztes einbinden
 * @see         docs/ARCHITECTURE.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'SCHWITZKASTEN — Athletic Club';
$pageDescription = 'SCHWITZKASTEN Athletic Club — Dein neues Fitnessstudio in Köln. Strength, Move und Fight auf 1.200 m².';
$pageScript      = 'index.page.js';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main id="top">

    <section class="hero">
      <div class="hero-content wrap">
        <p class="eyebrow">Eröffnung Frühjahr 2025 · Köln</p>
        <h1 class="display hero-title">Trainiere<br><em>deine</em><br>Basis.</h1>
        <div class="hero-bottom">
          <p class="hero-copy">Ein neuer Athletic Club für alle, die stärker werden wollen. Ohne Show. Mit System. Jeden Tag.</p>
          <span class="scroll-note"><i></i> Entdecke deinen Club</span>
        </div>
      </div>
    </section>

    <section class="scroll-video" data-scroll-video aria-label="Training in Bewegung">
      <div class="scroll-video-sticky">
        <video muted playsinline preload="auto" aria-label="Training im SCHWITZKASTEN Athletic Club">
          <source src="<?= e(BASE_URL) ?>assets/img/scroll-video.mp4" type="video/mp4">
          Dein Browser kann dieses Video nicht anzeigen.
        </video>
        <div class="scroll-video-overlay">
          <div class="wrap">
            <div class="scroll-video-copy">
              <p class="eyebrow">Jede Bewegung zaehlt</p>
              <h2>In den<br><span>Flow.</span></h2>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Die Wörter stehen doppelt, damit die Schleife nahtlos läuft.
         Erklärung in assets/css/components/ticker.css -->
    <div class="ticker" aria-label="Studio-Highlights">
      <div class="ticker-track">
        <span>Strength</span><span>Conditioning</span><span>Community</span><span>Recovery</span>
        <span>Strength</span><span>Conditioning</span><span>Community</span><span>Recovery</span>
      </div>
    </div>

    <section id="studio">
      <div class="wrap">
        <div class="intro-grid">
          <div>
            <p class="eyebrow">Dein neues Zuhause</p>
            <h2 class="display intro-title">Mehr als ein<br><span>Fitnessstudio.</span></h2>
          </div>
          <p class="intro-text">SCHWITZKASTEN verbindet durchdachtes Training, starke Coaches und eine Community, die dich wirklich weiterbringt. 1.200 m² für deinen nächsten Schritt.</p>
        </div>

        <div class="stats">
          <div class="stat"><strong>1.200</strong><span>Quadratmeter Trainingsfläche</span></div>
          <div class="stat"><strong>24/7</strong><span>Zugang für Mitglieder</span></div>
          <div class="stat"><strong>04</strong><span>Trainingszonen für jedes Ziel</span></div>
        </div>
      </div>
    </section>

    <section class="light-section" id="programme">
      <div class="wrap">
        <div class="section-head">
          <div>
            <p class="eyebrow">Finde deinen Fokus</p>
            <h2 class="display section-title">Dein<br>Programm.</h2>
          </div>
          <p class="section-lead">Drei Wege, ein Ziel: dich in deiner besten Form zu erleben.</p>
        </div>

        <div class="programs">
          <article class="program">
            <a class="program-link" href="<?= e(BASE_URL) ?>programme/strength.php">
              <img src="https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=900&q=85" alt="Athlet beim Krafttraining" loading="lazy">
              <div class="program-content">
                <span class="program-number">01</span>
                <h3>Strength</h3>
                <p>Grundlagen aufbauen, Grenzen verschieben, Kraft spüren.</p>
              </div>
            </a>
          </article>

          <article class="program">
            <a class="program-link" href="<?= e(BASE_URL) ?>programme/move.php">
              <img src="https://images.unsplash.com/photo-1538805060514-97d9cc17730c?auto=format&fit=crop&w=900&q=85" alt="Frau beim funktionellen Training" loading="lazy">
              <div class="program-content">
                <span class="program-number">02</span>
                <h3>Move</h3>
                <p>Funktionelle Workouts für mehr Energie und Beweglichkeit.</p>
              </div>
            </a>
          </article>

          <article class="program">
            <a class="program-link" href="<?= e(BASE_URL) ?>programme/fight.php">
              <img src="https://images.unsplash.com/photo-1549060279-7e168fcee0c2?auto=format&fit=crop&w=900&q=85" alt="Mann beim Boxtraining" loading="lazy">
              <div class="program-content">
                <span class="program-number">03</span>
                <h3>Fight</h3>
                <p>Intensiv, fokussiert, gemeinsam. Dein Ausgleich mit Punch.</p>
              </div>
            </a>
          </article>
        </div>
      </div>
    </section>

    <section id="philosophie">
      <div class="wrap quote-layout">
        <img class="quote-image" src="https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=1000&q=85" alt="Athlet mit Langhantel im Studio" loading="lazy">
        <div>
          <p class="eyebrow">Unsere Philosophie</p>
          <blockquote>Stärke ist kein Ziel.<br>Sie ist eine <span>Gewohnheit.</span></blockquote>
          <span class="quote-by">— Das SCHWITZKASTEN Prinzip</span>
        </div>
      </div>
    </section>

    <section class="join" id="mitgliedschaft">
      <div class="wrap join-inner">
        <div>
          <p class="eyebrow">Ready when you are</p>
          <h2 class="display join-title">Komm<br>rein.</h2>
        </div>
        <div class="join-info">
          <p>Die ersten 100 Mitglieder trainieren im ersten Monat zum Eröffnungspreis.</p>
          <button class="button button--dark" id="open-modal" type="button">Jetzt Platz sichern</button>
        </div>
      </div>
    </section>

  </main>

<?php
require ROOT_PATH . '/partials/modal-anmeldung.php';
require ROOT_PATH . '/partials/footer.php';
