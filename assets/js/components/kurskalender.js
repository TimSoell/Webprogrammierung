/**
 * @file        assets/js/components/kurskalender.js
 * @layer       2 – Komponente
 * @description Der Terminkalender im Fenster auf den drei Kursseiten.
 *              Öffnet sich über den Button "Termin buchen".
 *
 *              EIN KREIS PRO TAG
 *              Oben die nächsten zwei Wochen als Kreise mit der Tageszahl,
 *              darunter die Termine EINES Tages. Überfahren eines Kreises
 *              zeigt dessen Tag, ein Klick wählt ihn fest. So kann man mit
 *              der Maus zu den Terminen fahren, ohne dass unterwegs ein
 *              anderer Kreis den Tag austauscht: Sobald die Maus die Kreise
 *              verlässt, steht wieder der gewählte Tag da.
 *
 *              ZWEI STUFEN PRO TERMIN
 *              Überfahren mit der Maus (oder Fokus per Tastatur) klappt die
 *              Infos auf: Coach, Teilnehmer, Format. Das macht allein das
 *              CSS, siehe .termin:hover in kalender.css.
 *              Ein Klick hält den Termin offen und zeigt daneben die Aktion:
 *              Stufe wählen und "Buchen" - oder die Meldung, dass er voll
 *              ist. Auf dem Handy gibt es kein Überfahren; dort erledigt
 *              der Tipp beides auf einmal.
 *
 *              Das Fenster selbst (öffnen, schließen, Escape) kommt aus
 *              modal.js - dieselbe Komponente wie das Anmeldefenster.
 *
 *              KEIN innerHTML mit Daten vom Server, alles über textContent.
 * @see         partials/kurskalender.php
 * @see         assets/js/services/kurstermine.js
 * @see         assets/css/components/kalender.css
 */

import { $ } from '../lib/dom.js';
import { kalenderWochen, kurzesDatum, nachTagen, tagUeberschrift } from '../lib/datum.js';
import { initModal } from './modal.js';
import { stufenAuswahlBauen } from './stufen.js';
import { buchen, termineLaden } from '../services/kurstermine.js';

/** Was bei einem vollen Termin erscheint. Der Server schickt denselben Satz. */
const AUSGEBUCHT = 'Leider ist hier schon alles vollgeschwitzt.';

/**
 * Hilfsfunktion: Element mit Klasse und Text.
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
 * Baut einen Link als Teil eines Satzes.
 *
 * @param {string} href
 * @param {string} text
 * @returns {HTMLAnchorElement}
 */
function link(href, text) {
  const a = document.createElement('a');
  a.href = href;
  a.textContent = text;
  return a;
}

/**
 * Aktiviert den Kurskalender auf einer Kursseite.
 *
 * Erwartet in der Seite das Fenster aus partials/kurskalender.php und den
 * Button #kurskalender-oeffnen. Fehlt eins davon, passiert nichts.
 *
 * Die Termine werden erst beim Öffnen geladen, nicht beim Seitenaufruf -
 * wer das Fenster nie öffnet, löst auch keine Anfrage aus. Bei jedem
 * Öffnen neu, damit die Belegung stimmt.
 *
 * @param {string} slug  'strength', 'move' oder 'fight'
 * @returns {void}
 */
export function kurskalenderAufbauen(slug) {
  const liste = $('#kurskalender-liste');
  const oeffnen = $('#kurskalender-oeffnen');

  if (!liste || !oeffnen) {
    return;
  }

  initModal({
    modalId: 'kurskalender',
    openId: 'kurskalender-oeffnen',
    closeId: 'kurskalender-schliessen',
  });

  const baseUrl = document.documentElement.dataset.baseUrl ?? '/';

  /**
   * Welcher Termin nach dem Neuladen wieder offen sein soll ("terminId|datum").
   * Nach dem Buchen wird neu gezeichnet - ohne das spränge der Termin zu.
   */
  let offen = null;

  /** Rückmeldung, die nach dem Neuzeichnen am offenen Termin erscheinen soll. */
  let hinweis = null;

  /**
   * Baut die rechte Hälfte eines geöffneten Termins.
   *
   * @param {import('../services/kurstermine.js').Kurstermin} termin
   * @param {boolean} angemeldet
   * @returns {HTMLElement}
   */
  const aktionBauen = (termin, angemeldet) => {
    const aktion = element('div', 'termin-aktion');

    if (termin.gebucht) {
      const satz = element('p', 'kalender-meldung kalender-meldung--erfolg', 'Du bist dabei. Deine Termine findest du unter ');
      satz.append(link(`${baseUrl}mein-konto.php`, 'Mein Konto'), '.');
      aktion.append(satz);
      return aktion;
    }

    if (termin.belegt >= termin.max) {
      aktion.append(element('p', 'kalender-meldung kalender-meldung--voll', AUSGEBUCHT));
      return aktion;
    }

    if (!angemeldet) {
      const satz = element('p', 'kalender-meldung', '');
      satz.append(link(`${baseUrl}anmelden.php`, 'Melde dich an'), ', um diesen Termin zu buchen.');
      aktion.append(satz);
      return aktion;
    }

    const meldung = element('p', 'kalender-meldung');
    meldung.setAttribute('role', 'status');

    const stufe = stufenAuswahlBauen(() => { meldung.textContent = ''; });

    const knopf = element('button', 'button termin-buchen', 'Buchen');
    knopf.type = 'button';

    knopf.addEventListener('click', async () => {
      if (stufe.wert() === null) {
        meldung.textContent = 'Bitte wähle zuerst deine Stufe.';
        return;
      }

      knopf.disabled = true;
      meldung.textContent = '';

      try {
        await buchen(termin.terminId, termin.datum, stufe.wert());
        hinweis = 'Gebucht!';
      } catch (fehler) {
        // Auch bei "ausgebucht" neu laden: Dann zeigt der Termin die
        // aktuelle Belegung und die Meldung an der richtigen Stelle.
        hinweis = fehler.message === AUSGEBUCHT ? null : fehler.message;
      }

      await laden();
    });

    aktion.append(element('p', 'termin-label', 'Deine Stufe'), stufe.element, knopf, meldung);

    return aktion;
  };

  /**
   * Baut einen Termin: Kopfzeile, Infos, Aktion.
   *
   * @param {import('../services/kurstermine.js').Kurstermin} termin
   * @param {boolean} angemeldet
   * @returns {HTMLElement}
   */
  const terminBauen = (termin, angemeldet) => {
    const schluessel = `${termin.terminId}|${termin.datum}`;
    const voll = termin.belegt >= termin.max;
    const detailsId = `termin-${termin.terminId}-${termin.datum}`;

    const eintrag = element('li', 'termin');
    eintrag.classList.toggle('termin--voll', voll);
    eintrag.classList.toggle('termin--gebucht', termin.gebucht);

    // --- Kopfzeile ------------------------------------------------------------
    const kopf = element('button', 'termin-kopf');
    kopf.type = 'button';
    kopf.setAttribute('aria-expanded', 'false');
    kopf.setAttribute('aria-controls', detailsId);

    const plaetze = element('span', 'termin-plaetze', `${termin.belegt}/${termin.max}`);
    plaetze.setAttribute('aria-label', `${termin.belegt} von ${termin.max} Plätzen belegt`);

    kopf.append(
      element('span', 'termin-zeit', `${termin.beginn}–${termin.ende}`),
      element('span', 'termin-titel', termin.format),
      plaetze,
    );

    if (voll || termin.gebucht) {
      kopf.append(element('span', 'termin-marke', termin.gebucht ? 'Gebucht' : 'Ausgebucht'));
    }

    // --- Infos und Aktion -------------------------------------------------------
    const details = element('div', 'termin-details');
    details.id = detailsId;

    const infos = element('div', 'termin-infos');
    infos.append(
      element('p', 'termin-coach', `Coach: ${termin.coach}`),
      element('p', 'termin-schwerpunkt', termin.coachSchwerpunkt),
      element('p', 'termin-belegung', `${termin.belegt} von ${termin.max} Plätzen belegt`),
      element('p', 'termin-beschreibung', termin.formatBeschreibung),
    );

    const aktion = aktionBauen(termin, angemeldet);

    if (offen === schluessel && hinweis) {
      // hinweis ist entweder "Gebucht!" oder ein Fehlertext vom Server.
      const art = hinweis === 'Gebucht!' ? 'erfolg' : 'voll';
      aktion.prepend(element('p', `kalender-meldung kalender-meldung--${art}`, hinweis));
    }

    details.append(infos, aktion);
    eintrag.append(kopf, details);

    const aufklappen = (ja) => {
      eintrag.classList.toggle('termin--offen', ja);
      kopf.setAttribute('aria-expanded', String(ja));
    };

    kopf.addEventListener('click', () => {
      const warOffen = eintrag.classList.contains('termin--offen');

      // Immer nur einer offen - sonst wird das Fenster schnell unübersichtlich.
      liste.querySelectorAll('.termin--offen').forEach((anderer) => {
        anderer.classList.remove('termin--offen');
        anderer.querySelector('.termin-kopf').setAttribute('aria-expanded', 'false');
      });

      aufklappen(!warOffen);
      offen = warOffen ? null : schluessel;
      hinweis = null;
    });

    if (offen === schluessel) {
      aufklappen(true);
    }

    return eintrag;
  };

  // --- Monatsansicht: ein Kreis pro Tag --------------------------------------

  /** Antwort des Servers, zum Neuzeichnen ohne neue Anfrage. */
  let daten = null;

  /**
   * Der angeklickte Tag. Überfahren zeigt einen anderen Tag nur vorübergehend;
   * verlässt die Maus die Kreise, erscheint wieder dieser.
   */
  let gewaehlt = null;

  /** Welcher Tag gerade unten steht - damit er nicht unnötig neu gebaut wird. */
  let angezeigt = null;

  /** Der Bereich unter den Kreisen mit den Terminen eines Tages. */
  let tagesansicht = null;

  /** @type {Map<string, import('../services/kurstermine.js').Kurstermin[]>} */
  let proTag = new Map();

  /**
   * Zeigt die Termine eines Tages unter den Kreisen.
   *
   * @param {string} datum  'JJJJ-MM-TT'
   * @returns {void}
   */
  const tagZeigen = (datum) => {
    if (datum === angezeigt || !tagesansicht) {
      return;
    }

    angezeigt = datum;
    const termine = proTag.get(datum) ?? [];
    const inhalt = [element('h3', 'kalender-datum', tagUeberschrift(datum))];

    if (termine.length === 0) {
      inhalt.push(element('p', 'kalender-leer', 'An diesem Tag gibt es keine Kurse.'));
    } else {
      const termineListe = element('ul', 'termine');
      termineListe.append(...termine.map((termin) => terminBauen(termin, daten.angemeldet)));
      inhalt.push(termineListe);
    }

    tagesansicht.replaceChildren(...inhalt);
  };

  /**
   * Baut den Kreis eines Tages.
   *
   * @param {string} datum
   * @returns {HTMLButtonElement}
   */
  const kreisBauen = (datum) => {
    const termine = proTag.get(datum) ?? [];
    const voll = termine.length > 0 && termine.every((t) => t.belegt >= t.max);
    const gebucht = termine.some((t) => t.gebucht);

    // Nur die Zahl steht im Kreis. Was ein Screenreader vorliest, steht im
    // aria-label - dort gehört der ganze Tag samt Anzahl hin.
    const kreis = element('button', 'kalender-kreis', String(Number(datum.slice(8))));
    kreis.type = 'button';
    kreis.dataset.datum = datum;
    kreis.classList.toggle('kalender-kreis--termine', termine.length > 0);
    kreis.classList.toggle('kalender-kreis--voll', voll);
    kreis.classList.toggle('kalender-kreis--gebucht', gebucht);
    kreis.setAttribute('aria-pressed', String(datum === gewaehlt));

    const anzahl = termine.length === 0 ? 'keine Kurse'
      : `${termine.length} ${termine.length === 1 ? 'Kurs' : 'Kurse'}${voll ? ', ausgebucht' : ''}`;
    kreis.setAttribute('aria-label', `${tagUeberschrift(datum)}: ${anzahl}${gebucht ? ', von dir gebucht' : ''}`);

    // Überfahren und Tastaturfokus zeigen den Tag nur an ...
    kreis.addEventListener('mouseenter', () => tagZeigen(datum));
    kreis.addEventListener('focus', () => tagZeigen(datum));

    // ... erst der Klick wählt ihn. Dann bleibt er stehen, auch wenn die Maus
    // auf dem Weg zu den Terminen über andere Kreise fährt.
    kreis.addEventListener('click', () => {
      gewaehlt = datum;
      offen = null;
      hinweis = null;
      liste.querySelectorAll('.kalender-kreis').forEach((anderer) => {
        anderer.setAttribute('aria-pressed', String(anderer === kreis));
      });
      angezeigt = null;
      tagZeigen(datum);
    });

    return kreis;
  };

  /**
   * Zeichnet Kreise und Tagesansicht aus den geladenen Daten.
   *
   * @returns {void}
   */
  const zeichnen = () => {
    proTag = new Map(nachTagen(daten.termine).map(({ datum, eintraege }) => [datum, eintraege]));

    // Beim ersten Öffnen der erste Tag mit Kursen, danach bleibt die Wahl.
    if (gewaehlt === null || gewaehlt < daten.von || gewaehlt > daten.bis) {
      gewaehlt = daten.termine[0]?.datum ?? daten.von;
    }

    const kopf = element('div', 'kalender-woche kalender-wochentage');
    kopf.setAttribute('aria-hidden', 'true');
    kopf.append(...['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'].map((tag) => element('span', 'kalender-wochentag', tag)));

    const raster = element('div', 'kalender-raster');
    raster.setAttribute('role', 'group');
    raster.setAttribute('aria-label', 'Tag wählen');
    raster.append(kopf, ...kalenderWochen(daten.von, daten.bis).map((woche) => {
      const zeile = element('div', 'kalender-woche');
      // Tage außerhalb des Zeitraums bleiben als leerer Platz stehen, damit
      // jeder Kreis unter seinem Wochentag sitzt.
      zeile.append(...woche.map((datum) => (datum ? kreisBauen(datum) : element('span', 'kalender-kreis kalender-kreis--leer'))));
      return zeile;
    }));

    // Maus verlässt die Kreise: zurück zum gewählten Tag.
    raster.addEventListener('mouseleave', () => tagZeigen(gewaehlt));
    raster.addEventListener('focusout', (ereignis) => {
      if (!raster.contains(ereignis.relatedTarget)) {
        tagZeigen(gewaehlt);
      }
    });

    const legende = element('p', 'kalender-legende');
    legende.setAttribute('aria-hidden', 'true');
    legende.append(
      element('span', 'kalender-legende-eintrag kalender-legende-eintrag--termine', 'Kurse'),
      element('span', 'kalender-legende-eintrag kalender-legende-eintrag--voll', 'ausgebucht'),
      element('span', 'kalender-legende-eintrag kalender-legende-eintrag--gebucht', 'von dir gebucht'),
    );

    tagesansicht = element('div', 'kalender-tagesansicht');
    tagesansicht.setAttribute('aria-live', 'polite');
    angezeigt = null;

    liste.replaceChildren(
      element('p', 'kalender-zeitraum', `${kurzesDatum(daten.von)} – ${kurzesDatum(daten.bis)}`),
      raster,
      legende,
      tagesansicht,
    );

    tagZeigen(gewaehlt);
  };

  /**
   * Lädt die Termine und zeichnet den Kalender neu.
   *
   * @returns {Promise<void>}
   */
  async function laden() {
    try {
      daten = await termineLaden(slug);
      zeichnen();
    } catch (fehler) {
      liste.replaceChildren(element('p', 'kalender-meldung kalender-meldung--voll', fehler.message));
    }
  }

  oeffnen.addEventListener('click', () => {
    offen = null;
    hinweis = null;
    gewaehlt = null;
    liste.replaceChildren(element('p', 'kalender-leer', 'Termine werden geladen …'));
    laden();
  });
}
