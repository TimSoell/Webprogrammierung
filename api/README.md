# api/ — Endpunkte

Die Türen zwischen JavaScript und PHP. Ein Endpunkt gibt **immer JSON**
zurück, nie HTML.

Angesprochen werden sie ausschließlich aus `assets/js/services/`.

**Ausnahme: `profilbilder.php` liefert bei GET das Bild selbst**
(`image/jpeg`), damit die Adresse direkt in `<img src>` stehen kann. Speichern,
Freigeben und Entfernen laufen normal über den Service. Siehe
[ADR-0019](../docs/decisions/ADR-0019-profilbilder-in-der-datenbank.md).

**Ausnahme: `index.php` ist kein Endpunkt.** Sie ist der einzige Einstieg
auf Vercel und bindet von dort aus Seiten und Endpunkte ein. Vercel führt PHP
nur in diesem Ordner aus, deshalb liegt sie hier. Nicht anfassen, keinen
Endpunkt so nennen. Siehe
[ADR-0016](../docs/decisions/ADR-0016-hosting-auf-vercel.md).

## Aufbau eines Endpunkts

1. `bootstrap.php` einbinden
2. `Content-Type: application/json` setzen
3. HTTP-Methode prüfen (`GET`, `POST` ...)
4. Eingaben prüfen
5. Repository aufrufen
6. Ergebnis mit `json_encode()` ausgeben

Vollständiges Muster: [`beispiel-feature.php`](beispiel-feature.php)

Die Handgriffe 2, 4 und 6 nimmt `src/Api.php` ab — so machen es die
Endpunkte des Mitglieder-Logins:

```php
$eingabe = Api::eingabe();                 // JSON lesen, sonst 415
$email   = Api::text($eingabe, 'email');   // '' wenn fehlt oder kein Text
Api::fehler(400, 'Bitte gib eine E-Mail-Adresse an.');
Api::antworten(['id' => $id], 201);
```

`Api::eingabe()` verlangt den Content-Type `application/json`. Das schützt
vor CSRF, deshalb gehört der Aufruf in **jeden** Endpunkt, der etwas ändert.

## Statuscodes

| Code | Bedeutung | Wann |
|---|---|---|
| 200 | OK | Standardfall, muss nicht gesetzt werden |
| 400 | Falsche Eingabe | Pflichtfeld fehlt, E-Mail ungültig |
| 401 | Nicht angemeldet | Login fehlt oder Anmeldedaten falsch |
| 404 | Nicht gefunden | id existiert nicht |
| 405 | Methode nicht erlaubt | POST auf einen Nur-Lese-Endpunkt |
| 500 | Serverfehler | Unerwartete Ausnahme |

Im Fehlerfall ist die Antwort immer `{"error": "Text für Menschen"}`.
`assets/js/services/api.js` liest genau dieses Feld aus.

## Regeln

- **Kein SQL.** Das gehört in `src/Repositories/`.
- **Kein HTML.**
- **Kein `echo` zum Debuggen.** Jede zusätzliche Ausgabe zerstört das JSON;
  im Browser erscheint dann nur „Antwort war kein gültiges JSON".
  Zum Debuggen `error_log()` benutzen. Beim Start über `./start.sh` erscheint
  die Ausgabe direkt im Terminal; auf Vercel im Dashboard unter *Logs* – dort
  aber nur eine Stunde lang.
- **Eingaben immer prüfen**, auch wenn das Formular im Browser schon prüft.
  Ein Endpunkt lässt sich auch ohne Browser aufrufen.
- **Fehlermeldungen der Datenbank nie durchreichen.** Sie verraten
  Tabellennamen und Pfade. Ins Log damit, an den Browser geht ein neutraler
  Text.
- Dateiname in Mehrzahl, wie die Tabelle: `kurse.php`, `produkte.php`.
