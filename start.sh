#!/bin/sh
# =============================================================================
# @file        start.sh
# @layer       Infrastruktur
# @description Startet das Projekt mit dem eingebauten PHP-Webserver direkt aus
#              diesem Ordner - also ohne Kopie in xampp/htdocs. Nur fuer die
#              Entwicklung gedacht; fuer die Abgabe laeuft das Projekt
#              unveraendert unter Apache.
# @see         README.md
# =============================================================================

cd "$(dirname "$0")" || exit 1

# PHP aus der XAMPP-Installation (macOS). Fehlt es dort, wird das PHP aus dem
# PATH benutzt - damit laeuft das Skript auch auf anderen Rechnern.
PHP="/Applications/XAMPP/xamppfiles/bin/php"
[ -x "$PHP" ] || PHP="php"

echo "BASELINE laeuft auf http://localhost:8000/   (Beenden mit Ctrl+C)"
# router.php beantwortet Range-Anfragen, ohne die kein Video springen kann.
exec "$PHP" -S localhost:8000 -t . router.php
