<?php
/**
 * @file        api/personal.php
 * @layer       4 – API-Endpunkt
 * @description Wer gehört zum Personal - und das Vergeben und Entziehen
 *              dieser Rolle. Für die Seite admin.php.
 *
 *                GET               -> { personal: [...] }
 *                POST   { email }  -> 200 { personal: [...] }, Konto ist Personal
 *                DELETE { email }  -> 200 { personal: [...] }, Rolle entzogen
 *
 *              NUR FUER ADMINS (Rolle ADMIN), sonst 401 bzw. 403 - auch das
 *              Personal selbst bekommt 403. admin.php sperrt die Seite
 *              ebenfalls, verbindlich ist aber diese Prüfung: Die Seite lässt
 *              sich umgehen, der Endpunkt nicht.
 *
 *              Hier lässt sich nur die Rolle Personal vergeben. Admin wird
 *              man nur im Supabase-Dashboard. Gespeichert wird über die
 *              Login-Bibliothek in src/Auth.php - keine eigene Tabelle. Siehe
 *              docs/decisions/ADR-0025-admin-verwaltet-personal.md
 * @see         assets/js/services/personal.js
 * @see         src/Auth.php
 * @see         src/Repositories/MitgliedRepository.php
 * @see         docs/features/personal-verwalten.md
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Repositories\MitgliedRepository;

header('Content-Type: application/json; charset=utf-8');

try {
    if (!Auth::instanz()->isLoggedIn()) {
        Api::fehler(401, 'Du bist nicht angemeldet.');
    }
    if (!Auth::istAdmin()) {
        Api::fehler(403, 'Das Personal verwalten nur Admins.');
    }

    $mitglieder = new MitgliedRepository();
    $methode    = $_SERVER['REQUEST_METHOD'];

    if ($methode === 'GET') {
        Api::antworten(['personal' => $mitglieder->personalFinden()]);
    }

    if ($methode !== 'POST' && $methode !== 'DELETE') {
        Api::fehler(405, 'Methode nicht erlaubt.');
    }

    $email = Auth::emailNormalisieren(Api::text(Api::eingabe(), 'email'));

    if ($email === null) {
        Api::fehler(400, 'Bitte gib eine gültige E-Mail-Adresse ein.');
    }

    $istPersonal = in_array($email, array_column($mitglieder->personalFinden(), 'email'), true);

    if ($methode === 'POST') {
        if ($istPersonal) {
            Api::fehler(409, 'Dieses Konto gehört schon zum Personal.');
        }
        if (!Auth::personalErnennen($email)) {
            Api::fehler(404, 'Mit dieser E-Mail-Adresse gibt es kein Konto. Die Person muss sich zuerst registrieren.');
        }
    }

    // Steht das Konto nicht mehr in der Liste, hat meist gerade ein anderer
    // Admin schneller geklickt.
    if ($methode === 'DELETE' && (!$istPersonal || !Auth::personalEntziehen($email))) {
        Api::fehler(409, 'Dieses Konto gehört nicht mehr zum Personal.');
    }

    Api::antworten(['personal' => $mitglieder->personalFinden()]);

} catch (Throwable $fehler) {
    error_log((string) $fehler);
    Api::fehler(500, 'Interner Serverfehler.');
}
