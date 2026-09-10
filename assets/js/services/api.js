/**
 * @file        assets/js/services/api.js
 * @layer       3 – Service (Unterbau)
 * @description Der zentrale Weg, auf dem das Frontend mit dem Backend spricht.
 *
 *              Diese Datei ist das Gegenstück zu src/Database.php: so wie dort
 *              nur EINE Stelle die Datenbank öffnet, ruft hier nur EINE Stelle
 *              fetch() auf. Alles andere geht durch getJson() und postJson().
 *
 *              Vorteil: Fehlerbehandlung, JSON-Auswertung und die Basis-URL
 *              stehen an einem Ort. Wenn sich daran etwas ändert, ändert es
 *              sich für das ganze Projekt auf einmal.
 *
 *              WICHTIG: Seiten und Komponenten importieren diese Datei NICHT
 *              direkt. Sie sprechen immer mit einem Service
 *              (z. B. services/kurse.js), und der benutzt dann getJson().
 * @see         docs/ARCHITECTURE.md
 * @see         src/Database.php
 */

/**
 * Basis-URL des Projekts, z. B. '/Webprogrammierung/'.
 *
 * Kommt aus dem data-base-url Attribut am <html>-Tag, das partials/head.php
 * setzt. Ohne das würde ein fetch('api/kurse.php') auf einer Unterseite wie
 * /programme/strength.php fälschlich /programme/api/kurse.php ansteuern.
 *
 * @type {string}
 */
const BASE_URL = document.documentElement.dataset.baseUrl ?? '/';

/**
 * Fehler, den getJson() und postJson() werfen, wenn etwas schiefgeht.
 *
 * Eine eigene Fehlerklasse erlaubt es aufrufendem Code, gezielt auf
 * API-Fehler zu reagieren (catch (e) { if (e instanceof ApiError) ... }).
 */
export class ApiError extends Error {
  /**
   * @param {string} message  Für Menschen lesbare Meldung
   * @param {number} status   HTTP-Statuscode, 0 wenn die Anfrage gar nicht ankam
   */
  constructor(message, status) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }
}

/**
 * Wertet die Antwort aus und wirft bei Fehlern einen ApiError.
 *
 * @param {Response} response
 * @returns {Promise<unknown>}
 * @throws {ApiError}
 */
async function parse(response) {
  let data = null;

  try {
    data = await response.json();
  } catch {
    // Antwort war kein JSON - meistens eine PHP-Fehlermeldung als HTML.
    throw new ApiError('Die Antwort des Servers war kein gültiges JSON.', response.status);
  }

  if (!response.ok) {
    // Unsere Endpunkte liefern im Fehlerfall { "error": "..." }.
    const message = typeof data?.error === 'string' ? data.error : 'Unbekannter Fehler.';
    throw new ApiError(message, response.status);
  }

  return data;
}

/**
 * Holt Daten vom Server.
 *
 * @param {string} path                 Pfad ohne führenden Slash, z. B. 'api/kurse.php'
 * @param {Record<string,string|number>} [params]  Wird als Query-String angehängt
 * @returns {Promise<unknown>}
 * @throws {ApiError}
 */
export async function getJson(path, params = {}) {
  const url = new URL(BASE_URL + path, window.location.origin);

  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.set(key, String(value));
  });

  let response;

  try {
    response = await fetch(url, { headers: { Accept: 'application/json' } });
  } catch {
    throw new ApiError('Server nicht erreichbar. Läuft Apache in XAMPP?', 0);
  }

  return parse(response);
}

/**
 * Schickt Daten an den Server.
 *
 * @param {string} path    Pfad ohne führenden Slash, z. B. 'api/kurse.php'
 * @param {object} body    Wird als JSON übertragen
 * @returns {Promise<unknown>}
 * @throws {ApiError}
 */
export async function postJson(path, body) {
  const url = new URL(BASE_URL + path, window.location.origin);

  let response;

  try {
    response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify(body),
    });
  } catch {
    throw new ApiError('Server nicht erreichbar. Läuft Apache in XAMPP?', 0);
  }

  return parse(response);
}
