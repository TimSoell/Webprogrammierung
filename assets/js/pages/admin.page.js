/**
 * @file        assets/js/pages/admin.page.js
 * @layer       2 – Seitenskript
 * @description Die beiden Karten der Seite für Admins:
 *
 *              "Personal verwalten" zeigt alle Konten mit der Rolle Personal
 *              und schickt "Zum Personal machen" und "Rolle entziehen" an den
 *              Server.
 *
 *              "Unstimmigkeiten" zeigt die Konten, die das Personal am
 *              Empfang gemeldet hat, und schickt "Markierung aufheben" und
 *              "Konto löschen" an den Server.
 *
 *              Entziehen und Löschen fragen vorher nach. Wer das alles darf,
 *              prüfen allein api/personal.php und api/unstimmigkeiten.php.
 * @see         admin.php
 * @see         assets/js/services/personal.js
 * @see         assets/js/services/unstimmigkeiten.js
 */

import { $ } from '../lib/dom.js';
import { personalLaden, rolleEntziehen, zumPersonalMachen } from '../services/personal.js';
import { kontoLoeschen, markierungAufheben, unstimmigkeitenLaden } from '../services/unstimmigkeiten.js';
import { formularAbsenden, meldungZeigen } from '../components/auth-formular.js';

const seite = $('#admin-seite');

if (seite) {
  const formular = $('#admin-formular');
  const eingabe = $('#admin-email');
  const meldung = $('#admin-meldung');
  const liste = $('#admin-liste');
  const leer = $('#admin-leer');
  const anzahl = $('#admin-anzahl');

  const unstimmigListe = $('#unstimmig-liste');
  const unstimmigLeer = $('#unstimmig-leer');
  const unstimmigAnzahl = $('#unstimmig-anzahl');
  const unstimmigMeldung = $('#unstimmig-meldung');

  /**
   * Alle Konten mit der Rolle Personal, wie der Server sie zuletzt geliefert hat.
   *
   * @type {Array<import('../services/personal.js').PersonalKonto>}
   */
  let personal = [];

  /**
   * Alle gemeldeten Konten, wie der Server sie zuletzt geliefert hat.
   *
   * @type {Array<import('../services/unstimmigkeiten.js').GemeldetesKonto>}
   */
  let unstimmigkeiten = [];

  /**
   * Macht aus '2026-10-30' den Text '30.10.2026'. Von Hand statt über
   * new Date(), siehe assets/js/pages/empfang.page.js.
   *
   * @param {string} datum  'JJJJ-MM-TT'
   * @returns {string}
   */
  const datumAnzeigen = (datum) => datum.split('-').reverse().join('.');

  /**
   * Baut eine Zelle mit einer fett gesetzten ersten und einer grauen
   * zweiten Zeile, wie am Empfang.
   *
   * textContent statt innerHTML: Namen, E-Mails und Gründe sind Eingaben.
   *
   * @param {string} kopf
   * @param {string} zusatz        graue zweite Zeile
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
   * Baut einen Knopf, der vorher nachfragt und dann eine Anfrage schickt.
   * Während sie läuft, ist er gesperrt; bei einem Fehler wieder frei.
   *
   * @param {string} text            Beschriftung
   * @param {string} klasse          CSS-Klassen
   * @param {string} beschreibung    aria-label mit dem Namen der Person
   * @param {string} rueckfrage      Text für window.confirm
   * @param {Element} ausgabe        das <p class="auth-message"> der Karte
   * @param {() => Promise<string>} aktion  schickt die Anfrage, liefert die Erfolgsmeldung
   * @returns {HTMLButtonElement}
   */
  const knopfBauen = (text, klasse, beschreibung, rueckfrage, ausgabe, aktion) => {
    const knopf = document.createElement('button');
    knopf.type = 'button';
    knopf.className = klasse;
    knopf.textContent = text;
    knopf.setAttribute('aria-label', beschreibung);

    knopf.addEventListener('click', async () => {
      if (!window.confirm(rueckfrage)) {
        return;
      }

      knopf.disabled = true;
      meldungZeigen(ausgabe, '');

      try {
        meldungZeigen(ausgabe, await aktion(), true);
      } catch (fehler) {
        meldungZeigen(ausgabe, fehler.message);
        knopf.disabled = false;
      }
    });

    return knopf;
  };

  /**
   * Baut eine Zeile der Personal-Tabelle: Name und E-Mail, daneben "Rolle
   * entziehen".
   *
   * @param {import('../services/personal.js').PersonalKonto} eintrag
   * @returns {HTMLTableRowElement}
   */
  const personalZeileBauen = (eintrag) => {
    const name = `${eintrag.vorname} ${eintrag.nachname}`;

    const aktionen = document.createElement('td');
    aktionen.className = 'empfang-aktionen';
    aktionen.append(knopfBauen(
      'Rolle entziehen',
      'button button--ghost',
      `${name} die Rolle Personal entziehen`,
      `${name} die Rolle Personal entziehen? Danach kommt ${eintrag.vorname} nicht mehr an den Empfang.`,
      meldung,
      async () => {
        personal = (await rolleEntziehen(eintrag.email)).personal;
        personalZeigen();

        return `${name} gehört nicht mehr zum Personal.`;
      },
    ));

    const zeile = document.createElement('tr');
    zeile.append(zelleBauen(name, eintrag.email, 'Person'), aktionen);

    return zeile;
  };

  /**
   * Baut eine Zeile der Unstimmigkeiten: Person, wann und warum gemeldet,
   * daneben "Markierung aufheben" und "Konto löschen".
   *
   * @param {import('../services/unstimmigkeiten.js').GemeldetesKonto} eintrag
   * @returns {HTMLTableRowElement}
   */
  const unstimmigZeileBauen = (eintrag) => {
    const name = `${eintrag.vorname} ${eintrag.nachname}`;

    const aktionen = document.createElement('td');
    aktionen.className = 'empfang-aktionen';
    aktionen.append(
      knopfBauen(
        'Markierung aufheben',
        'button button--ghost',
        `Markierung bei ${name} aufheben`,
        `Markierung bei ${name} aufheben? Das Konto steht danach am Empfang wieder ganz normal in der Liste.`,
        unstimmigMeldung,
        async () => {
          unstimmigkeiten = (await markierungAufheben(eintrag.mitgliedId)).unstimmigkeiten;
          unstimmigZeigen();

          return `Die Markierung bei ${name} ist aufgehoben.`;
        },
      ),
      knopfBauen(
        'Konto löschen',
        'button button--ghost admin-loeschen',
        `Konto von ${name} löschen`,
        `Konto von ${name} (${eintrag.email}) endgültig löschen? Alles verschwindet: Login, Verträge, Nachweise, Buchungen, Bewertungen, Profilbild. Das lässt sich nicht rückgängig machen.`,
        unstimmigMeldung,
        async () => {
          unstimmigkeiten = (await kontoLoeschen(eintrag.mitgliedId)).unstimmigkeiten;
          unstimmigZeigen();

          return `Das Konto von ${name} ist gelöscht.`;
        },
      ),
    );

    const zeile = document.createElement('tr');
    zeile.dataset.stand = 'unstimmig';
    zeile.append(
      zelleBauen(name, eintrag.email, 'Person'),
      zelleBauen(datumAnzeigen(eintrag.gemeldetAm), eintrag.grund, 'Gemeldet'),
      aktionen,
    );

    return zeile;
  };

  /**
   * Zeichnet die Personal-Tabelle neu.
   *
   * @returns {void}
   */
  function personalZeigen() {
    liste.replaceChildren(...personal.map(personalZeileBauen));
    anzahl.textContent = personal.length === 1 ? '1 Konto' : `${personal.length} Konten`;
    leer.hidden = personal.length > 0;
  }

  /**
   * Zeichnet die Tabelle der Unstimmigkeiten neu.
   *
   * @returns {void}
   */
  function unstimmigZeigen() {
    unstimmigListe.replaceChildren(...unstimmigkeiten.map(unstimmigZeileBauen));
    unstimmigAnzahl.textContent = unstimmigkeiten.length === 1 ? '1 Konto' : `${unstimmigkeiten.length} Konten`;
    unstimmigLeer.hidden = unstimmigkeiten.length > 0;
  }

  formular.addEventListener('submit', (event) => {
    event.preventDefault();

    formularAbsenden(formular, meldung, async () => {
      // Der Server schreibt E-Mails klein - so findet sich das Konto danach
      // in der Liste wieder.
      const email = eingabe.value.trim().toLowerCase();

      personal = (await zumPersonalMachen(email)).personal;
      personalZeigen();
      formular.reset();

      // Mit Namen, damit der Admin sieht, ob er das richtige Konto erwischt hat.
      const neu = personal.find((eintrag) => eintrag.email === email);
      meldungZeigen(meldung, `${neu.vorname} ${neu.nachname} gehört jetzt zum Personal.`, true);
    });
  });

  try {
    const [antwortPersonal, antwortUnstimmig] = await Promise.all([personalLaden(), unstimmigkeitenLaden()]);

    personal = antwortPersonal.personal;
    unstimmigkeiten = antwortUnstimmig.unstimmigkeiten;
    personalZeigen();
    unstimmigZeigen();
  } catch (fehler) {
    // 401: Die Sitzung ist abgelaufen, seit die Seite geladen wurde.
    if (fehler.status === 401) {
      window.location.assign(seite.dataset.login);
    } else {
      meldungZeigen(meldung, fehler.message);
    }
  }
}
