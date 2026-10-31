# Feature: Mailversand

**Status:** fertig
**Verantwortlich:** Giani
**Zuletzt geprüft:** 2026-10-31

## Was kann man damit

Die Website verschickt E-Mails von `noreply@schwitzkasten-gym.de`. Im Moment
nur eine: Wer bei der Anmeldung „Passwort vergessen?“ wählt, bekommt den Link
zum Zurücksetzen per Mail. Er ist 60 Minuten gültig und funktioniert einmal.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 4 Endpunkt | `api/passwort-reset.php` (POST verschickt die Mail) |
| Infrastruktur | `src/Mailversand.php` - die einzige Stelle, die Mails verschickt |
| Konfiguration | `mail.api_key` in `config/config.php`, auf Vercel `RESEND_API_KEY` |
| Text | Abschnitt „Konto und Anmeldung“ in `datenschutz.php` |

Seite, Seitenskript und Service sind unverändert: Für den Browser sieht die
Antwort genauso aus wie vorher.

## Dienst und DNS

Versand über [Resend](https://resend.com), Domain verifiziert, Region Irland.
Die Einträge stehen bei Namecheap unter *Advanced DNS*:

| Typ | Host | Wofür |
|---|---|---|
| TXT | `resend._domainkey` | DKIM-Signatur |
| CNAME | `rsend` | SPF |
| CNAME | `send` | SPF, Rückläufer |
| TXT | `_dmarc` | DMARC (`p=none`) |

**Diese Einträge nicht löschen**, sonst landen alle Mails im Spam oder werden
abgelehnt. Entscheidung und Alternativen:
[ADR-0027](../decisions/ADR-0027-mailversand-ueber-resend.md).

## Ohne Schlüssel (Demo-Modus)

Ist `mail.api_key` leer, wird nichts verschickt. Der Link steht dann wie
früher im Server-Log (Zeile beginnt mit `[Demo-Mail]`) - lokal im Terminal von
`./start.sh`, auf Vercel unter *Logs*. Mit `'demo_reset_link' => true` steht
er zusätzlich auf der Seite, auch wenn eine Mail rausgeht.

## Neue Mail ergänzen

Im Endpunkt `Mailversand::senden($an, $betreff, $text)` aufrufen. Vorher mit
`Mailversand::verfuegbar()` prüfen, ob ein Schlüssel da ist, und sonst ins Log
schreiben. Links brauchen die Domain davor, siehe `api/passwort-reset.php`.

## Woher kommen die Daten aktuell

Empfängeradresse aus dem Formular, Link von der Login-Bibliothek. Gespeichert
wird nichts zusätzlich.

## Was fehlt noch

- Versand in der Preview ausprobieren. In Produktion kommt die Mail an, geprüft
  am 2026-10-31.
- Weitere Mails, z. B. Bestätigung nach Registrierung oder Buchung - noch
  nicht entschieden.
