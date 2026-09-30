@echo off
REM ===========================================================================
REM @file        start.bat
REM @layer       Infrastruktur
REM @description Windows-Gegenstueck zu start.sh. Startet das Projekt mit dem
REM              eingebauten PHP-Webserver direkt aus diesem Ordner - also
REM              ohne Kopie in xampp\htdocs. Nur fuer die Entwicklung; die
REM              veroeffentlichte Fassung laeuft auf Vercel.
REM @see         start.sh
REM @see         README.md
REM ===========================================================================

cd /d "%~dp0"

REM PHP aus der XAMPP-Installation. Der Installationsort ist im Team nicht
REM ueberall gleich, deshalb werden die ueblichen Pfade der Reihe nach
REM geprueft; zuletzt das PHP aus dem PATH.
set "PHP="
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
if not defined PHP if exist "C:\WebProgrammierung\XAMP\php\php.exe" set "PHP=C:\WebProgrammierung\XAMP\php\php.exe"
if not defined PHP where php >nul 2>&1 && set "PHP=php"

if not defined PHP (
    echo Kein PHP gefunden. Entweder XAMPP nach C:\xampp installieren oder
    echo php.exe in den PATH aufnehmen. Siehe README.md.
    pause
    exit /b 1
)

if not exist "config\config.php" (
    echo Konfiguration fehlt. Einmalig ausfuehren:
    echo     copy config\config.example.php config\config.php
    pause
    exit /b 1
)

REM Die Datenbank ist PostgreSQL bei Supabase. XAMPP bringt den Treiber
REM pdo_pgsql mit, schaltet ihn aber nicht ein. Statt dass jede Person ihre
REM php.ini aendert, laedt das Skript ihn beim Start dazu - nur falls noetig,
REM sonst meldet PHP "already loaded".
set "PGSQL="
"%PHP%" -m | findstr /i /x "pdo_pgsql" >nul || set "PGSQL=-d extension=pdo_pgsql"

echo SCHWITZKASTEN laeuft auf http://localhost:8000/   (Beenden mit Strg+C)
REM router.php beantwortet Range-Anfragen, ohne die kein Video springen kann.
"%PHP%" %PGSQL% -S localhost:8000 -t . router.php
pause
