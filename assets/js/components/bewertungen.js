/**
 * @file        assets/js/components/bewertungen.js
 * @layer       2 – Komponente
 * @description Die Bewertungen auf der Startseite: das Karussell aus
 *              Bildkacheln im Abschnitt #bewertungen (drei zu sehen, mit
 *              Pfeilen weiterzudrehen) und das Fenster "Alle Bewertungen" mit
 *              Durchschnitt, allen Karten und - für Angemeldete - dem
 *              Formular zum Bewerten. Wer schon bewertet hat, sieht dort
 *              statt des Formulars seine Bewertung und kann sie löschen.
 *
 *              Eine eigene Datei statt Code in index.page.js, weil Kacheln,
 *              Fenster und Formular zusammen zu lang für das Seitenskript
 *              sind. Aufgerufen wird sie von index.page.js. Öffnen und
 *              Schließen des Fensters übernimmt modal.js, wie beim
 *              Kurskalender.
 *
 *              Fehlt das Bild einer Person, wird die Kachel zur
 *              Zitat-Kachel. Im Fenster stehen im kleinen Kreis die
 *              Initialen - wie bei den Coaches in programm-details.js.
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
import { bewertungAbgeben, bewertungenLaden, bewertungLoeschen } from '../services/bewertungen.js';

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
 * Das kleine runde Bild neben der Karte im Fenster, ersatzweise die
 * Initialen - wie bei den Coaches in programm-details.js.
 *
 * alt bleibt leer: Der Name steht direkt daneben, ein Screenreader würde
 * ihn sonst zweimal vorlesen.
 *
 * @param {import('../services/bewertungen.js').Bewertung} bewertung
 * @param {string} baseUrl  Präfix aus data-base-url am <html>-Tag
 * @returns {HTMLElement}
 */
function bildBauen(bewertung, baseUrl) {
  const ersatz = element(
    'span',
    'bewertung-bild bewertung-bild--ersatz',
    bewertung.name.split(' ').map((teil) => teil.charAt(0)).join('').slice(0, 2),
  );

  if (bewertung.bild === null) {
    return ersatz;
  }

  const bild = element('img', 'bewertung-bild');
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
 * Ohne Bild - oder wenn die Datei fehlt - wird daraus eine Zitat-Kachel:
 * ein großes Anführungszeichen statt des Fotos, keine Initialen.
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

  const zeichen = element('span', 'bewertung-kachel-zeichen', '“');
  zeichen.setAttribute('aria-hidden', 'true');

  if (bewertung.bild === null) {
    kachel.classList.add('bewertung-kachel--zitat');
    kachel.append(zeichen, inhalt);
    return kachel;
  }

  const bild = element('img', 'bewertung-kachel-bild');
  bild.src = baseUrl + bewertung.bild;
  bild.alt = '';
  bild.loading = 'lazy';
  bild.addEventListener('error', () => {
    kachel.classList.add('bewertung-kachel--zitat');
    bild.replaceWith(zeichen);
  });

  kachel.append(bild, inhalt);

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
  kopf.append(bildBauen(bewertung, baseUrl), person);

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

  // --- Karussell ------------------------------------------------------------
  // Die Kacheln liegen in einer quer scrollenden Liste (scroll-snap in
  // bewertungen.css). Wischen und Touchpad funktionieren dadurch von selbst,
  // die Pfeile blättern um eine Kachel. Weich scrollt das CSS, nicht dieses
  // Skript - so gilt dort auch "Bewegung reduzieren" des Systems.
  const zurueck = $('#bewertungen-zurueck');
  const weiter = $('#bewertungen-weiter');

  /**
   * Blättert um eine Kachel. Am Ende geht es wieder von vorn los, am Anfang
   * zum Ende - das Karussell dreht sich.
   *
   * @param {1|-1} richtung  1 = weiter, -1 = zurück
   * @returns {void}
   */
  const blaettern = (richtung) => {
    const kachel = kacheln.firstElementChild;

    if (!kachel) {
      return;
    }

    // Gerechnet wird in Kacheln, nicht in Pixeln: Wer zweimal schnell
    // klickt, bevor das Gleiten fertig ist, landet trotzdem sauber auf
    // einer Kachel statt irgendwo dazwischen.
    const schritt = kachel.getBoundingClientRect().width + parseFloat(getComputedStyle(kacheln).columnGap);
    const letzte = Math.round((kacheln.scrollWidth - kacheln.clientWidth) / schritt);
    let ziel = Math.round(kacheln.scrollLeft / schritt) + richtung;

    if (ziel > letzte) {
      ziel = 0;
    } else if (ziel < 0) {
      ziel = letzte;
    }

    kacheln.scrollTo({ left: ziel * schritt });
  };

  /**
   * Zeigt die Pfeile nur, wenn nicht alle Kacheln auf einmal zu sehen sind -
   * bei drei Bewertungen auf dem Desktop gibt es nichts zu blättern.
   *
   * @returns {void}
   */
  const pfeileAnpassen = () => {
    const allesZuSehen = kacheln.scrollWidth <= kacheln.clientWidth + 2;

    zurueck.hidden = allesZuSehen;
    weiter.hidden = allesZuSehen;
  };

  zurueck.addEventListener('click', () => blaettern(-1));
  weiter.addEventListener('click', () => blaettern(1));

  // Beim Drehen des Handys oder Ändern der Fensterbreite passen mal drei,
  // mal nur eine Kachel hinein.
  new ResizeObserver(pfeileAnpassen).observe(kacheln);

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
   * Ins Karussell kommen alle Bewertungen, die mit Bild zuerst: Die Kacheln
   * leben vom Foto. Ein Bild haben die Beispiele aus seed.sql und alle
   * Bewertungen, deren Verfasser ein freigegebenes Profilbild haben.
   *
   * @returns {Promise<void>}
   */
  const laden = async () => {
    try {
      const bewertungen = await bewertungenLaden();
      const mitBild = bewertungen.filter((b) => b.bild !== null);
      const ohneBild = bewertungen.filter((b) => b.bild === null);

      kacheln.replaceChildren(...[...mitBild, ...ohneBild].map((b) => kachelBauen(b, baseUrl)));
      liste.replaceChildren(...bewertungen.map((b) => karteBauen(b, baseUrl)));
      pfeileAnpassen();
      eigeneZeigen(bewertungen.find((b) => b.eigene) ?? null);

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

  // --- Formular und eigene Bewertung, nur für Angemeldete --------------------
  // Pro Konto gibt es eine Bewertung. Wer schon bewertet hat, sieht sie
  // statt des Formulars und kann sie löschen - danach ist das Formular
  // wieder da.
  const formular = $('#bewertung-formular');
  const eigeneBereich = $('#bewertung-eigene');
  const eigeneMeldung = $('#bewertung-eigene-meldung');

  /**
   * Zeigt entweder das Formular oder "Deine Bewertung". Als Funktion und
   * nicht als const, weil laden() sie weiter oben schon aufruft.
   *
   * @param {import('../services/bewertungen.js').Bewertung|null} eigene
   * @returns {void}
   */
  function eigeneZeigen(eigene) {
    if (!formular) {
      return;
    }

    formular.hidden = eigene !== null;
    eigeneBereich.hidden = eigene === null;
    $('#bewertung-eigene-inhalt').replaceChildren(...(eigene ? [karteBauen(eigene, baseUrl)] : []));
  }

  if (formular) {
    const wahl = $('.bewertung-wahl', formular);
    const formularMeldung = $('#bewertung-formular-meldung');
    const knopf = $('button[type="submit"]', formular);

    /**
     * Füllt alle Sterne bis zum gewählten. Die Radio-Buttons selbst sind
     * unsichtbar, zu sehen sind nur ihre Labels. RadioNodeList.value ist
     * '', solange nichts gewählt ist -> 0.
     *
     * @returns {void}
     */
    const sterneFuellen = () => {
      const gewaehlt = Number(formular.elements.sterne.value);

      $$('label', wahl).forEach((label, index) => {
        label.classList.toggle('bewertung-wahl-stern--voll', index < gewaehlt);
      });
    };

    /**
     * Meldung unter "Deine Bewertung".
     *
     * @param {string} text
     * @param {boolean} [erfolg]  true = grün statt rot
     * @returns {void}
     */
    const eigeneMelden = (text, erfolg = false) => {
      eigeneMeldung.textContent = text;
      eigeneMeldung.classList.toggle('bewertung-formular-meldung--erfolg', erfolg);
    };

    wahl.addEventListener('change', sterneFuellen);

    $('#bewertung-loeschen').addEventListener('click', async () => {
      // Kein Rückgängig - deshalb einmal nachfragen.
      if (!window.confirm('Deine Bewertung wirklich löschen?')) {
        return;
      }

      eigeneMelden('');

      try {
        await bewertungLoeschen();
        await laden();
      } catch (fehler) {
        eigeneMelden(fehler.message);
      }
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

        // Leeren, falls die Bewertung später gelöscht und neu geschrieben
        // wird. laden() zeigt danach "Deine Bewertung" statt des Formulars.
        formular.reset();
        sterneFuellen();
        await laden();
        eigeneMelden('Danke! Deine Bewertung steht jetzt in der Liste.', true);
      } catch (fehler) {
        formularMeldung.textContent = fehler.message;
      } finally {
        knopf.disabled = false;
      }
    });
  }

  laden();
}
