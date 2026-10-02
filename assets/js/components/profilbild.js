/**
 * @file        assets/js/components/profilbild.js
 * @layer       2 – Komponente
 * @description Das eigene Profilbild in "Mein Konto": Ein Klick auf den
 *              runden Kreis öffnet die Dateiauswahl, das Foto wird im Browser
 *              quadratisch zugeschnitten, verkleinert und gespeichert.
 *              Darunter der Schalter "Profilbild bei meinen Bewertungen
 *              zeigen" und "Bild entfernen".
 *
 *              Eigene Komponente statt Teil von mein-konto.page.js - wie
 *              meine-termine.js, damit das Seitenskript nicht noch länger
 *              wird.
 * @see         mein-konto.php
 * @see         assets/js/services/profilbilder.js
 * @see         assets/js/lib/bild.js
 */

import { $ } from '../lib/dom.js';
import { quadratVerkleinern } from '../lib/bild.js';
import { meldungZeigen } from './auth-formular.js';
import { profilbildEntfernen, profilbildFreigeben, profilbildSpeichern } from '../services/profilbilder.js';

/** Kantenlänge des gespeicherten Bildes. Reicht für Kreis und Kachel. */
const KANTE = 512;

/**
 * Richtet Profilbild, Schalter und "Bild entfernen" ein. Steigt ohne Fehler
 * aus, wenn die Seite kein Profilbild-Feld hat.
 *
 * @param {{profilbild: string|null, profilbildOeffentlich: boolean}} mitglied
 *        Antwort von angemeldetesMitgliedLaden()
 * @returns {void}
 */
export function profilbildEinrichten(mitglied) {
  const eingabe = $('#profilbild-datei');

  if (!eingabe) {
    return;
  }

  const baseUrl = document.documentElement.dataset.baseUrl ?? '/';
  const kreis = $('#konto-avatar');
  const bild = $('#konto-avatar-bild');
  const optionen = $('#profilbild-optionen');
  const schalter = $('#profilbild-oeffentlich');
  const entfernen = $('#profilbild-entfernen');
  const meldung = $('#profilbild-meldung');

  /**
   * Zeigt das Bild oder das Personen-Symbol und blendet die Optionen ein
   * oder aus.
   *
   * @param {string|null} url         Adresse relativ zur BASE_URL
   * @param {boolean} oeffentlich
   * @returns {void}
   */
  const zeigen = (url, oeffentlich) => {
    kreis.classList.toggle('konto-avatar--bild', url !== null);
    optionen.hidden = url === null;
    schalter.checked = oeffentlich;

    if (url === null) {
      bild.removeAttribute('src');
    } else {
      bild.src = baseUrl + url;
    }
  };

  zeigen(mitglied.profilbild, mitglied.profilbildOeffentlich);

  eingabe.addEventListener('change', async () => {
    const datei = eingabe.files[0];

    if (!datei) {
      return;
    }

    meldungZeigen(meldung, '');
    kreis.classList.add('konto-avatar--laedt');

    try {
      const { daten } = await quadratVerkleinern(datei, KANTE);
      const antwort = await profilbildSpeichern(daten);

      zeigen(antwort.profilbild, antwort.oeffentlich);
      meldungZeigen(meldung, 'Profilbild gespeichert.', true);
    } catch (fehler) {
      meldungZeigen(meldung, fehler.message);
    } finally {
      kreis.classList.remove('konto-avatar--laedt');
      // Sonst löst dieselbe Datei beim zweiten Mal kein "change" aus.
      eingabe.value = '';
    }
  });

  schalter.addEventListener('change', async () => {
    meldungZeigen(meldung, '');
    schalter.disabled = true;

    try {
      await profilbildFreigeben(schalter.checked);
    } catch (fehler) {
      // Zurückstellen, damit der Haken zeigt, was wirklich gespeichert ist.
      schalter.checked = !schalter.checked;
      meldungZeigen(meldung, fehler.message);
    } finally {
      schalter.disabled = false;
    }
  });

  entfernen.addEventListener('click', async () => {
    // Kein Rückgängig - deshalb einmal nachfragen.
    if (!window.confirm('Profilbild wirklich entfernen?')) {
      return;
    }

    meldungZeigen(meldung, '');

    try {
      await profilbildEntfernen();
      zeigen(null, true);
    } catch (fehler) {
      meldungZeigen(meldung, fehler.message);
    }
  });
}
