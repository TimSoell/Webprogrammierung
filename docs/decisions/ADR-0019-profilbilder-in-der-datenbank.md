# ADR-0019 — Profilbilder liegen in der Datenbank und haben eine eigene Adresse

**Status:** angenommen
**Datum:** 2026-09-30

## Kontext

Mitglieder sollen in „Mein Konto" ein Profilbild hochladen können. Es
erscheint dort im runden Kreis und – wenn die Person es freigibt – bei ihren
Bewertungen auf der Startseite.

Zum ersten Mal lädt damit jemand eine Datei hoch, die **dauerhaft** bleiben
soll. Die Ausweisprüfung ([ADR-0011](ADR-0011-ausweispruefung-mit-ki.md))
verwirft ihr Bild sofort wieder.

Randbedingungen:

- Auf Vercel ([ADR-0016](ADR-0016-hosting-auf-vercel.md)) gibt es keinen
  dauerhaften Speicher für Dateien. Was eine Funktion auf die Platte schreibt,
  ist beim nächsten Aufruf weg.
- Eine Anfrage darf höchstens 4,5 MB groß sein.
- Die Seite ist öffentlich. Ein Bild darf nur dort auftauchen, wo die Person
  es erlaubt hat.
- Ein erster Versuch (PR #87) mit einer neuen Spalte in `mitglieder` wurde am
  selben Tag zurückgenommen. Eine neue Spalte kommt durch erneutes Einspielen
  von `schema.sql` nicht in eine bestehende Datenbank – fehlt sie, bricht die
  Kontoseite.

## Entscheidung

Profilbilder liegen **als JPEG in PostgreSQL**, in einer eigenen Tabelle
`profilbilder` (`bytea`, ein Bild pro Mitglied). Ausgeliefert werden sie über
**eine eigene Adresse**: `api/profilbilder.php?mitglied=7&v=…`.

- **Im Browser zuschneiden und verkleinern.** Quadratisch aus der Mitte,
  512 × 512 px, JPEG – rund 50 KB. Der Server nimmt trotzdem nur echte JPEGs
  bis 300 KB und 1024 × 1024 px an.
- **Eigene Tabelle statt Spalte.** `CREATE TABLE IF NOT EXISTS` legt sie auch
  in einer bestehenden Datenbank an, und Abfragen der Stammdaten laden das
  Bild nicht jedes Mal mit.
- **Bild-Adresse statt base64 im JSON.** Die Adresse steht direkt in
  `<img src>`. **Ausnahme von zwei Regeln**, bewusst: Dieser Endpunkt antwortet
  bei GET mit dem Bild statt mit JSON, und die Adresse wird nicht über einen
  Service abgerufen, sondern vom Browser geladen – wie jedes andere Bild.
  Speichern, Freigeben und Entfernen laufen normal über
  `assets/js/services/profilbilder.js`.
- **`?v=` mit dem Änderungszeitpunkt.** Jedes neue Bild bekommt eine neue
  Adresse; das alte darf der Browser deshalb ein Jahr behalten
  (`Cache-Control: private, immutable`).
- **Wer ein Bild sehen darf,** steht an genau einer Stelle,
  `ProfilbildRepository::bildFinden()`: die Person selbst immer, alle anderen
  nur bei Freigabe **und** wenn die Person mindestens eine Bewertung
  geschrieben hat. Sonst ließen sich über `?mitglied=1, 2, 3 …` die Bilder
  aller Mitglieder abrufen.
- **Freigabe anfangs an** (Entscheidung im Team), abschalten geht in
  „Mein Konto".

## Konsequenzen

**Positiv**

- Funktioniert auf Vercel ohne zusätzlichen Dienst und lokal genauso.
- Ein Bild verschwindet mit dem Konto (`ON DELETE CASCADE`), nichts bleibt
  irgendwo liegen.
- Die Bewertungen bekommen das Bild ohne Änderung an Seite und Komponente –
  sie kennen nur das Feld `bild`.

**Negativ**

- Jede Bildanzeige ist ein Datenbankzugriff. Für Profilbilder in Kreis- und
  Kachelgröße reicht das; für große Galerien wäre es der falsche Weg.
- Die Datenbank wächst mit jedem Bild um rund 50 KB. Das kostenlose Kontingent
  bei Supabase reicht für Tausende Bilder, ist aber endlich.
- Die Tabelle muss **vor dem Merge** auch in der Produktionsdatenbank
  angelegt sein.
- Hochgeladene Bilder erscheinen ohne Prüfung durch das Team bei den
  Bewertungen, wenn die Person bewertet hat.

## Verworfene Alternativen

**Spalte `profilbild` in `mitglieder` (erster Versuch, PR #87).**
Kommt per `schema.sql` nicht in bestehende Datenbanken, und jede Abfrage der
Stammdaten hätte das Bild mitgeschleppt.

**Supabase Storage.**
Wäre für große Dateien der sauberere Ort. Braucht aber einen zweiten Zugang
(Storage-Schlüssel) in Konfiguration und Vercel und eine eigene
Zugriffsregel – für Bilder von 50 KB zu viel Aufwand.

**Bild als base64 in der JSON-Antwort.**
Macht jede Antwort mit Bildern um ein Vielfaches größer, und der Browser kann
nichts zwischenspeichern.
