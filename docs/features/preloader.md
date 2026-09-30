# Feature: Animierter Preloader

**Status:** fertig
**Verantwortlich:** offen
**Zuletzt geprüft:** 2026-09-30

## Was kann man damit

Beim ersten Aufruf der Startseite in einer Browser-Sitzung zeigt die Website
kurz den animierten SCHWITZKASTEN-Preloader. Sobald alle Ressourcen geladen
sind, wird der Ladebildschirm weich ausgeblendet und aus dem Dokument
entfernt. Auf allen anderen Seiten und bei jedem weiteren Aufruf der
Startseite in derselben Sitzung erscheint er nicht.

Steuerung: `index.php` setzt `$pageHasPreloader = true`, erst dann gibt
`partials/head.php` das Element aus. Ob er schon lief, merkt sich
`preloader.js` im `sessionStorage`.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite (Baustein) | `partials/head.php` |
| 2 Seitenskript | `assets/js/main.js` |
| 2 Komponente | `assets/js/components/preloader.js` |
| CSS | `assets/css/components/preloader.css` |

## Datenform

Keine Daten.

## Endpunkte

Keine.

## Woher kommen die Daten aktuell

Der Preloader besteht aus HTML, CSS-Animationen und dem vorhandenen `SK`-
Monogramm als Text.

## Was fehlt noch

nichts