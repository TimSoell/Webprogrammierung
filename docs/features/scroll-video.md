# Feature: Scroll-Video

**Status:** in Arbeit
**Verantwortlich:** offen
**Zuletzt geprueft:** 2026-09-21

## Was kann man damit

Beim Scrollen durch den Abschnitt wird das Video passend zur Scrollposition
vorwaerts und rueckwaerts abgespielt. Das Video bleibt dabei im sichtbaren
Bereich stehen und wird nach dem Abschnitt wieder normal verlassen.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `index.php` |
| 2 Seitenskript | `assets/js/pages/index.page.js` |
| 2 Komponente | `assets/js/components/scroll-video.js` |
| CSS | `assets/css/components/scroll-video.css` |
| Mediendatei | `assets/img/scroll-video.mp4` |

## Datenform

Keine Daten. Die Animation verwendet die lokale MP4-Datei und deren Dauer.

## Endpunkte

Keine.

## Woher kommen die Daten aktuell

Aus der lokalen Videodatei `assets/img/scroll-video.mp4`.

## Anforderungen an die Videodatei

**Das ist der wichtigste Abschnitt dieser Doku.** Ob die Animation fluessig
laeuft, entscheidet fast ausschliesslich die Kodierung der Datei — nicht das
JavaScript.

| Punkt | Wert | Warum |
|---|---|---|
| Codec | H.264 (`avc1`) | H.265/HEVC spielt Firefox gar nicht ab |
| Keyframes | **jedes Bild** (`-g 1`) | Sonst rechnet der Decoder bei jedem Sprung vom letzten Keyframe an neu — die Hauptursache fuer Ruckeln |
| `faststart` | ja | Sonst stehen Dauer und Metadaten erst nach dem vollstaendigen Download fest |
| Laenge | 4–6 Sekunden | Laenger heisst bei „jedes Bild ein Keyframe" sehr grosse Dateien |
| Groesse | moeglichst unter 3 MB | Die Datei muss vor dem Scrollen komplett geladen sein |
| Bildrate | 24–25 fps | Mehr Bilder bringen beim Scrubben nichts, kosten aber Dateigroesse |

Ein Video mit nur einem Keyframe sieht beim normalen Abspielen voellig
normal aus. Der Unterschied faellt erst beim Scrubben auf. Deshalb steht das
hier: es ist nicht zu sehen, wenn man die Datei nur kurz abspielt.

### Datei umwandeln

Mit [ffmpeg](https://ffmpeg.org/download.html), aus dem Projektordner:

```bash
ffmpeg -i original.mp4 -an -vf "scale=1280:-2,fps=25" -c:v libx264 -profile:v high -pix_fmt yuv420p -g 1 -crf 26 -movflags +faststart assets/img/scroll-video.mp4
```

| Teil | Bedeutung |
|---|---|
| `-an` | Tonspur weg, sie wird nie abgespielt |
| `-g 1` | jedes Bild wird ein Keyframe |
| `-crf 26` | Qualitaet; kleiner = besser und groesser. 23–28 ausprobieren |
| `scale=1280:-2` | Breite 1280, Hoehe passend. Fuer eine Hintergrundflaeche reicht das |
| `+faststart` | Metadaten an den Dateianfang |

Danach pruefen, ob die Datei klein genug geworden ist:

```bash
ffprobe -v error -show_entries format=size,duration -show_entries stream=codec_name,nb_frames -of default=noprint_wrappers=1 assets/img/scroll-video.mp4
```

## Wie das Nachziehen funktioniert

Das Video wird nie abgespielt. Die Komponente setzt bei jedem Bild
`video.currentTime` neu. Drei Dinge sorgen dafuer, dass das weich wirkt:

- **Glaettung.** Ein Mausrad liefert grobe Spruenge. Statt direkt auf die
  Zielzeit zu springen, legt das Video pro Bild nur einen Teil der
  Reststrecke zurueck (`GLAETTUNG` in `scroll-video.js`).
- **Scrollweg.** Die Hoehe des Abschnitts im CSS legt fest, wie viel
  Videozeit auf einem Pixel Scrollweg liegt. Zu kurz = ruckelig.
- **Ein Sprung nach dem anderen.** Waehrend `video.seeking` laeuft, wird
  kein neuer Sprung angefordert. Sonst verwerfen sich die Spruenge
  gegenseitig.

## Was fehlt noch

- Die Videodatei ist aktuell H.265 mit **einem einzigen Keyframe** und
  6,7 MB gross. Sie muss nach der Anleitung oben neu kodiert werden — bis
  dahin ruckelt es unabhaengig vom Code, und in Firefox laeuft gar nichts.
- Auf verschiedenen Bildschirmgroessen im Browser pruefen.
