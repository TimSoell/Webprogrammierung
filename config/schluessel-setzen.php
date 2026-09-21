<?php
/**
 * @file        config/schluessel-setzen.php
 * @layer       Werkzeug (Kommandozeile, nie über den Browser)
 * @description Trägt den API-Schlüssel für die Ausweisprüfung in
 *              config/config.php ein.
 *
 *              Warum es das gibt: Die Datei von Hand zu bearbeiten geht
 *              schief, sobald ein Editor den Puffer nicht speichert oder in
 *              die falsche Datei schreibt - und man merkt es erst, wenn das
 *              Feature stumm im Demo-Modus bleibt. Dieses Skript schreibt die
 *              eine Zeile und sagt danach, was wirklich in der Datei steht.
 *
 *              AUFRUF (im Projektordner):
 *                  C:\xampp\php\php.exe config\schluessel-setzen.php
 *
 *              Der Schlüssel wird abgefragt, nicht als Argument übergeben -
 *              so landet er nicht in der Verlaufsliste der Eingabeaufforderung.
 *
 *              Schlüssel gibt es unter https://aistudio.google.com/apikey
 * @see         config/config.php
 * @see         docs/features/nachweise.md
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Dieses Skript läuft nur auf der Kommandozeile.');
}

$datei = __DIR__ . '/config.php';

if (!is_file($datei)) {
    exit("config/config.php fehlt. Zuerst config.example.php dorthin kopieren.\n");
}

echo "Schlüssel einfügen (Rechtsklick fügt in der Eingabeaufforderung ein) und Enter:\n> ";

$schluessel = trim((string) fgets(STDIN));

// Beim Kopieren aus dem Browser kommen gerne unsichtbare Zeichen mit: ein
// BOM am Anfang, ein geschütztes Leerzeichen, ein Zeilenumbruch mittendrin.
// Die würden mitgespeichert, und der Schlüssel wäre stumm kaputt - die API
// meldet dann nur "API key not valid". API-Schlüssel sind reines ASCII.
$bereinigt = preg_replace('/[^\x21-\x7E]/', '', $schluessel);

if ($bereinigt !== $schluessel) {
    echo sprintf(
        "Hinweis: %d unsichtbare Zeichen entfernt.\n",
        strlen($schluessel) - strlen((string) $bereinigt)
    );
}

$schluessel = (string) $bereinigt;

if ($schluessel === '') {
    exit("Nichts eingegeben, nichts geändert.\n");
}

// Ein versehentlich mitkopiertes Anführungszeichen würde die PHP-Datei
// zerschießen. Lieber hier abfangen als hinterher einen Parse-Fehler suchen.
if (str_contains($schluessel, "'") || str_contains($schluessel, '\\')) {
    exit("Der Schlüssel enthält ein Anführungszeichen oder einen Backslash. Bitte nur den reinen Wert einfügen.\n");
}

$inhalt = (string) file_get_contents($datei);

$neu = preg_replace(
    "/('api_key'\s*=>\s*)'[^']*'/",
    "$1'" . $schluessel . "'",
    $inhalt,
    1,
    $treffer
);

if ($treffer !== 1) {
    exit("In config/config.php wurde keine Zeile 'api_key' => '...' gefunden.\n");
}

file_put_contents($datei, $neu);

// Gegenprobe: nicht was wir geschrieben haben, sondern was PHP jetzt liest.
$geprueft = (string) ((require $datei)['ki']['api_key'] ?? '');

echo PHP_EOL;
echo $geprueft === $schluessel
    ? sprintf("Gespeichert. PHP liest jetzt %d Zeichen, beginnend mit \"%s…\".\n", strlen($geprueft), substr($geprueft, 0, 3))
    : "Geschrieben, aber die Gegenprobe stimmt nicht. Bitte config/config.php ansehen.\n";

echo "Jetzt mein-konto.php neu laden - dort muss das Feld für das Foto erscheinen.\n";
