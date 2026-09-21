@echo off
REM ===========================================================================
REM @file        schluessel-setzen.bat
REM @layer       Infrastruktur
REM @description Traegt den API-Schluessel fuer die Ausweispruefung in
REM              config\config.php ein - per Doppelklick, ohne Editor und
REM              ohne Eingabeaufforderung.
REM
REM              Warum es das gibt: config\config.php von Hand zu bearbeiten
REM              geht schief, sobald ein Editor den Puffer nicht speichert.
REM              Das faellt erst auf, wenn die Ausweispruefung stumm im
REM              Demo-Modus bleibt.
REM
REM              Schluessel gibt es unter https://aistudio.google.com/apikey
REM @see         config/schluessel-setzen.php
REM @see         docs/features/nachweise.md
REM ===========================================================================

cd /d "%~dp0"

REM Dieselbe PHP-Suche wie in start.bat - der Installationsort ist im Team
REM nicht ueberall gleich.
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

echo.
echo   SCHWITZKASTEN - API-Schluessel fuer die Ausweispruefung
echo   ------------------------------------------------------
echo   Schluessel holen: https://aistudio.google.com/apikey
echo.

"%PHP%" config\schluessel-setzen.php

echo.
pause
