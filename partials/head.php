<?php
/**
 * @file        partials/head.php
 * @layer       1 – Seite (Baustein)
 * @description Der obere Teil jeder Seite: <head> mit Titel, Schriften,
 *              Stylesheet und Skripten, plus das öffnende <body>.
 *
 *              Eine Seite steuert diesen Baustein über drei Variablen,
 *              die sie VOR dem require setzt:
 *
 *                  $pageTitle        Titel im Browser-Tab   (Pflicht)
 *                  $pageDescription  Text für Suchmaschinen (optional)
 *                  $pageScript       Dateiname in assets/js/pages/ (optional)
 *                  $pageUsesMap      Leaflet für interaktive Karten (optional)
 *                  $pageHasPreloader Ladebildschirm ausgeben (optional, nur Startseite)
 *
 *              Beispiel siehe index.php.
 * @see         partials/README.md
 */

declare(strict_types=1);

// Schutz davor, dass die Datei versehentlich direkt im Browser
// aufgerufen wird - dann wäre BASE_URL nicht definiert.
if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Dieser Baustein kann nicht einzeln aufgerufen werden.');
}
?>
<!doctype html>
<html lang="de" data-base-url="<?= e(BASE_URL) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle ?? 'SCHWITZKASTEN — Athletic Club') ?></title>
  <meta name="description" content="<?= e($pageDescription ?? 'SCHWITZKASTEN Athletic Club — Dein neues Fitnessstudio in Köln.') ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;500;600;700;800;900&family=DM+Sans:wght@400;500;700&display=swap">

  <!-- Eine einzige CSS-Datei. Was sie lädt, steht in assets/css/main.css. -->
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/css/main.css">

<?php if (!empty($pageUsesMap)): ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>

  <!-- type="module" bedeutet: wird erst nach dem Aufbau der Seite ausgeführt.
       Deshalb dürfen die Skripte hier oben stehen und nicht am Seitenende. -->
  <script type="module" src="<?= e(BASE_URL) ?>assets/js/main.js"></script>
<?php if (!empty($pageScript)): ?>
  <script type="module" src="<?= e(BASE_URL) ?>assets/js/pages/<?= e($pageScript) ?>"></script>
<?php endif; ?>
</head>
<body>
<?php if (!empty($pageHasPreloader)): ?>
  <div class="preloader" data-preloader role="status" aria-label="Seite wird geladen">
    <div class="preloader-inner">
      <div class="preloader-mark">
        <img src="<?= e(BASE_URL) ?>assets/img/schwitzkasten-logo.png" alt="SCHWITZKASTEN Athletic Club">
      </div>
      <p class="preloader-label">Athletic Club</p>
    </div>
  </div>
<?php endif; ?>
