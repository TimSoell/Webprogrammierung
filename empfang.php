<?php
/**
 * @file        empfang.php
 * @layer       1 – Seite
 * @description Die Seite für das Personal am Empfang. Listet alle
 *              Mitglieder, die ihren Ausweis noch einmal zeigen müssen, mit
 *              je einem Knopf zum Bestätigen und Ablehnen.
 *
 *              NUR FUER DAS PERSONAL: Auth::nurFuerPersonal() schickt alle
 *              anderen weg, bevor etwas ausgegeben wird.
 *
 *              KEIN LINK FUEHRT HIERHER - kein Menüpunkt, kein Knopf. Das
 *              Personal ruft die Adresse direkt auf (/empfang.php, am besten
 *              als Lesezeichen am Rechner des Empfangs). Geheim ist sie
 *              deshalb nicht: Geschützt wird die Seite über die Rolle.
 *
 *              Die Liste steht nicht im HTML, sondern kommt über
 *              assets/js/pages/empfang.page.js aus api/empfang.php.
 * @see         assets/js/pages/empfang.page.js
 * @see         assets/css/components/empfang.css
 * @see         docs/features/empfang.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

Auth::nurFuerPersonal();

$pageTitle  = 'Empfang — SCHWITZKASTEN';
$pageScript = 'empfang.page.js';
$activeNav  = 'konto';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="auth" id="empfang-seite" data-login="<?= e(BASE_URL) ?>anmelden.php">
    <div class="wrap auth-inner">

      <div class="auth-intro">
        <p class="eyebrow">Nur für das Personal</p>
        <h1 class="display auth-title">Emp&shy;fang.</h1>
        <p class="auth-lead">
          Hier steht, wer einen Nachweis für einen ermäßigten Preis hochgeladen
          hat und den Ausweis noch einmal zeigen muss. Vergleiche Foto und Name
          auf dem Ausweis mit der Person vor dir und mit dem Namen in der Liste.
        </p>
        <a class="auth-link empfang-zurueck" href="<?= e(BASE_URL) ?>mein-konto.php">Zurück zu Mein Konto</a>
      </div>

      <div class="auth-card">
        <h2 class="auth-heading">Ausweis zeigen</h2>
        <p class="auth-hint">
          Bestätigen heißt: Die Person ist die, auf die das Konto läuft. Ihr
          Name ist danach für immer fest. Ablehnen entfernt den Nachweis.
        </p>

        <!-- Füllt assets/js/pages/empfang.page.js aus api/empfang.php. -->
        <ul class="empfang-liste" id="empfang-liste" aria-live="polite"></ul>

        <p class="auth-hint" id="empfang-leer" hidden>
          Gerade muss niemand seinen Ausweis zeigen.
        </p>

        <p class="auth-message" id="empfang-meldung" role="alert"></p>
      </div>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
