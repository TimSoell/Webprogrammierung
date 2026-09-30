# ADR-0016 — Hosting auf Vercel mit der Laufzeit vercel-php

**Status:** angenommen
**Datum:** 2026-09-30

## Kontext

Bisher lief die Website nur auf den Rechnern im Team: entwickelt über den
PHP-Server aus XAMPP, vorgeführt über Apache aus `xampp/htdocs/`
([ADR-0004](ADR-0004-php-entwicklungsserver.md)). Wer die Seite sehen wollte,
brauchte eine eingerichtete XAMPP-Installation samt Datenbank.

Die Seite soll jetzt öffentlich erreichbar sein, ohne dass jemand einen
Rechner laufen lässt. Der Dozent hat zugestimmt, dass die Abgabe gehostet
statt über XAMPP erfolgt.

Randbedingungen:

- PHP ohne Framework und ohne Build-Schritt, sechs Schichten
  ([ADR-0002](ADR-0002-schichtenarchitektur.md)) – daran soll sich nichts
  ändern.
- Kein Budget: Vercel im kostenlosen Hobby-Plan.
- Das GitHub-Repository ist öffentlich und gehört Tim, das Vercel-Konto
  gehört Jonathan.

## Entscheidung

Die Website läuft auf **Vercel**. PHP führt die Community-Laufzeit
**`vercel-php`** aus, fest auf eine Version gepinnt (`vercel.json`).

- **Eine einzige Funktion.** Vercel führt PHP nur unter `api/` aus. Statt
  Seiten zu verschieben, leitet `vercel.json` jede Anfrage außer `/assets/`
  an `api/index.php`. Die Datei bindet die passende Seite oder den passenden
  Endpunkt ein – nur von einer Positivliste, alles andere ist 404.
  `api/index.php` ist Infrastruktur, kein Endpunkt.
- **`/assets/` liefert Vercel direkt aus**, ohne PHP. Videos mit
  Range-Anfragen funktionieren dort von selbst; `router.php` bleibt nur für
  den lokalen Server.
- **Region Frankfurt (`fra1`)**, dieselbe wie die Datenbank.
- **Konfiguration aus Umgebungsvariablen.** Auf Vercel gibt es keine
  `config/config.php`; `src/bootstrap.php` lädt dort
  `config/config.umgebung.php`, die dieselben Werte aus den Variablen des
  Vercel-Projekts liest. Geheimnisse trägt nur ein Mensch im Dashboard ein.
- **Deployment über GitHub Actions** (`.github/workflows/vercel.yml`) mit dem
  Token aus Jonathans Vercel-Konto: Push auf `main` geht in Produktion, jeder
  Pull Request bekommt eine Preview.
- **Lokal bleibt alles, wie es war:** `start.sh` / `start.bat` mit dem
  PHP-Server. Nur die Datenbank ist jetzt entfernt, siehe
  [ADR-0017](ADR-0017-postgresql-auf-supabase.md).
- **Die Abgabe über Apache aus `htdocs` entfällt.** Maßgeblich ist die
  Fassung auf Vercel.

## Konsequenzen

**Positiv**

- Die Seite ist ohne jede Einrichtung für alle erreichbar, auch für den
  Dozenten.
- Jeder Pull Request lässt sich vor dem Mergen als Preview ansehen.
- Seiten, Endpunkte, JavaScript und CSS bleiben unverändert; die Schichten
  gelten weiter.
- Mit einer Funktion statt fünfzehn bleibt sie öfter „warm", und es gibt
  keinen Weg, an Quelltext aus `src/`, `vendor/` oder `config/` zu kommen.

**Negativ**

- **`vercel-php` ist kein offizielles Produkt von Vercel.** Fällt die
  Laufzeit weg oder bricht ein Update, steht die Seite. Der Versions-Pin
  schützt vor Überraschungen, nicht vor dem Ende des Projekts.
- **Zwei Umgebungen:** lokal PHP 8.2 aus XAMPP, auf Vercel die PHP-Version
  der Laufzeit. Unterschiede fallen erst in der Preview auf.
- Kaltstarts: Der erste Aufruf nach einer Pause dauert spürbar länger.
- Laufzeit-Logs hält Vercel im Hobby-Plan nur eine Stunde.
- Eine Anfrage darf höchstens 4,5 MB groß sein – deshalb nimmt
  `api/nachweise.php` nur noch Bilder bis 3 MB an.
- Node.js läuft jetzt doch – aber nur in GitHub Actions, nicht im Projekt.
- Die Seite eines erfundenen Studios ist öffentlich. Deshalb der Header
  `X-Robots-Tag: noindex` und kein Reset-Link auf der Seite in Produktion.

## Verworfene Alternativen

**Vercels eingebaute Git-Anbindung.**
Die einfachste Lösung, aber im Hobby-Plan kann nur der Eigentümer eines
Repositories es verbinden, und das Vercel-Konto gehört jemand anderem.
Umziehen des Repositories oder Vercel Pro (20 $ pro Person und Monat) wären
die Alternativen gewesen.

**Jede Seite als eigene Funktion unter `api/`.**
Hätte die Ordnerstruktur umgekrempelt und `ROOT_PATH`/`BASE_URL` in jeder
Seite berührt. Außerdem wären alle übrigen Dateien des Projekts als
statische Dateien erreichbar geblieben – samt PHP-Quelltext.

**Seiten und Endpunkte in JavaScript oder einem Framework neu schreiben.**
Vercel wäre damit in seinem Element, aber die Aufgabenstellung sieht PHP vor,
und es wäre eine Neuentwicklung statt eines Umzugs.

**Klassischer PHP-Webspace oder ein eigener Server.**
Kostet Geld oder Pflege (Updates, Zertifikate), und Previews pro Pull Request
gäbe es nicht.
