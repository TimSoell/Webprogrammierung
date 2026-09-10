<?php
/**
 * @file        beispiel-feature.php
 * @layer       1 – Seite
 * @description VORLAGE. Diese Datei enthält absichtlich keinen Code, sondern
 *              beschreibt, was in Schicht 1 gehört. Sie ist von keiner
 *              Navigation aus verlinkt und darf gelöscht oder umbenannt
 *              werden, sobald ihr euer erstes echtes Feature baut.
 *
 *              Das Beispiel-Feature besteht aus sechs Dateien, eine pro
 *              Schicht. Lest sie in dieser Reihenfolge:
 *
 *                1. beispiel-feature.php                            <- ihr seid hier
 *                2. assets/js/pages/beispiel-feature.page.js
 *                3. assets/js/services/beispiel-feature.js
 *                4. api/beispiel-feature.php
 *                5. src/Repositories/BeispielFeatureRepository.php
 *                6. database/schema.sql
 *
 * ============================================================================
 * WAS GEHÖRT IN DIESE SCHICHT
 * ============================================================================
 *
 *   - Das HTML-Grundgerüst der Seite: Überschriften, Abschnitte, Formulare.
 *   - Die drei Seiten-Variablen ganz oben:
 *         $pageTitle, $pageDescription, $pageScript
 *   - Die require-Aufrufe für bootstrap, head, header und footer.
 *   - Leere Container mit einer id, die das Seitenskript später füllt,
 *     zum Beispiel:  <div id="kursliste"></div>
 *   - Ausgaben, die schon serverseitig feststehen, IMMER durch e() geschützt:
 *         <h1><?= e($ueberschrift) ?></h1>
 *
 * ============================================================================
 * WAS HIER NICHTS ZU SUCHEN HAT
 * ============================================================================
 *
 *   - <style>-Blöcke oder style="..."   -> gehört nach assets/css/components/
 *   - <script>-Blöcke                   -> gehört nach assets/js/pages/
 *   - SQL oder Datenbankzugriffe        -> gehört nach src/Repositories/
 *   - Berechnungen und Geschäftslogik   -> gehört nach src/
 *
 *   Faustregel: Wenn ihr in dieser Datei ein "=" tippt, das keine
 *   HTML-Eigenschaft ist, ist es vermutlich an der falschen Stelle.
 *
 * ============================================================================
 * AUFBAU EINER ECHTEN SEITE
 * ============================================================================
 *
 *   declare(strict_types=1);
 *   require __DIR__ . '/src/bootstrap.php';       // in Unterordnern: '/../src/...'
 *
 *   $pageTitle       = 'Kurse — BASELINE';
 *   $pageDescription = 'Alle Kurse im Überblick.';
 *   $pageScript      = 'kurse.page.js';           // optional
 *   $activeNav       = 'kurse';                   // optional, hebt den Menüpunkt hervor
 *
 *   require ROOT_PATH . '/partials/head.php';
 *   require ROOT_PATH . '/partials/header.php';
 *
 *   ... hier euer HTML ...
 *
 *   require ROOT_PATH . '/partials/footer.php';   // IMMER als letztes
 *
 *   Ein vollständiges Beispiel dafür ist index.php.
 *
 * @see         docs/ARCHITECTURE.md
 * @see         index.php
 * @see         assets/js/pages/beispiel-feature.page.js
 */
