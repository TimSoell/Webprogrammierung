/**
 * @file        assets/js/pages/mein-konto.page.js
 * @layer       2 – Seitenskript
 * @description Lädt die Stammdaten, die gemerkte Programm-Auswahl, die
 *              laufende Mitgliedschaft und die Nachweise des
 *              angemeldeten Mitglieds in die Seite und meldet über den
 *              Button "Abmelden" ab.
 * @see         mein-konto.php
 * @see         assets/js/services/mitglieder.js
 * @see         assets/js/services/auswahl.js
 * @see         assets/js/services/mitgliedschaften.js
 */

import { $ } from '../lib/dom.js';
import { abmelden, angemeldetesMitgliedLaden } from '../services/mitglieder.js';
import { auswahlEntfernen, meineAuswahlLaden } from '../services/auswahl.js';
import { standLaden } from '../services/mitgliedschaften.js';
import { alleLaden as nachweiseLaden, demoEintragen, hochladen } from '../services/nachweise.js';
import { formularAbsenden, meldungZeigen } from '../components/auth-formular.js';

const seite = $('#konto-seite');

if (seite) {
  const meldung = $('#konto-meldung');
  const liste = $('#auswahl-liste');
  const auswahlMeldung = $('#auswahl-meldung');
  const baseUrl = document.documentElement.dataset.baseUrl ?? '/';

  /**
   * Baut eine Zeile der Liste "Meine Auswahl".
   *
   * textContent statt innerHTML: Programm- und Merkmalnamen kommen aus der
   * Datenbank und dürfen nie als HTML ausgeführt werden.
   *
   * @param {{slug: string, programm: string, level: string|null, format: string|null}} eintrag
   * @param {() => Promise<void>} neuLaden  Nach dem Entfernen erneut anzeigen
   * @returns {HTMLElement}
   */
  const zeileBauen = (eintrag, neuLaden) => {
    const zeile = document.createElement('li');
    zeile.className = 'auswahl-eintrag';

    const link = document.createElement('a');
    link.className = 'auswahl-programm';
    link.href = `${baseUrl}programme/${eintrag.slug}.php`;
    link.textContent = eintrag.programm;

    const angaben = document.createElement('p');
    angaben.className = 'auswahl-angaben';
    // Fällt ein Merkmal in der Datenbank weg, steht hier null - dann nur
    // das andere zeigen statt "Level: null".
    angaben.textContent = [
      eintrag.level ? `Level: ${eintrag.level}` : null,
      eintrag.format ? `Format: ${eintrag.format}` : null,
    ].filter(Boolean).join(' · ');

    const entfernen = document.createElement('button');
    entfernen.type = 'button';
    entfernen.className = 'auswahl-entfernen';
    entfernen.textContent = 'Entfernen';
    entfernen.setAttribute('aria-label', `${eintrag.programm} aus meiner Auswahl entfernen`);

    entfernen.addEventListener('click', async () => {
      entfernen.disabled = true;
      meldungZeigen(auswahlMeldung, '');

      try {
        await auswahlEntfernen(eintrag.slug);
        await neuLaden();
      } catch (fehler) {
        meldungZeigen(auswahlMeldung, fehler.message);
        entfernen.disabled = false;
      }
    });

    zeile.append(link, angaben, entfernen);

    return zeile;
  };

  /**
   * Lädt die gemerkte Auswahl und zeigt sie an.
   *
   * @returns {Promise<void>}
   */
  const auswahlAnzeigen = async () => {
    const { auswahl } = await meineAuswahlLaden();

    if (auswahl.length === 0) {
      const leer = document.createElement('li');
      leer.className = 'auswahl-leer';
      leer.textContent = 'Du hast dir noch nichts gemerkt. Öffne ein Programm und wähle dort Level und Format.';
      liste.replaceChildren(leer);
      return;
    }

    liste.replaceChildren(...auswahl.map((eintrag) => zeileBauen(eintrag, auswahlAnzeigen)));
  };

  /** Beschriftung der drei Preisgruppen. Die Kennungen kommen vom Server. */
  const PREISGRUPPEN = {
    standard: 'Standard',
    ermaessigt: 'Schüler & Studierende',
    senior: 'Senioren',
  };

  const preisFormat = new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR',
  });

  /**
   * Macht aus '2026-09-21' den Text '21.09.2026'.
   *
   * Von Hand und nicht über new Date(): Ein Datum ohne Uhrzeit liest der
   * Browser als UTC-Mitternacht und kann dabei je nach Zeitzone einen Tag
   * daneben landen.
   *
   * @param {string} datum  Datum in der Form 'JJJJ-MM-TT'
   * @returns {string}
   */
  function datumAnzeigen(datum) {
    const [jahr, monat, tag] = datum.split('-');

    return `${tag}.${monat}.${jahr}`;
  }

  /**
   * Trägt den Tarifstand in die Seite ein - oder den Hinweis, dass noch
   * kein Tarif gewählt wurde.
   *
   * @param {import('../services/mitgliedschaften.js').Tarifstand} stand
   * @returns {void}
   */
  function mitgliedschaftZeigen(stand) {
    const laufend = stand.mitgliedschaft;

    $('#konto-tarif-daten').hidden = laufend === null;
    $('#konto-ohne-tarif').hidden = laufend !== null;

    if (laufend === null) {
      return;
    }

    $('#konto-tarif').textContent = laufend.name;
    $('#konto-preisgruppe').textContent = PREISGRUPPEN[laufend.preisgruppe];
    $('#konto-beitrag').textContent = `${preisFormat.format(laufend.preisMonatlich)} / Monat`;
    $('#konto-beginn').textContent = datumAnzeigen(laufend.beginntAm);

    $('#konto-wechsel-zeile').hidden = stand.geplant === null;

    if (stand.geplant !== null) {
      $('#konto-wechsel').textContent = `${stand.geplant.name} ab ${datumAnzeigen(stand.geplant.beginntAm)}`;
    }
  }

  /** Beschriftung der Nachweisarten, für die Liste. */
  const NACHWEISARTEN = {
    schueler: 'Schülerausweis',
    student: 'Studierendenausweis',
    senior: 'Senior',
  };

  const nachweisMeldung = $('#nachweis-meldung');
  const nachweisArt = $('#nachweis-art');

  /**
   * Zeichnet die Liste der Nachweise und schaltet das Formular zwischen
   * Bild- und Datumsfeld um.
   *
   * @param {import('../services/nachweise.js').Nachweisstand} stand
   * @returns {void}
   */
  function nachweiseZeigen(stand) {
    const heute = new Date().toISOString().slice(0, 10);

    $('#nachweis-leer').hidden = stand.nachweise.length > 0;
    $('#nachweis-liste').replaceChildren(...stand.nachweise.map((nachweis) => {
      const zeile = document.createElement('li');

      // Ein Nachweis ohne Datum ist unbefristet - das gibt es nur bei Senioren.
      const abgelaufen = nachweis.gueltigBis !== null && nachweis.gueltigBis < heute;
      const bis = nachweis.gueltigBis === null
        ? 'unbefristet'
        : `bis ${datumAnzeigen(nachweis.gueltigBis)}`;

      zeile.className = 'nachweis-eintrag';
      zeile.dataset.abgelaufen = abgelaufen ? 'ja' : 'nein';
      zeile.textContent = `${NACHWEISARTEN[nachweis.art]} — ${bis}`
        + (abgelaufen ? ' (abgelaufen)' : '')
        + (nachweis.quelle === 'demo' ? ' · im Demo-Modus eingetragen' : '');

      return zeile;
    }));

    // Ohne Schlüssel gibt es nichts auszulesen, dann wird das Datum getippt.
    $('#nachweis-bild-feld').hidden = !stand.kiVerfuegbar;
    $('#nachweis-datum-feld').hidden = stand.kiVerfuegbar;
  }

  // Beim Seniorennachweis wird nicht das Ablaufdatum gebraucht, sondern das
  // Geburtsdatum - im Demo-Modus muss das Feld also anders heißen.
  nachweisArt.addEventListener('change', () => {
    $('#nachweis-datum-label').textContent = nachweisArt.value === 'senior'
      ? 'Geburtsdatum'
      : 'Gültig bis';
  });

  $('#nachweis-form').addEventListener('submit', async (ereignis) => {
    ereignis.preventDefault();

    const formular = ereignis.target;
    const datei = $('#nachweis-bild').files[0];
    const mitBild = !$('#nachweis-bild-feld').hidden;

    if (mitBild && !datei) {
      meldungZeigen(nachweisMeldung, 'Bitte wähle ein Foto deines Ausweises aus.');

      return;
    }

    await formularAbsenden(formular, nachweisMeldung, async () => {
      const stand = mitBild
        ? await hochladen(nachweisArt.value, datei)
        : await demoEintragen(nachweisArt.value, $('#nachweis-datum').value);

      nachweiseZeigen(stand);
      formular.reset();
      meldungZeigen(nachweisMeldung, 'Nachweis gespeichert. Der Preis steht dir ab sofort offen.', true);
    });
  });

  $('#abmelden').addEventListener('click', async () => {
    try {
      await abmelden();
      window.location.assign(seite.dataset.abgemeldet);
    } catch (fehler) {
      meldungZeigen(meldung, fehler.message);
    }
  });

  try {
    const mitglied = await angemeldetesMitgliedLaden();

    // textContent statt innerHTML: Namen sind Nutzereingaben.
    $('#konto-vorname').textContent = mitglied.vorname;
    $('#konto-nachname').textContent = mitglied.nachname;
    $('#konto-email').textContent = mitglied.email;

    mitgliedschaftZeigen(await standLaden());
    nachweiseZeigen(await nachweiseLaden());
  } catch (fehler) {
    // 401: Die Sitzung ist abgelaufen, seit die Seite geladen wurde.
    if (fehler.status === 401) {
      window.location.assign(seite.dataset.login);
    } else {
      meldungZeigen(meldung, fehler.message);
    }
  }

  // Eigener try/catch: Ein Fehler hier soll die Stammdaten oben nicht
  // mitreißen, und umgekehrt.
  try {
    await auswahlAnzeigen();
  } catch (fehler) {
    meldungZeigen(auswahlMeldung, fehler.message);
  }
}
