# assets/img/coaches/ — Porträtfotos der Coaches

Hier liegen die Gesichter, die auf den drei Programm-Detailseiten im rechten
Kasten erscheinen.

**Solange eine Datei fehlt, zeigt die Seite die Initialen des Namens** statt
eines kaputten Bildsymbols. Das Feature funktioniert also auch ohne Fotos —
ihr könnt sie jederzeit nachliefern.

## Welche Dateien erwartet werden

Die Dateinamen stehen in der Spalte `coaches.bild` (siehe
`database/seed.sql`). Aktuell sind das:

| Programm | Dateien |
|---|---|
| Strength | `lena-brandt.jpg`, `mika-oezdemir.jpg`, `jonas-reiter.jpg` |
| Move | `sofia-lindqvist.jpg`, `tobias-krueger.jpg`, `amelie-fuchs.jpg` |
| Fight | `nuri-yilmaz.jpg`, `clara-vogt.jpg`, `david-ostermann.jpg` |

Anderer Dateiname? Dann in `seed.sql` ändern und neu einspielen — nicht im
PHP suchen, dort steht kein Dateiname.

## Anforderungen an die Fotos

- **Quadratisch.** Die Karte schneidet rund zu (`border-radius: 50%`), ein
  Hochformat verliert oben und unten.
- Mindestens 400 × 400 px, höchstens 800 × 800 px. Größer bringt nichts, die
  Karte ist knapp 100 px breit.
- Unter 150 KB pro Datei. Neun Fotos sind sonst schnell mehrere Megabyte im
  Repository.
- Gesicht mittig, Schultern mit drauf.
- `.jpg` für Fotos — siehe `assets/img/README.md`.

## Der alt-Text

Steht **nicht** hier, sondern in der Spalte `coaches.bild_alt`. Jedes Bild
braucht einen, das ist Pflicht. Beim Anlegen eines neuen Coaches also beide
Spalten füllen.

## Rechte

Nur Fotos verwenden, an denen das Studio die Rechte hat, oder freie Bilder
mit passender Lizenz. Für die Studienarbeit reichen Platzhalter.
