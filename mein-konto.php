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

      <h1 class="konto-visually-hidden">Mein Konto</h1>

      <div class="auth-cards">
        <div class="auth-card konto-uebersicht">
          <section class="konto-stammdaten" aria-labelledby="konto-stammdaten-titel">
            <!-- Kopfzeile: Profilbild, Überschrift, Abmelden. Der Kreis ist
                 ein <label> um die Dateiauswahl: Ein Klick darauf öffnet sie.
                 Ohne Bild zeigt er ein neutrales Personen-Symbol. Was beim
                 Auswählen passiert, steht in assets/js/components/profilbild.js. -->
            <div class="konto-kopf">
              <label class="konto-avatar" id="konto-avatar" title="Profilbild ändern">
                <input class="konto-visually-hidden" id="profilbild-datei" type="file" accept="image/jpeg,image/png,image/webp">
                <span class="konto-avatar-kreis">
                  <img class="konto-avatar-bild" id="konto-avatar-bild" alt="">
                  <svg class="konto-avatar-platzhalter" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><circle cx="24" cy="19" r="8"/><path d="M9 44c1.5-9.5 7.5-14 15-14s13.5 4.5 15 14z"/></svg>
                </span>
                <span class="konto-avatar-kamera" aria-hidden="true"></span>
                <span class="konto-visually-hidden">Profilbild ändern</span>
              </label>
              <h2 class="auth-heading" id="konto-stammdaten-titel">Stammdaten</h2>
              <button class="konto-abmelden" id="abmelden" type="button">Abmelden</button>
            </div>

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

            <!-- Nur sichtbar, wenn es ein Profilbild gibt. -->
            <div class="konto-profilbild-optionen" id="profilbild-optionen" hidden>
              <label class="konto-schalter">
                <input type="checkbox" id="profilbild-oeffentlich">
                Profilbild bei meinen Bewertungen zeigen
              </label>
              <button class="konto-link" id="profilbild-entfernen" type="button">Bild entfernen</button>
            </div>

            <p class="auth-message" id="profilbild-meldung" role="alert"></p>
            <p class="auth-message" id="konto-meldung" role="alert"></p>
          </section>

          <!-- Überschrift nur für Screenreader: Im Entwurf stehen die Termine
               ohne eigene Überschrift, das Datum reicht als Einstieg. -->
          <section class="konto-termine" aria-labelledby="konto-termine-titel">
            <h2 class="konto-visually-hidden" id="konto-termine-titel">Meine Termine</h2>

            <!-- Füllt assets/js/components/meine-termine.js aus
                 api/kursbuchungen.php und api/probetrainings.php. -->
            <div class="kalender" id="meine-termine" aria-live="polite">
              <p class="kalender-leer">Termine werden geladen …</p>
            </div>

            <p class="auth-message" id="termine-meldung" role="alert"></p>
          </section>
        </div>

        <!-- Welcher der beiden Blöcke sichtbar ist, entscheidet das
             Seitenskript: Ohne gewählten Tarif gibt es nichts anzuzeigen. -->
        <div class="auth-card konto-mitgliedschaft">
<?php if (is_file(ROOT_PATH . '/assets/img/konto-mitgliedschaft.png')): ?>
          <!-- Dezente Strichzeichnung im Hintergrund. Erscheint erst, wenn das
               Team die Datei abgelegt hat - siehe assets/img/README.md. -->
          <img class="konto-zeichnung" src="<?= e(BASE_URL) ?>assets/img/konto-mitgliedschaft.png" alt="">
<?php endif; ?>
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

        <div class="auth-card konto-besuch">
          <h2 class="auth-heading">Mein Besuch</h2>
          <p class="auth-hint">Sag Bescheid, wann du kommst. Dein Besuch zählt sofort in die Auslastung auf der Startseite.</p>

          <div class="besuch-aktionen">
            <button class="button" id="besuch-jetzt" type="button">Jetzt einchecken</button>

            <form class="besuch-form" id="besuch-form">
              <label class="besuch-label" for="besuch-zeit">Oder für später ankündigen</label>
              <div class="besuch-eingabe">
                <input class="besuch-zeit" id="besuch-zeit" name="zeit" type="time" required>
                <button class="button button--ghost" type="submit">Eintragen</button>
              </div>
            </form>
          </div>

          <!-- Füllt assets/js/pages/mein-konto.page.js aus api/auslastung.php. -->
          <ul class="besuch-liste" id="besuch-liste"></ul>

          <p class="auth-message" id="besuch-meldung" role="alert"></p>
        </div>

        <!-- Nachweise für die ermäßigten Preise. Was hier hochgeladen wird,
             wird ausgelesen und sofort verworfen - das Bild landet nie auf
             der Festplatte. Siehe api/nachweise.php. -->
        <div class="auth-card konto-nachweise">
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

        <div class="auth-card konto-auswahl">
          <h2 class="auth-heading">Meine Auswahl</h2>
          <p class="auth-hint">Was du dir auf den Programmseiten gemerkt hast.</p>

          <!-- Füllt assets/js/pages/mein-konto.page.js aus api/auswahl.php. -->
          <ul class="auswahl-liste" id="auswahl-liste"></ul>

          <p class="auth-message" id="auswahl-meldung" role="alert"></p>
        </div>
      </div>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
