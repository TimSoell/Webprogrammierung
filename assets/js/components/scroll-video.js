/**
 * @file        assets/js/components/scroll-video.js
 * @layer       2 – Komponente
 * @description Steuert die Abspielposition eines Videos über die
 *              Scrollposition: scrollt man vorwärts, läuft das Video
 *              vorwärts, scrollt man zurück, läuft es rückwärts.
 *
 *              Das Video wird nie abgespielt. Stattdessen wird bei jedem
 *              Bild `currentTime` neu gesetzt - der Browser zeigt dann genau
 *              das Einzelbild an dieser Stelle. Damit das flüssig aussieht,
 *              braucht es drei Dinge:
 *
 *              1. Ein Video, in dem JEDES Bild ein Keyframe ist. Sonst muss
 *                 der Decoder bei jedem Sprung vom Anfang an neu rechnen -
 *                 das ist die häufigste Ursache für Ruckeln. Wie die Datei
 *                 erzeugt wird, steht in docs/features/scroll-video.md.
 *              2. Genug Einzelbilder für den Scrollweg. Liegen mehr als
 *                 rund 20 px Scrollweg auf einem Bild, sieht man jedes Bild
 *                 einzeln springen. Den Scrollweg legt
 *                 assets/css/components/scroll-video.css fest, die Anzahl
 *                 der Bilder die Videodatei.
 *              3. Glättung. Ein Mausrad liefert grobe Sprünge. Deshalb
 *                 springt das Video nicht direkt auf die Zielzeit, sondern
 *                 zieht in kleinen Schritten nach (siehe NACHZIEHZEIT).
 * @see         assets/css/components/scroll-video.css
 * @see         docs/features/scroll-video.md
 */

/**
 * Wie weich das Video der Scrollposition hinterherläuft, in Millisekunden.
 *
 * Nach dieser Zeit hat das Video rund zwei Drittel der Reststrecke
 * zurückgelegt. Größer = weicher, aber träger. Kleiner = direkter, aber
 * ruckeliger.
 *
 * Eine Zeit statt eines festen Anteils pro Bild, weil Bildschirme
 * unterschiedlich oft neu zeichnen: meist 60-mal pro Sekunde, ein MacBook
 * Pro 120-mal. Ein fester Anteil pro Bild würde dort doppelt so schnell
 * nachziehen und nur halb so stark glätten.
 */
const NACHZIEHZEIT = 100;

/**
 * Unterhalb dieses Abstands (in Sekunden) wird nicht mehr gesprungen.
 *
 * Ohne diese Schwelle würde die Komponente auch im Stillstand bei jedem
 * Bild einen neuen Sprung anfordern, der ohnehin dasselbe Einzelbild zeigt.
 */
const MIN_ABSTAND = 0.01;

/**
 * Verbindet ein Video mit dem Scrollfortschritt seines Abschnitts.
 *
 * Tut nichts, wenn es den Abschnitt auf der aktuellen Seite nicht gibt oder
 * wenn das Betriebssystem auf "weniger Bewegung" eingestellt ist - dann
 * bleibt einfach das erste Einzelbild stehen.
 *
 * @param {string} [auswahl='[data-scroll-video]']  CSS-Auswahl des Abschnitts,
 *                                                  der das Video enthält.
 * @returns {void}
 */
export function initScrollVideo(auswahl = '[data-scroll-video]') {
  const abschnitt = document.querySelector(auswahl);
  const video = abschnitt?.querySelector('video');

  if (!abschnitt || !video) {
    return;
  }

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  // Zeit, die gerade angezeigt wird. Läuft der Zielzeit hinterher.
  let angezeigteZeit = 0;
  let laufendeSchleife;

  // Zeitstempel des letzten Nachziehens. Liegt er lange zurück - beim
  // ersten Durchlauf oder nach dem Wiederhereinscrollen -, springt das
  // Video direkt auf die Zielzeit, statt sichtbar hinterherzulaufen.
  let letztesNachziehen = 0;

  /**
   * Rechnet die aktuelle Scrollposition in eine Zeit im Video um.
   *
   * getBoundingClientRect() statt offsetTop: der Wert stimmt auch dann,
   * wenn ein Elternelement positioniert ist, und er ist nach einer
   * Größenänderung des Fensters sofort wieder richtig.
   *
   * @returns {number}  Zielzeit in Sekunden.
   */
  const zielZeitBerechnen = () => {
    const masse = abschnitt.getBoundingClientRect();

    // So viele Pixel lang klebt das Video am oberen Rand. Ist der Abschnitt
    // nicht höher als das Fenster, gibt es keinen Scrollweg - dann bleibt
    // das Video am Anfang stehen.
    const scrollWeg = masse.height - window.innerHeight;

    if (scrollWeg <= 0) {
      return 0;
    }

    // masse.top ist negativ, sobald der Abschnitt oben aus dem Bild läuft.
    const fortschritt = Math.min(1, Math.max(0, -masse.top / scrollWeg));

    return fortschritt * video.duration;
  };

  /**
   * Ein Durchlauf pro Bildwiederholung: Zielzeit holen, ein Stück nachziehen.
   *
   * @param {number} jetzt  Zeitstempel von requestAnimationFrame in ms.
   * @returns {void}
   */
  const bildAktualisieren = (jetzt) => {
    laufendeSchleife = window.requestAnimationFrame(bildAktualisieren);

    // duration ist NaN, solange die Metadaten noch nicht geladen sind.
    if (!Number.isFinite(video.duration)) {
      return;
    }

    // Ein Sprung läuft noch. Jetzt einen zweiten anzufordern würde den
    // ersten verwerfen - das Ergebnis wäre genau das Stocken, das wir
    // vermeiden wollen. Also dieses Bild auslassen. Die Zeit läuft dabei
    // weiter und wird beim nächsten Nachziehen mit aufgeholt.
    if (video.seeking) {
      return;
    }

    const ziel = zielZeitBerechnen();
    const abstand = ziel - angezeigteZeit;
    const vergangen = jetzt - letztesNachziehen;
    letztesNachziehen = jetzt;

    if (Math.abs(abstand) < MIN_ABSTAND) {
      return;
    }

    // Anteil der Reststrecke für die vergangene Zeit. Bei 60 Hz rund 15 %
    // pro Bild, bei 120 Hz rund 8 % - pro Sekunde gerechnet also gleich.
    angezeigteZeit += abstand * (1 - Math.exp(-vergangen / NACHZIEHZEIT));
    video.currentTime = angezeigteZeit;
  };

  /**
   * Startet und stoppt die Schleife, je nachdem ob der Abschnitt zu sehen ist.
   *
   * Außerhalb des Bildschirms muss nichts gerechnet werden - das spart auf
   * Notebooks spürbar Akku.
   *
   * @param {boolean} an
   * @returns {void}
   */
  const schleifeUmschalten = (an) => {
    if (an && laufendeSchleife === undefined) {
      laufendeSchleife = window.requestAnimationFrame(bildAktualisieren);
    } else if (!an && laufendeSchleife !== undefined) {
      window.cancelAnimationFrame(laufendeSchleife);
      laufendeSchleife = undefined;
    }
  };

  // Das Video darf nie von selbst laufen, sonst kämen sich Abspielen und
  // Scrollen in die Quere.
  video.pause();

  new IntersectionObserver(
    ([eintrag]) => schleifeUmschalten(eintrag.isIntersecting),
    // Etwas früher anfangen, damit beim Hereinscrollen schon das richtige
    // Einzelbild steht.
    { rootMargin: '200px 0px' },
  ).observe(abschnitt);
}
