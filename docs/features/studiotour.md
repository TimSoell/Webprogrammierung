# Feature: Studiotour

**Status:** in Arbeit
**Verantwortlich:** offen
**Zuletzt geprueft:** 2026-09-21

## Was kann man damit

Ein zweites scrollgesteuertes Video, das einen Rundgang durch das Studio
zeigt. Es steht auf der **Locations-Seite unter der Karte**, zusammen mit
einem Info-Abschnitt aus vier Kennzahlen.

Der Gedanke dahinter: wer wissen will, wo ein Club ist, will meistens auch
wissen, wie es drinnen aussieht. Auf der Startseite stand der Rundgang
vorher zwischen Studio-Vorstellung und Programmen und hat den Erzaehlfaden
unterbrochen.

Technik und Bedienung sind identisch zum ersten Scroll-Video - es ist
dieselbe Komponente, nur mit einer anderen Datei und mehr Scrollweg.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `locations.php` (Abschnitte `.studio-info` und `.scroll-video--tour`) |
| 2 Seitenskript | `assets/js/pages/locations.page.js` |
| 2 Komponente | `assets/js/components/scroll-video.js` (unveraendert mitbenutzt) |
| CSS | `assets/css/components/scroll-video.css` (Variante `--tour`) |
| CSS | `assets/css/components/studio-info.css` |
| Mediendatei | `assets/img/studio-tour.mp4` |
| Navigation | `partials/header.php` (Menuepunkt "Locations & Studio") |

## Warum keine eigene Komponente

Das Verhalten ist zu 100 % dasselbe. `initScrollVideo()` nimmt einen
Selektor entgegen, deshalb reicht ein Aufruf im Seitenskript der
Locations-Seite:

```js
initScrollVideo('[data-studio-tour]');
```

Auch das CSS wird geteilt. Unterschiedlich ist nur die Abschnittshoehe, und
die steht in einer Variante nach eurer Konvention mit zwei Bindestrichen:
`.scroll-video--tour`.

Der Info-Abschnitt darueber benutzt `.section-head` aus `section-head.css`
und `.stats` aus `intro.css` mit. `studio-info.css` enthaelt nur die
Abweichungen: kein Abstand nach unten, damit das Video buendig anschliesst,
und vier statt drei Spalten fuer die Kennzahlen.

## Scrollweg berechnen

Faustregel: **rund 40vh Abschnittshoehe je Sekunde Video**, plus die 100vh,
die das klebende Kind selbst einnimmt.

| Video | Laenge | Abschnittshoehe |
|---|---|---|
| Hero-Video | 5 s | 320vh |
| Studiotour | 13 s | 620vh |

Wird die Datei ausgetauscht, muss dieser Wert in
`assets/css/components/scroll-video.css` mitwandern. Zu wenig Scrollweg
heisst: zu viel Videozeit pro Pixel, und es wirkt ruckelig.

## Anforderungen an die Videodatei

Dieselben wie beim ersten Scroll-Video. Siehe
[`scroll-video.md`](scroll-video.md), Abschnitt "Anforderungen an die
Videodatei" - inklusive ffmpeg-Befehl.

## Die aktuelle Datei

Das Rohmaterial aus Higgsfield war H.265 (`hvc1`), 1920×1080, mit nur
2 Keyframes bei 313 Bildern und 39,8 MB gross. Damit dauerte ein Sprung im
Video bis zu 112 ms, und beim Scrollen kamen nur 52 der 313 Bilder
ueberhaupt auf den Bildschirm. Umgewandelt mit:

```bash
ffmpeg -i hf_20260921_110742_c6c26f9d-b1f7-4c47-83aa-2ca04db5dbe7.mp4 -an -vf "scale=1280:-2,fps=24" -c:v libx264 -profile:v high -pix_fmt yuv420p -g 1 -crf 28 -movflags +faststart assets/img/studio-tour.mp4
```

Ergebnis: H.264 High, 1280×720, 24 fps, 313 Bilder, 13,04 s, jedes Bild
ein Keyframe, 5,2 MB. Ein Sprung dauert rund 3 ms.

Abweichend vom Befehl in `scroll-video.md`:

- `fps=24` statt 25, weil das Rohmaterial 24 fps hat. Mit 25 wuerde ffmpeg
  einzelne Bilder doppelt einfuegen.
- `-crf 28` statt 26, sonst waeren es 6,4 MB. Der Unterschied ist bei
  dieser Kamerafahrt mit viel Bewegungsunschaerfe nicht zu sehen.

**Keine Zwischenbilder berechnet.** 313 Bilder auf rund 520vh Scrollweg sind
bei einem 900 px hohen Fenster rund 15 px pro Bild - das reicht (Grenze
siehe `scroll-video.md`). Interpolation wuerde die Datei verdoppeln und bei
der schnellen Kamerafahrt Verzerrungen an Kanten riskieren.

## Was fehlt noch

- Auf verschiedenen Bildschirmgroessen im Browser pruefen.
- Pruefen, ob 620vh sich im Gebrauch zu lang anfuehlen. Falls ja: Video auf
  rund 8 Sekunden kuerzen (`-t 8` im ffmpeg-Befehl) statt den Abschnitt zu
  verkuerzen - und die Hoehe dann auf 420vh anpassen.
