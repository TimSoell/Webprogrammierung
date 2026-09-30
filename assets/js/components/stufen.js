/**
 * @file        assets/js/components/stufen.js
 * @layer       2 – Komponente
 * @description Die drei Stufen (Einsteiger, Fortgeschritten, Erfahren), die
 *              man bei jeder Buchung angibt - beim Kurs wie beim
 *              Probetraining. Liefert die Beschriftungen und die Auswahl
 *              als drei Schaltflächen.
 *
 *              Die Kennungen müssen zu Terminplan::STUFEN im PHP und zum
 *              CHECK-Liste in der Datenbank passen.
 * @see         src/Terminplan.php
 * @see         assets/css/components/kalender.css
 */

/** Kennung -> Beschriftung, in Anzeigereihenfolge. */
export const STUFEN = {
  einsteiger: 'Einsteiger',
  fortgeschritten: 'Fortgeschritten',
  erfahren: 'Erfahren',
};

/**
 * Baut die Auswahl als Gruppe von drei Schaltflächen.
 *
 * Schaltflächen mit aria-pressed statt Radiobuttons: So sehen sie aus wie
 * die Level- und Format-Auswahl auf den Kursseiten, und Screenreader lesen
 * trotzdem vor, welche gedrückt ist.
 *
 * @param {(stufe: string) => void} [beiAuswahl]  wird bei jeder Wahl aufgerufen
 * @returns {{element: HTMLElement, wert: () => string|null}}
 *          element zum Einhängen, wert() liefert die gewählte Kennung oder null
 */
export function stufenAuswahlBauen(beiAuswahl = () => {}) {
  const gruppe = document.createElement('div');
  gruppe.className = 'stufen-auswahl';
  gruppe.setAttribute('role', 'group');
  gruppe.setAttribute('aria-label', 'Deine Stufe');

  let gewaehlt = null;

  const knoepfe = Object.entries(STUFEN).map(([kennung, beschriftung]) => {
    const knopf = document.createElement('button');
    knopf.type = 'button';
    knopf.className = 'stufe-option';
    knopf.textContent = beschriftung;
    knopf.setAttribute('aria-pressed', 'false');

    knopf.addEventListener('click', () => {
      knoepfe.forEach((anderer) => anderer.setAttribute('aria-pressed', 'false'));
      knopf.setAttribute('aria-pressed', 'true');
      gewaehlt = kennung;
      beiAuswahl(kennung);
    });

    return knopf;
  });

  gruppe.append(...knoepfe);

  return { element: gruppe, wert: () => gewaehlt };
}
