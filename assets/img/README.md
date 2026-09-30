# assets/img/ — Bilder

Die Hintergrundbilder kommen derzeit von Unsplash und sind direkt in CSS und
HTML verlinkt. Eigene Dateien liegen bisher nur hier:

- `schwitzkasten-logo.png` — das Logo in der Kopfzeile
- `favicon.png` — das Logo quadratisch, 192 × 192 px, für den Browser-Tab
- `apple-touch-icon.png` — das Logo auf Weiß, 180 × 180 px, für den
  Homescreen von iPhone und iPad
- `coaches/` — Porträtfotos für die Programmseiten, siehe die
  [README dort](coaches/README.md)

## Wenn eigene Bilder dazukommen

- Dateinamen kleingeschrieben, mit Bindestrich: `studio-halle-01.jpg`
- Vor dem Einchecken verkleinern. Faustregel: maximal 2000 px Breite,
  unter 300 KB. Ein 5-MB-Foto aus der Kamera macht das Repository
  unnötig groß und die Seite langsam.
- Format: `.jpg` für Fotos, `.svg` für Logos und Symbole, `.webp` wenn
  Dateigröße wichtiger ist als Kompatibilität mit sehr alten Browsern.
- **Jedes `<img>` braucht ein `alt`.** Beschreibt, was zu sehen ist. Bei rein
  dekorativen Bildern `alt=""` — leer, aber vorhanden.

## Verlinken

Immer über `BASE_URL`, sonst brechen die Pfade auf Unterseiten:

```php
<img src="<?= e(BASE_URL) ?>assets/img/studio-halle-01.jpg" alt="Trainingsfläche">
```

In CSS ist der Pfad relativ zur CSS-Datei:

```css
background-image: url("../img/studio-halle-01.jpg");
```
