# ADR-0027 — Mails gehen über Resend raus

**Status:** angenommen
**Datum:** 2026-10-19

## Kontext

Das Projekt verschickte bisher keine E-Mails. Bei „Passwort vergessen“ landete
der Link nur im Server-Log und, im Demo-Modus, direkt auf der Seite. In
Produktion ist der Demo-Modus aus ([ADR-0016](ADR-0016-hosting-auf-vercel.md),
Migrationsplan E4) - dort kam der Link also bei niemandem an, und „Passwort
vergessen“ funktionierte faktisch nicht.

Randbedingungen:

- Kein Budget, die Seite läuft auf Vercel im Hobby-Plan.
- Keine neue Bibliothek ohne Not, kein Build-Schritt.
- Die Domain `schwitzkasten-gym.de` liegt bei Namecheap und hatte bisher
  keinen Mail-Dienst.

## Entscheidung

**Mails verschickt der Dienst Resend**, Absender
`noreply@schwitzkasten-gym.de`.

- **Domain bei Resend verifiziert**, Region Irland (`eu-west-1`). Im DNS bei
  Namecheap stehen dafür DKIM (`resend._domainkey`), SPF (`rsend`, `send`)
  und DMARC (`_dmarc`, `p=none`). Kein MX-Eintrag: Die Domain empfängt keine
  Mails.
- **Ein HTTPS-Aufruf mit curl** in `src/Mailversand.php`, kein
  Composer-Paket - dasselbe Muster wie `src/Ausweispruefung.php`. Sie ist die
  einzige Stelle im Projekt, die Mails verschickt.
- **Schlüssel wie beim Gemini-Schlüssel:** lokal in `config.php` unter
  `mail.api_key`, auf Vercel in `RESEND_API_KEY`. Er hat nur „Sending
  access“ für diese eine Domain.
- **Ohne Schlüssel Demo-Modus:** Wer keinen Schlüssel hat, entwickelt wie
  bisher - der Link steht im Server-Log.
- **Reiner Text, kein Tracking.** Klick- und Öffnungs-Tracking von Resend ist
  aus. Sonst liefe der Link zum Zurücksetzen über einen Server von Resend.
- Zunächst nur „Passwort vergessen“. Weitere Mails nutzen dieselbe Klasse.

## Konsequenzen

**Positiv**

- „Passwort vergessen“ funktioniert in Produktion.
- Mails werden signiert und landen dadurch nicht im Spam.
- Lokal ändert sich für alle ohne Schlüssel nichts.

**Negativ**

- Ein weiterer Dienst, der Daten verarbeitet: Resend sieht Empfängeradresse
  und Inhalt jeder Mail. Steht in `datenschutz.php`.
- Kostenlose Stufe begrenzt (zum Zeitpunkt der Entscheidung 3.000 Mails im
  Monat, 100 am Tag). Für ein Studienprojekt reicht das.
- Scheitert der Versand, merkt die Person es nicht: Die Antwort muss dieselbe
  bleiben wie im Erfolgsfall, sonst ließe sich prüfen, welche Adressen ein
  Konto haben. Der Fehler steht nur im Log.
- Das Resend-Konto ist ein weiteres Konto, das jemand im Team verwalten muss.

## Verworfene Alternativen

**Namecheap Private Email mit SMTP.**
Kostet monatlich und bringt ein Postfach, das niemand braucht. SMTP aus PHP
hätte außerdem eine Bibliothek wie PHPMailer erfordert.

**PHPs eingebautes `mail()`.**
Auf Vercel gibt es keinen Mailserver, und selbst lokal landen solche Mails
ohne Signatur im Spam.

**Andere Versanddienste (Brevo, Mailgun, Amazon SES).**
Funktionieren gleich gut. Resend hat die einfachste API und eine kostenlose
Stufe ohne Kreditkarte; ein Wechsel betrifft nur `src/Mailversand.php`.
