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

## Anforderungen an den Server

Der Server muss **Range-Anfragen** beantworten, also Teilstuecke der Datei
mit `206 Partial Content` ausliefern. Sonst laesst Chrome kein Springen im
Video zu (`video.seekable` ist `[0, 0]`), jedes Setzen von `currentTime`
landet wieder bei 0, und das Bild bleibt beim Scrollen stehen.

Apache kann das von selbst. Der eingebaute PHP-Server kann es nicht, deshalb
startet `./start.sh` ihn mit `router.php`. Wer den Server von Hand ohne
Router startet, hat genau dieses Standbild. Siehe
[`ADR-0006`](../decisions/ADR-0006-router-fuer-entwicklungsserver.md).

Schnelltest, ob der Server passt (muss `206` liefern):

```bash
curl -s -o /dev/null -w "%{http_code}\n" -H "Range: bytes=0-99" http://localhost:8000/assets/img/scroll-video.mp4
```

## Anforderungen an die Videodatei

**Das ist der wichtigste Abschnitt dieser Doku.** Ob die Animation fluessig
laeuft, entscheidet fast ausschliesslich die Kodierung der Datei — nicht das
JavaScript.

| Punkt | Wert | Warum |
|---|---|---|
| Codec | H.264 (`avc1`) | H.265/HEVC spielt Firefox gar nicht ab |
| Keyframes | **jedes Bild** (`-g 1`) | Sonst rechnet der Decoder bei jedem Sprung vom letzten Keyframe an neu — die Hauptursache fuer Ruckeln |
| `faststart` | ja | Sonst stehen Dauer und Metadaten erst nach dem vollstaendigen Download fest |
| Laenge | hoechstens rund 6 Sekunden | Laenger heisst bei „jedes Bild ein Keyframe" sehr grosse Dateien |
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

Die aktuelle Datei ist mit denselben Einstellungen entstanden, aber ohne
ffmpeg: ueber AVFoundation, die Videobibliothek von macOS. Sie enthaelt nur
das letzte Viertel des Originals (Commit `214e1e9`), also Bild 91 bis 120
bzw. 3,79 s bis 5,04 s, in normaler Geschwindigkeit. Ergebnis: H.264 High,
1280×720, 24 fps, 30 Bilder, 1,25 s, jedes Bild ein Keyframe, ohne Tonspur,
Metadaten am Dateianfang, 0,49 MB. Ein Sprung dauert damit an jeder Stelle
des Videos rund 3 ms, mit dem Original waren es je nach Position bis zu
44 ms.

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

- Getestet ist bisher nur Chromium (die Technik hinter Chrome) unter
  macOS. Firefox, Safari und Edge sowie Windows muessen noch geprueft
  werden.
- Auf verschiedenen Bildschirmgroessen im Browser pruefen.
