<?php
/**
 * @file        anmelden.php
 * @layer       1 – Seite
 * @description Die zentrale Auth-Seite hinter dem Button "Login / Registrierung"
 *              in der Kopfzeile. Enthält drei Formulare: Login, Registrierung
 *              und "Passwort vergessen".
 *
 *              Alle drei stehen im HTML. Welches sichtbar ist, steuert
 *              assets/js/pages/anmelden.page.js über die Reiter und das
 *              hidden-Attribut. Wer schon angemeldet ist, landet sofort in
 *              mein-konto.php.
 * @see         assets/js/pages/anmelden.page.js
 * @see         assets/css/components/auth.css
 * @see         docs/features/mitglieder-login.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

Auth::nurFuerGaeste();

$pageTitle       = 'Login / Registrierung — SCHWITZKASTEN';
$pageDescription = 'Melde dich im Mitgliederbereich von SCHWITZKASTEN an oder erstelle dein Konto.';
$pageScript      = 'anmelden.page.js';
$activeNav       = 'konto';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="auth" id="auth-seite" data-weiter="<?= e(BASE_URL) ?>mein-konto.php">
    <div class="wrap auth-inner">

      <div class="auth-intro">
        <p class="eyebrow">Mitgliederbereich</p>
        <h1 class="display auth-title">Dein<br>Zugang.</h1>
        <p class="auth-lead">Deine Stammdaten, Verträge, Buchungen und Zahlungen – sicher hinter deinem persönlichen Login.</p>
      </div>

      <div class="auth-card">
        <div class="auth-tabs" role="tablist" aria-label="Login oder Registrierung">
          <button class="auth-tab" id="tab-login" type="button" role="tab" aria-selected="true" aria-controls="panel-login">Login</button>
          <button class="auth-tab" id="tab-registrierung" type="button" role="tab" aria-selected="false" aria-controls="panel-registrierung">Registrierung</button>
        </div>

        <!-- Reiter 1: Login, darin versteckt "Passwort vergessen" -->
        <div class="auth-panel" id="panel-login" role="tabpanel" aria-labelledby="tab-login">
          <form class="auth-form" id="form-login">
            <label for="login-email">E-Mail-Adresse</label>
            <input id="login-email" name="email" type="email" required autocomplete="email" placeholder="du@beispiel.de">

            <label for="login-passwort">Passwort</label>
            <input id="login-passwort" name="passwort" type="password" required autocomplete="current-password">

            <p class="auth-message" id="login-meldung" role="alert"></p>

            <button class="button" type="submit">Einloggen</button>
            <button class="auth-link" id="link-vergessen" type="button">Passwort vergessen?</button>
          </form>

          <form class="auth-form" id="form-vergessen" hidden>
            <h2 class="auth-heading">Passwort vergessen?</h2>
            <p class="auth-hint">Gib deine E-Mail-Adresse ein. Wir schicken dir einen Link, mit dem du ein neues Passwort festlegst.</p>

            <label for="vergessen-email">E-Mail-Adresse</label>
            <input id="vergessen-email" name="email" type="email" required autocomplete="email" placeholder="du@beispiel.de">

            <p class="auth-message" id="vergessen-meldung" role="alert"></p>

            <p class="auth-demo" id="vergessen-demo" hidden>
              <strong>Demo-Modus:</strong> Es wird keine echte E-Mail verschickt.
              <a id="vergessen-demo-link" href="#">Link aus der E-Mail öffnen</a>
            </p>

            <button class="button" type="submit">Link anfordern</button>
            <button class="auth-link" id="link-zurueck" type="button">Zurück zum Login</button>
          </form>
        </div>

        <!-- Reiter 2: Registrierung -->
        <div class="auth-panel" id="panel-registrierung" role="tabpanel" aria-labelledby="tab-registrierung" hidden>
          <form class="auth-form" id="form-registrierung">
            <div class="auth-row">
              <div class="auth-field">
                <label for="reg-vorname">Vorname</label>
                <input id="reg-vorname" name="vorname" required maxlength="100" autocomplete="given-name">
              </div>
              <div class="auth-field">
                <label for="reg-nachname">Nachname</label>
                <input id="reg-nachname" name="nachname" required maxlength="100" autocomplete="family-name">
              </div>
            </div>

            <label for="reg-email">E-Mail-Adresse</label>
            <input id="reg-email" name="email" type="email" required maxlength="249" autocomplete="email" placeholder="du@beispiel.de">

            <label for="reg-passwort">Passwort</label>
            <input id="reg-passwort" name="passwort" type="password" required autocomplete="new-password" aria-describedby="passwort-kriterien">
<?php require ROOT_PATH . '/partials/passwort-kriterien.php'; ?>

            <label for="reg-passwort-wiederholung">Passwort wiederholen</label>
            <input id="reg-passwort-wiederholung" name="passwortWiederholung" type="password" required autocomplete="new-password">

            <p class="auth-message" id="registrierung-meldung" role="alert"></p>

            <button class="button" type="submit">Konto erstellen</button>
          </form>
        </div>
      </div>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
