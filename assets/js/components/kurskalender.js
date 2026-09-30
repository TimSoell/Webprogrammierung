/**
 * @file        assets/js/components/kurskalender.js
 * @layer       2 – Komponente
 * @description Der Terminkalender im Fenster auf den drei Kursseiten.
 *              Öffnet sich über den Button "Termin buchen".
 *
 *              EIN MONAT, EIN KREIS PRO TAG
 *              Oben ein ganzer Monat als Kreise mit der Tageszahl, darunter
 *              die Termine EINES Tages. Überfahren eines Kreises zeigt
 *              dessen Tag, ein Klick wählt ihn fest. So kann man mit der
 *              Maus zu den Terminen fahren, ohne dass unterwegs ein anderer
 *              Kreis den Tag austauscht: Sobald die Maus die Kreise
 *              verlässt, steht wieder der gewählte Tag da.
 *
 *              BLÄTTERN
 *              Pfeile neben dem Monatsnamen, Wischen über den Kalender (Finger
 *              oder Maus) oder waagerechtes Wischen auf dem Touchpad. Wie weit
 *              es geht, sagt der Server (ersterMonat, letzterMonat). Jeder
 *              Monat wird einzeln geladen und bis zum Schließen gemerkt.
 *
 *              TARIF
 *              Ist man angemeldet, aber im gezeigten Monat sind keine Kurse
 *              im Tarif, steht über den Kreisen ein Hinweis mit Link zur
 *              Mitgliedschaft - und an den Terminen statt "Buchen" derselbe
 *              Hinweis in kurz. Geprüft wird pro Monat, weil ein Wechsel
 *              immer erst zum Monatsersten gilt. Die eigentliche Prüfung
 *              macht der Server beim Buchen; das hier ist nur die Anzeige.
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
import {
  kalenderWochen, kurzesDatum, monatUeberschrift, monatVerschieben, nachTagen, tagUeberschrift,
} from '../lib/datum.js';
import { initModal } from './modal.js';
import { stufenAuswahlBauen } from './stufen.js';
import { buchen, termineLaden } from '../services/kurstermine.js';

/** Was bei einem vollen Termin erscheint. Der Server schickt denselben Satz. */
const AUSGEBUCHT = 'Leider ist hier schon alles vollgeschwitzt.';

/** So weit (in Pixeln) muss man waagerecht wischen, damit der Monat wechselt. */
const WISCHWEG = 50;

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
  const mitgliedschaftUrl = `${baseUrl}mitgliedschaft.php`;

  /**
   * Welcher Termin nach dem Neuladen wieder offen sein soll ("terminId|datum").
   * Nach dem Buchen wird neu gezeichnet - ohne das spränge der Termin zu.
   */
  let offen = null;

  /** Rückmeldung, die nach dem Neuzeichnen am offenen Termin erscheinen soll. */
  let hinweis = null;

  /** Antwort des Servers für den gezeigten Monat, zum Neuzeichnen ohne neue Anfrage. */
  let daten = null;

  /**
   * Schon geladene Monate, bis das Fenster geschlossen und neu geöffnet wird.
   * So kostet Hin- und Herblättern keine neuen Anfragen.
   *
   * @type {Map<string, import('../services/kurstermine.js').Kursmonat>}
   */
  let geladen = new Map();

  /**
   * Zählt die Anfragen mit. Blättert jemand schnell weiter, kann eine ältere
   * Antwort nach einer neueren ankommen - sie wird dann verworfen.
   */
  let anfrage = 0;

  /**
   * Kurse in diesem Monat buchbar? Ohne Anmeldung "ja" - dann erscheint
   * stattdessen der Hinweis zum Anmelden.
   *
   * @returns {boolean}
   */
  const kurseImTarif = () => !daten.angemeldet || daten.tarif?.kurse === true;

  /**
   * Baut die rechte Hälfte eines geöffneten Termins.
   *
   * @param {import('../services/kurstermine.js').Kurstermin} termin
   * @returns {HTMLElement}
   */
  const aktionBauen = (termin) => {
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

    if (!daten.angemeldet) {
      const satz = element('p', 'kalender-meldung', '');
      satz.append(link(`${baseUrl}anmelden.php`, 'Melde dich an'), ', um diesen Termin zu buchen.');
      aktion.append(satz);
      return aktion;
    }

    if (!kurseImTarif()) {
      const satz = element('p', 'kalender-meldung kalender-meldung--voll', 'Kurse sind in deinem Tarif nicht enthalten. ');
      satz.append(link(mitgliedschaftUrl, 'Tarif anpassen'));
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

      // Die Belegung dieses Monats hat sich geändert - nicht aus dem Speicher nehmen.
      geladen.delete(daten.monat);
      await laden(daten.monat);
    });

    aktion.append(element('p', 'termin-label', 'Deine Stufe'), stufe.element, knopf, meldung);

    return aktion;
  };

  /**
   * Baut einen Termin: Kopfzeile, Infos, Aktion.
   *
   * @param {import('../services/kurstermine.js').Kurstermin} termin
   * @returns {HTMLElement}
   */
  const terminBauen = (termin) => {
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

    const aktion = aktionBauen(termin);

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
      const text = datum < daten.heute ? 'Dieser Tag ist schon vorbei.' : 'An diesem Tag gibt es keine Kurse.';
      inhalt.push(element('p', 'kalender-leer', text));
    } else {
      const termineListe = element('ul', 'termine');
      termineListe.append(...termine.map(terminBauen));
      inhalt.push(termineListe);
    }

    tagesansicht.replaceChildren(...inhalt);
  };

  /**
   * Baut den Kreis eines Tages. Vergangene Tage sind nur Zahl, kein Knopf.
   *
   * @param {string} datum
   * @returns {HTMLElement}
   */
  const kreisBauen = (datum) => {
    const tageszahl = String(Number(datum.slice(8)));

    if (datum < daten.heute) {
      return element('span', 'kalender-kreis kalender-kreis--vergangen', tageszahl);
    }

    const termine = proTag.get(datum) ?? [];
    const voll = termine.length > 0 && termine.every((t) => t.belegt >= t.max);
    const gebucht = termine.some((t) => t.gebucht);

    // Nur die Zahl steht im Kreis. Was ein Screenreader vorliest, steht im
    // aria-label - dort gehört der ganze Tag samt Anzahl hin.
    const kreis = element('button', 'kalender-kreis', tageszahl);
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
   * Die Zeile mit Monatsname und den beiden Pfeilen.
   *
   * @returns {HTMLElement}
   */
  const monatskopfBauen = () => {
    const pfeil = (zeichen, beschriftung, schritt, gesperrt) => {
      const knopf = element('button', 'kalender-blaettern', zeichen);
      knopf.type = 'button';
      knopf.disabled = gesperrt;
      knopf.setAttribute('aria-label', beschriftung);
      knopf.addEventListener('click', () => blaettern(schritt));
      return knopf;
    };

    const kopf = element('div', 'kalender-monatskopf');
    kopf.append(
      pfeil('‹', 'Vorheriger Monat', -1, daten.monat <= daten.ersterMonat),
      element('h3', 'kalender-monat', monatUeberschrift(daten.monat)),
      pfeil('›', 'Nächster Monat', 1, daten.monat >= daten.letzterMonat),
    );

    return kopf;
  };

  /**
   * Der Hinweis über den Kreisen, wenn im Tarif dieses Monats keine Kurse
   * enthalten sind. null, wenn alles passt oder niemand angemeldet ist.
   *
   * @returns {HTMLElement|null}
   */
  const tarifHinweisBauen = () => {
    if (kurseImTarif()) {
      return null;
    }

    // Ohne Vertrag gilt die erste Wahl sofort, ein Wechsel erst zum
    // nächsten Monatsersten (ADR-0010). Liegt der gezeigte Monat schon
    // dahinter, würde ein Wechsel für ihn rechtzeitig gelten.
    let titel;
    let text;

    if (daten.tarif === null) {
      titel = 'Du hast noch keinen Tarif.';
      text = 'Wähle einen Tarif mit Kursen – dann kannst du sofort buchen.';
    } else {
      titel = `Kurse sind in deinem Tarif „${daten.tarif.name}“ nicht enthalten.`;
      text = daten.von >= daten.wechselAb
        ? `Wechsle jetzt auf einen Tarif mit Kursen – dann kannst du ab dem ${kurzesDatum(daten.von)} buchen.`
        : `Ein Tarifwechsel gilt ab dem ${kurzesDatum(daten.wechselAb)}. Blättere weiter, um die Kurse ab dann zu sehen.`;
    }

    const kasten = element('div', 'kalender-hinweis');
    const zeilen = element('div', 'kalender-hinweis-text');
    zeilen.append(element('p', 'kalender-hinweis-titel', titel), element('p', 'kalender-hinweis-zusatz', text));

    const knopf = element('a', 'button kalender-hinweis-link', daten.tarif === null ? 'Tarif wählen' : 'Tarif anpassen');
    knopf.href = mitgliedschaftUrl;

    kasten.append(zeilen, knopf);

    return kasten;
  };

  /**
   * Zeichnet Monat und Tagesansicht aus den geladenen Daten.
   *
   * @param {number} [richtung]  1 = kam von rechts (weiter), -1 = von links, 0 = ohne Bewegung
   * @returns {void}
   */
  const zeichnen = (richtung = 0) => {
    proTag = new Map(nachTagen(daten.termine).map(({ datum, eintraege }) => [datum, eintraege]));

    // Neuer Monat: der erste Tag mit Kursen, sonst heute, sonst der Erste.
    // Beim Neuzeichnen nach dem Buchen bleibt die Wahl.
    if (gewaehlt === null || gewaehlt < daten.von || gewaehlt > daten.bis) {
      gewaehlt = daten.termine[0]?.datum ?? (daten.heute > daten.von ? daten.heute : daten.von);
    }

    const kopf = element('div', 'kalender-woche kalender-wochentage');
    kopf.setAttribute('aria-hidden', 'true');
    kopf.append(...['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'].map((tag) => element('span', 'kalender-wochentag', tag)));

    const raster = element('div', 'kalender-raster');
    raster.setAttribute('role', 'group');
    raster.setAttribute('aria-label', `Tag wählen, ${monatUeberschrift(daten.monat)}`);
    raster.append(kopf, ...kalenderWochen(daten.von, daten.bis).map((woche) => {
      const zeile = element('div', 'kalender-woche');
      // Tage aus dem Nachbarmonat bleiben als leerer Platz stehen, damit
      // jeder Kreis unter seinem Wochentag sitzt.
      zeile.append(...woche.map((datum) => (datum ? kreisBauen(datum) : element('span', 'kalender-kreis kalender-kreis--leer'))));
      return zeile;
    }));

    if (richtung !== 0) {
      raster.classList.add(richtung > 0 ? 'kalender-raster--von-rechts' : 'kalender-raster--von-links');
    }

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

    // Hier drin wird gewischt - nicht in der Tagesansicht, dort markiert
    // man eher Text oder scrollt.
    const monatsansicht = element('div', 'kalender-monatsansicht');
    monatsansicht.append(monatskopfBauen(), raster, legende);

    tagesansicht = element('div', 'kalender-tagesansicht');
    tagesansicht.setAttribute('aria-live', 'polite');
    angezeigt = null;

    const tarifHinweis = tarifHinweisBauen();

    liste.classList.remove('kalender--laedt');
    liste.replaceChildren(...[tarifHinweis, monatsansicht, tagesansicht].filter(Boolean));

    tagZeigen(gewaehlt);
  };

  /**
   * Lädt einen Monat - aus dem Speicher oder vom Server - und zeichnet ihn.
   *
   * @param {string} [monat]     'JJJJ-MM'; ohne Angabe der laufende Monat
   * @param {number} [richtung]  für die Bewegung beim Blättern, siehe zeichnen()
   * @returns {Promise<void>}
   */
  async function laden(monat, richtung = 0) {
    const nummer = ++anfrage;

    try {
      const antwort = geladen.get(monat) ?? await termineLaden(slug, monat);

      if (nummer !== anfrage) {
        return;
      }

      geladen.set(antwort.monat, antwort);
      daten = antwort;
      zeichnen(richtung);
    } catch (fehler) {
      if (nummer === anfrage) {
        liste.classList.remove('kalender--laedt');
        liste.replaceChildren(element('p', 'kalender-meldung kalender-meldung--voll', fehler.message));
      }
    }
  }

  /**
   * Einen Monat vor oder zurück, solange der Server ihn zeigt.
   *
   * @param {number} schritt  -1 oder 1
   * @returns {void}
   */
  function blaettern(schritt) {
    if (!daten) {
      return;
    }

    const ziel = monatVerschieben(daten.monat, schritt);

    if (ziel < daten.ersterMonat || ziel > daten.letzterMonat) {
      return;
    }

    offen = null;
    hinweis = null;
    // Der alte Monat bleibt stehen, bis der neue da ist - nur blasser.
    liste.classList.add('kalender--laedt');
    laden(ziel, schritt);
  }

  // --- Wischen ------------------------------------------------------------------
  // Einmal am festen Container, nicht an jedem neu gezeichneten Monat.

  /** Wo der Finger oder die Maus aufgesetzt hat. */
  let start = null;

  /** Nach einem Wischen mit der Maus darf der folgende Klick keinen Tag wählen. */
  let gewischt = false;

  liste.addEventListener('pointerdown', (ereignis) => {
    start = ereignis.target.closest('.kalender-monatsansicht') && ereignis.isPrimary
      ? { x: ereignis.clientX, y: ereignis.clientY }
      : null;
  });

  liste.addEventListener('pointerup', (ereignis) => {
    if (!start) {
      return;
    }

    const dx = ereignis.clientX - start.x;
    const dy = ereignis.clientY - start.y;
    start = null;

    // Deutlich mehr waagerecht als senkrecht - sonst war es Scrollen.
    if (Math.abs(dx) >= WISCHWEG && Math.abs(dx) > Math.abs(dy) * 1.5) {
      gewischt = true;
      setTimeout(() => { gewischt = false; }, 0);
      blaettern(dx < 0 ? 1 : -1);
    }
  });

  liste.addEventListener('pointercancel', () => { start = null; });

  liste.addEventListener('click', (ereignis) => {
    if (gewischt) {
      ereignis.stopPropagation();
      ereignis.preventDefault();
    }
  }, true);

  // Touchpad: zwei Finger waagerecht. Ein Wisch löst viele wheel-Ereignisse
  // aus; nach einem Wechsel ist deshalb kurz Pause.
  let wischSumme = 0;
  let wischPause = false;

  liste.addEventListener('wheel', (ereignis) => {
    if (!ereignis.target.closest('.kalender-monatsansicht') || Math.abs(ereignis.deltaX) <= Math.abs(ereignis.deltaY)) {
      return;
    }

    ereignis.preventDefault();

    if (wischPause) {
      return;
    }

    wischSumme += ereignis.deltaX;

    if (Math.abs(wischSumme) >= WISCHWEG * 2) {
      blaettern(wischSumme > 0 ? 1 : -1);
      wischSumme = 0;
      wischPause = true;
      setTimeout(() => { wischPause = false; }, 600);
    }
  }, { passive: false });

  oeffnen.addEventListener('click', () => {
    offen = null;
    hinweis = null;
    gewaehlt = null;
    geladen = new Map();
    liste.classList.remove('kalender--laedt');
    liste.replaceChildren(element('p', 'kalender-leer', 'Termine werden geladen …'));
    laden();
  });
}
