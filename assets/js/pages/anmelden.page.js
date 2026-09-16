/**
 * @file        assets/js/pages/anmelden.page.js
 * @layer       2 – Seitenskript
 * @description Verhalten der Auth-Seite: Umschalten zwischen den Reitern
 *              Login und Registrierung, "Passwort vergessen" und das
 *              Absenden aller drei Formulare.
 *
 *              Nach erfolgreichem Login oder Registrierung geht es zu der
 *              Adresse, die anmelden.php in data-weiter hinterlegt.
 * @see         anmelden.php
 * @see         assets/js/services/mitglieder.js
 */

import { $, $$ } from '../lib/dom.js';
import { anmelden, passwortResetAnfordern, registrieren } from '../services/mitglieder.js';
import { formularAbsenden, meldungZeigen } from '../components/auth-formular.js';
import { initPasswortKriterien, passwortPruefen } from '../components/passwort-kriterien.js';

const seite = $('#auth-seite');

if (seite) {
  const formLogin = $('#form-login');
  const formVergessen = $('#form-vergessen');
  const formRegistrierung = $('#form-registrierung');

  // --- Reiter ---------------------------------------------------------------
  // aria-selected sagt Screenreadern, welcher Reiter aktiv ist. Das CSS
  // hängt die Hervorhebung an dasselbe Attribut, es gibt keine Extra-Klasse.
  const reiter = $$('[role="tab"]', seite);

  reiter.forEach((tab) => {
    tab.addEventListener('click', () => {
      reiter.forEach((anderer) => {
        const aktiv = anderer === tab;
        anderer.setAttribute('aria-selected', String(aktiv));
        $(`#${anderer.getAttribute('aria-controls')}`).hidden = !aktiv;
      });

      // Ein Klick auf "Login" führt immer zurück zum Login-Formular,
      // auch wenn vorher "Passwort vergessen" offen war.
      formLogin.hidden = false;
      formVergessen.hidden = true;
    });
  });

  // --- Passwort vergessen ein- und ausblenden -------------------------------
  $('#link-vergessen').addEventListener('click', () => {
    formLogin.hidden = true;
    formVergessen.hidden = false;
    // Die eingetippte Adresse mitnehmen, dann muss man sie nicht doppelt schreiben.
    formVergessen.email.value = formLogin.email.value;
    formVergessen.email.focus();
  });

  $('#link-zurueck').addEventListener('click', () => {
    formVergessen.hidden = true;
    formLogin.hidden = false;
    formLogin.email.focus();
  });

  // --- Login ----------------------------------------------------------------
  formLogin.addEventListener('submit', (event) => {
    event.preventDefault();

    formularAbsenden(formLogin, $('#login-meldung'), async () => {
      await anmelden(formLogin.email.value, formLogin.passwort.value);
      window.location.assign(seite.dataset.weiter);
    });
  });

  // --- Passwort vergessen ---------------------------------------------------
  const demo = $('#vergessen-demo');

  formVergessen.addEventListener('submit', (event) => {
    event.preventDefault();
    demo.hidden = true;

    const meldung = $('#vergessen-meldung');

    formularAbsenden(formVergessen, meldung, async () => {
      const antwort = await passwortResetAnfordern(formVergessen.email.value);
      meldungZeigen(meldung, antwort.nachricht, true);

      // Nur im Demo-Modus vorhanden - siehe api/passwort-reset.php.
      if (antwort.demoLink) {
        $('#vergessen-demo-link').href = antwort.demoLink;
        demo.hidden = false;
      }
    });
  });

  // --- Registrierung --------------------------------------------------------
  initPasswortKriterien(formRegistrierung.passwort, $('#passwort-kriterien'));

  formRegistrierung.addEventListener('submit', (event) => {
    event.preventDefault();

    const meldung = $('#registrierung-meldung');
    const problem = passwortPruefen(
      formRegistrierung.passwort.value,
      formRegistrierung.passwortWiederholung.value,
    );

    if (problem) {
      meldungZeigen(meldung, problem);
      return;
    }

    formularAbsenden(formRegistrierung, meldung, async () => {
      await registrieren({
        vorname: formRegistrierung.vorname.value,
        nachname: formRegistrierung.nachname.value,
        email: formRegistrierung.email.value,
        passwort: formRegistrierung.passwort.value,
        passwortWiederholung: formRegistrierung.passwortWiederholung.value,
      });
      window.location.assign(seite.dataset.weiter);
    });
  });
}
