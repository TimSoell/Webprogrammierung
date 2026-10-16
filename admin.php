<?php
/**
 * @file        admin.php
 * @layer       1 – Seite
 * @description Die Seite für Admins. Bisher eine Karte "Personal verwalten":
 *              oben ein Feld für die E-Mail-Adresse und der Knopf "Zum
 *              Personal machen", darunter eine Tabelle mit allen, die gerade
 *              Personal sind, je Zeile ein Knopf "Rolle entziehen".
 *
 *              NUR FUER ADMINS: Auth::nurFuerAdmins() schickt alle anderen
 *              weg, auch das Personal, bevor etwas ausgegeben wird. Admin
 *              wird man nur im Supabase-Dashboard.
 *
 *              KEIN LINK FUEHRT HIERHER - kein Menüpunkt, kein Knopf. Der
 *              Admin ruft die Adresse direkt auf (/admin.php). Geheim ist sie
 *              deshalb nicht: Geschützt wird die Seite über die Rolle.
 *
 *              Die Liste steht nicht im HTML, sondern kommt über
 *              assets/js/pages/admin.page.js aus api/personal.php. Die
 *              Tabelle sieht aus wie am Empfang und nutzt dessen Klassen aus
 *              assets/css/components/empfang.css.
 * @see         assets/js/pages/admin.page.js
 * @see         assets/css/components/admin.css
 * @see         docs/features/personal-verwalten.md
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

Auth::nurFuerAdmins();

$pageTitle  = 'Admin — SCHWITZKASTEN';
$pageScript = 'admin.page.js';
$activeNav  = 'konto';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="auth" id="admin-seite" data-login="<?= e(BASE_URL) ?>anmelden.php">
    <div class="wrap empfang-inner">

      <div>
        <p class="eyebrow">Nur für Admins</p>
        <h1 class="display auth-title">Admin.</h1>
      </div>

      <section class="auth-card" aria-labelledby="admin-titel">
        <div class="empfang-titelzeile">
          <h2 class="auth-heading" id="admin-titel">Personal verwalten</h2>
          <p class="empfang-anzahl" id="admin-anzahl" aria-live="polite"></p>
        </div>

        <p class="auth-hint">Das Personal öffnet den Empfang und bestätigt dort Konten. Die Person muss sich vorher ganz normal registriert haben. Die Änderung gilt nach ihrem nächsten Anmelden, spätestens nach fünf Minuten.</p>

        <form class="auth-form admin-formular" id="admin-formular">
          <label for="admin-email">E-Mail-Adresse</label>
          <div class="admin-eingabe">
            <input id="admin-email" name="email" type="email" required maxlength="249" autocomplete="off" placeholder="name@beispiel.de">
            <button class="button" type="submit">Zum Personal machen</button>
          </div>

          <p class="auth-message" id="admin-meldung" role="alert"></p>
        </form>

        <table class="empfang-tabelle">
          <thead>
            <tr>
              <th scope="col">Personal</th>
              <th scope="col"><span class="konto-visually-hidden">Aktionen</span></th>
            </tr>
          </thead>
          <!-- Füllt assets/js/pages/admin.page.js aus api/personal.php. -->
          <tbody id="admin-liste"></tbody>
        </table>

        <p class="auth-hint empfang-leer" id="admin-leer" hidden>Noch niemand gehört zum Personal.</p>

        <a class="auth-link empfang-zurueck" href="<?= e(BASE_URL) ?>mein-konto.php">Zurück zu Mein Konto</a>
      </section>

    </div>
  </main>

<?php
require ROOT_PATH . '/partials/footer.php';
