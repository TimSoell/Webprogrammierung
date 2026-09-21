# ADR-0009 — Die Ausweisprüfung läuft über Gemini auf der kostenlosen Stufe

**Status:** angenommen
**Datum:** 2026-09-21

**Ergänzt [ADR-0008](ADR-0008-ausweispruefung-mit-ki.md).** Dessen
Entscheidungen gelten unverändert weiter: Bilder werden nie gespeichert, die
KI entscheidet nichts, ohne Schlüssel läuft der Demo-Modus, die Sperre sitzt
im Endpunkt. Hier geht es nur um die Frage, **wessen** Modell die Bilder
liest.

## Kontext

[ADR-0008](ADR-0008-ausweispruefung-mit-ki.md) hat die Ausweisprüfung gegen
die Anthropic-API gebaut. Für das Team ist das keine gangbare Lösung: Diese
API ist kostenpflichtig, und für eine Studienarbeit soll kein Geld ausgegeben
werden. Vorhanden ist ein Schlüssel für die **kostenlose Stufe der
Gemini-API** von Google.

Damit stand die Wahl zwischen einem Feature, das niemand einschalten kann,
und einem Anbieterwechsel.

## Entscheidung

**Die Ausweisprüfung ruft die Gemini-API von Google auf**, Endpunkt
`v1beta/models/{modell}:generateContent`, voreingestelltes Modell
`gemini-3.8-flash`.

- **Der Schlüssel geht als Header** (`x-goog-api-key`), nicht als `?key=...`
  in der Adresse. Adressen landen in Server- und Proxy-Protokollen, Header
  nicht.
- **Das Modell steht in `config/config.php`**, nicht im Quelltext. Welche
  Modelle die kostenlose Stufe abdeckt, ändert Google regelmäßig — das soll
  niemanden zwingen, `src/Ausweispruefung.php` anzufassen.
- **Das feste Antwortschema bleibt.** Gemini kennt dafür
  `generationConfig.response_schema`, inhaltlich dasselbe wie vorher: vier
  Felder, mehr kann das Modell nicht zurückgeben.
- **Es ändert sich nur diese eine Datei.** `api/nachweise.php`, das
  Repository, die Tabelle und die Oberfläche bleiben unverändert — genau
  dafür war die Prüfung von Anfang an als eigene Klasse gebaut.

**Für das Projekt gilt ab sofort: Es werden ausschließlich erfundene
Ausweise hochgeladen.** Auch nicht die eigenen. Der Grund steht unten.

## Konsequenzen

**Positiv**

- Das Feature ist einschaltbar, ohne dass jemand bezahlt. Das war der Punkt.
- Der Wechsel kostete eine Datei. Dass `Ausweispruefung` die einzige Stelle
  mit einem KI-Aufruf ist, hat sich damit zum ersten Mal ausgezahlt.
- Das Modell ist konfigurierbar, der nächste Wechsel also eine Zeile in
  `config.php`.

**Negativ**

- **Auf der kostenlosen Stufe darf Google die Eingaben zur
  Produktverbesserung verwenden, und menschliche Prüfer dürfen sie lesen.**
  Googles Nutzungsbedingungen sagen das ausdrücklich; auf der kostenpflichtigen
  Stufe gilt das Gegenteil. Für Ausweisdokumente ist das die schwerwiegendste
  Folge dieser Entscheidung. Sie ist vertretbar, **weil und solange** nur
  erfundene Dokumente hochgeladen werden — und das ist eine Regel, die
  niemand vergessen darf.
- Die kostenlose Stufe hat Kontingente pro Minute und pro Tag. Sind sie
  erschöpft, antwortet die API mit 429, und das Hochladen scheitert mit
  „Die Prüfung war gerade nicht erreichbar". Bei einer Vorführung mit
  mehreren Uploads hintereinander ist das ein realistischer Fall.
- Eine Abhängigkeit von einem Anbieter, dessen kostenlose Stufe jederzeit
  wegfallen kann. Der Demo-Modus bleibt deshalb bestehen.
- Der Abschnitt „Verworfene Alternativen" in ADR-0008 wägt das
  Anthropic-SDK gegen curl ab. Diese Abwägung ist durch den Anbieterwechsel
  gegenstandslos geworden, bleibt aber als Teil der Historie stehen — ADRs
  werden nicht umgeschrieben.

## Verworfene Alternativen

**Bei Anthropic bleiben und die Kosten tragen.**
Ein paar Cent für die Prüfungen einer Studienarbeit. Verworfen, weil die
Vorgabe des Teams ausdrücklich „kein Geld" lautet und weil ein Feature, das
an einer Kreditkarte hängt, von niemandem im Team eingeschaltet wird.

**Beide Anbieter unterstützen, umschaltbar per Konfiguration.**
Technisch naheliegend, weil die Klasse ohnehin gekapselt ist. Verworfen nach
„Bau nichts auf Vorrat" aus [CLAUDE.md](../../CLAUDE.md): Es gibt genau einen
Schlüssel im Team. Zwei Anbieter zu pflegen hieße, beide zu testen — und der
Anthropic-Pfad ist nie gegen die echte API gelaufen.

**Ein lokales Modell ohne Netz (Ollama o. Ä.).**
Datenschutzrechtlich die beste Lösung: Kein Bild verlässt den Rechner.
Verworfen, weil es eine Installation außerhalb von XAMPP voraussetzt, die
jedes Teammitglied und die Prüfungsumgebung mitmachen müsste — und weil ein
Modell, das Text von Fotos liest, auf einem Studienlaptop langsam ist.

**Beim Demo-Modus bleiben.**
Kostet nichts und ist ehrlich beschriftet. Verworfen, weil dann jede Person
ihr Ablaufdatum selbst einträgt und das Alleinstellungsmerkmal „geprüfter
Nachweis ohne Gang ins Studio" nur behauptet wäre.
