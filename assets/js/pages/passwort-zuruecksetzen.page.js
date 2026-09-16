/**
 * @file        assets/js/pages/passwort-zuruecksetzen.page.js
 * @layer       2 – Seitenskript
 * @description Neues Passwort setzen über den Link aus der (Demo-)E-Mail.
 *
 *              selector und token stehen in der Adresse der Seite, z. B.
 *              passwort-zuruecksetzen.php?selector=...&token=...
 *              Das Skript liest sie aus und schickt sie mit dem neuen
 *              Passwort an den Server. Geprüft werden sie nur dort.
 * @see         passwort-zuruecksetzen.php
 * @see         api/passwort-reset.php
 */

import { $ } from '../lib/dom.js';
import { passwortZuruecksetzen } from '../services/mitglieder.js';
import { formularAbsenden, meldungZeigen } from '../components/auth-formular.js';
import { initPasswortKriterien, passwortPruefen } from '../components/passwort-kriterien.js';

const form = $('#form-reset');
const erfolg = $('#reset-erfolg');

if (form && erfolg) {
  const parameter = new URLSearchParams(window.location.search);

  initPasswortKriterien(form.passwort, $('#passwort-kriterien'));

  form.addEventListener('submit', (event) => {
    event.preventDefault();

    const meldung = $('#reset-meldung');
    const problem = passwortPruefen(form.passwort.value, form.passwortWiederholung.value);

    if (problem) {
      meldungZeigen(meldung, problem);
      return;
    }

    formularAbsenden(form, meldung, async () => {
      await passwortZuruecksetzen({
        selector: parameter.get('selector') ?? '',
        token: parameter.get('token') ?? '',
        passwort: form.passwort.value,
        passwortWiederholung: form.passwortWiederholung.value,
      });

      form.hidden = true;
      erfolg.hidden = false;
    });
  });
}
