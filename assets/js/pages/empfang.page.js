/**
 * @file        assets/js/pages/empfang.page.js
 * @layer       2 – Seitenskript
 * @description Zeigt dem Personal die Liste der Ausweise, die am Empfang
 *              noch gezeigt werden müssen, und schickt Bestätigen und
 *              Ablehnen an den Server.
 *
 *              Beides fragt vorher nach: Bestätigen sperrt den Namen für
 *              immer, Ablehnen entfernt den Nachweis. Wer was darf, prüft
 *              allein api/empfang.php.
 * @see         empfang.php
 * @see         assets/js/services/empfang.js
 */

import { $ } from '../lib/dom.js';
import { ablehnen, bestaetigen, offeneLaden } from '../services/empfang.js';
import { meldungZeigen } from '../components/auth-formular.js';

const seite = $('#empfang-seite');

if (seite) {
  const liste = $('#empfang-liste');
  const meldung = $('#empfang-meldung');

  /** Beschriftung der Nachweisarten - dieselben wie unter "Mein Konto". */
  const NACHWEISARTEN = {
    schueler: 'Schülerausweis',
    student: 'Studierendenausweis',
    senior: 'Senior',
  };

  /**
   * Macht aus '2026-10-30' den Text '30.10.2026'.
   *
   * Von Hand und nicht über new Date(): Ein Datum ohne Uhrzeit liest der
   * Browser als UTC-Mitternacht und kann dabei einen Tag daneben landen.
   *
   * @param {string} datum  'JJJJ-MM-TT'
   * @returns {string}
   */
  const datumAnzeigen = (datum) => datum.split('-').reverse().join('.');

  /**
   * Führt Bestätigen oder Ablehnen aus und zeichnet danach die Liste neu.
   *
   * @param {HTMLElement} zeile                 die Zeile, deren Knöpfe gesperrt werden
   * @param {() => Promise<{offen: Array}>} aktion  Aufruf des Service
   * @param {string} erfolg                     Meldung danach
   * @returns {Promise<void>}
   */
  const entscheiden = async (zeile, aktion, erfolg) => {
    const knoepfe = zeile.querySelectorAll('button');

    knoepfe.forEach((knopf) => { knopf.disabled = true; });
    meldungZeigen(meldung, '');

    try {
      listeZeigen(await aktion());
      meldungZeigen(meldung, erfolg, true);
    } catch (fehler) {
      meldungZeigen(meldung, fehler.message);
      knoepfe.forEach((knopf) => { knopf.disabled = false; });
    }
  };

  /**
   * Baut eine Zeile der Liste.
   *
   * textContent statt innerHTML: Namen sind Eingaben der Mitglieder.
   *
   * @param {import('../services/empfang.js').OffenerNachweis} eintrag
   * @returns {HTMLLIElement}
   */
  const zeileBauen = (eintrag) => {
    const name = `${eintrag.vorname} ${eintrag.nachname}`;

    const zeile = document.createElement('li');
    zeile.className = 'empfang-eintrag';
    zeile.dataset.fristVerpasst = eintrag.fristVerpasst ? 'ja' : 'nein';

    const text = document.createElement('div');
    text.className = 'empfang-text';

    const titel = document.createElement('p');
    titel.className = 'empfang-name';
    titel.textContent = name;

    const angaben = document.createElement('p');
    angaben.className = 'empfang-angaben';
    angaben.textContent = `${NACHWEISARTEN[eintrag.art]}, `
      + (eintrag.gueltigBis === null ? 'unbefristet' : `bis ${datumAnzeigen(eintrag.gueltigBis)}`);

    const frist = document.createElement('p');
    frist.className = 'empfang-frist';
    frist.textContent = eintrag.fristVerpasst
      ? `Frist am ${datumAnzeigen(eintrag.vorzeigenBis)} verpasst — der Preis gilt erst nach dem Bestätigen wieder`
      : `Zeigen bis ${datumAnzeigen(eintrag.vorzeigenBis)}`;

    text.append(titel, angaben, frist);

    const aktionen = document.createElement('div');
    aktionen.className = 'empfang-aktionen';

    const ja = document.createElement('button');
    ja.type = 'button';
    ja.className = 'button';
    ja.textContent = 'Bestätigen';
    ja.setAttribute('aria-label', `${name} bestätigen`);

    ja.addEventListener('click', () => {
      if (!window.confirm(`Ausweis von ${name} gesehen, Foto und Name passen? Der Name ist danach für immer fest.`)) {
        return;
      }

      entscheiden(zeile, () => bestaetigen(eintrag.mitgliedId), `${name} ist bestätigt.`);
    });

    const nein = document.createElement('button');
    nein.type = 'button';
    nein.className = 'empfang-ablehnen';
    nein.textContent = 'Ablehnen';
    nein.setAttribute('aria-label', `Nachweis von ${name} ablehnen`);

    nein.addEventListener('click', () => {
      if (!window.confirm(`Nachweis von ${name} ablehnen? Er wird entfernt, und ein ermäßigter Tarif gilt ab dem nächsten Monatsersten zum Standardpreis.`)) {
        return;
      }

      entscheiden(zeile, () => ablehnen(eintrag.mitgliedId), `Nachweis von ${name} abgelehnt und entfernt.`);
    });

    aktionen.append(ja, nein);
    zeile.append(text, aktionen);

    return zeile;
  };

  /**
   * Zeichnet die Liste neu.
   *
   * @param {{offen: Array<import('../services/empfang.js').OffenerNachweis>}} stand
   * @returns {void}
   */
  function listeZeigen(stand) {
    liste.replaceChildren(...stand.offen.map(zeileBauen));
    $('#empfang-leer').hidden = stand.offen.length > 0;
  }

  try {
    listeZeigen(await offeneLaden());
  } catch (fehler) {
    // 401: Die Sitzung ist abgelaufen, seit die Seite geladen wurde.
    if (fehler.status === 401) {
      window.location.assign(seite.dataset.login);
    } else {
      meldungZeigen(meldung, fehler.message);
    }
  }
}
