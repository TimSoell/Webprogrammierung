/**
 * @file        assets/js/pages/empfang.page.js
 * @layer       2 – Seitenskript
 * @description Zeigt dem Personal die Tabelle der Konten, die am Empfang
 *              noch nicht mit dem Personalausweis bestätigt sind, und
 *              schickt das Bestätigen an den Server.
 *
 *              Suche (Name oder E-Mail) und Filter (mit Tarif / Frist
 *              verpasst) arbeiten nur im Browser auf der geladenen Liste -
 *              der Server liefert ohnehin alle unbestätigten Konten. "Neu
 *              laden" holt die Liste noch einmal, falls am zweiten Tresen
 *              jemand bestätigt hat.
 *
 *              Bestätigen fragt vorher nach, denn es sperrt den Namen für
 *              immer. Wer das darf, prüft allein api/empfang.php.
 * @see         empfang.php
 * @see         assets/js/services/empfang.js
 */

import { $ } from '../lib/dom.js';
import { bestaetigen, offeneLaden } from '../services/empfang.js';
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

  /** Beschriftung der Ermäßigungsnachweise - dieselben wie unter "Mein Konto". */
  const NACHWEISARTEN = {
    schueler: 'Schülerausweis',
    student: 'Studierendenausweis',
    senior: 'Senior',
  };

  /**
   * Alle unbestätigten Konten, wie der Server sie zuletzt geliefert hat.
   * Suche und Filter zeigen davon nur einen Teil.
   *
   * @type {Array<import('../services/empfang.js').OffenesKonto>}
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
   * Wie dringend ein Konto ist: Mit Tarif läuft eine Frist, die auch schon
   * verpasst sein kann. Ohne Tarif eilt nichts.
   *
   * @param {import('../services/empfang.js').OffenesKonto} eintrag
   * @returns {'laeuft'|'verpasst'|'ohne'}
   */
  const standVon = (eintrag) => {
    if (eintrag.vorzeigenBis === null) {
      return 'ohne';
    }

    return eintrag.fristVerpasst ? 'verpasst' : 'laeuft';
  };

  /**
   * Die Einträge, die zu Suche und Filter passen.
   *
   * @returns {Array<import('../services/empfang.js').OffenesKonto>}
   */
  const sichtbare = () => {
    const begriff = vergleichbar(suche.value.trim());

    return offen.filter((eintrag) => {
      if (filter.value === 'tarif' && eintrag.vorzeigenBis === null) {
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
   * @param {string} zusatz        '' = keine zweite Zeile
   * @param {string} beschriftung  Spaltenname, auf schmalen Bildschirmen sichtbar
   * @returns {HTMLTableCellElement}
   */
  const zelleBauen = (kopf, zusatz, beschriftung) => {
    const zelle = document.createElement('td');
    zelle.dataset.spalte = beschriftung;

    const oben = document.createElement('span');
    oben.className = 'empfang-zeile-kopf';
    oben.textContent = kopf;
    zelle.append(oben);

    if (zusatz !== '') {
      const unten = document.createElement('span');
      unten.className = 'empfang-zeile-zusatz';
      unten.textContent = zusatz;
      zelle.append(unten);
    }

    return zelle;
  };

  /**
   * Die Zelle "Status": ob und wie eine Frist läuft.
   *
   * @param {import('../services/empfang.js').OffenesKonto} eintrag
   * @returns {HTMLTableCellElement}
   */
  const statusZelle = (eintrag) => {
    const stand = standVon(eintrag);

    // Mit Ermäßigung steht dabei, welcher Nachweis - und bei verpasster
    // Frist, dass der günstige Preis gerade ruht.
    const art = eintrag.ermaessigung === null ? '' : ` · ${NACHWEISARTEN[eintrag.ermaessigung]}`;

    const zelle = {
      ohne: () => zelleBauen('Ohne Tarif', 'muss nicht, kann aber', 'Status'),
      laeuft: () => zelleBauen('Frist läuft', `bis ${datumAnzeigen(eintrag.vorzeigenBis)}${art}`, 'Status'),
      verpasst: () => zelleBauen(
        'Frist verpasst',
        `seit ${datumAnzeigen(eintrag.vorzeigenBis)}${eintrag.ermaessigung === null ? '' : ' · ermäßigter Preis ruht'}`,
        'Status',
      ),
    }[stand]();

    zelle.classList.add('empfang-status');

    return zelle;
  };

  /**
   * Baut eine Zeile der Tabelle.
   *
   * @param {import('../services/empfang.js').OffenesKonto} eintrag
   * @returns {HTMLTableRowElement}
   */
  const zeileBauen = (eintrag) => {
    const name = `${eintrag.vorname} ${eintrag.nachname}`;

    const zeile = document.createElement('tr');
    zeile.dataset.stand = standVon(eintrag);

    const aktionen = document.createElement('td');
    aktionen.className = 'empfang-aktionen';

    const knopf = document.createElement('button');
    knopf.type = 'button';
    knopf.className = 'button';
    knopf.textContent = 'Bestätigen';
    knopf.setAttribute('aria-label', `Konto von ${name} bestätigen`);

    knopf.addEventListener('click', async () => {
      if (!window.confirm(`Personalausweis von ${name} gesehen? Das Foto passt zur Person, der Name zum Konto? Der Name ist danach für immer fest.`)) {
        return;
      }

      knopf.disabled = true;
      meldungZeigen(meldung, '');

      try {
        offen = (await bestaetigen(eintrag.mitgliedId)).offen;
        tabelleZeigen();
        meldungZeigen(meldung, `Das Konto von ${name} ist bestätigt.`, true);
      } catch (fehler) {
        meldungZeigen(meldung, fehler.message);
        knopf.disabled = false;
      }
    });

    aktionen.append(knopf);
    zeile.append(
      zelleBauen(name, eintrag.email, 'Person'),
      zelleBauen(datumAnzeigen(eintrag.registriertAm), '', 'Registriert'),
      statusZelle(eintrag),
      aktionen,
    );

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
      ? `${offen.length} unbestätigt`
      : `${treffer.length} von ${offen.length}`;

    leer.hidden = treffer.length > 0;
    leer.textContent = offen.length === 0
      ? 'Alle Konten sind bestätigt.'
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
