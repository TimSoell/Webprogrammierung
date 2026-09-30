/**
 * @file        assets/js/components/bewertungen.js
 * @layer       2 – Komponente
 * @description Die Bewertungen auf der Startseite: Bildkacheln im Abschnitt
 *              #bewertungen und das Fenster "Alle Bewertungen" mit
 *              Durchschnitt, allen Karten und - für Angemeldete - dem
 *              Formular zum Bewerten.
 *
 *              Eine eigene Datei statt Code in index.page.js, weil Kacheln,
 *              Fenster und Formular zusammen zu lang für das Seitenskript
 *              sind. Aufgerufen wird sie von index.page.js. Öffnen und
 *              Schließen des Fensters übernimmt modal.js, wie beim
 *              Kurskalender.
 *
 *              Fehlt das Bild einer Person, treten die Initialen an seine
 *              Stelle - wie bei den Coaches in programm-details.js.
 *
 *              KEIN innerHTML: Namen und Texte kommen aus einem Formular,
 *              das jeder mit Konto ausfüllen kann.
 * @see         index.php
 * @see         assets/js/services/bewertungen.js
 * @see         assets/css/components/bewertungen.css
 */

import { $, $$ } from '../lib/dom.js';
import { monatUeberschrift } from '../lib/datum.js';
import { initModal } from './modal.js';
import { bewertungAbgeben, bewertungenLaden } from '../services/bewertungen.js';

/** So viele Bewertungen stehen als Kachel auf der Startseite. */
const KACHELN = 3;

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
 * Fünf Sterne, davon so viele gefüllt wie vergeben. Screenreader lesen
 * nur die Zahl vor, nicht fünfmal "Stern".
 *
 * @param {number} anzahl  1 bis 5 in halben Schritten; halbe gibt es nur
 *                         beim Durchschnitt
 * @returns {HTMLElement}
 */
function sterneBauen(anzahl) {
  const sterne = element('span', 'bewertung-sterne');
  sterne.setAttribute('role', 'img');
  sterne.setAttribute('aria-label', `${anzahl.toLocaleString('de-DE')} von 5 Sternen`);

  for (let stern = 1; stern <= 5; stern += 1) {
    let klasse = 'bewertung-stern';

    if (stern <= anzahl) {
      klasse += ' bewertung-stern--voll';
    } else if (stern - 0.5 === anzahl) {
      klasse += ' bewertung-stern--halb';
    }

    sterne.append(element('span', klasse, '★'));
  }

  return sterne;
}

/**
 * "Mitglied" oder "Kein Mitglied" als Plakette.
 *
 * @param {boolean} mitglied
 * @returns {HTMLElement}
 */
function statusBauen(mitglied) {
  return mitglied
    ? element('span', 'bewertung-status bewertung-status--mitglied', 'Mitglied')
    : element('span', 'bewertung-status', 'Kein Mitglied');
}

/**
 * Das Bild der Person, ersatzweise ihre Initialen. Dieselbe Datei erscheint
 * groß auf der Kachel und rund neben der Karte im Fenster - nur die Klasse
 * unterscheidet sich.
 *
 * alt bleibt leer: Der Name steht direkt daneben, ein Screenreader würde
 * ihn sonst zweimal vorlesen.
 *
 * @param {import('../services/bewertungen.js').Bewertung} bewertung
 * @param {string} klasse   'bewertung-kachel-bild' oder 'bewertung-bild'
 * @param {string} baseUrl  Präfix aus data-base-url am <html>-Tag
 * @returns {HTMLElement}
 */
function bildBauen(bewertung, klasse, baseUrl) {
  const ersatz = element(
    'span',
    `${klasse} ${klasse}--ersatz`,
    bewertung.name.split(' ').map((teil) => teil.charAt(0)).join('').slice(0, 2),
  );

  if (bewertung.bild === null) {
    return ersatz;
  }

  const bild = element('img', klasse);
  bild.src = baseUrl + bewertung.bild;
  bild.alt = '';
  bild.loading = 'lazy';
  bild.addEventListener('error', () => bild.replaceWith(ersatz));

  return bild;
}

/**
 * Kachel für die Startseite: Bild über die ganze Fläche, darauf Sterne,
 * Text, Name und Status.
 *
 * @param {import('../services/bewertungen.js').Bewertung} bewertung
 * @param {string} baseUrl
 * @returns {HTMLElement}
 */
function kachelBauen(bewertung, baseUrl) {
  const person = element('div', 'bewertung-kachel-person');
  person.append(element('p', 'bewertung-kachel-name', bewertung.name), statusBauen(bewertung.mitglied));

  const inhalt = element('div', 'bewertung-kachel-inhalt');
  inhalt.append(sterneBauen(bewertung.sterne), element('p', 'bewertung-kachel-text', bewertung.text), person);

  const kachel = element('li', 'bewertung-kachel');
  kachel.append(bildBauen(bewertung, 'bewertung-kachel-bild', baseUrl), inhalt);

  return kachel;
}

/**
 * Karte für das Fenster "Alle Bewertungen".
 *
 * @param {import('../services/bewertungen.js').Bewertung} bewertung
 * @param {string} baseUrl
 * @returns {HTMLElement}
 */
function karteBauen(bewertung, baseUrl) {
  const person = element('div', 'bewertung-person');
  person.append(element('p', 'bewertung-name', bewertung.name), statusBauen(bewertung.mitglied));

  const kopf = element('div', 'bewertung-kopf');
  kopf.append(bildBauen(bewertung, 'bewertung-bild', baseUrl), person);

  const wertung = element('div', 'bewertung-wertung');
  wertung.append(
    sterneBauen(bewertung.sterne),
    element('span', 'bewertung-datum', monatUeberschrift(bewertung.datum.slice(0, 7))),
  );

  const karte = element('li', 'bewertung');
  karte.append(kopf, wertung, element('p', 'bewertung-text', bewertung.text));

  return karte;
}

/**
 * Richtet Kacheln, Fenster und Formular ein. Steigt ohne Fehler aus, wenn
 * die Seite keinen Bewertungsabschnitt hat.
 *
 * @returns {void}
 */
export function initBewertungen() {
  const kacheln = $('#bewertungen-kacheln');

  if (!kacheln) {
    return;
  }

  const baseUrl = document.documentElement.dataset.baseUrl ?? '/';
  const meldung = $('#bewertungen-meldung');
  const fuss = $('#bewertungen-fuss');
  const fussSchnitt = $('#bewertungen-fuss-schnitt');
  const schnitt = $('#bewertungen-schnitt');
  const liste = $('#bewertungen-liste');

  initModal({
    modalId: 'bewertungen-modal',
    openId: 'bewertungen-oeffnen',
    closeId: 'bewertungen-schliessen',
  });

  /**
   * Durchschnitt und Anzahl - kurz unter den Kacheln, groß im Fenster.
   *
   * @param {import('../services/bewertungen.js').Bewertung[]} bewertungen  mindestens eine
   * @returns {void}
   */
  const schnittZeigen = (bewertungen) => {
    const durchschnitt = bewertungen.reduce((summe, b) => summe + b.sterne, 0) / bewertungen.length;
    const halbeSterne = Math.round(durchschnitt * 2) / 2;
    const wert = durchschnitt.toLocaleString('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
    const vonMitgliedern = bewertungen.filter((b) => b.mitglied).length;

    fussSchnitt.replaceChildren(
      sterneBauen(halbeSterne),
      element('span', '', `${wert} von 5 · ${bewertungen.length} Bewertungen`),
    );

    const angaben = element('div', 'bewertungen-schnitt-angaben');
    angaben.append(
      sterneBauen(halbeSterne),
      element('p', 'bewertungen-schnitt-anzahl', `${bewertungen.length} Bewertungen · ${vonMitgliedern} davon von Mitgliedern`),
    );

    schnitt.replaceChildren(element('strong', 'bewertungen-schnitt-wert', wert), angaben);
    schnitt.hidden = false;
  };

  /**
   * Lädt alle Bewertungen und zeigt sie an - beim Öffnen der Seite und
   * nach dem Abschicken einer neuen.
   *
   * Als Kachel stehen zuerst Bewertungen mit Bild: Die Kacheln leben vom
   * Foto, und Bilder haben nur die Beispiele aus seed.sql.
   *
   * @returns {Promise<void>}
   */
  const laden = async () => {
    try {
      const bewertungen = await bewertungenLaden();
      const mitBild = bewertungen.filter((b) => b.bild !== null);
      const ohneBild = bewertungen.filter((b) => b.bild === null);

      kacheln.replaceChildren(...[...mitBild, ...ohneBild].slice(0, KACHELN).map((b) => kachelBauen(b, baseUrl)));
      liste.replaceChildren(...bewertungen.map((b) => karteBauen(b, baseUrl)));

      if (bewertungen.length === 0) {
        meldung.textContent = 'Noch keine Bewertungen.';
      } else {
        schnittZeigen(bewertungen);
        meldung.hidden = true;
      }

      // Auch ohne Bewertungen: Im Fenster steht das Formular für die erste.
      fuss.hidden = false;
    } catch {
      // Die Bewertungen sind Beiwerk - eine Zeile Text reicht, der Rest
      // der Startseite bleibt benutzbar. Wie beim Auslastungsdiagramm.
      meldung.textContent = 'Die Bewertungen sind gerade nicht abrufbar.';
    }
  };

  // --- Formular, nur für Angemeldete ----------------------------------------
  const formular = $('#bewertung-formular');

  if (formular) {
    const wahl = $('.bewertung-wahl', formular);
    const formularMeldung = $('#bewertung-formular-meldung');
    const knopf = $('button[type="submit"]', formular);

    // Füllt alle Sterne bis zum gewählten. Die Radio-Buttons selbst sind
    // unsichtbar, zu sehen sind nur ihre Labels. RadioNodeList.value ist
    // '', solange nichts gewählt ist -> 0.
    wahl.addEventListener('change', () => {
      const gewaehlt = Number(formular.elements.sterne.value);

      $$('label', wahl).forEach((label, index) => {
        label.classList.toggle('bewertung-wahl-stern--voll', index < gewaehlt);
      });
    });

    formular.addEventListener('submit', async (ereignis) => {
      ereignis.preventDefault();

      const sterne = Number(formular.elements.sterne.value);
      const text = formular.elements.text.value.trim();

      // Dieselben Prüfungen macht api/bewertungen.php. Hier sind sie nur
      // Komfort, damit niemand auf die Antwort des Servers warten muss.
      if (sterne === 0) {
        formularMeldung.textContent = 'Bitte vergib 1 bis 5 Sterne.';
        return;
      }

      if (text === '') {
        formularMeldung.textContent = 'Schreib bitte ein paar Worte dazu.';
        return;
      }

      knopf.disabled = true;
      formularMeldung.textContent = '';

      try {
        await bewertungAbgeben(sterne, text);

        // Pro Konto gibt es eine Bewertung - das Formular hat damit
        // seinen Zweck erfüllt.
        formular.replaceWith(element('p', 'bewertungen-hinweis', 'Danke! Deine Bewertung steht jetzt in der Liste.'));
        await laden();
      } catch (fehler) {
        formularMeldung.textContent = fehler.message;
        knopf.disabled = false;
      }
    });
  }

  laden();
}
