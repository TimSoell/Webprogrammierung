# Feature: Empfang

**Status:** in Arbeit
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-10-16

## Was kann man damit

**Jedes Mitglied, das einen Tarif wählt, bestätigt sein Konto einmal am
Empfang mit dem Personalausweis** — innerhalb von 14 Tagen nach der ersten
Tarifwahl, am besten beim ersten Training. Das Personal sieht auf einer
eigenen Seite alle Konten, die das noch nicht sind. Steht die Person vor ihm, vergleicht es das Foto im Personalausweis mit
ihr und den Namen mit dem Konto und klickt **„Bestätigen"**. Passt etwas
nicht, bestätigt es einfach nicht.

**Schüler-, Studierenden- und Seniorennachweise prüft der Empfang nicht** —
die prüft die Ausweisprüfung online, siehe [Nachweise](nachweise.md).

Die Liste ist eine Tabelle mit den Spalten Person (Name und E-Mail),
Registriert, Status und dem Knopf. Darüber stehen eine **Suche nach Name
oder E-Mail**, ein Filter (alle, mit Tarif, Frist verpasst) und ein
Knopf zum Neuladen — falls am zweiten Tresen gerade jemand bestätigt hat.
Suche und Filter arbeiten im Browser auf der geladenen Liste; Groß- und
Kleinschreibung und Akzente zählen nicht („muller" findet „Müller"). Auf
dem Handy wird aus jeder Zeile ein Block.

Danach ist der Name für immer fest. Warum:
[ADR-0023](../decisions/ADR-0023-name-aendern-und-ausweis-am-empfang.md) und
[ADR-0024](../decisions/ADR-0024-empfang-bestaetigt-konto-mit-personalausweis.md).

Mitglieder sehen von alldem nur ihren Stand unter „Mein Konto": bis wann sie
den Personalausweis zeigen müssen, ob die Frist verpasst ist, oder dass ihr
Konto bestätigt ist.

## So entsteht ein Konto für das Personal

1. Die Person **registriert sich ganz normal** auf der Website, wie jedes
   Mitglied. Ein eigenes Login gibt es nicht.
2. Jemand mit Zugang zum **Supabase-Dashboard** gibt diesem Konto die Rolle
   `EMPLOYEE` der Login-Bibliothek (SQL unten). Zugang zum Dashboard hat nur
   das Team, nicht die Mitglieder.

Auf der Website gibt es dafür **keinen Knopf und kein Formular**. Ein
Mitglied kann sich die Rolle also nicht selbst geben, und niemand kann sie
über die Seite vergeben.

```sql
-- Rolle vergeben (2048 = Role::EMPLOYEE der Bibliothek)
UPDATE users SET roles_mask = roles_mask | 2048 WHERE email = 'name@beispiel.de';

-- Rolle wieder wegnehmen
UPDATE users SET roles_mask = roles_mask & ~2048 WHERE email = 'name@beispiel.de';
```

Die Bibliothek gleicht die Rolle alle fünf Minuten mit der Datenbank ab. Sie
wirkt also nach dem nächsten Anmelden sofort, sonst spätestens nach fünf
Minuten. Das ist die einzige Stelle, an der `users` von Hand geschrieben
wird — im Code passiert das weiterhin nur über `src/Auth.php`.

## So kommt das Personal auf die Seite

**Kein Link auf der Website führt zum Empfang** — kein Menüpunkt, kein Knopf
in „Mein Konto". Das Personal ruft die Adresse direkt auf, am besten als
Lesezeichen am Rechner des Empfangs:

| | |
|---|---|
| Live | `https://schwitzkasten-gym.de/empfang.php` |
| Lokal | `http://localhost:8000/empfang.php` |

Wer dort nicht angemeldet ist, landet auf der Anmeldung. Nach dem Anmelden
geht es wie immer nach „Mein Konto" — die Adresse dann noch einmal aufrufen.

Geheim ist die Adresse nicht, und sie muss es auch nicht sein: Geschützt ist
die Seite über die Rolle. Ein Mitglied, das sie eintippt, kommt zurück in
sein Konto (`Auth::nurFuerPersonal()`), und `api/empfang.php` antwortet mit
**403**. Die Sperre sitzt auf dem Server.

## Die Regeln

| | |
|---|---|
| Wer bestätigt | nur das Personal, und nie das eigene Konto |
| Was bestätigt wird | das Konto: Personalausweis gesehen, Foto passt zur Person, Name zum Konto |
| Was der Empfang nicht prüft | Schüler-, Studierenden- und Seniorennachweise |
| Wie oft | einmal je Konto, für immer |
| Wer in der Liste steht | alle unbestätigten Konten, ohne gesperrte (Demo-Konten) und ohne das eigene |
| Wer muss | jedes Mitglied mit Tarif, in jeder Preisgruppe, auch mit Gutschein |
| Frist | 14 Tage ab der **ersten Tarifwahl**, beginnt nie neu; ohne Tarif keine Frist |
| Bis zur Bestätigung | alles gilt, auch ein ermäßigter Preis |
| Frist verpasst, mit Ermäßigung | kein ermäßigter Preis, bis das Konto bestätigt ist; ein laufender Vertrag wird zum Monatsersten herabgestuft |
| Frist verpasst, ohne Ermäßigung | keine Sperre, nur rot markiert in „Mein Konto" und am Empfang |
| Bestätigen nach verpasster Frist | Preis gilt wieder, eine noch nicht wirksame Herabstufung wird zurückgenommen |
| Personalausweis passt nicht | nicht bestätigen — einen Knopf „Ablehnen" gibt es nicht |
| Name | bis zur Bestätigung änderbar (nicht, solange ein Nachweis gilt), danach für immer fest |

Oben stehen die Konten mit Tarif, die dringendste Frist zuerst; verpasste
sind rot markiert, mit Ermäßigung steht dabei, dass der günstige Preis ruht.
Danach die Konten ohne Tarif nach Namen, mit grauem Status „Ohne Tarif".

Mitglieder sehen ihre Frist in „Mein Konto" bei den Stammdaten (lime, bei
verpasster Frist rot) und nach der ersten Tarifwahl in der Meldung auf der
Tarifseite.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `empfang.php` |
| 2 Seitenskript | `assets/js/pages/empfang.page.js` |
| 3 Service | `assets/js/services/empfang.js` |
| 4 Endpunkt | `api/empfang.php` |
| Infrastruktur | `src/Auth.php` (`istPersonal()`, `nurFuerPersonal()`) |
| 5 Repository | `src/Repositories/MitgliedRepository.php` (Liste, bestätigen, Frist), `src/Repositories/NachweisRepository.php` (Gültigkeit für den Preis) |
| 6 Tabelle | Spalten `bestaetigt_am` und `vorzeigen_bis` in `mitglieder`, Rolle in `users.roles_mask` |
| CSS | `assets/css/components/empfang.css` |

Was sich am Nachweis selbst ändert, steht in [Nachweise](nachweise.md).

## Datenform

Ein Eintrag der Liste:

```json
{ "mitgliedId": 12, "vorname": "Maximilian", "nachname": "Testmann",
  "email": "max@beispiel.de", "registriertAm": "2026-10-02",
  "ermaessigung": "student", "vorzeigenBis": "2026-10-30", "fristVerpasst": false }
```

`vorzeigenBis` ist `null`, solange kein Tarif gewählt ist — dann gibt es keine
Frist, und `fristVerpasst` ist `false`. `ermaessigung` ist `null` ohne
gültigen Ermäßigungsnachweis; es steht nur zur Anzeige da.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| GET | `api/empfang.php` | alle unbestätigten Konten | `{ "offen": [...] }` · 401 · 403 |
| PUT | `api/empfang.php` | Konto bestätigen, `{ mitgliedId }` | `{ "offen": [...] }` · 400 · 403 eigenes Konto · 409 schon bestätigt |

**409** heißt meist: Jemand anderes am Empfang hat gerade schneller
geklickt. Die Seite zeigt die Meldung, die Liste lädt man neu.

## Woher kommen die Daten aktuell

Aus PostgreSQL bei Supabase, Tabellen `mitglieder`, `nachweise` und `users`.
Neue Tabellen gibt es nicht. Die zwei Spalten kommen über den Abschnitt
„NACHTRÄGE" am Ende von `database/schema.sql` in vorhandene Datenbanken;
wer dort schon einen Tarif hat, bekommt seine 14 Tage ab dem Einspielen.
Fristen, die nach der alten Regel ohne Tarif entstanden sind, setzt der
Nachtrag zurück.

## Was fehlt noch

- **Gespeichert wird nur, wann bestätigt wurde, nicht von wem.**
- **Eine Bestätigung lässt sich nicht zurücknehmen** — nur von Hand im
  Dashboard (`bestaetigt_am` auf `NULL`). Mit Absicht kein Knopf dafür.
- **Rollen vergibt man noch im Dashboard.** Eine Rolle „Admin", die
  Personal auf der Website ernennen kann, ist der nächste Schritt.
- **Im Demo-Modus prüft niemand den Studentenstatus.** Ohne Schlüssel läuft
  keine Ausweisprüfung, und der Empfang sieht die Nachweise nicht. Der Name
  bleibt nach der Bestätigung aber fest.
- **Einen falschen Ermäßigungsnachweis kann der Empfang nicht entfernen.** Er
  bestätigt das Konto dann nicht, nach 14 Tagen ruht der Preis von selbst.
- **Ohne Ermäßigung hat eine verpasste Frist keine Folge** — bewusst die
  lockere Variante. Strenger wäre, Kursbuchungen bis zur Bestätigung zu
  sperren.
- **Die Tarifseite sagt bei verpasster Frist „Nachweis fehlt".** Den genauen
  Grund nennt nur „Mein Konto".
