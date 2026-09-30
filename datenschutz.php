<?php
/**
 * @file        datenschutz.php
 * @layer       1 – Seite
 * @description Generische Datenschutzerklärung für den SCHWITZKASTEN Athletic Club.
 * @see         partials/footer.php
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'Datenschutz — SCHWITZKASTEN Athletic Club';
$pageDescription = 'Datenschutzhinweise des SCHWITZKASTEN Athletic Club.';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="legal-page">
    <div class="wrap legal-content">
      <p class="eyebrow">Rechtliches</p>
      <h1 class="display legal-title">Datenschutz</h1>

      <section class="legal-section">
        <h2>1. Verantwortliche Stelle</h2>
        <p>Verantwortlich für die Verarbeitung personenbezogener Daten ist der SCHWITZKASTEN Athletic Club, Venloer Straße 213, 50823 Köln.</p>
      </section>

      <section class="legal-section">
        <h2>2. Besuch der Website</h2>
        <p>Beim Aufruf dieser Website können technische Daten verarbeitet werden, die für den sicheren Betrieb, die Darstellung und die Fehleranalyse erforderlich sind.</p>
      </section>

      <section class="legal-section">
        <h2>3. Google Analytics</h2>
        <p>Mit deiner Einwilligung nutzen wir Google Analytics, einen Webanalysedienst der Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland. Google Analytics setzt Cookies, mit denen ausgewertet wird, wie die Website genutzt wird. Dabei können Daten auch an Server von Google in den USA übertragen werden. Rechtsgrundlage ist deine Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG.</p>
        <p>Ohne Einwilligung wird Google Analytics nicht geladen. Du kannst deine Einwilligung jederzeit über den Link „Cookie-Einstellungen“ im Footer widerrufen. Deine Entscheidung wird im Speicher deines Browsers (localStorage) abgelegt.</p>
      </section>

      <section class="legal-section">
        <h2>4. Kontakt und Konto</h2>
        <p>Wenn du uns kontaktierst oder ein Konto nutzt, verarbeiten wir die von dir übermittelten Angaben zur Bearbeitung deiner Anfrage und zur Bereitstellung der gebuchten Funktionen.</p>
      </section>

      <section class="legal-section">
        <h2>5. Weitergabe und Speicherdauer</h2>
        <p>Daten werden nur weitergegeben, wenn dies für den Betrieb erforderlich ist, eine gesetzliche Pflicht besteht oder du eingewilligt hast. Wir speichern Daten nur so lange, wie es für den jeweiligen Zweck oder gesetzliche Pflichten notwendig ist.</p>
      </section>

      <section class="legal-section">
        <h2>6. Deine Rechte</h2>
        <p>Du kannst im Rahmen der gesetzlichen Voraussetzungen Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung und Datenübertragbarkeit verlangen. Außerdem kannst du einer Verarbeitung widersprechen.</p>
      </section>

      <p class="legal-note">Stand: September 2026. Diese generische Vorlage ersetzt keine rechtliche Prüfung und muss an die tatsächlich eingesetzten Dienste angepasst werden.</p>
    </div>
  </main>

<?php require ROOT_PATH . '/partials/footer.php'; ?>
