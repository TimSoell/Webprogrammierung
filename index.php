<?php
/**
 * @file        index.php
 * @layer       1 – Seite
 * @description Die Startseite. Hero-Video, Hero, Studio-Vorstellung, die
 *              drei Programme, Philosophie, die Bewertungen samt Fenster
 *              "Alle Bewertungen", der Aufruf zur Mitgliedschaft und ganz
 *              unten "Freunde werben" samt Fenster "Freund einladen".
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
$pageHasPreloader = true;

// Entscheidet nur, ob in den Fenstern "Alle Bewertungen" und "Freund
// einladen" das Formular oder der Link zur Anmeldung steht. isLoggedIn()
// liest die Session, nicht die Datenbank.
$angemeldet = Auth::instanz()->isLoggedIn();

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main id="top">

    <section class="scroll-video scroll-video--einmal" data-hero-video aria-label="Training in Bewegung">
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

    <section class="hero">
      <div class="hero-content wrap">
        <p class="eyebrow">Eröffnung Frühjahr 2025 · Köln</p>
        <h1 class="display hero-title">Trainiere<br><em>deine</em><br>Basis.</h1>
        <div class="hero-bottom">
          <p class="hero-copy">Ein neuer Athletic Club für alle, die stärker werden wollen. Ohne Show. Mit System. Jeden Tag.</p>
        </div>
      </div>
    </section>

    <!-- Zwei GLEICHE Gruppen, damit die Schleife nahtlos läuft. Die zweite
         ist nur für die Optik da und für Screenreader ausgeblendet.
         Erklärung in assets/css/components/ticker.css -->
    <div class="ticker" aria-label="Studio-Highlights">
      <div class="ticker-track">
        <div class="ticker-group">
          <span>Strength</span><span>Conditioning</span><span>Community</span><span>Recovery</span>
          <span>Strength</span><span>Conditioning</span><span>Community</span><span>Recovery</span>
        </div>
        <div class="ticker-group" aria-hidden="true">
          <span>Strength</span><span>Conditioning</span><span>Community</span><span>Recovery</span>
          <span>Strength</span><span>Conditioning</span><span>Community</span><span>Recovery</span>
        </div>
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

    <section class="auslastung" id="auslastung" aria-labelledby="auslastung-heading">
      <div class="wrap">
        <div class="section-head">
          <div>
            <p class="eyebrow">Live aus dem Studio</p>
            <h2 class="display section-title" id="auslastung-heading">Wie voll<br><span>ist es?</span></h2>
          </div>
          <p class="section-lead">Die Kurve zeigt, wie viel an einem Tag wie heute üblicherweise los ist. Angekündigte Besuche kommen oben drauf — als Mitglied kannst du deinen im Konto eintragen.</p>
        </div>

        <div class="auslastung-jetzt" id="auslastung-jetzt" hidden>
          <p class="auslastung-stufe" id="auslastung-stufe">…</p>
          <p class="auslastung-zahl"><strong id="auslastung-personen">…</strong><span id="auslastung-prozent">…</span></p>
        </div>

        <!-- Das Diagramm wird von assets/js/components/auslastung-diagramm.js
             als SVG hineingezeichnet. Ohne JavaScript bleibt die Meldung
             darunter stehen. -->
        <div class="auslastung-diagramm" id="auslastung-diagramm"></div>

        <p class="auslastung-meldung" id="auslastung-meldung" role="status">Auslastung wird geladen …</p>

        <ul class="auslastung-legende">
          <li><i class="auslastung-punkt auslastung-punkt--basis"></i>Üblich zu dieser Zeit</li>
          <li><i class="auslastung-punkt auslastung-punkt--gebucht"></i>Angekündigte Besuche</li>
        </ul>
      </div>
    </section>
    <section class="light-section" id="bewertungen" aria-labelledby="bewertungen-heading">
      <div class="wrap">
        <div class="section-head">
          <div>
            <p class="eyebrow">Stimmen aus dem Club</p>
            <h2 class="display section-title" id="bewertungen-heading">Was andere<br>sagen.</h2>
          </div>

          <!-- Blättern im Karussell. Erscheint nur, wenn nicht alle Kacheln
               auf einmal zu sehen sind - das entscheidet bewertungen.js. -->
          <div class="bewertungen-pfeile" id="bewertungen-pfeile" hidden>
            <button class="bewertungen-pfeil" id="bewertungen-zurueck" type="button" aria-label="Vorherige Bewertung">←</button>
            <button class="bewertungen-pfeil" id="bewertungen-weiter" type="button" aria-label="Nächste Bewertung">→</button>
          </div>
        </div>

        <!-- Die Kacheln trägt assets/js/components/bewertungen.js ein. Die
             Liste scrollt quer: drei Kacheln zu sehen, der Rest daneben. -->
        <ul class="bewertungen-kacheln" id="bewertungen-kacheln" aria-label="Bewertungen, zum Blättern quer scrollen"></ul>
        <p class="bewertungen-meldung" id="bewertungen-meldung" role="status">Bewertungen werden geladen …</p>

        <div class="bewertungen-fuss" id="bewertungen-fuss" hidden>
          <p class="bewertungen-fuss-schnitt" id="bewertungen-fuss-schnitt"></p>
          <button class="button button--dark" id="bewertungen-oeffnen" type="button">Alle Bewertungen</button>
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
          <a class="button button--dark" href="<?= e(BASE_URL) ?>mitgliedschaft.php">Jetzt Platz sichern</a>
        </div>
      </div>
    </section>

    <!-- Freunde werben: der letzte Abschnitt der Seite. Der Button öffnet
         das Fenster weiter unten, siehe docs/features/freunde-werben.md -->
    <section class="werben" id="freunde-werben" aria-labelledby="werben-heading">
      <div class="wrap werben-inner">
        <div>
          <p class="eyebrow">Zu zweit schwitzt es sich besser</p>
          <h2 class="display section-title" id="werben-heading">Bring wen<br><span>mit.</span></h2>
        </div>
        <div class="werben-info">
          <p>Lade einen Freund ein. Registriert er sich, trainierst du 3 Monate gratis im Basisplan.</p>
          <button class="button" id="werben-oeffnen" type="button">Freund einladen</button>
        </div>
      </div>
    </section>

  </main>

  <!-- Das Fenster "Alle Bewertungen". Öffnen und Schließen übernimmt
       assets/js/components/modal.js, befüllt wird es von
       assets/js/components/bewertungen.js. -->
  <div class="modal" id="bewertungen-modal" role="dialog" aria-modal="true" aria-labelledby="bewertungen-modal-titel">
    <div class="modal-card modal-card--bewertungen">
      <button class="modal-close" id="bewertungen-schliessen" type="button" aria-label="Fenster schließen">×</button>

      <h2 id="bewertungen-modal-titel">Alle Bewertungen.</h2>

      <div class="bewertungen-schnitt" id="bewertungen-schnitt" hidden></div>

<?php if ($angemeldet): ?>
      <form class="bewertung-formular" id="bewertung-formular" novalidate>
        <h3 class="bewertung-formular-titel">Deine Bewertung</h3>

        <fieldset class="bewertung-wahl">
          <legend>Sterne</legend>
          <input type="radio" name="sterne" id="sterne-1" value="1">
          <label for="sterne-1" aria-label="1 Stern">★</label>
          <input type="radio" name="sterne" id="sterne-2" value="2">
          <label for="sterne-2" aria-label="2 Sterne">★</label>
          <input type="radio" name="sterne" id="sterne-3" value="3">
          <label for="sterne-3" aria-label="3 Sterne">★</label>
          <input type="radio" name="sterne" id="sterne-4" value="4">
          <label for="sterne-4" aria-label="4 Sterne">★</label>
          <input type="radio" name="sterne" id="sterne-5" value="5">
          <label for="sterne-5" aria-label="5 Sterne">★</label>
        </fieldset>

        <label class="bewertung-formular-label" for="bewertung-text">Was hat dir gefallen, was nicht?</label>
        <textarea class="bewertung-formular-text" id="bewertung-text" name="text" rows="4" maxlength="1000"></textarea>

        <button class="button" type="submit">Bewertung abschicken</button>
        <p class="bewertung-formular-meldung" id="bewertung-formular-meldung" aria-live="polite"></p>
      </form>
<?php else: ?>
      <p class="bewertungen-hinweis">
        Du willst selbst bewerten?
        <a href="<?= e(BASE_URL) ?>anmelden.php">Melde dich an</a> - als Mitglied oder mit einem kostenlosen Konto.
      </p>
<?php endif; ?>

      <ul class="bewertungen-liste" id="bewertungen-liste"></ul>
    </div>
  </div>

  <!-- Das Fenster "Freund einladen". Öffnen und Schließen übernimmt
       assets/js/components/modal.js, das Formular
       assets/js/components/freunde-werben.js. -->
  <div class="modal" id="werben-modal" role="dialog" aria-modal="true" aria-labelledby="werben-modal-titel">
    <div class="modal-card modal-card--werben">
      <button class="modal-close" id="werben-schliessen" type="button" aria-label="Fenster schließen">×</button>

      <h2 id="werben-modal-titel">Freund einladen.</h2>

<?php if ($angemeldet): ?>
      <form class="auth-form werben-formular" id="werben-formular" novalidate>
        <p class="werben-text">
          Registriert sich dein Freund mit genau dieser E-Mail, bekommst du
          einen Gutschein: 3 Monate Basisplan gratis.
        </p>

        <label for="werben-name">Name deines Freundes</label>
        <input id="werben-name" name="name" maxlength="120" autocomplete="off" required>

        <label for="werben-email">E-Mail deines Freundes</label>
        <input id="werben-email" name="email" type="email" maxlength="249" autocomplete="off" required>

        <button class="button" type="submit">Einladung eintragen</button>
        <p class="auth-message" id="werben-meldung" role="alert"></p>
      </form>

      <!-- Erscheint nach dem Absenden an Stelle des Formulars. -->
      <div class="werben-erfolg" id="werben-erfolg" hidden>
        <p class="werben-erfolg-text" id="werben-erfolg-text" role="status"></p>

        <label class="werben-label" for="werben-link">Link zum Weitergeben</label>
        <div class="werben-link">
          <input id="werben-link" readonly>
          <button class="button" id="werben-kopieren" type="button">Link kopieren</button>
        </div>

        <p class="werben-text">
          Wir verschicken keine E-Mail - gib den Link selbst weiter. Deinen
          Gutschein findest du danach unter
          <a href="<?= e(BASE_URL) ?>mein-konto.php">Mein Konto</a>.
        </p>

        <button class="button button--ghost" id="werben-weitere" type="button">Weiteren Freund einladen</button>
      </div>
<?php else: ?>
      <p class="werben-text">
        Für jeden Freund, der sich registriert, bekommst du 3 Monate Basisplan
        gratis. <a href="<?= e(BASE_URL) ?>anmelden.php">Melde dich an</a>,
        um jemanden einzuladen - sonst wissen wir nicht, wem der Gutschein gehört.
      </p>
<?php endif; ?>
    </div>
  </div>

<?php
require ROOT_PATH . '/partials/footer.php';
