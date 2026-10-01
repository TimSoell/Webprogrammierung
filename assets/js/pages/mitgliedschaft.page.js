/**
 * @file        assets/js/pages/mitgliedschaft.page.js
 * @layer       2 – Seitenskript
 * @description Tarifkarten zeichnen, Auswahl verwalten und einen Wechsel
 *              nach Rückfrage verbindlich machen.
 *
 *              WICHTIG für das Verständnis: Ein Klick auf eine Karte ändert
 *              nichts am Vertrag. Er ändert nur die AUSWAHL. Erst der Button
 *              unten und die Bestätigung im Fenster schicken etwas an den
 *              Server. Deshalb hält diese Datei zwei Dinge getrennt:
 *
 *                stand    was laut Server gilt (laufender + vorgemerkter Tarif)
 *                auswahl  was gerade angeklickt ist
 *
 *              Unterscheiden sich beide, gibt es eine offene Änderung: Der
 *              Button wird aktiv, und beim Verlassen der Seite fragt der
 *              Browser nach.
 *
 *              Bei jeder Änderung wird die Seite komplett neu gezeichnet.
 *              Das ist bei vier Karten billiger als einzelne Stellen
 *              nachzuführen - und es kann nicht auseinanderlaufen.
 * @see         mitgliedschaft.php
 * @see         assets/js/services/tarife.js
 * @see         assets/js/services/mitgliedschaften.js
 * @see         docs/decisions/ADR-0010-tarifwechsel-zum-monatsersten.md
 */

import { $, $$ } from '../lib/dom.js';
import { alleLaden } from '../services/tarife.js';
import { anpassen, standLaden } from '../services/mitgliedschaften.js';
import { meldungZeigen } from '../components/auth-formular.js';
import { initModal } from '../components/modal.js';

const seite = $('#tarife-seite');

if (seite) {
  const liste = $('#tarife-liste');
  const vorlage = $('#tarifkarte-vorlage');
  const meldung = $('#tarife-meldung');
  const aktionText = $('#tarife-aktion-text');
  const aktionButton = $('#mitgliedschaft-anpassen');
  const bestaetigen = $('#wechsel-bestaetigen');

  /** Ob jemand angemeldet ist, steht im HTML - siehe mitgliedschaft.php. */
  const angemeldet = seite.dataset.angemeldet === '1';

  /**
   * Beschriftung der drei Leistungen, gelesen aus der Karten-Vorlage im HTML.
   * So steht der Wortlaut nur an einer Stelle: Ändert jemand "Sauna &
   * Sonnenbank" im HTML, ändert sich auch der Text im Bestätigungsfenster.
   */
  const LEISTUNGEN = Object.fromEntries(
    $$('.tarifkarte-leistung', vorlage.content).map((zeile) => [
      zeile.dataset.leistung,
      zeile.textContent.trim(),
    ]),
  );

  /** Für die Karten: 29.9 -> '29,90'. Das Euro-Zeichen steht dort im HTML. */
  const betragFormat = new Intl.NumberFormat('de-DE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  /** Für das Bestätigungsfenster, wo kein Euro-Zeichen daneben steht. */
  const preisFormat = new Intl.NumberFormat('de-DE', {
    style: 'currency',
    currency: 'EUR',
  });

  /** @type {Array<import('../services/tarife.js').Tarif>} */
  let tarife = [];

  /** @type {import('../services/mitgliedschaften.js').Tarifstand} */
  let stand = {
    mitgliedschaft: null,
    geplant: null,
    wechselAb: '',
    kurseAbWechsel: 0,
    storniert: 0,

    // Gäste sehen alle Preise, dürfen aber ohnehin nichts abschließen.
    // Für Angemeldete überschreibt der Server diese Liste.
    erlaubtePreisgruppen: ['standard', 'ermaessigt', 'senior'],
  };

  /** Was gerade angeklickt ist. tarif === null heißt: noch nichts gewählt. */
  let auswahl = { tarif: null, preisgruppe: 'standard' };

  /**
   * Macht aus '2026-10-01' den Text '01.10.2026'.
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
   * Die Auswahl, die dem gespeicherten Stand entspricht. Ist ein Wechsel
   * vorgemerkt, ist das der vorgemerkte Tarif - denn der ist das, was
   * gerade "eingestellt" ist.
   *
   * @returns {{tarif: string|null, preisgruppe: string}}
   */
  function gespeicherteAuswahl() {
    const quelle = stand.geplant ?? stand.mitgliedschaft;

    return quelle
      ? { tarif: quelle.tarif, preisgruppe: quelle.preisgruppe }
      : { tarif: null, preisgruppe: 'standard' };
  }

  /** Setzt die Auswahl auf den gespeicherten Stand zurück. */
  function auswahlAusStandUebernehmen() {
    auswahl = gespeicherteAuswahl();

    if (auswahl.tarif === null) {
      return;
    }

    $(`#preisgruppe input[value="${auswahl.preisgruppe}"]`).checked = true;
  }

  /**
   * Beschreibt, was die aktuelle Auswahl gegenüber dem gespeicherten Stand
   * bedeuten würde. Herzstück der Seite: Aus diesem Objekt speisen sich die
   * Leiste unten, das Bestätigungsfenster und die Erfolgsmeldung.
   *
   * @returns {object|null}  null, wenn die Auswahl dem Stand entspricht
   */
  function aenderung() {
    const gespeichert = gespeicherteAuswahl();
    const offen = auswahl.tarif !== null
      && (auswahl.tarif !== gespeichert.tarif || auswahl.preisgruppe !== gespeichert.preisgruppe);

    if (!offen) {
      return null;
    }

    const ziel = tarife.find((tarif) => tarif.kennung === auswahl.tarif);
    const laufend = stand.mitgliedschaft;

    // Zurück auf den laufenden Tarif, obwohl ein Wechsel vorgemerkt ist:
    // Das nimmt die Vormerkung zurück, statt etwas Neues zu buchen.
    const istRuecknahme = Boolean(stand.geplant) && laufend !== null
      && auswahl.tarif === laufend.tarif
      && auswahl.preisgruppe === laufend.preisgruppe;

    const art = laufend === null ? 'erstwahl' : (istRuecknahme ? 'ruecknahme' : 'wechsel');
    const preisNeu = art === 'ruecknahme' ? laufend.preisMonatlich : ziel.preise[auswahl.preisgruppe];

    // Was kommt dazu, was fällt weg. Beim ersten Tarif gibt es nichts zu
    // vergleichen - dann zählt alles Enthaltene als Gewinn.
    const vorher = laufend !== null && art === 'wechsel'
      ? laufend.zugang
      : { geraete: false, wellness: false, kurse: false };
    const nachher = art === 'ruecknahme' ? vorher : ziel.zugang;

    const rechte = (von, nach) => Object.keys(LEISTUNGEN).filter((recht) => !von[recht] && nach[recht]);

    // Enthält der Tarif ab dem nächsten Monatsersten keine Kurse, storniert
    // der Server alle Kurse ab dann. Bei einer Rücknahme gilt ab dann wieder
    // der laufende Tarif. Bei der Erstwahl kann es noch keine Buchungen geben.
    const kurseDanach = art === 'erstwahl' || (art === 'ruecknahme' ? laufend.zugang.kurse : ziel.zugang.kurse);

    return {
      art,
      ziel,
      preisNeu,
      differenz: laufend === null || art === 'ruecknahme' ? null : preisNeu - laufend.preisMonatlich,
      gewinnt: rechte(vorher, nachher),
      verliert: rechte(nachher, vorher),
      gueltigAb: art === 'wechsel' ? stand.wechselAb : null,
      storno: kurseDanach ? 0 : stand.kurseAbWechsel,
    };
  }

  /**
   * Der Satz, der unten links steht, wenn nichts zu ändern ist.
   *
   * @returns {string}
   */
  function standText() {
    if (!angemeldet) {
      return 'Für eine Mitgliedschaft brauchst du ein Konto.';
    }
    if (stand.mitgliedschaft === null) {
      return 'Du hast noch keinen Tarif gewählt.';
    }
    if (stand.geplant !== null) {
      return `Ab ${datumAnzeigen(stand.geplant.beginntAm)} wechselst du zu ${stand.geplant.name}.`;
    }

    return `Dein Tarif: ${stand.mitgliedschaft.name}. `
      + `Ein Wechsel würde ab ${datumAnzeigen(stand.wechselAb)} gelten.`;
  }

  /**
   * Baut eine einzelne Karte aus der Vorlage im HTML.
   *
   * @param {import('../services/tarife.js').Tarif} tarif
   * @returns {DocumentFragment}
   */
  function karteBauen(tarif) {
    const karte = vorlage.content.cloneNode(true);
    const preis = tarif.preise[auswahl.preisgruppe];
    const istGewaehlt = auswahl.tarif === tarif.kennung;

    // textContent statt innerHTML: Die Texte kommen aus der Datenbank.
    $('.tarifkarte-name', karte).textContent = tarif.name;
    $('.tarifkarte-beschreibung', karte).textContent = tarif.beschreibung;

    // null heißt: diesen Tarif gibt es für die gewählte Preisgruppe nicht.
    $('.tarifkarte-betrag', karte).textContent = preis === null ? '—' : betragFormat.format(preis);
    $('.tarifkarte-einheit', karte).hidden = preis === null;

    // Ein leerer Hinweis wird vom CSS ausgeblendet (:empty).
    let status = '';
    if (stand.mitgliedschaft?.tarif === tarif.kennung) {
      status = 'Aktueller Tarif';
    }
    if (stand.geplant?.tarif === tarif.kennung) {
      status = `Vorgemerkt ab ${datumAnzeigen(stand.geplant.beginntAm)}`;
    }
    $('.tarifkarte-status', karte).textContent = status;

    $$('.tarifkarte-leistung', karte).forEach((zeile) => {
      zeile.dataset.enthalten = tarif.zugang[zeile.dataset.leistung] ? 'ja' : 'nein';
    });

    // Zwei verschiedene Auszeichnungen, weil es zwei verschiedene Dinge sind:
    // --aktuell ist der Tarif, der heute gilt. --gewaehlt ist der, den man
    // gerade angeklickt hat und der noch nicht übernommen wurde.
    const element = $('.tarifkarte', karte);
    element.classList.toggle('tarifkarte--aktuell', stand.mitgliedschaft?.tarif === tarif.kennung);
    element.classList.toggle('tarifkarte--gewaehlt', istGewaehlt);

    // Ohne Nachweis sind die Karten nur zum Ansehen da: Der Button sagt,
    // was fehlt, und lässt sich nicht drücken. Das Ausgrauen macht das CSS.
    const gesperrt = angemeldet && !preisgruppeErlaubt();

    const button = $('.tarifkarte-button', karte);
    button.textContent = istGewaehlt ? 'Ausgewählt' : 'Auswählen';
    button.disabled = preis === null || gesperrt;

    if (gesperrt) {
      button.textContent = 'Nachweis fehlt';
    }

    // aria-pressed statt disabled: Der Button bleibt für die Tastatur
    // erreichbar, und Screenreader sagen "gedrückt" statt gar nichts.
    button.setAttribute('aria-pressed', String(istGewaehlt));
    button.addEventListener('click', () => {
      auswahl = { ...auswahl, tarif: tarif.kennung };
      zeichnen();
    });

    return karte;
  }

  /**
   * Füllt das Bestätigungsfenster. Wird bei jedem Zeichnen mitgeführt,
   * damit beim Öffnen garantiert der aktuelle Stand drinsteht.
   *
   * @param {object|null} was  Rückgabe von aenderung()
   * @returns {void}
   */
  function dialogFuellen(was) {
    if (was === null) {
      return;
    }

    const einleitung = {
      erstwahl: `Du startest mit dem ${was.ziel.name}. Der Tarif gilt ab heute.`,
      wechsel: `Du wechselst zum ${was.ziel.name}. Bis zum Monatsende läuft dein bisheriger Tarif weiter.`,
      ruecknahme: `Der vorgemerkte Wechsel entfällt. Dein ${was.ziel.name} läuft unverändert weiter.`,
    };

    $('#wechsel-titel').textContent = {
      erstwahl: 'Mitgliedschaft starten?',
      wechsel: 'Wirklich wechseln?',
      ruecknahme: 'Wechsel zurücknehmen?',
    }[was.art];
    $('#wechsel-einleitung').textContent = einleitung[was.art];
    $('#wechsel-beitrag').textContent = `${preisFormat.format(was.preisNeu)} / Monat`;

    // Bei einer Rücknahme ändert sich nichts - weder Beitrag noch Datum.
    // Dann wäre die Tabelle nur Beiwerk; der Einleitungssatz sagt alles.
    $('.wechsel-daten').hidden = was.art === 'ruecknahme';

    // Bei der Erstwahl gibt es keinen Vorher-Wert, mit dem sich der neue
    // Beitrag vergleichen ließe.
    $('#wechsel-differenz-zeile').hidden = was.differenz === null;

    if (was.differenz !== null) {
      const betrag = preisFormat.format(Math.abs(was.differenz));
      $('#wechsel-differenz').textContent = was.differenz > 0
        ? `${betrag} mehr im Monat`
        : `${betrag} weniger im Monat`;
    }

    $('#wechsel-ab').textContent = was.gueltigAb === null
      ? 'sofort'
      : datumAnzeigen(was.gueltigAb);

    [['gewinn', was.gewinnt], ['verlust', was.verliert]].forEach(([name, rechte]) => {
      $(`#wechsel-${name}-block`).hidden = rechte.length === 0;
      $(`#wechsel-${name}`).replaceChildren(...rechte.map((recht) => {
        const punkt = document.createElement('li');
        punkt.textContent = LEISTUNGEN[recht];

        return punkt;
      }));
    });

    const storno = $('#wechsel-storno');
    storno.hidden = was.storno === 0;
    storno.textContent = was.storno === 0 ? '' : `Ab ${datumAnzeigen(stand.wechselAb)} sind in deinem Tarif keine Kurse `
      + `enthalten. ${kurseText(was.storno)} ab diesem Tag ${was.storno === 1 ? 'wird' : 'werden'} automatisch storniert.`;

    bestaetigen.textContent = was.art === 'ruecknahme' ? 'Wechsel zurücknehmen' : 'Verbindlich ändern';
  }

  /**
   * 'Dein gebuchter Kurs' oder 'Deine 3 gebuchten Kurse'.
   *
   * @param {number} anzahl  mindestens 1
   * @returns {string}
   */
  function kurseText(anzahl) {
    return anzahl === 1 ? 'Dein gebuchter Kurs' : `Deine ${anzahl} gebuchten Kurse`;
  }

  /**
   * Darf die aktuell gewählte Preisgruppe überhaupt gebucht werden?
   *
   * Die verbindliche Prüfung sitzt im Endpunkt - hier geht es nur darum,
   * den Button zu sperren und zu erklären, warum.
   *
   * @returns {boolean}
   */
  function preisgruppeErlaubt() {
    return stand.erlaubtePreisgruppen.includes(auswahl.preisgruppe);
  }

  /** Zeichnet Karten, Aktionsleiste und Fensterinhalt neu. */
  function zeichnen() {
    const was = aenderung();

    liste.replaceChildren(...tarife.map(karteBauen));

    // Preisgruppen ohne Nachweis werden als gesperrt markiert. Das Feld
    // bleibt bedienbar: Man darf sehen, was der Preis wäre - nur buchen
    // lässt er sich nicht.
    $$('#preisgruppe input').forEach((feld) => {
      feld.closest('.preisgruppe-option')
        .dataset.gesperrt = stand.erlaubtePreisgruppen.includes(feld.value) ? 'nein' : 'ja';
    });

    // Ist gerade eine gesperrte Gruppe angeklickt, graut das CSS über diese
    // Klasse die Karten und den Button unten aus - siehe tarifkarte.css.
    seite.classList.toggle('tarife-page--gesperrt', angemeldet && !preisgruppeErlaubt());

    aktionButton.disabled = angemeldet
      ? was === null || !preisgruppeErlaubt()
      : auswahl.tarif === null;
    aktionButton.textContent = {
      erstwahl: 'Mitgliedschaft starten',
      ruecknahme: 'Wechsel zurücknehmen',
      wechsel: 'Jetzt Mitgliedschaft anpassen',
    }[was?.art] ?? 'Jetzt Mitgliedschaft anpassen';

    if (!angemeldet) {
      aktionButton.textContent = 'Mitglied werden';
    }

    // Ohne Nachweis ist der Button ohnehin gesperrt. Dann wäre eine
    // Beschriftung wie "Wechsel zurücknehmen" nur verwirrend - der Text
    // daneben erklärt bereits, was fehlt.
    if (angemeldet && !preisgruppeErlaubt()) {
      aktionButton.textContent = 'Jetzt Mitgliedschaft anpassen';
    }

    if (angemeldet && !preisgruppeErlaubt()) {
      aktionText.textContent = auswahl.preisgruppe === 'senior'
        ? 'Für den Seniorenpreis fehlt dein Nachweis. Unter „Mein Konto“ hochladen.'
        : 'Für den ermäßigten Preis fehlt dein Nachweis. Unter „Mein Konto“ hochladen.';
    } else {
      aktionText.textContent = was === null || !angemeldet
        ? standText()
        : `Ausgewählt: ${was.ziel.name}. Noch nicht übernommen.`;
    }

    dialogFuellen(was);
  }

  /**
   * Schickt die Auswahl an den Server und übernimmt den Stand aus der
   * Antwort - nicht die eigene Annahme, sondern das, was wirklich
   * gespeichert wurde.
   *
   * @returns {Promise<void>}
   */
  async function uebernehmen() {
    const was = aenderung();

    if (was === null) {
      return;
    }

    bestaetigen.disabled = true;

    try {
      stand = await anpassen(auswahl.tarif, auswahl.preisgruppe);
      auswahlAusStandUebernehmen();
      dialog?.close();
      zeichnen();

      const erfolg = {
        erstwahl: `Willkommen im ${was.ziel.name}. Dein Tarif gilt ab heute.`,
        wechsel: `Wechsel vorgemerkt: Ab ${was.gueltigAb ? datumAnzeigen(was.gueltigAb) : 'dem Monatsersten'} gilt ${was.ziel.name}.`,
        ruecknahme: `Zurückgenommen. Dein ${was.ziel.name} läuft unverändert weiter.`,
      };

      // Die Zahl kommt aus der Antwort - storniert hat der Server, nicht wir.
      const storno = stand.storniert > 0
        ? ` ${kurseText(stand.storniert)} ab ${datumAnzeigen(stand.wechselAb)} ${stand.storniert === 1 ? 'wurde' : 'wurden'} storniert.`
        : '';

      meldungZeigen(meldung, erfolg[was.art] + storno, true);
    } catch (fehler) {
      // 401: Die Sitzung ist abgelaufen, seit die Seite geladen wurde.
      if (fehler.status === 401) {
        window.location.assign(seite.dataset.login);

        return;
      }

      dialog?.close();
      meldungZeigen(meldung, fehler.message);
    } finally {
      bestaetigen.disabled = false;
    }
  }

  // Für Gäste öffnet der Button kein Fenster, sondern führt zur Anmeldung.
  // Deshalb wird das Fenster nur für Angemeldete an den Button gehängt.
  const dialog = angemeldet
    ? initModal({
      modalId: 'wechsel-modal',
      openId: 'mitgliedschaft-anpassen',
      closeId: 'wechsel-schliessen',
      focusId: 'wechsel-bestaetigen',
    })
    : undefined;

  if (!angemeldet) {
    aktionButton.addEventListener('click', () => window.location.assign(seite.dataset.login));
  }

  $('#wechsel-abbrechen').addEventListener('click', () => dialog?.close());
  bestaetigen.addEventListener('click', uebernehmen);

  // Der Hinweis auf den fehlenden Nachweis hat keinen eigenen Button: Er
  // geht auf, sobald jemand eine gesperrte Preisgruppe anklickt.
  const nachweisFenster = initModal({
    modalId: 'nachweis-modal',
    openId: '',
    closeId: 'nachweis-schliessen',
    focusId: 'nachweis-hochladen',
  });

  $('#nachweis-spaeter').addEventListener('click', () => nachweisFenster.close());

  $('#preisgruppe').addEventListener('change', (ereignis) => {
    auswahl = { ...auswahl, preisgruppe: ereignis.target.value };
    zeichnen();

    if (angemeldet && !preisgruppeErlaubt()) {
      $('#nachweis-preis').textContent = auswahl.preisgruppe === 'senior'
        ? 'Seniorenpreis'
        : 'ermäßigten Preis';
      nachweisFenster.open();
    }
  });

  // Die letzte Bremse: Wer die Seite mit einer offenen Auswahl verlässt,
  // bekommt die Rückfrage des Browsers. Den Text bestimmt der Browser
  // selbst, eigene Formulierungen sind dort seit Jahren nicht mehr erlaubt.
  // Eine gesperrte Preisgruppe zählt nicht als offene Auswahl - sie lässt
  // sich ohnehin nicht übernehmen, und der Weg zum Hochladen des Nachweises
  // soll nicht an einer Rückfrage hängen bleiben.
  window.addEventListener('beforeunload', (ereignis) => {
    if (angemeldet && aenderung() !== null && preisgruppeErlaubt()) {
      ereignis.preventDefault();
    }
  });

  try {
    tarife = await alleLaden();

    if (angemeldet) {
      stand = await standLaden();
      auswahlAusStandUebernehmen();
    }

    zeichnen();
  } catch (fehler) {
    meldungZeigen(meldung, fehler.message);
  }
}
