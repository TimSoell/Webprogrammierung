/**
 * @file        assets/js/components/auswahl-speicher.js
 * @layer       2 – Komponente
 * @description Die Auswahl von Level und Format im localStorage des Browsers.
 *              Das ist der Speicher für NICHT angemeldete Besucher; für
 *              angemeldete Mitglieder übernimmt services/auswahl.js.
 *
 *              Hier steht der Schlüsselaufbau genau einmal. Er wird an drei
 *              Stellen gebraucht - Programmseite, Übernahme beim Anmelden,
 *              Aufräumen beim Abmelden -, und drei Kopien wären drei
 *              Gelegenheiten für einen Tippfehler.
 *
 *              JEDER Zugriff steht in try/catch: Im privaten Fenster und bei
 *              blockierten Website-Daten wirft localStorage eine Ausnahme,
 *              statt null zu liefern. Die Seite muss auch dann laufen.
 * @see         assets/js/components/programm-details.js
 * @see         assets/js/services/auswahl.js
 */

/** Vorsilbe aller Schlüssel. Unter XAMPP teilen sich alle Projekte localhost. */
const PRAEFIX = 'schwitzkasten:programm:';

/**
 * @param {string} slug  'strength', 'move' oder 'fight'
 * @param {string} art   'level' oder 'format'
 * @returns {string}
 */
function schluessel(slug, art) {
  return `${PRAEFIX}${slug}:${art}`;
}

/**
 * Liest die zuletzt gewählte Option aus dem Browser.
 *
 * @param {string} slug
 * @param {string} art
 * @returns {string|null}  Titel der Option, oder null
 */
export function lokalLesen(slug, art) {
  try {
    return window.localStorage.getItem(schluessel(slug, art));
  } catch {
    return null;
  }
}

/**
 * Merkt die gewählte Option im Browser.
 *
 * @param {string} slug
 * @param {string} art
 * @param {string} titel
 * @returns {void}
 */
export function lokalSpeichern(slug, art, titel) {
  try {
    window.localStorage.setItem(schluessel(slug, art), titel);
  } catch {
    // Kein Speicher verfügbar. Die Auswahl gilt dann nur für diesen Besuch -
    // die Seite funktioniert weiter, deshalb passiert hier nichts.
  }
}

/**
 * Sammelt alles, was im Browser gemerkt ist, nach Programm gruppiert.
 *
 * Gebraucht beim Anmelden, um eine als Gast getroffene Auswahl ans Konto zu
 * übernehmen.
 *
 * @returns {Array<{slug: string, level: string|null, format: string|null}>}
 */
export function lokalAlleLesen() {
  const nachSlug = new Map();

  try {
    for (let i = 0; i < window.localStorage.length; i += 1) {
      const key = window.localStorage.key(i);

      if (!key?.startsWith(PRAEFIX)) {
        continue;
      }

      const [slug, art] = key.slice(PRAEFIX.length).split(':');

      if (!slug || (art !== 'level' && art !== 'format')) {
        continue;
      }

      const eintrag = nachSlug.get(slug) ?? { slug, level: null, format: null };
      eintrag[art] = window.localStorage.getItem(key);
      nachSlug.set(slug, eintrag);
    }
  } catch {
    return [];
  }

  return [...nachSlug.values()];
}
