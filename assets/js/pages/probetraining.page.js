/**
 * @file        assets/js/pages/probetraining.page.js
 * @layer       2 – Seitenskript
 * @description Die Seite "Probetraining": Coaches anzeigen, nach der Wahl
 *              eines Coaches seine freien Termine laden, Stufe und Termin
 *              zusammenführen und buchen.
 *
 *              Die Seite merkt sich die drei Entscheidungen in "wahl".
 *              Gebucht wird erst, wenn alle drei getroffen sind - fehlt
 *              etwas, sagt die Meldung unten genau, was.
 * @see         probetraining.php
 * @see         assets/js/services/probetrainings.js
 */

import { $ } from '../lib/dom.js';
import { nachTagen, tagUeberschrift } from '../lib/datum.js';
import { STUFEN, stufenAuswahlBauen } from '../components/stufen.js';
import { buchen, coachesLaden, freieTermineLaden } from '../services/probetrainings.js';

const seite = $('#probetraining-seite');

if (seite) {
  const baseUrl = document.documentElement.dataset.baseUrl ?? '/';
  const coachWahl = $('#coach-wahl');
  const termineBereich = $('#probetermine');
  const zusammenfassung = $('#pt-zusammenfassung');
  const meldung = $('#pt-meldung');
  const buchenKnopf = $('#pt-buchen');   // fehlt, wenn niemand angemeldet ist

  /** Die drei Entscheidungen. */
  const wahl = {
    /** @type {import('../services/probetrainings.js').ProbetrainingCoach|null} */
    coach: null,
    /** @type {string|null} */
    stufe: null,
    /** @type {import('../services/probetrainings.js').FreierTermin|null} */
    termin: null,
  };

  /**
   * Element mit Klasse und Text - textContent, nie innerHTML.
   *
   * @param {string} tag
   * @param {string} klasse
   * @param {string} [text]
   * @returns {HTMLElement}
   */
  const element = (tag, klasse, text = '') => {
    const el = document.createElement(tag);
    el.className = klasse;
    el.textContent = text;
    return el;
  };

  /**
   * Scrollt zur nächsten Frage, die noch nicht beantwortet ist - nach der
   * Stufe also zu den Terminen, nach dem Termin zum Buchen-Button. Wer den
   * Coach wechselt und die Stufe schon gewählt hat, landet direkt bei den
   * Terminen.
   *
   * Weich scrollt das CSS (scroll-behavior in 02-base.css), nicht dieses
   * Skript - dadurch gilt dort auch "Bewegung reduzieren" des Systems.
   * Den Abstand zur Kopfzeile regelt scroll-margin-top in probetraining.css.
   *
   * @returns {void}
   */
  const zurNaechstenFrage = () => {
    const ziel = [
      [wahl.stufe, $('#stufe-wahl')],
      [wahl.termin, termineBereich],
    ].find(([beantwortet]) => !beantwortet)?.[1] ?? zusammenfassung;

    (ziel.closest('.probetraining-schritt') ?? ziel.closest('.probetraining-abschluss')).scrollIntoView({ block: 'start' });
  };

  /** Schreibt unten hin, was gewählt ist. */
  const zusammenfassen = () => {
    if (!wahl.coach || !wahl.stufe || !wahl.termin) {
      zusammenfassung.textContent = 'Noch nicht alles gewählt.';
      return;
    }

    zusammenfassung.textContent = `${tagUeberschrift(wahl.termin.datum)}, ${wahl.termin.beginn}–${wahl.termin.ende} `
      + `mit ${wahl.coach.name} · ${STUFEN[wahl.stufe]}`;
  };

  /**
   * Baut die Auswahlkarte eines Coaches. Fehlt das Foto, stehen dort die
   * Initialen - wie auf den Kursseiten.
   *
   * @param {import('../services/probetrainings.js').ProbetrainingCoach} coach
   * @param {HTMLButtonElement[]} alle  alle Karten, zum Umschalten von aria-pressed
   * @returns {HTMLButtonElement}
   */
  const coachKarteBauen = (coach, alle) => {
    const karte = element('button', 'coach-option');
    karte.type = 'button';
    karte.setAttribute('aria-pressed', 'false');

    const bild = document.createElement('img');
    bild.className = 'coach-option-bild';
    bild.src = baseUrl + coach.bild;
    bild.alt = '';   // Der Name steht direkt daneben, das Bild ist Schmuck.
    bild.addEventListener('error', () => {
      const ersatz = element('span', 'coach-option-bild coach-option-bild--ersatz',
        coach.name.split(' ').slice(0, 2).map((teil) => teil.charAt(0)).join(''));
      ersatz.setAttribute('aria-hidden', 'true');
      bild.replaceWith(ersatz);
    });

    karte.append(
      bild,
      element('span', 'coach-option-programm', coach.programm),
      element('span', 'coach-option-name', coach.name),
      element('span', 'coach-option-schwerpunkt', coach.schwerpunkt),
    );

    karte.addEventListener('click', async () => {
      alle.forEach((andere) => andere.setAttribute('aria-pressed', 'false'));
      karte.setAttribute('aria-pressed', 'true');
      wahl.coach = coach;
      wahl.termin = null;
      meldung.textContent = '';
      zusammenfassen();

      // Erst laden, dann scrollen: Solange die Termine noch fehlen, ist die
      // Seite zu kurz, um die nächste Frage ganz nach oben zu holen - sie
      // bliebe ein Stück tiefer stehen.
      await termineLaden();
      zurNaechstenFrage();
    });

    return karte;
  };

  /**
   * Lädt die freien Termine des gewählten Coaches und zeigt sie nach Tagen.
   *
   * @returns {Promise<void>}
   */
  async function termineLaden() {
    termineBereich.replaceChildren(element('p', 'kalender-leer', 'Freie Termine werden geladen …'));

    try {
      const { termine } = await freieTermineLaden(wahl.coach.id);

      if (termine.length === 0) {
        termineBereich.replaceChildren(element('p', 'kalender-leer',
          `${wahl.coach.name} ist in den nächsten zwei Wochen ausgebucht. Wähle einen anderen Coach.`));
        return;
      }

      const knoepfe = [];

      termineBereich.replaceChildren(...nachTagen(termine).map(({ datum, eintraege }) => {
        // div, nicht section: 03-layout.css gibt jedem <section> 118px Abstand.
        const tag = element('div', 'kalender-tag');
        const zeiten = element('div', 'slot-liste');

        eintraege.forEach((termin) => {
          const knopf = element('button', 'slot', `${termin.beginn}–${termin.ende}`);
          knopf.type = 'button';
          knopf.setAttribute('aria-pressed', 'false');

          knopf.addEventListener('click', () => {
            knoepfe.forEach((anderer) => anderer.setAttribute('aria-pressed', 'false'));
            knopf.setAttribute('aria-pressed', 'true');
            wahl.termin = termin;
            meldung.textContent = '';
            zusammenfassen();
            zurNaechstenFrage();
          });

          knoepfe.push(knopf);
          zeiten.append(knopf);
        });

        tag.append(element('h3', 'kalender-datum', tagUeberschrift(datum)), zeiten);
        return tag;
      }));
    } catch (fehler) {
      termineBereich.replaceChildren(element('p', 'kalender-meldung kalender-meldung--voll', fehler.message));
    }
  }

  // --- Stufe -----------------------------------------------------------------
  const stufe = stufenAuswahlBauen((kennung) => {
    wahl.stufe = kennung;
    meldung.textContent = '';
    zusammenfassen();
    zurNaechstenFrage();
  });
  $('#stufe-wahl').append(stufe.element);

  // --- Buchen ----------------------------------------------------------------
  buchenKnopf?.addEventListener('click', async () => {
    meldung.className = 'kalender-meldung';

    const fehlt = [
      wahl.coach ? null : 'einen Coach',
      wahl.stufe ? null : 'deine Stufe',
      wahl.termin ? null : 'einen Termin',
    ].filter(Boolean);

    if (fehlt.length > 0) {
      meldung.textContent = `Bitte wähle noch ${fehlt.join(', ')}.`;
      return;
    }

    buchenKnopf.disabled = true;

    try {
      await buchen(wahl.coach.id, wahl.termin.beginntAm, wahl.stufe);

      meldung.classList.add('kalender-meldung--erfolg');
      meldung.replaceChildren(`Gebucht! Wir sehen uns ${tagUeberschrift(wahl.termin.datum)} um ${wahl.termin.beginn} Uhr. Du findest den Termin unter `);
      const link = document.createElement('a');
      link.href = `${baseUrl}mein-konto.php`;
      link.textContent = 'Mein Konto';
      meldung.append(link, '.');

      wahl.termin = null;
      zusammenfassen();
    } catch (fehler) {
      meldung.classList.add('kalender-meldung--voll');
      meldung.textContent = fehler.message;
      wahl.termin = null;
      zusammenfassen();
    } finally {
      buchenKnopf.disabled = false;
      // In beiden Fällen neu laden: Nach der Buchung ist der Termin weg,
      // nach "gerade vergeben" auch.
      termineLaden();
    }
  });

  // --- Start -----------------------------------------------------------------
  try {
    const { coaches } = await coachesLaden();
    const karten = [];
    coaches.forEach((coach) => karten.push(coachKarteBauen(coach, karten)));
    coachWahl.replaceChildren(...karten);
  } catch (fehler) {
    coachWahl.replaceChildren(element('p', 'kalender-meldung kalender-meldung--voll', fehler.message));
  }
}
