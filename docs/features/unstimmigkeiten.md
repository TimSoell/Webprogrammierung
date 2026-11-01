# Feature: Unstimmigkeiten und Konto löschen

**Status:** fertig
**Verantwortlich:** Felix
**Zuletzt geprüft:** 2026-11-01

## Was kann man damit

Passt am [Empfang](empfang.md) etwas nicht — der Personalausweis ist falsch,
oder die Person vor dem Tresen ist nicht die aus dem Konto —, klickt das
Personal **„Unstimmigkeit"** und schreibt kurz dazu, was nicht passt. Das
Konto bleibt in der Liste, ist aber rot markiert.

Ein **Admin** sieht alle gemeldeten Konten auf `admin.php` in der Karte
**„Unstimmigkeiten"**. Dort löscht er ein Konto endgültig — oder hebt die
Markierung auf, wenn sich alles aufgeklärt hat. So bleibt nach einem
Betrugsversuch kein Datenmüll zurück.

Dazu gehört eine Regel aus [Freunde werben](freunde-werben.md): Ein Gutschein
lässt sich erst einlösen, wenn das Konto des Geworbenen am Empfang bestätigt
ist. Ein erfundenes Konto bringt dem Werber also nichts.

## Die Regeln

| | |
|---|---|
| Wer meldet | nur das Personal, nie das eigene Konto |
| Was gemeldet werden kann | Konten aus der Liste am Empfang, also noch nicht bestätigte |
| Grund | Pflicht, höchstens 200 Zeichen |
| Wie oft | einmal; eine zweite Meldung zum selben Konto geht nicht |
| Kommt die Person wieder | das Personal kann trotzdem bestätigen — die Markierung verschwindet dann |
| Wer löscht | nur ein Admin, und nur gemeldete Konten, nie das eigene |
| Was gelöscht wird | alles: Login, Stammdaten, Verträge, Nachweise, Kursbuchungen, Probetrainings, Besuche, Bewertungen, Profilbild, gemerkte Auswahl, Einladungen und Gutscheine |
| Einladung beim Werber | hat jemand das gelöschte Konto geworben, verschwindet seine Einladung samt noch nicht eingelöstem Gutschein; ein schon eingelöster Gratiszeitraum bleibt |
| Rückgängig | Löschen nicht. Eine Markierung hebt der Admin auf |
| Personal löscht | nie |

Gelöscht wird in einem Schritt (Transaktion): Entweder ist danach alles weg,
oder es bleibt alles, wie es war.

## Beteiligte Dateien

| Schicht | Datei |
|---|---|
| 1 Seite | `empfang.php` (Knopf je Zeile), `admin.php` (Karte „Unstimmigkeiten") |
| 2 Seitenskript | `assets/js/pages/empfang.page.js`, `assets/js/pages/admin.page.js` |
| 3 Service | `assets/js/services/empfang.js` (`unstimmigkeitMelden()`), `assets/js/services/unstimmigkeiten.js` |
| 4 Endpunkt | `api/empfang.php` (POST melden), `api/unstimmigkeiten.php` |
| Infrastruktur | `src/Auth.php` (`kontoLoeschen()`) |
| 5 Repository | `src/Repositories/MitgliedRepository.php` |
| 6 Tabelle | Spalten `unstimmigkeit_am` und `unstimmigkeit_grund` in `mitglieder` |
| CSS | `assets/css/components/empfang.css`, `assets/css/components/admin.css` |

## Datenform

Ein Eintrag der Liste auf `admin.php`:

```json
{ "mitgliedId": 12, "vorname": "Maximilian", "nachname": "Testmann",
  "email": "max@beispiel.de", "registriertAm": "2026-10-02",
  "gemeldetAm": "2026-10-16", "grund": "Foto passt nicht zur Person" }
```

Am Empfang hat jeder Eintrag zusätzlich `"unstimmigkeit": { "am": "2026-10-16", "grund": "…" }`
oder `null`.

## Endpunkte

| Methode | Pfad | Zweck | Antwort |
|---|---|---|---|
| POST | `api/empfang.php` | Unstimmigkeit melden, `{ mitgliedId, grund }` | `{ "offen": [...] }` · 400 · 403 eigenes Konto · 409 schon gemeldet |
| GET | `api/unstimmigkeiten.php` | alle gemeldeten Konten | `{ "unstimmigkeiten": [...] }` · 401 · 403 |
| PUT | `api/unstimmigkeiten.php` | Markierung aufheben, `{ mitgliedId }` | `{ "unstimmigkeiten": [...] }` · 400 · 409 |
| DELETE | `api/unstimmigkeiten.php` | Konto löschen, `{ mitgliedId }` | `{ "unstimmigkeiten": [...] }` · 400 · 403 eigenes Konto · 409 |

`api/unstimmigkeiten.php` ist nur für Admins, siehe
[Personal verwalten](personal-verwalten.md).

## Woher kommen die Daten aktuell

Aus PostgreSQL bei Supabase. Die zwei neuen Spalten in `mitglieder` kommen
über den Abschnitt „NACHTRÄGE" am Ende von `database/schema.sql` in
vorhandene Datenbanken. Bestehende Zeilen bleiben unberührt — beide Spalten
sind dort leer, also ohne Meldung.

Gelöscht wird über die Verknüpfungen, die es schon gibt: Fast alle Tabellen
hängen mit `ON DELETE CASCADE` an `mitglieder`, und `mitglieder` hängt so an
`users`. Nur die Einladung beim Werber (`empfehlungen.geworben_id`) wird beim
Löschen bloß geleert — die räumt `MitgliedRepository::unstimmigesKontoLoeschen()`
vorher selbst ab.

## Was fehlt noch

- **Wer gemeldet oder gelöscht hat, wird nicht gespeichert.**
- **Das gemeldete Mitglied erfährt davon nichts.** In „Mein Konto" steht kein
  Hinweis.
- **Ist die Person gerade angemeldet, endet ihre Sitzung erst nach bis zu
  fünf Minuten** — dann gleicht die Login-Bibliothek ab und meldet das
  gelöschte Konto ab. Bis dahin findet die Seite zu dem Konto keine
  Stammdaten mehr.
- **Kein Filter „Unstimmigkeit" am Empfang.** Die markierten Konten stehen
  zwischen den anderen.
