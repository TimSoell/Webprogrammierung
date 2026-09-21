# Feature: Studiotour

**Status:** in Arbeit
**Verantwortlich:** offen
**Zuletzt geprueft:** 2026-09-21

## Was kann man damit

Ein zweites scrollgesteuertes Video, das einen Rundgang durch das Studio
zeigt. Es liegt zwischen der Studio-Vorstellung und den Programmen. Technik
und Bedienung sind identisch zum ersten Scroll-Video - es ist dieselbe
Komponente, nur mit einer anderen Datei und mehr Scrollweg.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `index.php` (Abschnitt `.scroll-video--tour`) |
| 2 Seitenskript | `assets/js/pages/index.page.js` |
| 2 Komponente | `assets/js/components/scroll-video.js` (unveraendert mitbenutzt) |
| CSS | `assets/css/components/scroll-video.css` (Variante `--tour`) |
| Mediendatei | `assets/img/studio-tour.mp4` |

## Warum keine eigene Komponente

Das Verhalten ist zu 100 % dasselbe. `initScrollVideo()` nimmt einen
Selektor entgegen, deshalb reicht ein zweiter Aufruf:

```js
initScrollVideo('[data-scroll-video]');
initScrollVideo('[data-studio-tour]');
```

Auch das CSS wird geteilt. Unterschiedlich ist nur die Abschnittshoehe, und
die steht in einer Variante nach eurer Konvention mit zwei Bindestrichen:
`.scroll-video--tour`.

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

## Was fehlt noch

**Die Videodatei.** Das Rohmaterial aus Higgsfield ist H.265 (`hvc1`) mit
nur 2 Keyframes bei 313 Bildern und 39,8 MB gross - damit funktioniert
weder das Springen noch die Ladezeit. Es muss einmal durch ffmpeg, genau
wie `scroll-video.mp4` vorher auch:

```bash
ffmpeg -i hf_20260921_110742_c6c26f9d-b1f7-4c47-83aa-2ca04db5dbe7.mp4 -an -vf "scale=1280:-2,fps=25" -c:v libx264 -profile:v high -pix_fmt yuv420p -g 1 -crf 26 -movflags +faststart assets/img/studio-tour.mp4
```

Danach pruefen - es darf **keine** `stss`-Zeile erscheinen, denn die
bedeutet, dass nur ein Teil der Bilder Keyframes sind:

```bash
ffprobe -v error -show_entries format=size,duration -show_entries stream=codec_name,nb_frames -of default=noprint_wrappers=1 assets/img/studio-tour.mp4
```

Erwartet: `avc1`, rund 320 Bilder, moeglichst unter 5 MB.

- Auf verschiedenen Bildschirmgroessen im Browser pruefen.
- Pruefen, ob 620vh sich im Gebrauch zu lang anfuehlen. Falls ja: Video auf
  rund 8 Sekunden kuerzen (`-t 8` im ffmpeg-Befehl) statt den Abschnitt zu
  verkuerzen - und die Hoehe dann auf 420vh anpassen.
