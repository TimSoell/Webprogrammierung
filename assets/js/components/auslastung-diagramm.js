/**
 * @file        assets/js/components/auslastung-diagramm.js
 * @layer       2 – Komponente
 * @description Zeichnet die Auslastung eines Tages als Balkendiagramm.
 *
 *              Das SVG wird von Hand zusammengesetzt, ohne Diagramm-
 *              bibliothek - die Aufgabenstellung gibt HTML, CSS und
 *              JavaScript vor, und für 24 Balken lohnt sich keine.
 *
 *              Jeder Balken besteht aus zwei Teilen:
 *
 *                unten  die typische Auslastung aus der Datenbank (gedeckt)
 *                oben   der Aufschlag durch angekündigte Besuche (lime)
 *
 *              Diese Teilung ist der Kern der Vorführung: checkt jemand ein,
 *              wächst nur der obere Teil, und man sieht sofort, welcher Anteil
 *              aus echten Anmeldungen stammt.
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
 * @param {Array<{stunde: number, basis: number, gebucht: number, gesamt: number}>} daten.verlauf
 * @returns {void}
 */
export function diagrammZeichnen(container, daten) {
  const { verlauf, kapazitaet, jetzt } = daten;

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
    role: 'img',
    'aria-label': `Auslastung im Tagesverlauf, aktuell ${verlauf[jetzt.stunde]?.gesamt ?? 0} von ${kapazitaet} Personen`,
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

    // Unterer Teil: die typische Kurve.
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

    // Oberer Teil: der Aufschlag aus angekündigten Besuchen. Mindestens
    // zwei Pixel hoch, sonst wäre der erste Check-in unsichtbar - und genau
    // der soll bei der Vorführung auffallen.
    if (wert.gebucht > 0) {
      const sichtbareHoehe = Math.max(gebuchtHoehe, 2);

      svg.append(element('rect', {
        x,
        y: unten - basisHoehe - sichtbareHoehe,
        width: balkenBreite,
        height: sichtbareHoehe,
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
      beschriftung.textContent = `${wert.stunde}`;
      svg.append(beschriftung);
    }
  });

  container.replaceChildren(svg);
}
