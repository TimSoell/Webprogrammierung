# assets/img/ — Bilder

Aktuell leer. Alle Bilder kommen derzeit von Unsplash und sind direkt in
CSS und HTML verlinkt.

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
