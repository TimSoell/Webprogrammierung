/**
 * @file        assets/js/pages/mein-konto.page.js
 * @layer       2 – Seitenskript
 * @description Lädt die Stammdaten des angemeldeten Mitglieds in die Seite
 *              und meldet über den Button "Abmelden" ab.
 * @see         mein-konto.php
 * @see         assets/js/services/mitglieder.js
 */

import { $ } from '../lib/dom.js';
import { abmelden, angemeldetesMitgliedLaden } from '../services/mitglieder.js';
import { meldungZeigen } from '../components/auth-formular.js';

const seite = $('#konto-seite');

if (seite) {
  const meldung = $('#konto-meldung');

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
}
