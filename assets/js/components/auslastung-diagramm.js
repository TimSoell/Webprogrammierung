/**
 * @file        assets/js/components/auslastung-diagramm.js
 * @layer       2 – Komponente
 * @description Zeichnet die Auslastung eines Tages als Balkendiagramm.
 *
 *              Das SVG wird von Hand zusammengesetzt, ohne Diagramm-
 *              bibliothek - die Aufgabenstellung gibt HTML, CSS und
 *              JavaScript vor, und für 24 Balken lohnt sich keine.
 *
 *              Die typische Auslastung bildet den grauen Hintergrund.
 *              Angekündigte Besuche und Check-ins füllen ihn von unten grün.
 *              Sind mehr Besuche erfasst als üblich, wächst Grün darüber hinaus.
 *
 *              Die Farben stehen im CSS, nicht hier - siehe
 *              assets/css/components/auslastung.css.
 * @see         assets/css/components/auslastung.css
 * @see         assets/js/services/auslastung.js
 * @see         docs/features/auslastung.md
 */

/** Zeichenfläche des SVG. Die Anzeige skaliert über viewBox mit. */
const BREITE = 720;
const HOEHE = 240;

/** Platz für die Stundenbeschriftung unten und die Kapazitätslinie oben. */
const RAND_UNTEN = 26;
const RAND_OBEN = 14;

const SVG_NS = 'http://www.w3.org/2000/svg';
const hovercardSchliessen = new WeakMap();

/**
 * Erzeugt ein SVG-Element mit Attributen.
 *
 * Spart das wiederholte createElementNS/setAttribute weiter unten.
 *
 * @param {string} name  Elementname, z. B. 'rect'
 * @param {Record<string, string|number>} attribute
 * @returns {SVGElement}
 */
function element(name, attribute) {
  const knoten = document.createElementNS(SVG_NS, name);

  for (const [schluessel, wert] of Object.entries(attribute)) {
    knoten.setAttribute(schluessel, String(wert));
  }

  return knoten;
}

/**
 * Zeichnet das Diagramm neu in einen Container.
 *
 * Der bisherige Inhalt wird ersetzt. Die Funktion ist deshalb gefahrlos
 * mehrfach aufrufbar - genau so wird sie beim Aktualisieren benutzt.
 *
 * @param {HTMLElement} container   Element, das das SVG aufnimmt
 * @param {object}      daten       Antwort von auslastungLaden()
 * @param {number}      daten.kapazitaet
 * @param {{stunde: number}} daten.jetzt
 * @param {Array<{stunde: number, basis: number, gebucht: number, checkins: number, gesamt: number}>} daten.verlauf
 * @returns {void}
 */
export function diagrammZeichnen(container, daten) {
  const { verlauf, kapazitaet, jetzt } = daten;
  const fokusStunde = container.querySelector?.('.auslastung-spalte:focus')?.getAttribute('data-stunde');
  hovercardSchliessen.get(container)?.();
  const spalten = [];

  const karte = document.createElement('div');
  karte.className = 'auslastung-hovercard';
  karte.id = `${container.id}-hovercard`;
  karte.setAttribute('role', 'tooltip');
  karte.hidden = true;

  const uhrzeit = document.createElement('strong');
  const zahlen = document.createElement('dl');
  karte.append(uhrzeit, zahlen);

  /** Schließt die Karte und entfernt den nur dafür benötigten Listener. */
  const ausblenden = () => {
    karte.hidden = true;
    document.removeEventListener('keydown', beiEscape);
  };
  const beiEscape = (event) => {
    if (event.key === 'Escape') ausblenden();
  };
  hovercardSchliessen.set(container, ausblenden);

  // Die Skala richtet sich nach der Kapazität, nicht nach dem höchsten Wert.
  // Sonst sähe ein ruhiger Dienstag genauso voll aus wie ein Montagabend.
  // Liegt ein Wert darüber, wächst die Skala mit, damit nichts abgeschnitten
  // wird - bei einer Vorführung mit vielen Check-ins kommt das vor.
  const hoechstwert = Math.max(kapazitaet, ...verlauf.map((w) => w.gesamt));

  const zeichenHoehe = HOEHE - RAND_UNTEN - RAND_OBEN;
  const spalte = BREITE / verlauf.length;
  const balkenBreite = spalte * 0.62;

  /**
   * Rechnet eine Personenzahl in eine Höhe in der Zeichenfläche um.
   *
   * @param {number} personen
   * @returns {number}
   */
  const hoeheFuer = (personen) => (personen / hoechstwert) * zeichenHoehe;

  const svg = element('svg', {
    viewBox: `0 0 ${BREITE} ${HOEHE}`,
    class: 'auslastung-svg',
    role: 'group',
    'aria-label': `Auslastung im Tagesverlauf, aktuell ${jetzt.personen ?? verlauf[jetzt.stunde]?.gesamt ?? 0} von ${kapazitaet} Personen`,
  });

  // --- Kapazitätslinie -------------------------------------------------------
  const linieY = RAND_OBEN + zeichenHoehe - hoeheFuer(kapazitaet);

  svg.append(element('line', {
    x1: 0,
    x2: BREITE,
    y1: linieY,
    y2: linieY,
    class: 'auslastung-kapazitaet',
  }));

  const linieText = element('text', {
    x: BREITE - 4,
    y: linieY - 5,
    class: 'auslastung-kapazitaet-text',
    'text-anchor': 'end',
  });
  linieText.textContent = `${kapazitaet} Plätze`;
  svg.append(linieText);

  // --- Balken ----------------------------------------------------------------
  verlauf.forEach((wert, index) => {
    const x = index * spalte + (spalte - balkenBreite) / 2;
    const unten = RAND_OBEN + zeichenHoehe;
    const istJetzt = wert.stunde === jetzt.stunde;

    const basisHoehe = hoeheFuer(wert.basis);
    const gebuchtHoehe = hoeheFuer(wert.gebucht);

    // Hintergrund: die typische Kurve.
    if (basisHoehe > 0) {
      svg.append(element('rect', {
        x,
        y: unten - basisHoehe,
        width: balkenBreite,
        height: basisHoehe,
        rx: 2,
        class: istJetzt ? 'auslastung-balken auslastung-balken--jetzt' : 'auslastung-balken',
      }));
    }

    // Besuche füllen von derselben Grundlinie aus; jeder Check-in zählt mit.
    if (wert.gebucht > 0) {
      svg.append(element('rect', {
        x,
        y: unten - gebuchtHoehe,
        width: balkenBreite,
        height: gebuchtHoehe,
        rx: 2,
        class: 'auslastung-balken-gebucht',
      }));
    }

    // Beschriftung nur alle drei Stunden, sonst überlappen die Zahlen.
    if (wert.stunde % 3 === 0) {
      const beschriftung = element('text', {
        x: index * spalte + spalte / 2,
        y: HOEHE - 8,
        class: istJetzt ? 'auslastung-stunde auslastung-stunde--jetzt' : 'auslastung-stunde',
        'text-anchor': 'middle',
      });
      beschriftung.textContent = `${wert.stunde}:00`;
      svg.append(beschriftung);
    }

    // Die ganze Spalte bleibt auch bei leeren Balken gut erreichbar.
    const treffer = element('g', {
      class: 'auslastung-spalte',
      tabindex: 0,
      role: 'button',
      'data-stunde': wert.stunde,
      'aria-label': `${wert.stunde}:00 Uhr: ${wert.checkins} Check-ins, ${wert.basis} üblich, ${wert.gebucht - wert.checkins} angekündigt`,
      'aria-describedby': karte.id,
    });
    treffer.append(element('rect', {
      x: index * spalte, y: RAND_OBEN, width: spalte, height: zeichenHoehe,
      class: 'auslastung-treffer', rx: 2,
    }));

    /** Zeigt die Stundenwerte an der Oberkante des Balkens. @returns {void} */
    const anzeigen = () => {
      uhrzeit.textContent = `${wert.stunde}:00 Uhr`;
      zahlen.replaceChildren();
      for (const [label, anzahl] of [
        ['Check-ins', wert.checkins],
        ['Üblich', wert.basis],
        ['Angekündigt', wert.gebucht - wert.checkins],
      ]) {
        const name = document.createElement('dt');
        const zahl = document.createElement('dd');
        name.textContent = label;
        zahl.textContent = String(anzahl);
        zahlen.append(name, zahl);
      }
      karte.style.setProperty('--hover-x', `${(index + 0.5) * spalte / BREITE * 100}%`);
      karte.hidden = false;
      const rahmen = svg.getBoundingClientRect();
      const ankerY = rahmen.top + (unten - hoeheFuer(wert.gesamt)) / HOEHE * rahmen.height;
      // Innerhalb des sichtbaren Fensters und unter der festen Navigation bleiben.
      const kopfzeile = document.querySelector?.('header')?.getBoundingClientRect();
      const mindestAbstand = Math.max(8, (kopfzeile?.bottom ?? 0) + 8);
      const oben = Math.max(mindestAbstand, Math.min(ankerY - karte.offsetHeight - 12, window.innerHeight - karte.offsetHeight - 8));
      karte.style.setProperty('--hover-y', `${oben - container.getBoundingClientRect().top}px`);
      document.addEventListener('keydown', beiEscape);
    };
    treffer.addEventListener('pointerenter', (event) => {
      if (event.pointerType !== 'touch') anzeigen();
    });
    treffer.addEventListener('pointerleave', (event) => {
      if (event.pointerType !== 'touch' && document.activeElement !== treffer) ausblenden();
    });
    treffer.addEventListener('focus', anzeigen);
    treffer.addEventListener('blur', ausblenden);
    treffer.addEventListener('click', anzeigen);
    treffer.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') ausblenden();
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        anzeigen();
      }
    });
    svg.append(treffer);
    spalten.push(treffer);
  });

  container.replaceChildren(svg, karte);
  // Das automatische Nachladen darf den Tastaturfokus nicht verlieren.
  if (fokusStunde !== undefined) {
    spalten.find((spalte) => spalte.getAttribute('data-stunde') === fokusStunde)?.focus();
  }
}
