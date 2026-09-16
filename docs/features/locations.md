# Feature: Standortübersicht

**Status:** fertig  
**Verantwortlich:** Team  
**Zuletzt geprüft:** 2026-09-16

## Was kann man damit

Besucherinnen und Besucher sehen die vier SCHWITZKASTEN Standorte Köln,
München, Berlin und Stuttgart auf einer interaktiven Karte. Jeder Marker zeigt
den Stadtnamen und die vollständige Adresse. Ein Klick auf einen Eintrag in
der Standortliste zoomt die Karte animiert auf den ausgewählten Standort.
Die Kartenansicht bleibt auf Deutschland begrenzt; ein Reset-Button führt
jederzeit animiert zur Deutschlandübersicht zurück.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `locations.php` |
| 2 Seitenskript | `assets/js/pages/locations.page.js` |
| CSS | `assets/css/components/locations.css` |
| Einbindung | `partials/head.php`, `partials/header.php` |

## Datenform

```json
{
  "city": "Köln",
  "address": "Venloer Straße 213, 50823 Köln",
  "coordinates": [50.9472, 6.9242]
}
```

## Externe Abhängigkeiten

Leaflet 1.9.4 wird auf der Standortseite über CDN geladen. Die Karte nutzt
kostenlose OpenStreetMap-Kacheln unter
`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`. Es wird kein API-Schlüssel
benötigt.

## Woher kommen die Daten aktuell

Die vier Standorte sind statische Daten im Seitenskript
`assets/js/pages/locations.page.js`. Eine Datenbank ist für dieses Feature
nicht erforderlich.

## Bedienung

Die Karte startet mit einem Deutschlandausschnitt und kann nicht aus diesem
Bereich heraus verschoben werden. Die Zoomsteuerung sitzt unten rechts. Der
`DE`-Button oben links stellt die Deutschlandübersicht wieder her; ein
Standortlisteneintrag zoomt mit `flyTo()` auf den jeweiligen Club.

## Was fehlt noch

- Die aktuelle Projektstruktur verwendet `locations.php` als Seitenpfad. Eine
  echte URL `/locations` benötigt später eine Rewrite-Regel oder einen Router.
