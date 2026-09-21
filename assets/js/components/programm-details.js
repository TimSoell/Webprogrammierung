/**
 * @file        assets/js/components/programm-details.js
 * @layer       2 – Komponente
 * @description Baut den unteren Teil der drei Programmseiten aus den Daten
 *              der Datenbank: die aufklappbaren Merkmale links und die
 *              Coaches im gelben Kasten rechts.
 *
 *              Eine Komponente statt drei Seitenskripten, weil Strength,
 *              Move und Fight sich nur im Inhalt unterscheiden, nicht im
 *              Verhalten. Die drei Skripte unter pages/ reichen nur noch das
 *              Kürzel durch - genau wie auth-formular.js von zwei Seiten
 *              benutzt wird.
 *
 *              WO DIE AUSWAHL LANDET
 *              Nicht angemeldet: im localStorage des Browsers, sofort beim
 *              Klick (auswahl-speicher.js). Angemeldet: zusätzlich am Konto,
 *              aber erst auf Knopfdruck (services/auswahl.js). Beim Öffnen
 *              gewinnt das Konto - es ist die bewusstere Entscheidung.
 *              Begründung: docs/decisions/ADR-0007-gemerkte-auswahl-am-konto.md
 *
 *              KEIN innerHTML mit Daten aus der Datenbank. Alles wird über
 *              createElement und textContent aufgebaut. Das ist das Gegenstück
 *              zu e() im PHP: Ein Coach namens "<script>" bleibt so Text.
 * @see         assets/js/services/programme.js
 * @see         assets/js/services/auswahl.js
 * @see         assets/css/components/merkmale.css
 */

import { $ } from '../lib/dom.js';
import { programmDetailsLaden } from '../services/programme.js';
import { auswahlFuerProgrammLaden, auswahlMerken } from '../services/auswahl.js';
import { lokalLesen, lokalSpeichern } from './auswahl-speicher.js';

/** Überschrift über jedem Block. Schlüssel = Art aus der Tabelle merkmale. */
const BESCHRIFTUNG = {
  fokus: 'Fokus',
  level: 'Level',
  format: 'Format',
};

/**
 * Baut einen aufklappbaren Block.
 *
 * Bei einem einzigen Eintrag (Fokus) erscheint nur der Text. Bei mehreren
 * (Level, Format) kommen darüber die Schaltflächen zur Auswahl.
 *
 * @param {string} slug
 * @param {string} art        'fokus', 'level' oder 'format'
 * @param {Array<{titel: string, beschreibung: string}>} eintraege
 * @param {{level: string|null, format: string|null}} zustand  Wird beim Klick fortgeschrieben
 * @param {() => void} beiAuswahl  Meldet dem Merken-Button, dass sich etwas geändert hat
 * @returns {HTMLElement|null}  null, wenn es zu dieser Art nichts gibt
 */
function merkmalBauen(slug, art, eintraege, zustand, beiAuswahl) {
  if (eintraege.length === 0) {
    return null;
  }

  // Gibt es den vorgemerkten Titel nicht mehr (Text in der Datenbank
  // geändert), fällt die Auswahl auf den ersten Eintrag zurück.
  const startIndex = Math.max(0, eintraege.findIndex((e) => e.titel === zustand[art]));

  if (art in zustand) {
    zustand[art] = eintraege[startIndex].titel;
  }

  // Bewusst ein div und kein section: 03-layout.css gibt JEDEM <section>
  // 118px Innenabstand. Die Überschrift und role="region" unten tragen die
  // Bedeutung ohnehin - dafür braucht es das Element nicht.
  const block = document.createElement('div');
  block.className = 'merkmal';
  block.dataset.art = art;

  const knopfId = `merkmal-${art}-knopf`;
  const inhaltId = `merkmal-${art}-inhalt`;

  // --- Kopfzeile zum Aufklappen ---------------------------------------------
  const ueberschrift = document.createElement('h3');
  ueberschrift.className = 'merkmal-kopf';

  const schalter = document.createElement('button');
  schalter.type = 'button';
  schalter.className = 'merkmal-schalter';
  schalter.id = knopfId;
  // aria-expanded sagt Screenreadern, ob der Block offen ist. Das CSS dreht
  // daran auch den Pfeil - deshalb steht der Zustand nur an dieser Stelle.
  schalter.setAttribute('aria-expanded', 'false');
  schalter.setAttribute('aria-controls', inhaltId);

  const artText = document.createElement('span');
  artText.className = 'merkmal-art';
  artText.textContent = BESCHRIFTUNG[art] ?? art;

  const aktuellText = document.createElement('span');
  aktuellText.className = 'merkmal-aktuell';
  aktuellText.textContent = eintraege[startIndex].titel;

  const pfeil = document.createElement('span');
  pfeil.className = 'merkmal-pfeil';
  pfeil.setAttribute('aria-hidden', 'true');

  schalter.append(artText, aktuellText, pfeil);
  ueberschrift.append(schalter);

  // --- Aufklappbarer Inhalt --------------------------------------------------
  const inhalt = document.createElement('div');
  inhalt.className = 'merkmal-inhalt';
  inhalt.id = inhaltId;
  inhalt.setAttribute('role', 'region');
  inhalt.setAttribute('aria-labelledby', knopfId);
  inhalt.hidden = true;

  const text = document.createElement('p');
  text.className = 'merkmal-text';
  text.textContent = eintraege[startIndex].beschreibung;

  if (eintraege.length > 1) {
    const auswahl = document.createElement('div');
    auswahl.className = 'merkmal-auswahl';
    auswahl.setAttribute('role', 'group');
    auswahl.setAttribute('aria-label', `${BESCHRIFTUNG[art] ?? art} wählen`);

    const optionen = eintraege.map((eintrag, index) => {
      const option = document.createElement('button');
      option.type = 'button';
      option.className = 'merkmal-option';
      option.textContent = eintrag.titel;
      option.setAttribute('aria-pressed', String(index === startIndex));

      option.addEventListener('click', () => {
        optionen.forEach((andere) => andere.setAttribute('aria-pressed', 'false'));
        option.setAttribute('aria-pressed', 'true');

        text.textContent = eintrag.beschreibung;
        aktuellText.textContent = eintrag.titel;

        zustand[art] = eintrag.titel;
        lokalSpeichern(slug, art, eintrag.titel);
        beiAuswahl();
      });

      return option;
    });

    auswahl.append(...optionen);
    inhalt.append(auswahl);
  }

  inhalt.append(text);

  schalter.addEventListener('click', () => {
    const offen = schalter.getAttribute('aria-expanded') === 'true';

    // Erst alle schließen, dann den geklickten öffnen: Es soll immer nur
    // einer offen sein, sonst springt der Text darunter zu stark.
    block.parentElement?.querySelectorAll('.merkmal-schalter').forEach((anderer) => {
      anderer.setAttribute('aria-expanded', 'false');
      const zugehoerig = document.getElementById(anderer.getAttribute('aria-controls'));
      if (zugehoerig) {
        zugehoerig.hidden = true;
      }
    });

    if (!offen) {
      schalter.setAttribute('aria-expanded', 'true');
      inhalt.hidden = false;
    }
  });

  block.append(ueberschrift, inhalt);

  return block;
}

/**
 * Baut die Karte eines Coaches.
 *
 * Fehlt die Bilddatei, treten an ihre Stelle die Initialen. Ohne das zeigt
 * der Browser ein kaputtes Bildsymbol - siehe assets/img/coaches/README.md.
 *
 * @param {{name: string, schwerpunkt: string, bild: string, bildAlt: string}} coach
 * @param {string} baseUrl  Präfix aus data-base-url am <html>-Tag
 * @returns {HTMLElement}
 */
function coachBauen(coach, baseUrl) {
  const karte = document.createElement('li');
  karte.className = 'coach-karte';

  const bild = document.createElement('img');
  bild.className = 'coach-bild';
  bild.src = baseUrl + coach.bild;
  bild.alt = coach.bildAlt;
  bild.loading = 'lazy';

  bild.addEventListener('error', () => {
    const ersatz = document.createElement('span');
    ersatz.className = 'coach-bild coach-bild--ersatz';
    // Anfangsbuchstaben von Vor- und Nachname, höchstens zwei.
    ersatz.textContent = coach.name
      .split(' ')
      .slice(0, 2)
      .map((teil) => teil.charAt(0))
      .join('');
    ersatz.setAttribute('role', 'img');
    ersatz.setAttribute('aria-label', coach.bildAlt);

    bild.replaceWith(ersatz);
  });

  const name = document.createElement('p');
  name.className = 'coach-name';
  name.textContent = coach.name;

  const schwerpunkt = document.createElement('p');
  schwerpunkt.className = 'coach-schwerpunkt';
  schwerpunkt.textContent = coach.schwerpunkt;

  karte.append(bild, name, schwerpunkt);

  return karte;
}

/**
 * Baut den Bereich unter den Blöcken: entweder den Merken-Button für
 * angemeldete Mitglieder oder den Hinweis mit Link zur Anmeldung.
 *
 * @param {Element} container  #merken aus der Seite
 * @param {string}  slug
 * @param {{level: string|null, format: string|null}} zustand  Die aktuelle Auswahl
 * @param {{level: string|null, format: string|null}|null} gemerkt  Was am Konto steht
 * @param {string}  baseUrl
 * @returns {() => void}  Funktion, die den Button nach einer Auswahl neu beschriftet
 */
function merkenBauen(container, slug, zustand, gemerkt, baseUrl) {
  container.replaceChildren();

  if (container.dataset.angemeldet !== '1') {
    const hinweis = document.createElement('p');
    hinweis.className = 'merken-hinweis';
    hinweis.append('Deine Auswahl gilt nur in diesem Browser. ');

    const link = document.createElement('a');
    link.href = `${baseUrl}anmelden.php`;
    link.textContent = 'Melde dich an';
    hinweis.append(link, ', um sie an deinem Konto zu merken.');

    container.append(hinweis);

    return () => {};
  }

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'button button--ghost merken-button';

  const meldung = document.createElement('p');
  meldung.className = 'merken-meldung';
  meldung.setAttribute('role', 'status');

  container.append(button, meldung);

  /** Zuletzt am Konto gespeicherter Stand. Wird nach dem Merken fortgeschrieben. */
  let gespeichert = gemerkt;

  /** true, wenn die Anzeige genau dem entspricht, was am Konto steht. */
  const istGemerkt = () => gespeichert !== null
    && gespeichert.level === zustand.level
    && gespeichert.format === zustand.format;

  const beschriften = () => {
    const fertig = istGemerkt();

    button.disabled = fertig;
    button.textContent = fertig
      ? 'Gemerkt'
      : (gespeichert === null ? 'Auswahl merken' : 'Auswahl aktualisieren');
  };

  button.addEventListener('click', async () => {
    button.disabled = true;
    meldung.textContent = '';
    meldung.classList.remove('merken-meldung--fehler');

    try {
      await auswahlMerken(slug, zustand.level, zustand.format);

      gespeichert = { level: zustand.level, format: zustand.format };
      meldung.textContent = 'In deinem Konto gemerkt.';
    } catch (fehler) {
      meldung.textContent = fehler.message;
      meldung.classList.add('merken-meldung--fehler');
    } finally {
      beschriften();
    }
  });

  beschriften();

  return beschriften;
}

/**
 * Lädt die Details eines Programms und baut sie in die Seite ein.
 *
 * Erwartet drei Container in der Seite:
 *   #merkmale  für die aufklappbaren Blöcke
 *   #coaches   für die Coach-Karten
 *   #merken    für den Merken-Button (mit data-angemeldet)
 *
 * Fehlt einer davon, passiert nichts - dieselbe Logik wie in initModal().
 *
 * @param {string} slug  'strength', 'move' oder 'fight'
 * @returns {Promise<void>}
 */
export async function programmDetailsAufbauen(slug) {
  const merkmaleContainer = $('#merkmale');
  const coachesContainer = $('#coaches');
  const merkenContainer = $('#merken');

  if (!merkmaleContainer || !coachesContainer || !merkenContainer) {
    return;
  }

  const baseUrl = document.documentElement.dataset.baseUrl ?? '/';
  const angemeldet = merkenContainer.dataset.angemeldet === '1';

  let details;

  try {
    details = await programmDetailsLaden(slug);
  } catch (fehler) {
    // ApiError.message ist schon ein fertiger Satz, siehe services/api.js.
    merkmaleContainer.textContent = fehler.message;
    merkmaleContainer.classList.add('merkmale--fehler');
    coachesContainer.closest('.coaches')?.remove();
    return;
  }

  // Was am Konto steht, hat Vorrang vor dem Browser: Der Klick auf "merken"
  // war die bewusstere Entscheidung, und er gilt auf jedem Gerät.
  let gemerkt = null;

  if (angemeldet) {
    try {
      gemerkt = (await auswahlFuerProgrammLaden(slug)).auswahl;
    } catch {
      // Sitzung abgelaufen oder Server kurz weg. Dann eben ohne - die Seite
      // funktioniert weiter, nur der Button startet auf "Auswahl merken".
    }
  }

  const zustand = {
    level: gemerkt?.level ?? lokalLesen(slug, 'level'),
    format: gemerkt?.format ?? lokalLesen(slug, 'format'),
  };

  // Wird gleich durch die echte Funktion ersetzt. Bis dahin ein Platzhalter,
  // weil merkmalBauen sie schon als Rückruf bekommt.
  let beschriften = () => {};

  merkmaleContainer.replaceChildren();

  Object.keys(BESCHRIFTUNG).forEach((art) => {
    const block = merkmalBauen(slug, art, details.merkmale[art] ?? [], zustand, () => beschriften());

    if (block) {
      merkmaleContainer.append(block);
    }
  });

  beschriften = merkenBauen(merkenContainer, slug, zustand, gemerkt, baseUrl);

  if (details.coaches.length === 0) {
    coachesContainer.closest('.coaches')?.remove();
    return;
  }

  coachesContainer.replaceChildren(
    ...details.coaches.map((coach) => coachBauen(coach, baseUrl)),
  );
}
