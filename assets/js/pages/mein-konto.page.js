/**
 * @file        assets/js/pages/mein-konto.page.js
 * @layer       2 – Seitenskript
 * @description Lädt die Stammdaten und die gemerkte Programm-Auswahl des
 *              angemeldeten Mitglieds in die Seite und meldet über den
 *              Button "Abmelden" ab.
 * @see         mein-konto.php
 * @see         assets/js/services/mitglieder.js
 * @see         assets/js/services/auswahl.js
 */

import { $ } from '../lib/dom.js';
import { abmelden, angemeldetesMitgliedLaden } from '../services/mitglieder.js';
import { auswahlEntfernen, meineAuswahlLaden } from '../services/auswahl.js';
import { meldungZeigen } from '../components/auth-formular.js';

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
