<?php
/**
 * @file        agb.php
 * @layer       1 – Seite
 * @description Allgemeine Geschäftsbedingungen in generischer Kurzfassung.
 * @see         partials/footer.php
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'AGB — SCHWITZKASTEN Athletic Club';
$pageDescription = 'Allgemeine Geschäftsbedingungen des SCHWITZKASTEN Athletic Club.';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="legal-page">
    <div class="wrap legal-content">
      <p class="eyebrow">Rechtliches</p>
      <h1 class="display legal-title">Allgemeine<br><span>Geschäftsbedingungen</span></h1>

      <p class="legal-intro">Diese allgemeine Fassung informiert über die grundlegenden Regeln für die Nutzung des SCHWITZKASTEN Athletic Club.</p>

      <section class="legal-section">
        <h2>1. Geltungsbereich</h2>
        <p>Diese Bedingungen gelten für Mitgliedschaften, Buchungen und die Nutzung der Angebote und Räume des SCHWITZKASTEN Athletic Club.</p>
      </section>

      <section class="legal-section">
        <h2>2. Mitgliedschaft und Nutzung</h2>
        <p>Die Nutzung ist nur im Rahmen einer gültigen Mitgliedschaft oder einer bestätigten Buchung möglich. Zugangsdaten und Mitgliedskarten sind persönlich und dürfen nicht weitergegeben werden.</p>
      </section>

      <section class="legal-section">
        <h2>3. Buchungen und Stornierungen</h2>
        <p>Kurs- und Trainingsbuchungen können nur im vorgesehenen Zeitraum und nach den verfügbaren Plätzen vorgenommen werden. Einzelheiten zu Fristen werden bei der jeweiligen Buchung angezeigt.</p>
      </section>

      <section class="legal-section">
        <h2>4. Hausordnung</h2>
        <p>Die Anweisungen des Teams, die Hausordnung und die Hinweise zur Sicherheit sind zu beachten. Bei erheblichen Verstößen kann die Nutzung vorübergehend eingeschränkt werden.</p>
      </section>

      <section class="legal-section">
        <h2>5. Änderungen</h2>
        <p>Änderungen dieser Bedingungen werden rechtzeitig in geeigneter Form bekannt gegeben. Es gilt die jeweils aktuelle Fassung.</p>
      </section>

      <p class="legal-note">Stand: September 2026. Diese generische Vorlage ersetzt keine rechtliche Prüfung.</p>
    </div>
  </main>

<?php require ROOT_PATH . '/partials/footer.php'; ?>
