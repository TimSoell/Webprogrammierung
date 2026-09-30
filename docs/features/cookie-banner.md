# Feature: Cookie-Banner

**Status:** in Arbeit
**Verantwortlich:** Giani
**Zuletzt geprüft:** 2026-09-30

## Was kann man damit

Beim ersten Besuch erscheint am unteren Rand eine Leiste, die fragt, ob
Google Analytics verwendet werden darf. Man kann „Alle akzeptieren“ oder
„Nur notwendige“ wählen. Erst nach „Alle akzeptieren“ wird Google Analytics
geladen, vorher geht keine Anfrage an Google. Über „Cookie-Einstellungen“ im
Footer lässt sich die Leiste jederzeit wieder öffnen und die Entscheidung
ändern.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `partials/cookie-banner.php` (eingebunden in `partials/footer.php`) |
| 2 Komponente | `assets/js/components/cookie-banner.js` (gestartet in `assets/js/main.js`) |
| CSS | `assets/css/components/cookie-banner.css` |
| Text | Abschnitt „Google Analytics“ in `datenschutz.php` |

Schichten 3–6 gibt es nicht: Die Einwilligung wird nur im Browser gespeichert,
der Server erfährt nichts davon.

## Datenform

Ein Eintrag im `localStorage`:

| Schlüssel | Wert |
|---|---|
| `schwitzkasten:cookie-einwilligung` | `zugestimmt` oder `abgelehnt` |

Fehlt der Eintrag, erscheint die Leiste.

## Ablauf

- **Zustimmen:** Entscheidung speichern, `gtag.js` nachladen. Bei jedem
  weiteren Seitenaufruf wird es sofort geladen.
- **Ablehnen:** Entscheidung speichern, nichts laden.
- **Widerrufen** (erst zugestimmt, dann über den Footer „Nur notwendige“):
  `_ga`-Cookies löschen und Seite neu laden, damit das geladene `gtag.js`
  verschwindet.

Die Mess-ID `G-723B66WSY1` steht als Konstante oben in `cookie-banner.js`.
Früher stand das GA-Snippet direkt in `partials/head.php`, lud ohne
Einwilligung und war ein `<script>`-Block im PHP (Regel 4). Beides ist damit
behoben.

## Woher kommen die Daten aktuell

Nur aus dem `localStorage` des Browsers.

## Was fehlt noch

- Nichts Bekanntes.
