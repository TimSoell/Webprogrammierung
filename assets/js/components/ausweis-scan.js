/**
 * @file        assets/js/components/ausweis-scan.js
 * @layer       2 – Komponente
 * @description Überbrückt die Wartezeit der KI-Prüfung eines Ausweisfotos:
 *              zeigt das gerade gewählte Bild, einen durchlaufenden
 *              Scanstrahl, eine Fortschrittsanzeige und zum Schluss einen
 *              Haken mit Konfetti.
 *
 *              DAS BILD BLEIBT IM BROWSER. Es wird nur als lokale
 *              Objekt-URL angezeigt und beim Ausblenden wieder freigegeben.
 *              Diese Komponente lädt nichts nach und speichert nichts - die
 *              Zusage aus docs/features/nachweise.md gilt unverändert.
 *
 *              DER PROZENTWERT IST EINE SCHÄTZUNG. Eine einzelne Anfrage an
 *              die Prüfung meldet keinen Zwischenstand, es gibt also nichts
 *              Echtes anzuzeigen. Der Wert läuft deshalb gebremst gegen 95 %
 *              und springt erst auf 100 %, wenn die Antwort wirklich da ist.
 *              Damit kann die Anzeige den Abschluss nie vortäuschen.
 *
 *              Das Aussehen steckt vollständig in der CSS-Datei. Die
 *              Konfetti-Schnipsel bekommen ihre Zufallswerte über
 *              CSS-Variablen gesetzt - in den PHP-Dateien steht kein style.
 * @see         mein-konto.php
 * @see         assets/js/pages/mein-konto.page.js
 * @see         assets/css/components/ausweis-scan.css
 */

/** Texte unter dem Ausweis. Wechseln sich ab, solange die Prüfung läuft. */
const TITEL = [
  'KI scannt deine Angaben',
  'Überprüfung auf Richtigkeit der Daten',
];

/** Wie lange ein Titel stehen bleibt, bevor der nächste kommt (ms). */
const TITEL_DAUER = 2600;

/** Wie oft die Fortschrittsanzeige neu gerechnet wird (ms). */
const TAKT = 60;

/** Bremst die Schätzung. Größer = der Wert wächst langsamer (ms). */
const TEMPO = 2600;

/** Weiter läuft die Schätzung nicht, solange keine Antwort da ist (Prozent). */
const MAX_SCHAETZUNG = 95;

/** So lange bleiben Haken und Konfetti stehen, bevor alles verschwindet (ms). */
const ERFOLG_DAUER = 1900;

/** Anzahl der Konfetti-Schnipsel. */
const KONFETTI_ANZAHL = 70;

/**
 * @typedef {object} Scansteuerung
 * @property {() => Promise<void>} abschliessen  Haken und Konfetti zeigen,
 *                                               danach ausblenden
 * @property {() => void} abbrechen              Sofort ausblenden (Fehlerfall)
 */

/**
 * Blendet die Scan-Animation ein und beginnt zu zählen.
 *
 * Der Aufruf zeigt die Animation sofort. Wie sie endet, entscheidet die
 * aufrufende Stelle: abschliessen() bei Erfolg, abbrechen() im Fehlerfall.
 *
 * @param {File} datei  Das Foto, das gerade hochgeladen wird
 * @returns {Scansteuerung}
 */
export function scanStarten(datei) {
  const wurzel = document.querySelector('[data-ausweis-scan]');

  if (!wurzel) {
    return { abschliessen: async () => {}, abbrechen: () => {} };
  }

  const bild = wurzel.querySelector('[data-ausweis-scan-bild]');
  const titel = wurzel.querySelector('[data-ausweis-scan-titel]');
  const wert = wurzel.querySelector('[data-ausweis-scan-wert]');
  const konfetti = wurzel.querySelector('[data-ausweis-scan-konfetti]');

  const bildUrl = URL.createObjectURL(datei);
  const start = performance.now();
  const sparsam = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  let titelUhr = 0;
  let fortschrittUhr = 0;

  /**
   * Schreibt den Fortschritt in die Anzeige und in die CSS-Variable,
   * aus der der Balken seine Breite bezieht.
   *
   * @param {number} anteil  0 bis 100
   * @returns {void}
   */
  const fortschrittSetzen = (anteil) => {
    const gerundet = Math.round(anteil);

    wert.textContent = `${gerundet} %`;
    wurzel.style.setProperty('--ausweis-scan-fortschritt', `${gerundet}%`);
  };

  /** Räumt alles ab: Animation aus, Objekt-URL freigeben, Overlay verstecken. */
  const aufraeumen = () => {
    window.clearInterval(titelUhr);
    window.clearInterval(fortschrittUhr);
    window.setTimeout(() => URL.revokeObjectURL(bildUrl), 0);

    wurzel.hidden = true;
    wurzel.classList.remove('is-fertig');
    konfetti.replaceChildren();
    document.body.classList.remove('hat-ausweis-scan');
  };

  /**
   * Rechnet die Schätzung neu aus und zeigt sie an.
   *
   * Bewusst setInterval statt requestAnimationFrame: In einem Tab im
   * Hintergrund laufen keine Animationsbilder, die Anzeige bliebe stehen.
   * Der Wert kommt ohnehin aus der verstrichenen Zeit und nicht aus der
   * Zahl der Schritte - ein gedrosselter Takt macht ihn also nur grober,
   * nie falsch.
   */
  const schritt = () => {
    const verstrichen = performance.now() - start;

    // Gebremstes Wachstum: schnell am Anfang, immer langsamer zum Ende.
    // Der Wert nähert sich MAX_SCHAETZUNG an, erreicht es aber nie.
    fortschrittSetzen(MAX_SCHAETZUNG * (1 - Math.exp(-verstrichen / TEMPO)));
  };

  bild.src = bildUrl;
  titel.textContent = TITEL[0];
  fortschrittSetzen(0);

  wurzel.classList.remove('is-fertig');
  wurzel.hidden = false;
  document.body.classList.add('hat-ausweis-scan');

  fortschrittUhr = window.setInterval(schritt, TAKT);

  let naechster = 1;

  titelUhr = window.setInterval(() => {
    titel.textContent = TITEL[naechster % TITEL.length];
    naechster += 1;
  }, TITEL_DAUER);

  return {
    async abschliessen() {
      window.clearInterval(titelUhr);
      window.clearInterval(fortschrittUhr);

      fortschrittSetzen(100);
      titel.textContent = 'Geprüft und bestätigt';
      wurzel.classList.add('is-fertig');

      if (!sparsam) {
        konfettiStreuen(konfetti);
      }

      await warten(sparsam ? 700 : ERFOLG_DAUER);
      aufraeumen();
    },

    abbrechen: aufraeumen,
  };
}

/**
 * Füllt den Konfetti-Behälter mit Schnipseln.
 *
 * Jeder Schnipsel bekommt Richtung, Drehung, Farbe und Verzögerung als
 * CSS-Variable. Die Bewegung selbst steht in der CSS-Datei.
 *
 * @param {Element} behaelter  Das leere <div> für die Schnipsel
 * @returns {void}
 */
function konfettiStreuen(behaelter) {
  const farben = ['var(--lime)', 'var(--lime-hover)', 'var(--paper)', 'var(--lime-dark)'];

  behaelter.replaceChildren(...Array.from({ length: KONFETTI_ANZAHL }, () => {
    const schnipsel = document.createElement('span');

    schnipsel.className = 'ausweis-scan__schnipsel';
    schnipsel.style.setProperty('--weite', `${(Math.random() - 0.5) * 90}vw`);
    schnipsel.style.setProperty('--hoehe', `${40 + Math.random() * 45}vh`);
    schnipsel.style.setProperty('--drehung', `${(Math.random() - 0.5) * 1080}deg`);
    schnipsel.style.setProperty('--verzug', `${Math.random() * 260}ms`);
    schnipsel.style.setProperty('--dauer', `${1100 + Math.random() * 700}ms`);
    schnipsel.style.setProperty('--farbe', farben[Math.floor(Math.random() * farben.length)]);
    schnipsel.style.setProperty('--breite', `${6 + Math.random() * 6}px`);

    return schnipsel;
  }));
}

/**
 * Wartet eine bestimmte Zeit.
 *
 * @param {number} dauer  Millisekunden
 * @returns {Promise<void>}
 */
function warten(dauer) {
  return new Promise((aufloesen) => window.setTimeout(aufloesen, dauer));
}
