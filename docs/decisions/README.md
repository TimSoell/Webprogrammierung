# Architekturentscheidungen (ADRs)

Ein ADR (*Architecture Decision Record*) hält eine Entscheidung fest, die den
Aufbau des Projekts betrifft — **samt der Gründe und der verworfenen
Alternativen**.

## Wann ein ADR

Immer, wenn eine Entscheidung schwer rückgängig zu machen ist oder wenn sich
in drei Wochen jemand fragen wird „warum eigentlich so?".

Typisch: Ordnerstruktur, Wahl einer Technik, eine feste Regel fürs Team.

Kein ADR für: Farbwerte, Textänderungen, das Anlegen einer einzelnen Datei.

## Regeln

- Dateiname: `ADR-XXXX-kurztitel.md`, fortlaufend nummeriert ab `0001`.
- **Ein ADR wird nie gelöscht und nie umgeschrieben.** Ändert sich die
  Entscheidung, entsteht ein neues ADR, und im alten wird der Status auf
  `ersetzt durch ADR-YYYY` gesetzt. Die Historie ist der eigentliche Wert.
- Vorlage: [`../templates/adr.md`](../templates/adr.md)

## Bestand

| Nr. | Titel | Status |
|---|---|---|
| [0001](ADR-0001-php-includes.md) | PHP-Includes statt vier eigenständiger HTML-Dateien | angenommen |
| [0002](ADR-0002-schichtenarchitektur.md) | Sechs Schichten mit einem Service als Naht zur Datenbank | angenommen |
| [0003](ADR-0003-css-aufteilung.md) | CSS in Tokens, Basis, Layout und Komponenten | angenommen |
| [0004](ADR-0004-php-entwicklungsserver.md) | PHP-Entwicklungsserver als Standard-Startweg | angenommen, Abgabe/MySQL ersetzt durch 0016/0017 |
| [0005](ADR-0005-login-bibliothek.md) | Mitglieder-Login mit delight-im/auth über Composer | angenommen |
| [0006](ADR-0006-router-fuer-entwicklungsserver.md) | Router-Skript für den PHP-Entwicklungsserver | angenommen |
| [0007](ADR-0007-programm-details-aus-der-datenbank.md) | Programm-Details kommen aus der Datenbank | angenommen |
| [0008](ADR-0008-gemerkte-auswahl-am-konto.md) | Gemerkte Auswahl liegt am Konto, nicht nur im Browser | angenommen |
| [0009](ADR-0009-mitgliedschaften-datenmodell.md) | Tarife im Katalog, Verträge mit eingefrorenem Preis | angenommen |
| [0010](ADR-0010-tarifwechsel-zum-monatsersten.md) | Tarifwechsel gelten zum Monatsersten und brauchen eine Bestätigung | angenommen |
| [0011](ADR-0011-ausweispruefung-mit-ki.md) | Ausweise werden per KI geprüft, das Bild wird nie gespeichert | angenommen, Hinweissatz vom Modell ersetzt durch 0020 |
| [0012](ADR-0012-gemini-statt-claude.md) | Die Ausweisprüfung läuft über Gemini auf der kostenlosen Stufe | angenommen |
| [0013](ADR-0013-auslastung-und-besuche.md) | Auslastung als Summe aus fester Kurve und gezählten Besuchen | angenommen |
| [0014](ADR-0014-terminkalender-wochenplan.md) | Termine als Wochenplan, Buchungen mit Datum | angenommen |
| [0015](ADR-0015-kurskalender-monate-und-tarif.md) | Kurskalender monatsweise, Kurse nur mit passendem Tarif | angenommen |
| [0016](ADR-0016-hosting-auf-vercel.md) | Hosting auf Vercel mit der Laufzeit vercel-php | angenommen, noindex ersetzt durch 0018 |
| [0017](ADR-0017-postgresql-auf-supabase.md) | PostgreSQL bei Supabase, Sitzungen in der Datenbank | angenommen |
| [0018](ADR-0018-indexierung-bei-google.md) | Die Seite darf bei Google erscheinen | angenommen |
| [0019](ADR-0019-profilbilder-in-der-datenbank.md) | Profilbilder liegen in der Datenbank und haben eine eigene Adresse | angenommen |
| [0020](ADR-0020-nachweise-absichern.md) | Ein Nachweis je Mitglied, und er muss auf den Kontoinhaber lauten | angenommen |
