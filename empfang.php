<?php
/**
 * @file        empfang.php
 * @layer       1 – Seite
 * @description Die Seite für das Personal am Empfang. Eine Tabelle mit
 *              allen Mitgliedern, die ihren Ausweis noch einmal zeigen
 *              müssen, je Zeile Bestätigen und Ablehnen. Darüber eine Suche
 *              nach Name oder E-Mail und ein Filter nach Frist, damit man
 *              auch bei vielen Einträgen die Person vor sich schnell findet.
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
    <div class="wrap empfang-inner">

      <div class="empfang-kopf">
        <div>
          <p class="eyebrow">Nur für das Personal</p>
          <h1 class="display auth-title">Empfang.</h1>
        </div>
        <p class="auth-lead">
          Hier steht, wer einen Nachweis für einen ermäßigten Preis hochgeladen
          hat und den Ausweis noch einmal zeigen muss. Vergleiche Foto und Name
          auf dem Ausweis mit der Person vor dir und mit dem Namen in der Liste.
        </p>
      </div>

      <section class="auth-card empfang-karte" aria-labelledby="empfang-titel">
        <div class="empfang-titelzeile">
          <h2 class="auth-heading" id="empfang-titel">Ausweis zeigen</h2>
          <p class="empfang-anzahl" id="empfang-anzahl" aria-live="polite"></p>
        </div>
        <p class="auth-hint">
          Eine Zeile je Person. Bestätigen heißt: Die Person ist die, auf die
          das Konto läuft — ihr Name ist danach für immer fest. Ablehnen
          entfernt den Nachweis.
        </p>

        <!-- Suchen und Filtern geschieht im Browser über die geladene Liste:
             Der Server liefert ohnehin alle offenen Einträge. -->
        <div class="empfang-werkzeuge">
          <label class="empfang-suche">
            <span class="konto-visually-hidden">Suchen</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="empfang-suche" type="search" placeholder="Name oder E-Mail-Adresse" autocomplete="off">
          </label>

          <label class="konto-visually-hidden" for="empfang-filter">Nach Frist filtern</label>
          <select class="empfang-filter" id="empfang-filter">
            <option value="alle">Alle</option>
            <option value="offen">Frist läuft</option>
            <option value="verpasst">Frist verpasst</option>
          </select>

          <button class="empfang-neu" id="empfang-neu" type="button" title="Liste neu laden">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/></svg>
            <span class="konto-visually-hidden">Liste neu laden</span>
          </button>
        </div>

        <table class="empfang-tabelle">
          <thead>
            <tr>
              <th scope="col">Person</th>
              <th scope="col">Nachweis</th>
              <th scope="col">Status</th>
              <th scope="col"><span class="konto-visually-hidden">Aktionen</span></th>
            </tr>
          </thead>
          <!-- Füllt assets/js/pages/empfang.page.js aus api/empfang.php. -->
          <tbody id="empfang-liste"></tbody>
        </table>

        <!-- Text setzt das Seitenskript: niemand offen oder niemand gefunden. -->
        <p class="auth-hint empfang-leer" id="empfang-leer" hidden></p>

        <p class="auth-message" id="empfang-meldung" role="alert"></p>

        <a class="auth-link empfang-zurueck" href="<?= e(BASE_URL) ?>mein-konto.php">Zurück zu Mein Konto</a>
      </section>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
