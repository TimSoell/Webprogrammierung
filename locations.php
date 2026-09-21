<?php
/**
 * @file        locations.php
 * @layer       1 – Seite
 * @description Standortübersicht mit interaktiver Leaflet-Karte für die vier
 *              SCHWITZKASTEN Athletic Club Locations. Darunter folgen ein
 *              Info-Abschnitt und der scrollgesteuerte Studio-Rundgang.
 * @see         assets/js/pages/locations.page.js
 * @see         assets/css/components/locations.css
 * @see         assets/css/components/studio-info.css
 * @see         docs/features/studiotour.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'Locations — SCHWITZKASTEN';
$pageDescription = 'Finde deinen SCHWITZKASTEN Athletic Club in Köln, München, Berlin oder Stuttgart.';
$pageScript      = 'locations.page.js';
$pageUsesMap     = true;
$activeNav       = 'locations';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="locations-page">
    <section class="locations-hero">
      <div class="wrap">
        <p class="eyebrow">Vier Clubs · eine Community</p>
        <h1 class="display locations-title">Dein<br><span>Standort.</span></h1>
        <p class="locations-lead">Finde den SCHWITZKASTEN Athletic Club in deiner Stadt und plane deinen nächsten Besuch.</p>
      </div>
    </section>

    <section class="locations-content" aria-labelledby="locations-heading">
      <div class="wrap">
        <div class="section-head locations-section-head">
          <div>
            <p class="eyebrow">SCHWITZKASTEN Athletic Club</p>
            <h2 class="display section-title" id="locations-heading">Vier<br>Adressen.</h2>
          </div>
          <p class="section-lead">Wähle einen Standort aus der Liste aus oder entdecke alle Clubs direkt auf der Karte.</p>
        </div>

        <div class="locations-layout">
          <div class="locations-map-wrap">
            <div id="map" class="locations-map" aria-label="Karte mit den vier SCHWITZKASTEN Standorten"></div>
            <p class="map-attribution">Kartendaten © <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a>-Mitwirkende</p>
          </div>

          <div class="locations-list" id="locations-list" aria-label="Standortliste"></div>
        </div>
      </div>
    </section>

    <section class="studio-info" aria-labelledby="studio-heading">
      <div class="wrap">
        <div class="section-head locations-section-head">
          <div>
            <p class="eyebrow">Rundgang</p>
            <h2 class="display section-title" id="studio-heading">So sieht es<br>drinnen aus.</h2>
          </div>
          <p class="section-lead">Alle vier Clubs sind nach demselben Prinzip aufgebaut: vier Zonen, kurze Wege, keine Wartezeiten an den Geräten. Scrolle durch den Rundgang und sieh dich um.</p>
        </div>

        <div class="stats">
          <div class="stat"><strong>1.200</strong><span>Quadratmeter Trainingsfläche</span></div>
          <div class="stat"><strong>04</strong><span>Zonen: Strength, Move, Fight, Recovery</span></div>
          <div class="stat"><strong>24/7</strong><span>Zugang für Mitglieder</span></div>
          <div class="stat"><strong>120</strong><span>Geräte und Trainingsstationen</span></div>
        </div>
      </div>
    </section>

    <section class="scroll-video scroll-video--tour" data-studio-tour aria-label="Rundgang durch das Studio">
      <div class="scroll-video-sticky">
        <video muted playsinline preload="auto" aria-label="Rundgang durch den SCHWITZKASTEN Athletic Club">
          <source src="<?= e(BASE_URL) ?>assets/img/studio-tour.mp4" type="video/mp4">
          Dein Browser kann dieses Video nicht anzeigen.
        </video>
        <div class="scroll-video-overlay">
          <div class="wrap">
            <div class="scroll-video-copy">
              <p class="eyebrow">Jede Zone, ein Zweck</p>
              <h2>Dein<br><span>Revier.</span></h2>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
