<?php
/**
 * @file        passwort-zuruecksetzen.php
 * @layer       1 – Seite
 * @description Ziel des Links aus "Passwort vergessen". Hier legt das Mitglied
 *              ein neues Passwort fest.
 *
 *              Die Adresse enthält selector und token. Ob sie gültig sind,
 *              prüft erst der Server beim Absenden - diese Seite zeigt immer
 *              das Formular.
 * @see         assets/js/pages/passwort-zuruecksetzen.page.js
 * @see         api/passwort-reset.php
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle   = 'Neues Passwort — SCHWITZKASTEN';
$pageScript  = 'passwort-zuruecksetzen.page.js';
$activeNav   = 'konto';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="auth">
    <div class="wrap auth-inner">

      <div class="auth-intro">
        <p class="eyebrow">Mitgliederbereich</p>
        <h1 class="display auth-title">Neues<br>Passwort.</h1>
        <p class="auth-lead">Der Link ist 60 Minuten gültig und funktioniert nur ein einziges Mal.</p>
      </div>

      <div class="auth-card">
        <form class="auth-form" id="form-reset">
          <label for="reset-passwort">Neues Passwort</label>
          <input id="reset-passwort" name="passwort" type="password" required autocomplete="new-password" aria-describedby="passwort-kriterien">
<?php require ROOT_PATH . '/partials/passwort-kriterien.php'; ?>

          <label for="reset-passwort-wiederholung">Passwort wiederholen</label>
          <input id="reset-passwort-wiederholung" name="passwortWiederholung" type="password" required autocomplete="new-password">

          <p class="auth-message" id="reset-meldung" role="alert"></p>

          <button class="button" type="submit">Passwort speichern</button>
          <a class="auth-link" href="<?= e(BASE_URL) ?>anmelden.php">Neuen Link anfordern</a>
        </form>

        <div class="auth-success" id="reset-erfolg" role="status" hidden>
          <h2 class="auth-heading">Geschafft.</h2>
          <p class="auth-hint">Dein Passwort ist geändert. Auf anderen Geräten wirst du aus Sicherheitsgründen abgemeldet.</p>
          <a class="button" href="<?= e(BASE_URL) ?>anmelden.php">Zum Login</a>
        </div>
      </div>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
