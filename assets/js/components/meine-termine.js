/**
 * @file        assets/js/components/meine-termine.js
 * @layer       2 – Komponente
 * @description Der private Terminkalender im Profil: gebuchte Kurse und
 *              Probetrainings zusammen, nach Tagen sortiert, jeweils mit
 *              "Stornieren".
 *
 *              Eigene Komponente statt Teil von mein-konto.page.js, damit
 *              das Seitenskript nicht noch länger wird und zwei Leute an
 *              Konto und Kalender arbeiten können, ohne sich in derselben
 *              Datei zu treffen.
 * @see         mein-konto.php
 * @see         assets/js/services/kurstermine.js
 * @see         assets/js/services/probetrainings.js
 */

import { $ } from '../lib/dom.js';
import { nachTagen, tagUeberschrift } from '../lib/datum.js';
import { STUFEN } from './stufen.js';
import { meineLaden as kurseLaden, stornieren as kursStornieren } from '../services/kurstermine.js';
import { meineLaden as probetrainingsLaden, stornieren as probetrainingStornieren } from '../services/probetrainings.js';

/**
 * @typedef  {object} Eintrag
 * @property {'kurs'|'probetraining'} art
 * @property {number} id
 * @property {string} datum
 * @property {string} beginn
 * @property {string} ende
 * @property {string} titel
 * @property {string} zusatz   Coach und Stufe
 * @property {() => Promise<unknown>} stornieren
 */

/**
 * Element mit Klasse und Text - textContent, nie innerHTML.
 *
 * @param {string} tag
 * @param {string} klasse
 * @param {string} [text]
 * @returns {HTMLElement}
 */
function element(tag, klasse, text = '') {
  const el = document.createElement(tag);
  el.className = klasse;
  el.textContent = text;
  return el;
}

/**
 * Lädt beide Arten von Buchungen und bringt sie in eine gemeinsame Form.
 *
 * @returns {Promise<Eintrag[]>}  nach Datum und Uhrzeit sortiert
 */
async function eintraegeLaden() {
  const [{ buchungen }, { probetrainings }] = await Promise.all([kurseLaden(), probetrainingsLaden()]);

  const eintraege = [
    ...buchungen.map((b) => ({
      art: 'kurs',
      id: b.id,
      datum: b.datum,
      beginn: b.beginn,
      ende: b.ende,
      titel: `${b.programm} · ${b.format}`,
      zusatz: `mit ${b.coach} · ${STUFEN[b.stufe]}`,
      stornieren: () => kursStornieren(b.id),
    })),
    ...probetrainings.map((p) => ({
      art: 'probetraining',
      id: p.id,
      datum: p.datum,
      beginn: p.beginn,
      ende: p.ende,
      titel: 'Probetraining',
      zusatz: `mit ${p.coach} (${p.programm}) · ${STUFEN[p.stufe]}`,
      stornieren: () => probetrainingStornieren(p.id),
    })),
  ];

  return eintraege.sort((a, b) => `${a.datum} ${a.beginn}`.localeCompare(`${b.datum} ${b.beginn}`));
}

/**
 * Füllt die Karte "Meine Termine" in mein-konto.php.
 *
 * Erwartet #meine-termine und #termine-meldung. Fehlt eins davon,
 * passiert nichts.
 *
 * @returns {Promise<void>}
 */
export async function meineTermineAufbauen() {
  const liste = $('#meine-termine');
  const meldung = $('#termine-meldung');

  if (!liste || !meldung) {
    return;
  }

  const baseUrl = document.documentElement.dataset.baseUrl ?? '/';

  /**
   * @param {Eintrag} eintrag
   * @param {() => Promise<void>} neuLaden
   * @returns {HTMLElement}
   */
  const zeileBauen = (eintrag, neuLaden) => {
    const zeile = element('li', `mein-termin mein-termin--${eintrag.art}`);

    const text = element('div', 'mein-termin-text');
    text.append(element('span', 'mein-termin-titel', eintrag.titel), element('span', 'mein-termin-zusatz', eintrag.zusatz));

    const knopf = element('button', 'mein-termin-stornieren', 'Stornieren');
    knopf.type = 'button';
    knopf.setAttribute('aria-label', `${eintrag.titel} am ${tagUeberschrift(eintrag.datum)} stornieren`);

    knopf.addEventListener('click', async () => {
      // Kein Rückgängig - deshalb einmal nachfragen.
      if (!window.confirm(`${eintrag.titel} am ${tagUeberschrift(eintrag.datum)} um ${eintrag.beginn} Uhr wirklich stornieren?`)) {
        return;
      }

      knopf.disabled = true;
      meldung.textContent = '';

      try {
        await eintrag.stornieren();
        await neuLaden();
      } catch (fehler) {
        meldung.textContent = fehler.message;
        knopf.disabled = false;
      }
    });

    zeile.append(
      element('span', 'mein-termin-zeit', `${eintrag.beginn}–${eintrag.ende}`),
      element('span', 'mein-termin-art', eintrag.art === 'kurs' ? 'Kurs' : 'Probetraining'),
      text,
      knopf,
    );

    return zeile;
  };

  const anzeigen = async () => {
    const eintraege = await eintraegeLaden();

    if (eintraege.length === 0) {
      const leer = element('p', 'kalender-leer', 'Du hast noch keine Termine. Buch dir ein ');
      const probe = document.createElement('a');
      probe.href = `${baseUrl}probetraining.php`;
      probe.textContent = 'Probetraining';
      const kurse = document.createElement('a');
      kurse.href = `${baseUrl}index.php#programme`;
      kurse.textContent = 'einen Kurs';
      leer.append(probe, ' oder such dir ', kurse, ' aus.');
      liste.replaceChildren(leer);
      return;
    }

    liste.replaceChildren(...nachTagen(eintraege).map(({ datum, eintraege: amTag }) => {
      const tag = element('div', 'kalender-tag');
      const zeilen = element('ul', 'meine-termine');
      zeilen.append(...amTag.map((eintrag) => zeileBauen(eintrag, anzeigen)));
      tag.append(element('h3', 'kalender-datum', tagUeberschrift(datum)), zeilen);
      return tag;
    }));
  };

  try {
    await anzeigen();
  } catch (fehler) {
    meldung.textContent = fehler.message;
  }
}
