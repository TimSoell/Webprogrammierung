/**
 * @file        assets/js/components/cookie-banner.js
 * @layer       2 – Komponente
 * @description Fragt nach der Einwilligung für Google Analytics und lädt
 *              gtag.js erst, wenn zugestimmt wurde. Vorher geht keine
 *              einzige Anfrage an Google.
 *
 *              Die Entscheidung liegt im localStorage des Browsers. Über den
 *              Link „Cookie-Einstellungen“ im Footer lässt sich die Leiste
 *              erneut öffnen und die Entscheidung ändern. Wer eine Zustimmung
 *              widerruft, dessen _ga-Cookies werden gelöscht und die Seite
 *              wird neu geladen, damit das bereits geladene gtag.js verschwindet.
 * @see         partials/cookie-banner.php
 * @see         assets/css/components/cookie-banner.css
 * @see         docs/features/cookie-banner.md
 */

/** Mess-ID der Google-Analytics-Property. */
const GA_ID = 'G-723B66WSY1';

/** Schlüssel im localStorage. Wert ist 'zugestimmt' oder 'abgelehnt'. */
const MERKER = 'schwitzkasten:cookie-einwilligung';

/**
 * Liest die gespeicherte Entscheidung. Bei blockiertem Speicher (privates
 * Fenster) gibt es keine - dann erscheint die Leiste bei jedem Aufruf.
 *
 * @returns {'zugestimmt'|'abgelehnt'|null}
 */
function entscheidungLesen() {
  try {
    return window.localStorage.getItem(MERKER);
  } catch {
    return null;
  }
}

/**
 * Speichert die Entscheidung, sofern der Browser es zulässt.
 *
 * @param {'zugestimmt'|'abgelehnt'} wert
 * @returns {void}
 */
function entscheidungSpeichern(wert) {
  try {
    window.localStorage.setItem(MERKER, wert);
  } catch {
    // Kein Speicher verfügbar - die Entscheidung gilt nur für diesen Aufruf.
  }
}

/**
 * Bindet gtag.js ein und startet die Messung. Entspricht dem Snippet, das
 * Google vorgibt, nur eben erst nach der Zustimmung.
 *
 * @returns {void}
 */
function analyticsLaden() {
  window.dataLayer = window.dataLayer || [];
  window.gtag = function gtag() {
    window.dataLayer.push(arguments);
  };
  window.gtag('js', new Date());
  window.gtag('config', GA_ID);

  const skript = document.createElement('script');
  skript.async = true;
  skript.src = `https://www.googletagmanager.com/gtag/js?id=${GA_ID}`;
  document.head.append(skript);
}

/**
 * Löscht die Cookies, die Google Analytics gesetzt hat (_ga, _ga_XXXX).
 * GA setzt sie auf die oberste mögliche Domain, deshalb wird jede Stufe
 * des Hostnamens probiert.
 *
 * @returns {void}
 */
function analyticsCookiesLoeschen() {
  const teile = window.location.hostname.split('.');
  const domains = [''];
  for (let i = 0; i < teile.length - 1; i++) {
    domains.push(`; domain=.${teile.slice(i).join('.')}`);
  }

  document.cookie.split(';')
    .map((eintrag) => eintrag.split('=')[0].trim())
    .filter((name) => name.startsWith('_ga'))
    .forEach((name) => {
      domains.forEach((domain) => {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/${domain}`;
      });
    });
}

/**
 * Initialisiert die Einwilligungsleiste. Lädt Analytics sofort, wenn früher
 * schon zugestimmt wurde, und zeigt die Leiste, wenn noch nichts entschieden ist.
 *
 * @returns {void}
 */
export function initCookieBanner() {
  const banner = document.querySelector('[data-cookie-banner]');

  if (!banner) {
    return;
  }

  const entscheidung = entscheidungLesen();

  if (entscheidung === 'zugestimmt') {
    analyticsLaden();
  } else if (entscheidung === null) {
    banner.hidden = false;
  }

  banner.querySelector('[data-cookie-zustimmen]').addEventListener('click', () => {
    entscheidungSpeichern('zugestimmt');
    banner.hidden = true;
    if (!window.gtag) {
      analyticsLaden();
    }
  });

  banner.querySelector('[data-cookie-ablehnen]').addEventListener('click', () => {
    const warZugestimmt = Boolean(window.gtag);
    entscheidungSpeichern('abgelehnt');
    banner.hidden = true;
    if (warZugestimmt) {
      analyticsCookiesLoeschen();
      window.location.reload();
    }
  });

  document.querySelectorAll('[data-cookie-einstellungen]').forEach((knopf) => {
    knopf.addEventListener('click', () => {
      banner.hidden = false;
      banner.querySelector('[data-cookie-zustimmen]').focus();
    });
  });
}
