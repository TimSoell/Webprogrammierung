<?php
/**
 * @file        impressum.php
 * @layer       1 – Seite
 * @description Impressum des SCHWITZKASTEN Athletic Club. Weist aus, dass
 *              das Studio ein Studienprojekt der DHBW Stuttgart ist.
 * @see         partials/footer.php
 * @see         datenschutz.php
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'Impressum — SCHWITZKASTEN Athletic Club';
$pageDescription = 'Impressum des SCHWITZKASTEN Athletic Club, eines Studienprojekts der DHBW Stuttgart.';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="legal-page">
    <div class="wrap legal-content">
      <p class="eyebrow">Rechtliches</p>
      <h1 class="display legal-title">Impressum</h1>

      <p class="legal-intro">Diese Website ist ein Studienprojekt der Dualen Hochschule Baden-Württemberg (DHBW). Den SCHWITZKASTEN Athletic Club gibt es nicht als echtes Fitnessstudio.</p>

      <section class="legal-section">
        <h2>Angaben gemäß § 5 DDG und § 18 Abs. 1 MStV</h2>
        <p><strong>SCHWITZKASTEN Athletic Club</strong><br>
        Studienprojekt der DHBW Stuttgart<br>
        Venloer Straße 213<br>
        50823 Köln<br>
        Deutschland</p>
      </section>

      <section class="legal-section">
        <h2>Kontakt</h2>
        <p>E-Mail: kontakt@schwitzkasten.example</p>
      </section>

      <section class="legal-section">
        <h2>Hochschule und Kurs</h2>
        <p>Entstanden im Fach Webprogrammierung im Kurs WWI2025C an der:</p>
        <p>Duale Hochschule Baden-Württemberg Stuttgart<br>
        Paulinenstraße 50<br>
        70178 Stuttgart</p>
        <p>Die DHBW ist nicht Betreiberin dieser Website und nicht für ihre Inhalte verantwortlich.</p>
      </section>

      <section class="legal-section">
        <h2>Hinweis zum Studienprojekt</h2>
        <p>Alle Angebote auf dieser Website sind erfunden und dienen nur zur Übung. Dazu gehören Mitgliedschaften, Tarife, Preise, Kurse, Probetrainings, Gutscheine und die AGB. Es kommen keine Verträge zustande, es werden keine Leistungen erbracht und keine Zahlungen angenommen.</p>
        <p>Bitte gib beim Ausprobieren keine echten persönlichen Daten ein und lade insbesondere keine echten Ausweise hoch. Mehr dazu steht in der <a href="<?= e(BASE_URL) ?>datenschutz.php">Datenschutzerklärung</a>.</p>
      </section>

      <section class="legal-section">
        <h2>Links zu anderen Websites</h2>
        <p>Diese Website verlinkt auf Angebote anderer Anbieter, etwa auf Facebook, Instagram, Google Play und den App Store. Für deren Inhalte ist allein der jeweilige Anbieter verantwortlich. Beim Setzen der Links waren keine Rechtsverstöße erkennbar. Wird uns ein Verstoß bekannt, entfernen wir den Link.</p>
      </section>

      <section class="legal-section">
        <h2>Bildnachweise und Marken</h2>
        <p>Die Fotos, die von images.unsplash.com geladen werden, stehen unter der Unsplash-Lizenz.</p>
        <p>Google Play, App Store, Facebook und Instagram sind Marken ihrer jeweiligen Inhaber. Diese Website steht mit ihnen in keiner Verbindung. Eine App des SCHWITZKASTEN Athletic Club gibt es nicht.</p>
      </section>

      <p class="legal-note">Stand: Oktober 2026.</p>
    </div>
  </main>

<?php require ROOT_PATH . '/partials/footer.php'; ?>
