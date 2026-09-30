# ADR-0018 — Die Seite darf bei Google erscheinen

**Status:** angenommen
**Datum:** 2026-09-30

## Kontext

Beim Umzug auf Vercel ([ADR-0016](ADR-0016-hosting-auf-vercel.md)) bekam jede
Seite den HTTP-Header `X-Robots-Tag: noindex`. Der Gedanke: Das Studio ist
erfunden, und eine öffentliche Seite mit echter Registrierung sollte nicht
bei Google auftauchen.

Inzwischen soll die Seite gefunden werden können. Die Google Search Console
meldet für die Produktionsadresse „Indexierung zulässig? Nein: 'noindex'
wurde im HTTP-Header 'X-Robots-Tag' gefunden". Solange der Header gesetzt
ist, nimmt Google keine einzige Seite auf.

## Entscheidung

Die Produktion wird **ohne `noindex`** ausgeliefert. Das Team hat das
gemeinsam entschieden.

- In `vercel.json` setzt die Route auf `api/index.php` keinen
  `X-Robots-Tag` mehr.
- Preview-Deployments bleiben trotzdem unsichtbar: Vercel setzt dort von
  selbst `X-Robots-Tag: noindex`, unabhängig von `vercel.json`.
- Kein `robots.txt` und keine Sitemap. Ohne beides darf Google alles
  crawlen, was verlinkt ist - für eine Seite dieser Größe genügt das.

## Konsequenzen

**Positiv**

- Die Seite lässt sich über die Search Console zur Indexierung anmelden und
  wird bei Google gefunden.
- Google Analytics (siehe `docs/features/cookie-banner.md`) bekommt damit
  auch Besuche über die Suche.

**Negativ**

- Ein erfundenes Studio mit echter Registrierung ist jetzt über Google
  auffindbar. Wer nicht weiß, dass es ein Studienprojekt ist, kann es für
  echt halten und sich registrieren. Ein sichtbarer Hinweis
  „Studienprojekt" auf der Seite wäre die naheliegende Abhilfe; er ist mit
  diesem ADR nicht umgesetzt.
- Auch Seiten wie Anmeldung oder Passwort-Reset können im Index landen.

## Verworfene Alternativen

**`noindex` nur für einzelne Seiten behalten** (z. B. Anmeldung, Mein Konto).
Wäre ein `<meta name="robots">` pro Seite oder eine Liste in `vercel.json`.
Für den jetzigen Stand nicht nötig - Seiten nur für Mitglieder leiten
ohnehin auf die Anmeldung um. Lässt sich später ergänzen, ohne diese
Entscheidung zurückzunehmen.

**`robots.txt` mit Sitemap anlegen.**
Hilft Google bei großen Seiten, alles zu finden. Bei rund einem Dutzend
verlinkter Seiten bringt es nichts, was die Navigation nicht schon leistet.
