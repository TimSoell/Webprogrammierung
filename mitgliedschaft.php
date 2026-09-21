<?php
/**
 * @file        mitgliedschaft.php
 * @layer       1 – Seite
 * @description Die vier Tarife des Studios mit Preisen und Leistungen.
 *              Angemeldete Mitglieder wählen hier ihren Tarif.
 *
 *              Ein Klick auf eine Karte ändert noch nichts. Er merkt die
 *              Auswahl nur vor; verbindlich wird sie erst über den Button
 *              unten und die Rückfrage im Bestätigungsfenster. Grund:
 *              Ein Tarifwechsel kostet Geld und ändert Zugangsrechte -
 *              siehe docs/decisions/ADR-0007-tarifwechsel-zum-monatsersten.md
 *
 *              Die Tarife stehen NICHT im HTML, sondern kommen über
 *              assets/js/pages/mitgliedschaft.page.js aus api/tarife.php -
 *              der gewohnte Weg durch die Schichten. Im HTML steht nur die
 *              leere Hülle und die Vorlage für eine Karte (<template>).
 *
 *              Die Seite ist öffentlich. Ob jemand angemeldet ist, steht in
 *              data-angemeldet - so wie in partials/header.php und ohne eine
 *              Anfrage, die für Gäste ohnehin nur 401 zurückgäbe.
 * @see         assets/js/pages/mitgliedschaft.page.js
 * @see         assets/css/components/tarifkarte.css
 * @see         docs/features/mitgliedschaften.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'Mitgliedschaft — SCHWITZKASTEN';
$pageDescription = 'Vier Tarife, drei Preisgruppen: Finde die Mitgliedschaft, die zu deinem Training passt.';
$pageScript      = 'mitgliedschaft.page.js';
$activeNav       = 'mitgliedschaft';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="tarife-page"
        id="tarife-seite"
        data-angemeldet="<?= Auth::instanz()->isLoggedIn() ? '1' : '0' ?>"
        data-login="<?= e(BASE_URL) ?>anmelden.php">

    <section class="tarife-hero">
      <div class="wrap">
        <p class="eyebrow">Vier Wege · ein Club</p>
        <h1 class="display tarife-title">Deine<br><span>Mitgliedschaft.</span></h1>
        <p class="tarife-lead">Keine Aufnahmegebühr, keine versteckten Kosten. Wechseln kannst du zum nächsten Monatsersten.</p>
      </div>
    </section>

    <section class="tarife-content" aria-labelledby="tarife-heading">
      <div class="wrap">

        <h2 class="display section-title" id="tarife-heading">Preise.</h2>

        <fieldset class="preisgruppe" id="preisgruppe">
          <legend class="preisgruppe-legend">Ich zahle als</legend>

          <label class="preisgruppe-option">
            <input type="radio" name="preisgruppe" value="standard" checked>
            <span>Standard</span>
          </label>
          <label class="preisgruppe-option">
            <input type="radio" name="preisgruppe" value="ermaessigt">
            <span>Schüler &amp; Studierende</span>
          </label>
          <label class="preisgruppe-option">
            <input type="radio" name="preisgruppe" value="senior">
            <span>Senioren</span>
          </label>
        </fieldset>

        <!-- class="auth-message" ist Absicht: Aussehen und die grüne
             Erfolgsvariante kommen aus der bestehenden Komponente, damit
             Meldungen überall im Projekt gleich aussehen. -->
        <p class="auth-message tarife-meldung" id="tarife-meldung" role="alert"></p>

        <div class="tarife-liste" id="tarife-liste"></div>

        <!-- Die Leiste unten. Sie ist der einzige Weg, eine Auswahl
             verbindlich zu machen. role="status", damit Screenreader die
             wechselnde Zusammenfassung vorgelesen bekommen. -->
        <div class="tarife-aktion" id="tarife-aktion">
          <p class="tarife-aktion-text" id="tarife-aktion-text" role="status"></p>
          <button class="button" id="mitgliedschaft-anpassen" type="button" disabled>
            Jetzt Mitgliedschaft anpassen
          </button>
        </div>

        <p class="tarife-hinweis">
          Der ermäßigte Preis gilt gegen Vorlage eines gültigen Schüler- oder
          Studierendenausweises, der Seniorenpreis ab 65 Jahren.
        </p>

      </div>
    </section>

    <!-- Vorlage für eine Tarifkarte. Das Seitenskript klont sie je Tarif und
         füllt die Felder mit textContent - Markup gehört ins HTML, nicht in
         einen Template-String im JavaScript. -->
    <template id="tarifkarte-vorlage">
      <article class="tarifkarte">
        <p class="tarifkarte-status"></p>

        <h3 class="tarifkarte-name"></h3>

        <p class="tarifkarte-preis">
          <span class="tarifkarte-betrag"></span>
          <span class="tarifkarte-einheit">€ / Monat</span>
        </p>

        <p class="tarifkarte-beschreibung"></p>

        <ul class="tarifkarte-leistungen">
          <li class="tarifkarte-leistung" data-leistung="geraete">Alle Geräte</li>
          <li class="tarifkarte-leistung" data-leistung="wellness">Sauna &amp; Sonnenbank</li>
          <li class="tarifkarte-leistung" data-leistung="kurse">Alle Kurse</li>
        </ul>

        <button class="button tarifkarte-button" type="button"></button>
      </article>
    </template>

    <!-- Die Rückfrage vor dem Wechsel. Verhalten (Öffnen, Schließen, Escape)
         kommt aus der bestehenden Komponente assets/js/components/modal.js,
         befüllt wird das Fenster vom Seitenskript. -->
    <div class="modal" id="wechsel-modal" role="dialog" aria-modal="true" aria-labelledby="wechsel-titel">
      <div class="modal-card">
        <button class="modal-close" id="wechsel-schliessen" type="button" aria-label="Fenster schließen">×</button>

        <h2 id="wechsel-titel">Wirklich ändern?</h2>
        <p id="wechsel-einleitung"></p>

        <dl class="wechsel-daten">
          <div>
            <dt>Neuer Beitrag</dt>
            <dd id="wechsel-beitrag"></dd>
          </div>
          <div id="wechsel-differenz-zeile">
            <dt>Unterschied</dt>
            <dd id="wechsel-differenz"></dd>
          </div>
          <div>
            <dt>Gültig ab</dt>
            <dd id="wechsel-ab"></dd>
          </div>
        </dl>

        <div class="wechsel-rechte">
          <div class="wechsel-rechte-block" id="wechsel-gewinn-block" hidden>
            <p class="wechsel-rechte-titel">Das kommt dazu</p>
            <ul class="wechsel-rechte-liste" id="wechsel-gewinn"></ul>
          </div>

          <div class="wechsel-rechte-block wechsel-rechte-block--verlust" id="wechsel-verlust-block" hidden>
            <p class="wechsel-rechte-titel">Das fällt weg</p>
            <ul class="wechsel-rechte-liste" id="wechsel-verlust"></ul>
          </div>
        </div>

        <div class="wechsel-buttons">
          <button class="button button--dark" id="wechsel-abbrechen" type="button">Abbrechen</button>
          <button class="button" id="wechsel-bestaetigen" type="button">Verbindlich ändern</button>
        </div>
      </div>
    </div>

  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
