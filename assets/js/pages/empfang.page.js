/**
 * @file        assets/js/pages/empfang.page.js
 * @layer       2 – Seitenskript
 * @description Zeigt dem Personal die Tabelle der Ausweise, die am Empfang
 *              noch gezeigt werden müssen, und schickt Bestätigen und
 *              Ablehnen an den Server.
 *
 *              Suche (Name oder E-Mail) und Filter (Frist läuft / verpasst)
 *              arbeiten nur im Browser auf der geladenen Liste - der Server
 *              liefert ohnehin alle offenen Einträge. "Neu laden" holt die
 *              Liste noch einmal, falls am zweiten Tresen jemand bestätigt hat.
 *
 *              Bestätigen und Ablehnen fragen vorher nach: Bestätigen sperrt
 *              den Namen für immer, Ablehnen entfernt den Nachweis. Wer was
 *              darf, prüft allein api/empfang.php.
 * @see         empfang.php
 * @see         assets/js/services/empfang.js
 */

import { $ } from '../lib/dom.js';
import { ablehnen, bestaetigen, offeneLaden } from '../services/empfang.js';
import { meldungZeigen } from '../components/auth-formular.js';

const seite = $('#empfang-seite');

if (seite) {
  const liste = $('#empfang-liste');
  const leer = $('#empfang-leer');
  const anzahl = $('#empfang-anzahl');
  const meldung = $('#empfang-meldung');
  const suche = $('#empfang-suche');
  const filter = $('#empfang-filter');
  const neuKnopf = $('#empfang-neu');

  /** Beschriftung der Nachweisarten - dieselben wie unter "Mein Konto". */
  const NACHWEISARTEN = {
    schueler: 'Schülerausweis',
    student: 'Studierendenausweis',
    senior: 'Senior',
  };

  /**
   * Alle offenen Einträge, wie der Server sie zuletzt geliefert hat. Suche
   * und Filter zeigen davon nur einen Teil.
   *
   * @type {Array<import('../services/empfang.js').OffenerNachweis>}
   */
  let offen = [];

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
   * Bringt Text in eine Form, in der die Suche Groß- und Kleinschreibung
   * und Akzente nicht unterscheidet: "Müller" findet man auch mit "muller".
   *
   * @param {string} text
   * @returns {string}
   */
  const vergleichbar = (text) => text
    .toLocaleLowerCase('de')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '');

  /**
   * Die Einträge, die zu Suche und Filter passen.
   *
   * @returns {Array<import('../services/empfang.js').OffenerNachweis>}
   */
  const sichtbare = () => {
    const begriff = vergleichbar(suche.value.trim());

    return offen.filter((eintrag) => {
      if (filter.value === 'offen' && eintrag.fristVerpasst) {
        return false;
      }
      if (filter.value === 'verpasst' && !eintrag.fristVerpasst) {
        return false;
      }

      return vergleichbar(`${eintrag.vorname} ${eintrag.nachname} ${eintrag.email}`).includes(begriff);
    });
  };

  /**
   * Baut eine Zelle mit einer fett gesetzten ersten und einer grauen
   * zweiten Zeile.
   *
   * textContent statt innerHTML: Namen und E-Mails sind Eingaben der
   * Mitglieder.
   *
   * @param {string} kopf
   * @param {string} zusatz
   * @param {string} beschriftung  Spaltenname, auf schmalen Bildschirmen sichtbar
   * @returns {HTMLTableCellElement}
   */
  const zelleBauen = (kopf, zusatz, beschriftung) => {
    const zelle = document.createElement('td');
    zelle.dataset.spalte = beschriftung;

    const oben = document.createElement('span');
    oben.className = 'empfang-zeile-kopf';
    oben.textContent = kopf;

    const unten = document.createElement('span');
    unten.className = 'empfang-zeile-zusatz';
    unten.textContent = zusatz;

    zelle.append(oben, unten);

    return zelle;
  };

  /**
   * Führt Bestätigen oder Ablehnen aus und zeichnet danach die Tabelle neu.
   *
   * @param {HTMLTableRowElement} zeile               deren Knöpfe gesperrt werden
   * @param {() => Promise<{offen: Array}>} aktion    Aufruf des Service
   * @param {string} erfolg                           Meldung danach
   * @returns {Promise<void>}
   */
  const entscheiden = async (zeile, aktion, erfolg) => {
    const knoepfe = zeile.querySelectorAll('button');

    knoepfe.forEach((knopf) => { knopf.disabled = true; });
    meldungZeigen(meldung, '');

    try {
      offen = (await aktion()).offen;
      tabelleZeigen();
      meldungZeigen(meldung, erfolg, true);
    } catch (fehler) {
      meldungZeigen(meldung, fehler.message);
      knoepfe.forEach((knopf) => { knopf.disabled = false; });
    }
  };

  /**
   * Baut eine Zeile der Tabelle.
   *
   * @param {import('../services/empfang.js').OffenerNachweis} eintrag
   * @returns {HTMLTableRowElement}
   */
  const zeileBauen = (eintrag) => {
    const name = `${eintrag.vorname} ${eintrag.nachname}`;

    const zeile = document.createElement('tr');
    zeile.dataset.fristVerpasst = eintrag.fristVerpasst ? 'ja' : 'nein';

    const person = zelleBauen(name, eintrag.email, 'Person');
    const nachweis = zelleBauen(
      NACHWEISARTEN[eintrag.art],
      eintrag.gueltigBis === null ? 'unbefristet' : `gültig bis ${datumAnzeigen(eintrag.gueltigBis)}`,
      'Nachweis',
    );
    const status = eintrag.fristVerpasst
      ? zelleBauen('Frist verpasst', `seit ${datumAnzeigen(eintrag.vorzeigenBis)} · Preis ruht`, 'Status')
      : zelleBauen('Frist läuft', `zeigen bis ${datumAnzeigen(eintrag.vorzeigenBis)}`, 'Status');
    status.classList.add('empfang-status');

    const aktionen = document.createElement('td');
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
    zeile.append(person, nachweis, status, aktionen);

    return zeile;
  };

  /**
   * Zeichnet die Tabelle neu - mit dem, was zu Suche und Filter passt.
   *
   * @returns {void}
   */
  function tabelleZeigen() {
    const treffer = sichtbare();

    liste.replaceChildren(...treffer.map(zeileBauen));

    anzahl.textContent = treffer.length === offen.length
      ? `${offen.length} offen`
      : `${treffer.length} von ${offen.length}`;

    leer.hidden = treffer.length > 0;
    leer.textContent = offen.length === 0
      ? 'Gerade muss niemand seinen Ausweis zeigen.'
      : 'Niemand passt zu Suche und Filter.';
  }

  /**
   * Holt die Liste vom Server und zeigt sie an.
   *
   * @returns {Promise<void>}
   */
  const laden = async () => {
    try {
      offen = (await offeneLaden()).offen;
      tabelleZeigen();
    } catch (fehler) {
      // 401: Die Sitzung ist abgelaufen, seit die Seite geladen wurde.
      if (fehler.status === 401) {
        window.location.assign(seite.dataset.login);
      } else {
        meldungZeigen(meldung, fehler.message);
      }
    }
  };

  suche.addEventListener('input', tabelleZeigen);
  filter.addEventListener('change', tabelleZeigen);

  neuKnopf.addEventListener('click', async () => {
    neuKnopf.disabled = true;
    meldungZeigen(meldung, '');
    await laden();
    neuKnopf.disabled = false;
  });

  await laden();
}
