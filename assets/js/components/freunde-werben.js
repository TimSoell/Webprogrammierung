/**
 * @file        assets/js/components/freunde-werben.js
 * @layer       2 – Komponente
 * @description "Freunde werben" an zwei Stellen: das Fenster "Freund
 *              einladen" auf der Startseite (initFreundeWerben) und die
 *              Karte mit Gutscheinen und Einladungen unter "Mein Konto"
 *              (kontoWerbenAufbauen).
 *
 *              Das Fenster öffnet sich über den Button #werben-oeffnen ganz
 *              unten auf der Startseite.
 *
 *              ZWEI ZUSTÄNDE
 *              Erst das Formular mit Name und E-Mail des Freundes. Nach dem
 *              Absenden tritt an seine Stelle die Bestätigung mit dem Link
 *              zum Weitergeben. Die Seite verschickt selbst keine E-Mail.
 *
 *              Ohne Anmeldung gibt es im Fenster kein Formular, sondern nur
 *              den Hinweis mit dem Link zum Login - das entscheidet
 *              index.php. Dann richtet diese Datei nur das Öffnen ein.
 *
 *              Das Fenster selbst (öffnen, schließen, Escape) kommt aus
 *              modal.js.
 * @see         index.php
 * @see         mein-konto.php
 * @see         assets/js/services/empfehlungen.js
 * @see         assets/js/services/gutscheine.js
 * @see         assets/css/components/freunde-werben.css
 */

import { $ } from '../lib/dom.js';
import { meldungZeigen } from './auth-formular.js';
import { initModal } from './modal.js';
import { einladungenLaden, freundEinladen } from '../services/empfehlungen.js';
import { gutscheineLaden } from '../services/gutscheine.js';

/**
 * Aktiviert das Fenster "Freund einladen". Fehlt es auf der Seite,
 * passiert nichts.
 *
 * @returns {void}
 */
export function initFreundeWerben() {
  initModal({
    modalId: 'werben-modal',
    openId: 'werben-oeffnen',
    closeId: 'werben-schliessen',
    focusId: 'werben-name',
  });

  const formular = $('#werben-formular');

  // Gäste sehen statt des Formulars den Hinweis zum Anmelden.
  if (!formular) {
    return;
  }

  const meldung = $('#werben-meldung');
  const erfolg = $('#werben-erfolg');
  const linkFeld = $('#werben-link');
  const kopieren = $('#werben-kopieren');
  const knopf = $('button[type="submit"]', formular);

  // Der Link führt direkt auf den Reiter "Registrierung", siehe
  // anmelden.page.js. Absolut, damit er sich weitergeben lässt.
  const baseUrl = document.documentElement.dataset.baseUrl ?? '/';
  linkFeld.value = new URL(`${baseUrl}anmelden.php#registrierung`, window.location.origin).href;

  formular.addEventListener('submit', async (ereignis) => {
    ereignis.preventDefault();

    const name = formular.elements.name.value.trim();
    const email = formular.elements.email.value.trim();

    if (name === '' || email === '') {
      meldungZeigen(meldung, 'Bitte gib Name und E-Mail deines Freundes an.');
      return;
    }

    knopf.disabled = true;
    meldungZeigen(meldung, '');

    try {
      await freundEinladen(name, email);

      $('#werben-erfolg-text').textContent = `${name} ist eingetragen. Registriert sich ${name} mit ${email}, bekommst du deinen Gutschein.`;
      formular.hidden = true;
      erfolg.hidden = false;
      kopieren.focus();
    } catch (fehler) {
      meldungZeigen(meldung, fehler.message);
    } finally {
      knopf.disabled = false;
    }
  });

  kopieren.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(linkFeld.value);
      kopieren.textContent = 'Kopiert';
    } catch {
      // Ohne Zugriff auf die Zwischenablage (ältere Browser, kein HTTPS):
      // Link markieren, dann geht Strg+C von Hand.
      linkFeld.select();
    }
  });

  $('#werben-weitere').addEventListener('click', () => {
    formular.reset();
    kopieren.textContent = 'Link kopieren';
    erfolg.hidden = true;
    formular.hidden = false;
    formular.elements.name.focus();
  });
}

/**
 * Macht aus '2026-10-01' den Text '01.10.2026'. Von Hand statt über
 * new Date(), siehe assets/js/lib/datum.js.
 *
 * @param {string} datum  'JJJJ-MM-TT'
 * @returns {string}
 */
function datumAnzeigen(datum) {
  const [jahr, monat, tag] = datum.split('-');

  return `${tag}.${monat}.${jahr}`;
}

/**
 * Baut eine Zeile für eine der beiden Listen: links das Wichtige, rechts
 * der Stand.
 *
 * textContent statt innerHTML: Name und E-Mail sind Nutzereingaben.
 *
 * @param {string}  haupt   z. B. der Code oder der Name
 * @param {string}  zusatz  kleine Zeile darunter, '' lässt sie weg
 * @param {string}  status  z. B. 'offen'
 * @param {boolean} fertig  true färbt den Status in der Akzentfarbe
 * @returns {HTMLLIElement}
 */
function eintragBauen(haupt, zusatz, status, fertig) {
  const eintrag = document.createElement('li');
  eintrag.className = 'werben-eintrag';

  const text = document.createElement('span');
  text.className = 'werben-eintrag-text';

  const titel = document.createElement('strong');
  titel.textContent = haupt;
  text.append(titel);

  if (zusatz !== '') {
    const klein = document.createElement('small');
    klein.textContent = zusatz;
    text.append(klein);
  }

  const marke = document.createElement('span');
  marke.className = 'werben-eintrag-status';
  marke.classList.toggle('werben-eintrag-status--fertig', fertig);
  marke.textContent = status;

  eintrag.append(text, marke);

  return eintrag;
}

/**
 * Füllt die Karte "Freunde werben" unter "Mein Konto" mit den eigenen
 * Gutscheinen und Einladungen. Fehlt die Karte, passiert nichts.
 *
 * Fängt Fehler selbst ab und zeigt sie in der Karte - ein Fehler hier soll
 * den Rest von "Mein Konto" nicht mitreißen.
 *
 * @returns {Promise<void>}
 */
export async function kontoWerbenAufbauen() {
  const gutscheinListe = $('#konto-gutscheine');
  const einladungListe = $('#konto-einladungen');

  if (!gutscheinListe || !einladungListe) {
    return;
  }

  const leer = (text) => {
    const zeile = document.createElement('li');
    zeile.className = 'werben-leer';
    zeile.textContent = text;
    return zeile;
  };

  try {
    const [{ gutscheine }, { einladungen }] = await Promise.all([gutscheineLaden(), einladungenLaden()]);

    gutscheinListe.replaceChildren(...(gutscheine.length === 0
      ? [leer('Noch kein Gutschein. Er kommt, sobald sich ein eingeladener Freund registriert.')]
      : gutscheine.map((g) => eintragBauen(
        g.code,
        g.eingeloest ? `Basisplan gratis vom ${datumAnzeigen(g.gratisVon)} bis ${datumAnzeigen(g.gratisBis)}` : '3 Monate Basisplan gratis',
        g.eingeloest ? 'eingelöst' : 'einlösbar',
        !g.eingeloest,
      ))));

    einladungListe.replaceChildren(...(einladungen.length === 0
      ? [leer('Du hast noch niemanden eingeladen.')]
      : einladungen.map((e) => eintragBauen(
        e.name,
        `${e.email} · eingeladen am ${datumAnzeigen(e.datum)}`,
        e.registriert ? 'registriert' : 'offen',
        e.registriert,
      ))));
  } catch (fehler) {
    meldungZeigen($('#konto-werben-meldung'), fehler.message);
  }
}
