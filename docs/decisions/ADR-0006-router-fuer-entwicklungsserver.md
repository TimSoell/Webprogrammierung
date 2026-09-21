# ADR-0006 — Router-Skript für den PHP-Entwicklungsserver

**Status:** angenommen
**Datum:** 2026-09-21

## Kontext

Das Scroll-Video auf der Startseite stand unter `./start.sh` still: Man
scrollte, aber das Bild änderte sich nicht. Unter Apache lief es.

Die Ursache liegt im eingebauten PHP-Server aus
[`ADR-0004`](ADR-0004-php-entwicklungsserver.md). Er beantwortet keine
Range-Anfragen, also Anfragen nach einem Teilstück einer Datei
(`Range: bytes=1000-`). Er antwortet immer mit `200` und der ganzen Datei,
ohne `Accept-Ranges`.

Chrome schließt daraus, dass im Video nicht gesprungen werden kann:
`video.seekable` ist `[0, 0]`, obwohl die Datei komplett geladen ist. Jedes
Setzen von `currentTime` landet wieder bei 0. Das Scroll-Video besteht aber
aus nichts anderem als dem Setzen von `currentTime`.

Apache beherrscht Range-Anfragen. Der Fehler trat also nur beim Entwickeln
auf, genau die Art Unterschied zwischen zwei Umgebungen, vor der ADR-0004
warnt.

## Entscheidung

Der Entwicklungsserver startet mit einem Router-Skript `router.php` im
Projektordner:

```bash
php -S localhost:8000 -t . router.php
```

Der Router beantwortet Range-Anfragen auf `.mp4`-Dateien selbst mit
`206 Partial Content`. Alle anderen Anfragen gibt er mit `return false`
unverändert an den eingebauten Server zurück.

- `start.sh`, `start.bat` und `.claude/launch.json` starten den Server mit
  dem Router.
- Unter Apache wird `router.php` nicht benutzt. Wird die Datei dort direkt
  aufgerufen, antwortet sie mit `404`.
- ADR-0004 gilt weiter. Dieses ADR ergänzt es nur.

## Konsequenzen

**Positiv**

- Videos lassen sich unter `./start.sh` genauso anspringen wie unter
  Apache. Das Scroll-Video läuft in beiden Umgebungen.
- Am Quelltext der Seiten ändert sich nichts.

**Negativ**

- Eine Datei mehr im Projektordner, die keine Seite ist.
- Wer den Server von Hand mit `php -S localhost:8000 -t .` startet, hat den
  Fehler wieder. Deshalb: immer über das Startskript starten.
- Der Router liest das angefragte Teilstück komplett in den Speicher. Für
  Videos von wenigen MB ist das egal, für große Dateien wäre es das nicht.

## Verworfene Alternativen

**Video per `fetch()` als Blob laden und über `URL.createObjectURL()`
einbinden.**
Blob-URLs sind immer anspringbar, der Server wäre egal. Aber das verstößt
gegen die Regel „kein `fetch()` außerhalb von `assets/js/services/`“ und
baut Produktionscode um, nur um eine Schwäche des Entwicklungsservers zu
umgehen.

**Scroll-Video nur unter Apache testen.**
Dann läuft der normale Entwicklungsweg aus ADR-0004 für dieses Feature
nicht, und das nächste Teammitglied sucht denselben Fehler noch einmal.
