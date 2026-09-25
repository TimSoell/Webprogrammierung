<?php
/**
 * @file        impressum.php
 * @layer       1 – Seite
 * @description Generisches Impressum für den SCHWITZKASTEN Athletic Club.
 * @see         partials/footer.php
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'Impressum — SCHWITZKASTEN Athletic Club';
$pageDescription = 'Impressum des SCHWITZKASTEN Athletic Club.';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="legal-page">
    <div class="wrap legal-content">
      <p class="eyebrow">Rechtliches</p>
      <h1 class="display legal-title">Impressum</h1>

      <section class="legal-section">
        <h2>Angaben gemäß den gesetzlichen Vorgaben</h2>
        <p><strong>SCHWITZKASTEN Athletic Club</strong><br>
        Venloer Straße 213<br>
        50823 Köln<br>
        Deutschland</p>
      </section>

      <section class="legal-section">
        <h2>Kontakt</h2>
        <p>E-Mail: kontakt@schwitzkasten.example<br>
        Telefon: +49 (0) 221 000000</p>
      </section>

      <section class="legal-section">
        <h2>Verantwortlich für den Inhalt</h2>
        <p>Verantwortlich für die Inhalte dieser Website ist der oben genannte Betreiber.</p>
      </section>

      <p class="legal-note">Stand: September 2026. Die Kontaktangaben müssen vor einer Veröffentlichung mit den echten Betreiberangaben ersetzt und rechtlich geprüft werden.</p>
    </div>
  </main>

<?php require ROOT_PATH . '/partials/footer.php'; ?>
