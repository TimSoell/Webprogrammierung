<?php
/**
 * @file        partials/header.php
 * @layer       1 – Seite (Baustein)
 * @description Die Kopfzeile mit Logo, Navigation und Aktionsbutton.
 *              Erscheint auf jeder Seite - deshalb steht sie genau hier
 *              und nicht in den einzelnen Seiten.
 *
 *              NEUER MENÜPUNKT? Nur das Array $navItems unten ergänzen.
 *              Dadurch bleibt die Navigation auf allen Seiten automatisch
 *              gleich, und niemand kann eine Seite vergessen.
 *
 *              Eine Seite kann $activeNav setzen (z. B. 'programme'), dann
 *              wird der passende Punkt hervorgehoben.
 * @see         assets/css/03-layout.css
 * @see         assets/js/components/nav.js
 */

declare(strict_types=1);

if (!defined('BASE_URL')) {
    http_response_code(403);
    exit('Dieser Baustein kann nicht einzeln aufgerufen werden.');
}

/**
 * Die Menüpunkte der Hauptnavigation.
 * Schlüssel = Kennung für $activeNav, Wert = [Beschriftung, Ziel].
 */
$navItems = [
  'probetraining' => ['Probetraining buchen', 'index.php#mitgliedschaft'],
  'kundenlogin'   => ['Kundenlogin', 'index.php#kundenlogin'],
  'locations'     => ['Locations', 'index.php#locations'],
];
?>
<header>
  <nav class="nav wrap" aria-label="Hauptnavigation">
    <a class="logo" href="<?= e(BASE_URL) ?>index.php" aria-label="Schwitzkasten Athletic Club – Startseite">
      <img src="<?= e(BASE_URL) ?>assets/img/schwitzkasten-logo.png" alt="Schwitzkasten Athletic Club">
    </a>

    <div class="nav-links" id="nav-links">
<?php foreach ($navItems as $key => [$label, $target]): ?>
      <a href="<?= e(BASE_URL . $target) ?>"<?= ($activeNav ?? '') === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
    </div>

    <a class="button" href="<?= e(BASE_URL) ?>index.php#mitgliedschaft">Mitglied werden</a>

    <button class="menu-button" id="menu-button" type="button" aria-label="Menü öffnen" aria-expanded="false" aria-controls="nav-links">MENU</button>
  </nav>
</header>
