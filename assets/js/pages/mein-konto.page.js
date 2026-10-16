/**
 * @file        assets/js/pages/mein-konto.page.js
 * @layer       2 – Seitenskript
 * @description Lädt die Stammdaten, die gemerkte Programm-Auswahl, die
 *              laufende Mitgliedschaft, die Nachweise und die angekündigten
 *              Besuche des angemeldeten Mitglieds in die Seite und meldet
 *              über den Button "Abmelden" ab.
 *
 *              Zwei Dinge hängen am Empfang: Der Name lässt sich nur ändern,
 *              bis das Personal ihn bestätigt hat (und davor nicht, solange
 *              ein Nachweis gilt), und die Nachweisliste zeigt, bis wann der
 *              Ausweis gezeigt werden muss. Beides ist hier nur Anzeige -
 *              verbindlich sind api/mitglieder.php und api/nachweise.php.
 * @see         mein-konto.php
 * @see         assets/js/services/mitglieder.js
 * @see         assets/js/services/auswahl.js
 * @see         assets/js/services/mitgliedschaften.js
 * @see         assets/js/components/meine-termine.js  (Karte "Meine Termine")
 * @see         assets/js/components/freunde-werben.js (Karte "Freunde werben")
 * @see         assets/js/components/profilbild.js     (Profilbild in den Stammdaten)
 * @see         assets/js/components/ausweis-scan.js
 * @see         assets/js/services/auslastung.js
 */

import { $ } from '../lib/dom.js';
import { abmelden, angemeldetesMitgliedLaden, nameAendern } from '../services/mitglieder.js';
import { auswahlEntfernen, meineAuswahlLaden } from '../services/auswahl.js';
import { standLaden } from '../services/mitgliedschaften.js';
import {
  alleLaden as nachweiseLaden,
  demoEintragen,
  entfernen as nachweisEntfernen,
  hochladen,
} from '../services/nachweise.js';
import { formularAbsenden, meldungZeigen } from '../components/auth-formular.js';
import { meineTermineAufbauen } from '../components/meine-termine.js';
import { kontoWerbenAufbauen } from '../components/freunde-werben.js';
import { profilbildEinrichten } from '../components/profilbild.js';
import { scanStarten } from '../components/ausweis-scan.js';
import { auslastungLaden, besuchEintragen, besuchEntfernen } from '../services/auslastung.js';

const seite = $('#konto-seite');

if (seite) {
  const meldung = $('#konto-meldung');
  const liste = $('#auswahl-liste');
  const auswahlMeldung = $('#auswahl-meldung');
  const besuchListe = $('#besuch-liste');
  const besuchMeldung = $('#besuch-meldung');
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
   * Der gültige Nachweis des Mitglieds - es gibt höchstens einen, ein neuer
   * ersetzt ihn. null, solange keiner hinterlegt ist.
   *
   * @type {import('../services/nachweise.js').Nachweis|null}
   */
  let gueltigerNachweis = null;

  /**
   * Wie weit das Mitglied mit dem Empfang ist. Kommt mit jedem Nachweisstand
   * vom Server, siehe api/nachweise.php.
   *
   * @type {{bestaetigt: boolean, vorzeigenBis: string|null, fristVerpasst: boolean}}
   */
  let empfang = { bestaetigt: false, vorzeigenBis: null, fristVerpasst: false };

  /**
   * Was beim gültigen Nachweis zum Empfang dasteht.
   *
   * @returns {{stand: 'bestaetigt'|'verpasst'|'offen', text: string}}
   */
  const empfangStand = () => {
    if (empfang.bestaetigt) {
      return { stand: 'bestaetigt', text: 'Konto am Empfang bestätigt' };
    }
    if (empfang.fristVerpasst) {
      return {
        stand: 'verpasst',
        text: 'Frist für den Empfang verpasst — der ermäßigte Preis gilt erst wieder, wenn das Personal deinen Personalausweis gesehen hat',
      };
    }

    return { stand: 'offen', text: `Zeig bis ${datumAnzeigen(empfang.vorzeigenBis)} einmal deinen Personalausweis am Empfang` };
  };

  /**
   * Macht aus dem Ablaufdatum eines Nachweises den Text für die Anzeige.
   * Ein Nachweis ohne Datum ist unbefristet - das gibt es nur bei Senioren.
   *
   * @param {string|null} gueltigBis  'JJJJ-MM-TT' oder null
   * @returns {string}
   */
  const bisAnzeigen = (gueltigBis) => (gueltigBis === null
    ? 'unbefristet'
    : `bis ${datumAnzeigen(gueltigBis)}`);

  /**
   * Baut eine Zeile der Nachweisliste samt Knopf zum Entfernen.
   *
   * @param {import('../services/nachweise.js').Nachweis} nachweis
   * @param {boolean} abgelaufen
   * @returns {HTMLLIElement}
   */
  const nachweisZeile = (nachweis, abgelaufen) => {
    const zeile = document.createElement('li');
    zeile.className = 'nachweis-eintrag';
    zeile.dataset.abgelaufen = abgelaufen ? 'ja' : 'nein';

    const text = document.createElement('span');
    text.textContent = `${NACHWEISARTEN[nachweis.art]} — ${bisAnzeigen(nachweis.gueltigBis)}`
      + (abgelaufen ? ' (abgelaufen)' : '')
      + (nachweis.quelle === 'demo' ? ' · im Demo-Modus eingetragen' : '');

    // Nur beim gültigen Nachweis: Steht der Empfang noch aus, und bis wann?
    if (!abgelaufen) {
      const { stand, text: standText } = empfangStand();

      const status = document.createElement('span');
      status.className = 'nachweis-status';
      status.dataset.stand = stand;
      status.textContent = standText;

      text.append(status);
    }

    const knopf = document.createElement('button');
    knopf.type = 'button';
    knopf.className = 'nachweis-entfernen';
    knopf.textContent = 'Entfernen';
    knopf.setAttribute('aria-label', `${NACHWEISARTEN[nachweis.art]} entfernen`);

    knopf.addEventListener('click', async () => {
      // Die Rückfrage nennt die Folge: Ein gültiger Nachweis hält den
      // ermäßigten Preis, ein abgelaufener nicht mehr.
      const frage = abgelaufen
        ? 'Diesen abgelaufenen Nachweis wirklich entfernen?'
        : 'Nachweis wirklich entfernen? Ohne gültigen Nachweis gilt für einen '
          + 'ermäßigten Tarif ab dem nächsten Monatsersten der Standardpreis.';

      if (!window.confirm(frage)) {
        return;
      }

      knopf.disabled = true;

      try {
        nachweiseZeigen(await nachweisEntfernen(nachweis.id));
        meldungZeigen(nachweisMeldung, 'Nachweis entfernt.', true);
      } catch (fehler) {
        meldungZeigen(nachweisMeldung, fehler.message);
        knopf.disabled = false;
      }
    });

    zeile.append(text, knopf);

    return zeile;
  };

  /**
   * Zeichnet die Liste der Nachweise und schaltet das Formular zwischen
   * Bild- und Datumsfeld um.
   *
   * @param {import('../services/nachweise.js').Nachweisstand} stand
   * @returns {void}
   */
  function nachweiseZeigen(stand) {
    const heute = new Date().toISOString().slice(0, 10);
    const istAbgelaufen = (nachweis) => nachweis.gueltigBis !== null && nachweis.gueltigBis < heute;

    gueltigerNachweis = stand.nachweise.find((nachweis) => !istAbgelaufen(nachweis)) ?? null;
    empfang = {
      bestaetigt: stand.bestaetigt,
      vorzeigenBis: stand.vorzeigenBis,
      fristVerpasst: stand.fristVerpasst,
    };

    $('#nachweis-leer').hidden = stand.nachweise.length > 0;
    $('#nachweis-liste').replaceChildren(
      ...stand.nachweise.map((nachweis) => nachweisZeile(nachweis, istAbgelaufen(nachweis))),
    );

    // Ohne Schlüssel gibt es nichts auszulesen, dann wird das Datum getippt.
    $('#nachweis-bild-feld').hidden = !stand.kiVerfuegbar;
    $('#nachweis-datum-feld').hidden = stand.kiVerfuegbar;

    // Ohne gültigen Nachweis ist der Name wieder frei - dann stimmt der
    // Hinweis "Name gesperrt" oben in den Stammdaten nicht mehr.
    if (gueltigerNachweis === null) {
      $('#name-gesperrt').hidden = true;
    }

    // Nach der Bestätigung am Empfang ist der Name für immer fest: Statt
    // "Name ändern" steht dann nur noch, warum.
    if (empfang.bestaetigt) {
      $('#name-aendern').hidden = true;
      $('#name-form').hidden = true;
      $('#name-gesperrt').hidden = true;
      $('#name-bestaetigt').hidden = false;
    }

    vorhandenenHinweisZeigen();
  }

  /**
   * Sagt unter der Auswahl, was ein Upload mit dem schon hinterlegten
   * Nachweis machen würde - und sperrt den Button, wo ein weiterer Upload
   * nichts ändern kann.
   *
   * Das ist nur die Bequemlichkeit. Die verbindliche Prüfung steht in
   * api/nachweise.php, sonst könnte man sie mit der Konsole umgehen.
   *
   * @returns {void}
   */
  function vorhandenenHinweisZeigen() {
    const hinweis = $('#nachweis-vorhanden');
    const button = $('#nachweis-form button[type="submit"]');

    if (gueltigerNachweis === null) {
      hinweis.hidden = true;
      button.disabled = false;

      return;
    }

    const bis = gueltigerNachweis.gueltigBis;
    const gleicheArt = gueltigerNachweis.art === nachweisArt.value;

    if (!gleicheArt) {
      hinweis.textContent = `Du hast schon einen Nachweis hinterlegt (${NACHWEISARTEN[gueltigerNachweis.art]}, `
        + `${bisAnzeigen(bis)}). Es gilt immer nur einer - ein neuer ersetzt ihn.`;
    } else if (bis === null) {
      // Unbefristet gibt es nur beim Senior. Da kann kein zweiter Ausweis
      // derselben Art etwas verbessern, also bleibt der Button gesperrt.
      hinweis.textContent = 'Diesen Nachweis hast du schon hinterlegt, er gilt unbefristet. Ein weiterer Upload ändert nichts.';
    } else {
      hinweis.textContent = `Du hast dafür schon einen Nachweis bis ${datumAnzeigen(bis)}. `
        + 'Lade nur einen hoch, der länger gilt - er ersetzt den bisherigen.';
    }

    hinweis.hidden = false;
    button.disabled = gleicheArt && bis === null;
  }

  // Beim Seniorennachweis wird nicht das Ablaufdatum gebraucht, sondern das
  // Geburtsdatum - im Demo-Modus muss das Feld also anders heißen.
  nachweisArt.addEventListener('change', () => {
    $('#nachweis-datum-label').textContent = nachweisArt.value === 'senior'
      ? 'Geburtsdatum'
      : 'Gültig bis';

    vorhandenenHinweisZeigen();
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
      // Die Animation gibt es nur beim Bild-Upload: Nur der dauert, weil das
      // Foto geprueft werden muss. Im Demo-Modus ist die Antwort sofort da,
      // da waere ein Scanner reine Behauptung.
      const scan = mitBild ? scanStarten(datei) : null;

      let stand;

      try {
        stand = mitBild
          ? await hochladen(nachweisArt.value, datei)
          : await demoEintragen(nachweisArt.value, $('#nachweis-datum').value);
      } catch (fehler) {
        // Weg mit dem Overlay, sonst liegt es ueber der Fehlermeldung.
        // formularAbsenden faengt den Fehler und zeigt ihn im Formular an.
        scan?.abbrechen();

        throw fehler;
      }

      // Erst Haken und Konfetti zu Ende laufen lassen, dann die Seite
      // aktualisieren - sonst sieht man das Ergebnis hinter dem Overlay.
      await scan?.abschliessen();

      nachweiseZeigen(stand);
      formular.reset();

      // Frist verpasst: Gespeichert ist der Nachweis, für den Preis zählt er
      // aber erst nach dem Empfang - das muss die Meldung sagen.
      if (stand.fristVerpasst) {
        meldungZeigen(nachweisMeldung, 'Nachweis gespeichert. Deine 14 Tage für den Empfang sind aber vorbei: Der ermäßigte Preis gilt erst wieder, wenn das Personal deinen Personalausweis gesehen hat.', true);

        return;
      }

      // Noch nicht bestätigt: an den Empfang erinnern, mit dem Datum.
      const empfangHinweis = stand.bestaetigt
        ? ''
        : ` Zeig bis ${datumAnzeigen(stand.vorzeigenBis)} einmal deinen Personalausweis am Empfang.`;

      if (stand.herabstufungZurueckgenommen) {
        // Oben auf der Seite steht noch der vorgemerkte Wechsel auf den
        // Standardpreis. Den gibt es nicht mehr, also neu laden.
        mitgliedschaftZeigen(await standLaden());
        meldungZeigen(nachweisMeldung, `Nachweis gespeichert. Der vorgemerkte Wechsel auf den Standardpreis entfällt, dein Preis bleibt wie bisher.${empfangHinweis}`, true);

        return;
      }

      meldungZeigen(nachweisMeldung, `Nachweis gespeichert. Der Preis steht dir ab sofort offen.${empfangHinweis}`, true);
    });

    // formularAbsenden() gibt den Button am Ende immer wieder frei. Wo er
    // gesperrt bleiben soll, weil schon ein unbefristeter Nachweis vorliegt,
    // muss das danach noch einmal gesetzt werden.
    vorhandenenHinweisZeigen();
  });

  // --- Mein Besuch -----------------------------------------------------------

  /**
   * Macht aus '2026-09-21 18:00:00' die Anzeige '18:00'.
   *
   * Der Server schickt volle Zeitstempel. Für die Liste reicht die Uhrzeit,
   * weil dort nur Besuche stehen, die noch bevorstehen.
   *
   * @param {string} zeitstempel  'YYYY-MM-DD HH:MM:SS'
   * @returns {string}
   */
  const nurUhrzeit = (zeitstempel) => zeitstempel.slice(11, 16);

  /**
   * Baut eine Zeile der Besuchsliste.
   *
   * @param {{id: number, beginn: string, ende: string, art: string}} besuch
   * @returns {HTMLLIElement}
   */
  const besuchZeile = (besuch) => {
    const zeile = document.createElement('li');
    zeile.className = 'besuch-eintrag';

    const text = document.createElement('span');
    text.textContent = besuch.art === 'jetzt'
      ? `Eingecheckt, gezählt bis ${nurUhrzeit(besuch.ende)}`
      : `Angekündigt für ${nurUhrzeit(besuch.beginn)} bis ${nurUhrzeit(besuch.ende)}`;

    const knopf = document.createElement('button');
    knopf.type = 'button';
    knopf.className = 'besuch-entfernen';
    knopf.textContent = 'Entfernen';
    knopf.setAttribute('aria-label', `Besuch um ${nurUhrzeit(besuch.beginn)} entfernen`);

    knopf.addEventListener('click', async () => {
      try {
        await besuchEntfernen(besuch.id);
        await besucheAnzeigen();
        meldungZeigen(besuchMeldung, 'Besuch entfernt.', true);
      } catch (fehler) {
        meldungZeigen(besuchMeldung, fehler.message);
      }
    });

    zeile.append(text, knopf);

    return zeile;
  };

  /**
   * Lädt die offenen Besuche und schreibt sie in die Liste.
   *
   * @returns {Promise<void>}
   */
  async function besucheAnzeigen() {
    const daten = await auslastungLaden();
    const meine = daten.meine ?? [];

    besuchListe.replaceChildren(...meine.map(besuchZeile));

    if (meine.length === 0) {
      const leer = document.createElement('li');
      leer.className = 'besuch-leer';
      leer.textContent = 'Kein Besuch eingetragen.';
      besuchListe.append(leer);
    }
  }

  $('#besuch-jetzt').addEventListener('click', async (event) => {
    // Vor dem ersten await merken: Danach ist event.currentTarget null, und
    // der Knopf bliebe gesperrt.
    const knopf = event.currentTarget;

    knopf.disabled = true;

    try {
      await besuchEintragen('jetzt');
      await besucheAnzeigen();
      meldungZeigen(besuchMeldung, 'Eingecheckt. Du zählst jetzt in die Auslastung.', true);
    } catch (fehler) {
      meldungZeigen(besuchMeldung, fehler.message);
    } finally {
      knopf.disabled = false;
    }
  });

  $('#besuch-form').addEventListener('submit', async (event) => {
    event.preventDefault();

    try {
      await besuchEintragen('geplant', $('#besuch-zeit').value);
      await besucheAnzeigen();
      $('#besuch-form').reset();
      meldungZeigen(besuchMeldung, 'Besuch eingetragen. Danke fürs Bescheidsagen.', true);
    } catch (fehler) {
      meldungZeigen(besuchMeldung, fehler.message);
    }
  });

  // --- Name ändern -----------------------------------------------------------

  const nameKnopf = $('#name-aendern');
  const nameForm = $('#name-form');
  const nameMeldung = $('#name-meldung');

  /**
   * Zeigt entweder das Formular oder den Knopf "Name ändern".
   *
   * @param {boolean} offen  true = Formular sichtbar
   * @returns {void}
   */
  const nameFormZeigen = (offen) => {
    nameForm.hidden = !offen;
    nameKnopf.hidden = offen;
  };

  nameKnopf.addEventListener('click', () => {
    // Der Nachweis wurde gegen den Namen geprüft, deshalb bleibt der Name
    // gesperrt, solange einer gilt. Nach der Bestätigung am Empfang ist der
    // Knopf gar nicht mehr zu sehen. Das hier ist nur die Erklärung -
    // verbindlich sperrt api/mitglieder.php.
    $('#name-gesperrt').hidden = gueltigerNachweis === null;

    if (gueltigerNachweis !== null) {
      return;
    }

    nameForm.vorname.value = $('#konto-vorname').textContent;
    nameForm.nachname.value = $('#konto-nachname').textContent;
    meldungZeigen(nameMeldung, '');
    nameFormZeigen(true);
    nameForm.vorname.focus();
  });

  $('#name-abbrechen').addEventListener('click', () => nameFormZeigen(false));

  nameForm.addEventListener('submit', async (ereignis) => {
    ereignis.preventDefault();

    await formularAbsenden(nameForm, nameMeldung, async () => {
      const name = await nameAendern(nameForm.vorname.value, nameForm.nachname.value);

      // textContent statt innerHTML: Namen sind Nutzereingaben.
      $('#konto-vorname').textContent = name.vorname;
      $('#konto-nachname').textContent = name.nachname;
      nameFormZeigen(false);
      meldungZeigen(meldung, 'Name geändert.', true);
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
    profilbildEinrichten(mitglied);

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

  // Fängt seine Fehler selbst ab und zeigt sie in der eigenen Karte.
  meineTermineAufbauen();

  // Ebenso: Einladungen und Gutscheine in der Karte "Freunde werben".
  kontoWerbenAufbauen();

  // Eigener try/catch: Ein Fehler hier soll die Stammdaten oben nicht
  // mitreißen, und umgekehrt.
  try {
    await auswahlAnzeigen();
  } catch (fehler) {
    meldungZeigen(auswahlMeldung, fehler.message);
  }

  try {
    await besucheAnzeigen();
  } catch (fehler) {
    meldungZeigen(besuchMeldung, fehler.message);
  }
}
