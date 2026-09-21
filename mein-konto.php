<?php
/**
 * @file        mein-konto.php
 * @layer       1 – Seite
 * @description Startpunkt des Mitgliederbereichs. Zeigt die Stammdaten, die
 *              gemerkte Programm-Auswahl, die laufende Mitgliedschaft und die
 *              Nachweise und bietet das Abmelden an.
 *
 *              Nur für angemeldete Mitglieder: Wer nicht angemeldet ist, wird
 *              von Auth::nurFuerMitglieder() zur Anmeldeseite geschickt,
 *              bevor irgendetwas ausgegeben wird.
 *
 *              Die Daten selbst stehen NICHT im HTML, sondern kommen über
 *              assets/js/pages/mein-konto.page.js aus api/sitzung.php -
 *              der gewohnte Weg durch die Schichten.
 * @see         assets/js/pages/mein-konto.page.js
 * @see         docs/features/mitglieder-login.md
 * @see         docs/features/mitgliedschaften.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

Auth::nurFuerMitglieder();

$pageTitle  = 'Mein Konto — SCHWITZKASTEN';
$pageScript = 'mein-konto.page.js';
$activeNav  = 'konto';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="auth" id="konto-seite" data-abgemeldet="<?= e(BASE_URL) ?>index.php" data-login="<?= e(BASE_URL) ?>anmelden.php">
    <div class="wrap auth-inner">

      <div class="auth-intro">
        <p class="eyebrow">Mitgliederbereich</p>
        <h1 class="display auth-title">Mein<br>Konto.</h1>
      </div>

      <div class="auth-cards">
        <div class="auth-card">
          <h2 class="auth-heading">Stammdaten</h2>

          <dl class="auth-data">
            <div>
              <dt>Vorname</dt>
              <dd id="konto-vorname">…</dd>
            </div>
            <div>
              <dt>Nachname</dt>
              <dd id="konto-nachname">…</dd>
            </div>
            <div>
              <dt>E-Mail-Adresse</dt>
              <dd id="konto-email">…</dd>
            </div>
          </dl>

          <p class="auth-message" id="konto-meldung" role="alert"></p>

          <button class="button button--ghost" id="abmelden" type="button">Abmelden</button>
        </div>

        <div class="auth-card">
          <h2 class="auth-heading">Meine Auswahl</h2>
          <p class="auth-hint">Was du dir auf den Programmseiten gemerkt hast.</p>

          <!-- Füllt assets/js/pages/mein-konto.page.js aus api/auswahl.php. -->
          <ul class="auswahl-liste" id="auswahl-liste"></ul>

          <p class="auth-message" id="auswahl-meldung" role="alert"></p>
        </div>

        <!-- Welcher der beiden Blöcke sichtbar ist, entscheidet das
             Seitenskript: Ohne gewählten Tarif gibt es nichts anzuzeigen. -->
        <div class="auth-card">
          <h2 class="auth-heading">Mitgliedschaft</h2>

          <dl class="auth-data" id="konto-tarif-daten" hidden>
            <div>
              <dt>Tarif</dt>
              <dd id="konto-tarif">…</dd>
            </div>
            <div>
              <dt>Preisgruppe</dt>
              <dd id="konto-preisgruppe">…</dd>
            </div>
            <div>
              <dt>Monatsbeitrag</dt>
              <dd id="konto-beitrag">…</dd>
            </div>
            <div>
              <dt>Tarif seit</dt>
              <dd id="konto-beginn">…</dd>
            </div>

            <!-- Nur sichtbar, wenn ein Wechsel zum Monatsersten vorgemerkt
                 ist. Das Seitenskript blendet die Zeile sonst aus. -->
            <div id="konto-wechsel-zeile" hidden>
              <dt>Vorgemerkter Wechsel</dt>
              <dd id="konto-wechsel">…</dd>
            </div>
          </dl>

          <p class="auth-hint" id="konto-ohne-tarif" hidden>
            Du hast noch keinen Tarif gewählt. Trainieren kannst du erst,
            wenn eine Mitgliedschaft läuft.
          </p>

          <a class="button button--ghost" href="<?= e(BASE_URL) ?>mitgliedschaft.php">Tarife ansehen</a>
        </div>

        <!-- Nachweise für die ermäßigten Preise. Was hier hochgeladen wird,
             wird ausgelesen und sofort verworfen - das Bild landet nie auf
             der Festplatte. Siehe api/nachweise.php. -->
        <div class="auth-card">
          <h2 class="auth-heading">Nachweise</h2>
          <p class="auth-hint">
            Schüler-, Studierenden- und Seniorenpreise gelten nur mit Nachweis.
            Fotografiere deinen Ausweis — wir lesen das Datum aus und löschen
            das Bild sofort danach.
          </p>

          <ul class="nachweis-liste" id="nachweis-liste"></ul>

          <p class="auth-hint" id="nachweis-leer" hidden>
            Du hast noch keinen Nachweis hinterlegt.
          </p>

          <form class="auth-form nachweis-form" id="nachweis-form">
            <label for="nachweis-art">Was möchtest du nachweisen?</label>
            <select id="nachweis-art" name="art">
              <option value="student">Studierendenausweis</option>
              <option value="schueler">Schülerausweis</option>
              <option value="senior">Senior (ab 65, Lichtbildausweis)</option>
            </select>

            <!-- Steht hier, wenn für die gewählte Art schon ein gültiger
                 Nachweis vorliegt. Text und Sichtbarkeit setzt das
                 Seitenskript; der Endpunkt lehnt Doppelte ohnehin ab. -->
            <p class="auth-hint nachweis-vorhanden" id="nachweis-vorhanden" hidden></p>

            <!-- Genau eines der beiden Felder ist sichtbar: das Bildfeld,
                 wenn ein API-Schlüssel hinterlegt ist, sonst das Datumsfeld.
                 Das entscheidet der Server, nicht die Seite. -->
            <div id="nachweis-bild-feld" hidden>
              <label for="nachweis-bild">Foto des Ausweises</label>
              <input id="nachweis-bild" name="bild" type="file" accept="image/jpeg,image/png,image/webp">
            </div>

            <div id="nachweis-datum-feld" hidden>
              <label for="nachweis-datum" id="nachweis-datum-label">Gültig bis</label>
              <input id="nachweis-datum" name="datum" type="date">
              <p class="auth-hint nachweis-demo-hinweis">
                Demo-Modus: Es ist kein Schlüssel für die Bildprüfung
                hinterlegt, deshalb wird das Datum von Hand eingetragen.
              </p>
            </div>

            <p class="auth-message" id="nachweis-meldung" role="alert"></p>

            <button class="button" type="submit">Nachweis prüfen lassen</button>
          </form>

          <!-- Liegt über der ganzen Seite, solange die KI das Foto prüft.
               Eingeblendet wird es von assets/js/components/ausweis-scan.js,
               im Demo-Modus erscheint es nicht. Das Bild wird hier nur lokal
               aus der gewählten Datei angezeigt und nie geladen. -->
          <div class="ausweis-scan" data-ausweis-scan role="status" aria-live="polite" hidden>
            <div class="ausweis-scan__karte">
              <img class="ausweis-scan__bild" data-ausweis-scan-bild alt="">
              <div class="ausweis-scan__raster"></div>
              <div class="ausweis-scan__strahl"></div>
              <div class="ausweis-scan__haken">
                <svg viewBox="0 0 48 48" aria-hidden="true">
                  <circle cx="24" cy="24" r="21"></circle>
                  <path d="M14 24.5 21 31.5 34 18"></path>
                </svg>
              </div>
            </div>

            <div class="ausweis-scan__text">
              <p class="ausweis-scan__titel" data-ausweis-scan-titel></p>
              <div class="ausweis-scan__balken"></div>
              <p class="ausweis-scan__wert" data-ausweis-scan-wert>0 %</p>
            </div>

            <div class="ausweis-scan__konfetti" data-ausweis-scan-konfetti aria-hidden="true"></div>
          </div>
        </div>
      </div>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
