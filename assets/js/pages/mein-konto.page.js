/**
 * @file        assets/js/pages/mein-konto.page.js
 * @layer       2 – Seitenskript
 * @description Lädt die Stammdaten, die gemerkte Programm-Auswahl, die
 *              laufende Mitgliedschaft, die Nachweise und die angekündigten
 *              Besuche des angemeldeten Mitglieds in die Seite und meldet
 *              über den Button "Abmelden" ab.
 * @see         mein-konto.php
 * @see         assets/js/services/mitglieder.js
 * @see         assets/js/services/auswahl.js
 * @see         assets/js/services/mitgliedschaften.js
 * @see         assets/js/components/meine-termine.js  (Karte "Meine Termine")
 * @see         assets/js/components/freunde-werben.js (Karte "Freunde werben")
 * @see         assets/js/components/ausweis-scan.js
 * @see         assets/js/services/auslastung.js
 */

import { $ } from '../lib/dom.js';
import { abmelden, angemeldetesMitgliedLaden } from '../services/mitglieder.js';
import { auswahlEntfernen, meineAuswahlLaden } from '../services/auswahl.js';
import { standLaden } from '../services/mitgliedschaften.js';
import { alleLaden as nachweiseLaden, demoEintragen, hochladen } from '../services/nachweise.js';
import { formularAbsenden, meldungZeigen } from '../components/auth-formular.js';
import { meineTermineAufbauen } from '../components/meine-termine.js';
import { kontoWerbenAufbauen } from '../components/freunde-werben.js';
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
   * Bis wann je Art schon ein gültiger Nachweis vorliegt.
   *
   * Schlüssel ist die Art, Wert das Ablaufdatum - oder null für
   * unbefristet. Fehlt die Art, gibt es keinen gültigen Nachweis.
   *
   * @type {Record<string, string|null>}
   */
  let vorhandeneNachweise = {};

  /**
   * Zeichnet die Liste der Nachweise und schaltet das Formular zwischen
   * Bild- und Datumsfeld um.
   *
   * @param {import('../services/nachweise.js').Nachweisstand} stand
   * @returns {void}
   */
  function nachweiseZeigen(stand) {
    const heute = new Date().toISOString().slice(0, 10);

    // Je Art den besten noch gültigen Nachweis merken: unbefristet schlägt
    // befristet, sonst gewinnt der, der am längsten gilt. Dieselbe Regel
    // wie in NachweisRepository::gueltigenFindenNachArt().
    vorhandeneNachweise = {};
    stand.nachweise.forEach((nachweis) => {
      if (nachweis.gueltigBis !== null && nachweis.gueltigBis < heute) {
        return;
      }

      const bisher = vorhandeneNachweise[nachweis.art];

      if (!(nachweis.art in vorhandeneNachweise)
        || (bisher !== null && (nachweis.gueltigBis === null || nachweis.gueltigBis > bisher))) {
        vorhandeneNachweise[nachweis.art] = nachweis.gueltigBis;
      }
    });

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

    vorhandenenHinweisZeigen();
  }

  /**
   * Sagt unter der Auswahl, ob für die gewählte Art schon ein gültiger
   * Nachweis vorliegt - und sperrt den Button, wo ein weiterer Upload
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
    const art = nachweisArt.value;

    if (!(art in vorhandeneNachweise)) {
      hinweis.hidden = true;
      button.disabled = false;

      return;
    }

    const bis = vorhandeneNachweise[art];

    // Unbefristet gibt es nur beim Senior. Da kann kein zweiter Ausweis
    // etwas verbessern, also bleibt der Button gesperrt.
    hinweis.textContent = bis === null
      ? 'Diesen Nachweis hast du schon hinterlegt, er gilt unbefristet. Ein weiterer Upload ändert nichts.'
      : `Du hast dafür schon einen Nachweis bis ${datumAnzeigen(bis)}. `
        + 'Lade nur einen hoch, der länger gilt - oder wähle eine andere Art.';

    hinweis.hidden = false;
    button.disabled = bis === null;
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
      meldungZeigen(nachweisMeldung, 'Nachweis gespeichert. Der Preis steht dir ab sofort offen.', true);
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
    event.currentTarget.disabled = true;

    try {
      await besuchEintragen('jetzt');
      await besucheAnzeigen();
      meldungZeigen(besuchMeldung, 'Eingecheckt. Du zählst jetzt in die Auslastung.', true);
    } catch (fehler) {
      meldungZeigen(besuchMeldung, fehler.message);
    } finally {
      event.currentTarget.disabled = false;
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
