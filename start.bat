@echo off
REM ===========================================================================
REM @file        start.bat
REM @layer       Infrastruktur
REM @description Windows-Gegenstueck zu start.sh. Startet das Projekt mit dem
REM              eingebauten PHP-Webserver direkt aus diesem Ordner - also
REM              ohne Kopie in xampp\htdocs. Nur fuer die Entwicklung; fuer
REM              die Abgabe laeuft das Projekt unveraendert unter Apache.
REM @see         start.sh
REM @see         README.md
REM ===========================================================================

cd /d "%~dp0"

REM PHP aus der XAMPP-Installation. Fehlt es dort, wird das PHP aus dem PATH
REM benutzt - damit laeuft das Skript auch bei abweichendem Installationsort.
set "PHP=C:\WebProgrammierung\XAMP\php\php.exe"
if not exist "%PHP%" set "PHP=php"

echo BASELINE laeuft auf http://localhost:8000/   (Beenden mit Strg+C)
"%PHP%" -S localhost:8000 -t .
pause
