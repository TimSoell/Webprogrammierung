/**
 * @file        assets/js/lib/datum.js
 * @layer       Hilfsfunktionen
 * @description Datumsangaben aus der API ('JJJJ-MM-TT') lesbar machen und
 *              Termine nach Tagen gruppieren.
 *
 *              lib/, weil nichts davon ein Feature kennt - dasselbe würde
 *              auch in einem Kalender für Rechnungen funktionieren.
 *
 *              WARUM NICHT new Date('2026-09-22')?
 *              Ein Datum ohne Uhrzeit liest der Browser als Mitternacht in
 *              UTC. In Deutschland ist das 01:00 oder 02:00 - in Zeitzonen
 *              westlich von UTC aber der Vortag. Deshalb wird hier in
 *              Jahr, Monat und Tag zerlegt und lokal zusammengesetzt.
 */

const WOCHENTAGE = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
const MONATE = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli',
  'August', 'September', 'Oktober', 'November', 'Dezember'];

/**
 * @param {string} datum  'JJJJ-MM-TT'
 * @returns {Date}        Mitternacht in der Zeitzone des Browsers
 */
function lokal(datum) {
  const [jahr, monat, tag] = datum.split('-').map(Number);

  return new Date(jahr, monat - 1, tag);
}

/**
 * Überschrift für einen Tag im Kalender.
 *
 * @param {string} datum  'JJJJ-MM-TT'
 * @returns {string}      z. B. 'Dienstag, 22. September'
 */
export function tagUeberschrift(datum) {
  const tag = lokal(datum);

  return `${WOCHENTAGE[tag.getDay()]}, ${tag.getDate()}. ${MONATE[tag.getMonth()]}`;
}

/**
 * Datum ohne Wochentag, z. B. für einen Zeitraum.
 *
 * @param {string} datum  'JJJJ-MM-TT'
 * @returns {string}      z. B. '22. September'
 */
export function kurzesDatum(datum) {
  const tag = lokal(datum);

  return `${tag.getDate()}. ${MONATE[tag.getMonth()]}`;
}

/**
 * @param {Date} tag
 * @returns {string}  'JJJJ-MM-TT' in der Zeitzone des Browsers
 */
function alsText(tag) {
  const zweistellig = (zahl) => String(zahl).padStart(2, '0');

  return `${tag.getFullYear()}-${zweistellig(tag.getMonth() + 1)}-${zweistellig(tag.getDate())}`;
}

/**
 * Teilt einen Zeitraum in Kalenderwochen von Montag bis Sonntag.
 *
 * Tage vor dem Anfang und nach dem Ende stehen als null in der Woche, damit
 * jeder Tag in der richtigen Spalte landet: Beginnt der Zeitraum an einem
 * Donnerstag, sind Montag bis Mittwoch der ersten Woche null.
 *
 * @param {string} von  'JJJJ-MM-TT', einschließlich
 * @param {string} bis  'JJJJ-MM-TT', einschließlich
 * @returns {Array<Array<string|null>>}  Wochen mit je sieben Einträgen
 */
export function kalenderWochen(von, bis) {
  const ende = lokal(bis);
  const tag = lokal(von);
  // getDay(): 0 = Sonntag. Für eine Woche ab Montag wird daraus 6.
  tag.setDate(tag.getDate() - ((tag.getDay() + 6) % 7));

  const wochen = [];

  while (tag <= ende) {
    const woche = [];

    for (let i = 0; i < 7; i += 1) {
      const text = alsText(tag);
      woche.push(text >= von && text <= bis ? text : null);
      tag.setDate(tag.getDate() + 1);
    }

    wochen.push(woche);
  }

  return wochen;
}

/**
 * Gruppiert eine nach Datum sortierte Liste nach Tagen.
 *
 * @template T
 * @param {T[]} eintraege  Jeder Eintrag braucht ein Feld datum ('JJJJ-MM-TT')
 * @returns {Array<{datum: string, eintraege: T[]}>}  in der Reihenfolge der Eingabe
 */
export function nachTagen(eintraege) {
  const tage = new Map();

  eintraege.forEach((eintrag) => {
    if (!tage.has(eintrag.datum)) {
      tage.set(eintrag.datum, []);
    }
    tage.get(eintrag.datum).push(eintrag);
  });

  return [...tage].map(([datum, liste]) => ({ datum, eintraege: liste }));
}
