/**
 * @file        assets/js/lib/bild.js
 * @layer       Hilfsmittel (wie lib/dom.js)
 * @description Bereitet ein ausgewähltes Foto für den Upload vor.
 *
 *              Warum das nötig ist: Ein Handyfoto hat heute 4000 Pixel Breite
 *              und 6 MB. Für einen Ausweis ist das um ein Vielfaches mehr, als
 *              zum Ablesen eines Datums gebraucht wird - und es scheitert am
 *              Größenlimit des Endpunkts. Verkleinert kommen dieselben
 *              Informationen in rund einem Zehntel der Datenmenge an.
 *
 *              Nebeneffekt, der hier wichtiger ist als die Dateigröße: Was
 *              nicht hochgeladen wird, kann auch nicht ausgewertet werden.
 * @see         assets/js/services/nachweise.js
 */

/** Längste Kante nach dem Verkleinern. Zum Ablesen von Text reicht das. */
const MAX_KANTE = 1600;

/**
 * Verkleinert ein Bild und gibt es als base64-Text zurück.
 *
 * Das Ergebnis ist immer JPEG - auch wenn eine PNG-Datei hineingeht. Das
 * hält die Datenmenge klein, und ein Ausweisfoto braucht keine
 * verlustfreie Kompression.
 *
 * @param {File} datei  Die vom Nutzer ausgewählte Datei
 * @returns {Promise<{daten: string, mimeTyp: string}>}
 *          daten ist base64 OHNE das "data:image/jpeg;base64," davor,
 *          weil der Endpunkt genau das erwartet
 * @throws {Error}  wenn die Datei kein lesbares Bild ist
 */
export async function verkleinern(datei) {
  const bild = await laden(datei);

  // Nur verkleinern, nie vergrößern: Ein kleines Foto wird durch Hochrechnen
  // nicht lesbarer, nur größer.
  const faktor = Math.min(1, MAX_KANTE / Math.max(bild.width, bild.height));
  const flaeche = document.createElement('canvas');

  flaeche.width = Math.round(bild.width * faktor);
  flaeche.height = Math.round(bild.height * faktor);

  flaeche.getContext('2d').drawImage(bild, 0, 0, flaeche.width, flaeche.height);

  // toDataURL liefert 'data:image/jpeg;base64,XXXX' - der Endpunkt will nur
  // den Teil hinter dem Komma.
  const datenUrl = flaeche.toDataURL('image/jpeg', 0.85);

  return {
    daten: datenUrl.slice(datenUrl.indexOf(',') + 1),
    mimeTyp: 'image/jpeg',
  };
}

/**
 * Lädt eine Datei in ein Image-Objekt, damit sie gezeichnet werden kann.
 *
 * @param {File} datei
 * @returns {Promise<HTMLImageElement>}
 * @throws {Error}
 */
function laden(datei) {
  return new Promise((erfuellen, ablehnen) => {
    // createObjectURL statt FileReader: Das Bild muss nicht erst komplett
    // als Text im Speicher landen, nur um gezeichnet zu werden.
    const url = URL.createObjectURL(datei);
    const bild = new Image();

    bild.addEventListener('load', () => {
      URL.revokeObjectURL(url);
      erfuellen(bild);
    });

    bild.addEventListener('error', () => {
      URL.revokeObjectURL(url);
      ablehnen(new Error('Diese Datei konnte nicht als Bild gelesen werden.'));
    });

    bild.src = url;
  });
}
