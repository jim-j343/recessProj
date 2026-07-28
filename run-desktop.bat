@echo off
REM ===================================================================
REM  run-desktop.bat  --  launch the ACES JavaFX desktop client
REM
REM  Usage:
REM     run-desktop.bat                 (talks to http://localhost:8000/api)
REM     run-desktop.bat <api-base-url>  (talks to that URL instead)
REM
REM  Put this file in the repo root, next to web-app/ and desktop-app/.
REM ===================================================================

setlocal

REM Work relative to this script, so it runs from any directory
set "ROOT=%~dp0"
set "APPDIR=%ROOT%desktop-app\ACESDesktop"

if not exist "%APPDIR%\pom.xml" (
    echo [X] Could not find %APPDIR%\pom.xml
    echo     Put run-desktop.bat in the repo root, beside web-app and desktop-app.
    exit /b 1
)

REM --- Maven present? ------------------------------------------------
where mvn >nul 2>&1
if errorlevel 1 (
    echo [X] Maven ^(mvn^) is not on your PATH.
    echo     Install Maven, or run the app from your IDE instead.
    exit /b 1
)

REM --- Which API is it talking to? -----------------------------------
set "API=%~1"
if "%API%"=="" set "API=http://localhost:8000/api"

echo.
echo  ACES Desktop
echo  ------------
echo  API: %API%
echo.

REM --- Is that API actually up? --------------------------------------
REM  The usual cause of a hang is Laravel not running, so check first.
curl -s -o nul -m 5 "%API%/../" >nul 2>&1
if errorlevel 1 (
    echo  [!] Could not reach the server at %API%
    echo      If this is localhost, start Laravel first:
    echo          cd web-app ^&^& php artisan serve
    echo.
    choice /C YN /M "Launch anyway"
    if errorlevel 2 exit /b 1
)

REM --- Launch ---------------------------------------------------------
cd /d "%APPDIR%"

if "%~1"=="" (
    REM No URL given: the app's own default is already localhost:8000/api
    mvn javafx:run
) else (
    REM Pass the override through to the forked JVM
    mvn javafx:run -Djavafx.options=-Daces.api=%API%
)

endlocal