# api/ — Endpunkte

Die Türen zwischen JavaScript und PHP. Ein Endpunkt gibt **immer JSON**
zurück, nie HTML.

Angesprochen werden sie ausschließlich aus `assets/js/services/`.

## Aufbau eines Endpunkts

1. `bootstrap.php` einbinden
2. `Content-Type: application/json` setzen
3. HTTP-Methode prüfen (`GET`, `POST` ...)
4. Eingaben prüfen
5. Repository aufrufen
6. Ergebnis mit `json_encode()` ausgeben

Vollständiges Muster: [`beispiel-feature.php`](beispiel-feature.php)

## Statuscodes

| Code | Bedeutung | Wann |
|---|---|---|
| 200 | OK | Standardfall, muss nicht gesetzt werden |
| 400 | Falsche Eingabe | Pflichtfeld fehlt, E-Mail ungültig |
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
  die Ausgabe direkt im Terminal; läuft das Projekt über Apache, landet sie in
  `xampp/apache/logs/error.log`.
- **Eingaben immer prüfen**, auch wenn das Formular im Browser schon prüft.
  Ein Endpunkt lässt sich auch ohne Browser aufrufen.
- **Fehlermeldungen der Datenbank nie durchreichen.** Sie verraten
  Tabellennamen und Pfade. Ins Log damit, an den Browser geht ein neutraler
  Text.
- Dateiname in Mehrzahl, wie die Tabelle: `kurse.php`, `produkte.php`.
