# ADR-0004 — PHP-Entwicklungsserver als Standard-Startweg

**Status:** angenommen – die Regeln zu Abgabe über Apache sowie MySQL und
phpMyAdmin aus XAMPP sind ersetzt durch [ADR-0016](ADR-0016-hosting-auf-vercel.md)
und [ADR-0017](ADR-0017-postgresql-auf-supabase.md)
**Datum:** 2026-09-10

## Kontext

Bisher musste das Projekt in `xampp/htdocs/` liegen, damit Apache es
ausliefert. Das Repository liegt bei den meisten im Team aber dort, wo auch
die übrigen Studienunterlagen liegen — nicht im XAMPP-Ordner.

Daraus folgten zwei unschöne Gewohnheiten: entweder nach jedem `git pull` in
den XAMPP-Ordner kopieren, oder das Repository direkt in `htdocs` klonen und
damit den Quelltext an einem Ort führen, an dem ihn niemand sucht. Beides
kostet bei jeder Änderung Zeit, und beim Kopieren arbeitet man regelmäßig
versehentlich in der falschen Kopie.

Gesucht war ein Weg, das Projekt **direkt aus dem Git-Ordner** im Browser zu
öffnen — ohne Kopie, ohne dass jemand an der Apache-Konfiguration schrauben
muss, und ohne dass sich am Quelltext etwas ändert.

## Entscheidung

Zum Entwickeln wird der in PHP eingebaute Webserver benutzt, gestartet über
ein Skript im Projektordner:

- `./start.sh` unter macOS und Linux
- `start.bat` unter Windows

Beide starten `php -S localhost:8000` mit dem Projektordner als
Dokumentwurzel. Die Seite läuft dann unter `http://localhost:8000/`.

Daraus ergeben sich diese Regeln:

- **Zum Entwickeln ist das Startskript der normale Weg.** Der Umweg über
  `xampp/htdocs/` bleibt gültig und dokumentiert, ist aber nicht mehr die
  empfohlene Variante.
- **Für die Abgabe und die Vorführung gilt weiterhin Apache.** Am Quelltext
  ändert das nichts: `BASE_URL` wird berechnet und stimmt in beiden Fällen.
- **MySQL und phpMyAdmin kommen unverändert aus XAMPP.** Wer phpMyAdmin
  benutzt, startet dafür zusätzlich Apache.
- Die Skripte gehören ins Repository, damit alle fünf denselben Startbefehl
  haben.

## Konsequenzen

**Positiv**

- Kein Kopieren mehr nach `xampp/htdocs/`. Was im Editor steht, ist das, was
  im Browser erscheint.
- Das Repository darf liegen, wo es soll.
- Ein einziger Befehl für alle, unabhängig davon, wo XAMPP installiert ist —
  die Skripte finden PHP selbst.
- Fehlermeldungen von PHP erscheinen direkt im Terminal, statt in einer
  Logdatei tief im XAMPP-Ordner.

**Negativ**

- **Zwei Umgebungen statt einer.** Entwickelt wird auf dem PHP-Server,
  vorgeführt wird auf Apache. Ein Unterschied zwischen beiden fällt damit
  erst spät auf. Deshalb: vor der Abgabe einmal unter Apache prüfen.
- Der eingebaute Server kennt **kein `.htaccess` und kein `mod_rewrite`**.
  Sollte das Projekt später URL-Rewriting brauchen, ist diese Entscheidung
  neu zu bewerten.
- Er beantwortet Anfragen nacheinander. Für die Entwicklung reicht das, für
  Lasttests taugt er nicht.
- Port 8000 muss frei sein.
- Wer phpMyAdmin will, startet doch wieder Apache — der XAMPP-Manager wird
  also nicht überflüssig.

## Verworfene Alternativen

**Symlink von `xampp/htdocs/` in den Projektordner.**
Der naheliegende Weg, aber er scheitert doppelt. Erstens läuft Apache als
Benutzer `daemon` und kommt nicht in einen privaten Benutzerordner hinein.
Zweitens — und das wiegt schwerer — löst PHP in `__DIR__` Symlinks auf.
`ROOT_PATH` zeigte dann in den echten Projektordner, `DOCUMENT_ROOT` weiter
nach `htdocs`, und die Berechnung von `BASE_URL` in `src/bootstrap.php` ergäbe
Unsinn. Sämtliche Links der Seite wären kaputt.

**Eigener VirtualHost in Apache, der auf den Projektordner zeigt.**
Technisch sauber und `BASE_URL` bliebe korrekt, weil `DOCUMENT_ROOT` dann dem
Projektordner entspricht. Aber jede Person im Team müsste dafür an
`httpd.conf` und an den Dateirechten des eigenen Benutzerordners drehen. Bei
fünf Personen mit unterschiedlichen Betriebssystemen ist das mehr
Fehlerquelle als Gewinn — für eine reine Entwicklungsbequemlichkeit zu teuer.

**Weiter kopieren wie bisher.**
Kostet bei jeder Änderung Zeit und führt verlässlich dazu, dass jemand eine
halbe Stunde in der falschen Kopie sucht.
