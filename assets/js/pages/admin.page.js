/**
 * @file        assets/js/pages/admin.page.js
 * @layer       2 – Seitenskript
 * @description Zeigt dem Admin die Tabelle aller Konten mit der Rolle
 *              Personal und schickt "Zum Personal machen" und "Rolle
 *              entziehen" an den Server.
 *
 *              Entziehen fragt vorher nach. Wer das alles darf, prüft allein
 *              api/personal.php.
 * @see         admin.php
 * @see         assets/js/services/personal.js
 */

import { $ } from '../lib/dom.js';
import { personalLaden, rolleEntziehen, zumPersonalMachen } from '../services/personal.js';
import { formularAbsenden, meldungZeigen } from '../components/auth-formular.js';

const seite = $('#admin-seite');

if (seite) {
  const formular = $('#admin-formular');
  const eingabe = $('#admin-email');
  const meldung = $('#admin-meldung');
  const liste = $('#admin-liste');
  const leer = $('#admin-leer');
  const anzahl = $('#admin-anzahl');

  /**
   * Alle Konten mit der Rolle Personal, wie der Server sie zuletzt geliefert hat.
   *
   * @type {Array<import('../services/personal.js').PersonalKonto>}
   */
  let personal = [];

  /**
   * Baut eine Zeile: Name und E-Mail, daneben "Rolle entziehen".
   *
   * textContent statt innerHTML: Namen und E-Mails sind Eingaben der
   * Mitglieder.
   *
   * @param {import('../services/personal.js').PersonalKonto} eintrag
   * @returns {HTMLTableRowElement}
   */
  const zeileBauen = (eintrag) => {
    const name = `${eintrag.vorname} ${eintrag.nachname}`;

    const person = document.createElement('td');
    person.dataset.spalte = 'Person';

    const kopf = document.createElement('span');
    kopf.className = 'empfang-zeile-kopf';
    kopf.textContent = name;

    const zusatz = document.createElement('span');
    zusatz.className = 'empfang-zeile-zusatz';
    zusatz.textContent = eintrag.email;

    person.append(kopf, zusatz);

    const aktionen = document.createElement('td');
    aktionen.className = 'empfang-aktionen';

    const knopf = document.createElement('button');
    knopf.type = 'button';
    knopf.className = 'button button--ghost';
    knopf.textContent = 'Rolle entziehen';
    knopf.setAttribute('aria-label', `${name} die Rolle Personal entziehen`);

    knopf.addEventListener('click', async () => {
      if (!window.confirm(`${name} die Rolle Personal entziehen? Danach kommt ${eintrag.vorname} nicht mehr an den Empfang.`)) {
        return;
      }

      knopf.disabled = true;
      meldungZeigen(meldung, '');

      try {
        personal = (await rolleEntziehen(eintrag.email)).personal;
        listeZeigen();
        meldungZeigen(meldung, `${name} gehört nicht mehr zum Personal.`, true);
      } catch (fehler) {
        meldungZeigen(meldung, fehler.message);
        knopf.disabled = false;
      }
    });

    aktionen.append(knopf);

    const zeile = document.createElement('tr');
    zeile.append(person, aktionen);

    return zeile;
  };

  /**
   * Zeichnet die Tabelle neu.
   *
   * @returns {void}
   */
  function listeZeigen() {
    liste.replaceChildren(...personal.map(zeileBauen));
    anzahl.textContent = personal.length === 1 ? '1 Konto' : `${personal.length} Konten`;
    leer.hidden = personal.length > 0;
  }

  formular.addEventListener('submit', (event) => {
    event.preventDefault();

    formularAbsenden(formular, meldung, async () => {
      // Der Server schreibt E-Mails klein - so findet sich das Konto danach
      // in der Liste wieder.
      const email = eingabe.value.trim().toLowerCase();

      personal = (await zumPersonalMachen(email)).personal;
      listeZeigen();
      formular.reset();

      // Mit Namen, damit der Admin sieht, ob er das richtige Konto erwischt hat.
      const neu = personal.find((eintrag) => eintrag.email === email);
      meldungZeigen(meldung, `${neu.vorname} ${neu.nachname} gehört jetzt zum Personal.`, true);
    });
  });

  try {
    personal = (await personalLaden()).personal;
    listeZeigen();
  } catch (fehler) {
    // 401: Die Sitzung ist abgelaufen, seit die Seite geladen wurde.
    if (fehler.status === 401) {
      window.location.assign(seite.dataset.login);
    } else {
      meldungZeigen(meldung, fehler.message);
    }
  }
}
