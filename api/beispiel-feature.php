<?php
/**
 * @file        api/beispiel-feature.php
 * @layer       4 – API-Endpunkt
 * @description VORLAGE, Schicht 4 von 6. Enthält absichtlich keinen Code.
 *              Vorherige Schicht: assets/js/services/beispiel-feature.js
 *              Nächste Schicht:   src/Repositories/BeispielFeatureRepository.php
 *
 *              Ein Endpunkt ist die Tür zwischen JavaScript und PHP.
 *              Er gibt IMMER JSON zurück, nie HTML.
 *
 * ============================================================================
 * WAS GEHÖRT IN DIESE SCHICHT
 * ============================================================================
 *
 *   - Prüfen, welche HTTP-Methode ankam (GET, POST ...)
 *   - Eingaben entgegennehmen und PRÜFEN: fehlt etwas, ist es zu lang,
 *     ist die E-Mail eine E-Mail, ist die id wirklich eine Zahl
 *   - Das passende Repository aufrufen
 *   - Ergebnis als JSON ausgeben
 *   - Im Fehlerfall den richtigen Statuscode setzen
 *         400 Eingabe falsch · 404 nicht gefunden · 405 Methode nicht erlaubt
 *         500 Serverfehler
 *
 *   Prüfen ist hier PFLICHT, auch wenn das Formular im Browser schon prüft.
 *   Die Prüfung im Browser ist Komfort für Nutzende. Verlassen kann man sich
 *   nur auf die Prüfung hier, weil ein Endpunkt auch ohne Browser
 *   aufgerufen werden kann.
 *
 * ============================================================================
 * WAS HIER NICHTS ZU SUCHEN HAT
 * ============================================================================
 *
 *   - SQL          -> gehört ins Repository (Schicht 5)
 *   - HTML         -> ein Endpunkt gibt nur JSON zurück
 *   - echo für Debug-Ausgaben. Jedes zusätzliche echo zerstört das JSON,
 *     und im Browser erscheint dann "Die Antwort war kein gültiges JSON".
 *
 * ============================================================================
 * TYPISCHER AUFBAU
 * ============================================================================
 *
 *   declare(strict_types=1);
 *   require __DIR__ . '/../src/bootstrap.php';
 *
 *   header('Content-Type: application/json; charset=utf-8');
 *
 *   try {
 *       $repository = new Repositories\BeispielFeatureRepository();
 *
 *       if ($_SERVER['REQUEST_METHOD'] === 'GET') {
 *           echo json_encode($repository->alleFinden());
 *           exit;
 *       }
 *
 *       if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 *           // Der Service schickt JSON, nicht ein klassisches Formular.
 *           // Deshalb wird der Rumpf gelesen und nicht $_POST benutzt.
 *           $daten = json_decode(file_get_contents('php://input'), true);
 *
 *           $name = trim($daten['name'] ?? '');
 *           if ($name === '') {
 *               http_response_code(400);
 *               echo json_encode(['error' => 'Bitte einen Namen angeben.']);
 *               exit;
 *           }
 *
 *           echo json_encode(['id' => $repository->anlegen($name)]);
 *           exit;
 *       }
 *
 *       http_response_code(405);
 *       echo json_encode(['error' => 'Methode nicht erlaubt.']);
 *
 *   } catch (Throwable $fehler) {
 *       // Die Originalmeldung kann Tabellennamen oder Pfade verraten und
 *       // geht deshalb nicht an den Browser, sondern nur ins Log.
 *       error_log((string) $fehler);
 *       http_response_code(500);
 *       echo json_encode(['error' => 'Interner Serverfehler.']);
 *   }
 *
 * @see         assets/js/services/api.js
 * @see         docs/ARCHITECTURE.md
 */
