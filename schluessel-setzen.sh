#!/bin/sh
# =============================================================================
# @file        schluessel-setzen.sh
# @layer       Infrastruktur
# @description Gegenstueck zu schluessel-setzen.bat fuer macOS und Linux.
#              Traegt den API-Schluessel fuer die Ausweispruefung in
#              config/config.php ein, ohne die Datei von Hand zu bearbeiten.
#
#              Schluessel gibt es unter https://aistudio.google.com/apikey
# @see         config/schluessel-setzen.php
# @see         docs/features/nachweise.md
# =============================================================================

cd "$(dirname "$0")" || exit 1

# Dieselbe PHP-Suche wie in start.sh.
PHP="/Applications/XAMPP/xamppfiles/bin/php"
[ -x "$PHP" ] || PHP="php"

if ! command -v "$PHP" >/dev/null 2>&1; then
    echo "Kein PHP gefunden. Entweder XAMPP installieren oder PHP ueber"
    echo "Homebrew (brew install php). Siehe README.md."
    exit 1
fi

if [ ! -f config/config.php ]; then
    echo "Konfiguration fehlt. Einmalig ausfuehren:"
    echo "    cp config/config.example.php config/config.php"
    exit 1
fi

echo
echo "  SCHWITZKASTEN - API-Schluessel fuer die Ausweispruefung"
echo "  ------------------------------------------------------"
echo "  Schluessel holen: https://aistudio.google.com/apikey"
echo

exec "$PHP" config/schluessel-setzen.php
