@echo off
setlocal EnableDelayedExpansion
title Serveur de Photos - Maison des Familles

cls
echo.
echo  ============================================================
echo    SERVEUR DE PHOTOS - MAISON DES FAMILLES
echo  ============================================================
echo.

:: Lire le fichier de configuration
set "CONF=%~dp0server.conf"
if not exist "!CONF!" (
    echo  [ERREUR] Fichier server.conf introuvable.
    echo.
    echo  Assurez-vous que server.conf est dans le meme dossier que ce script.
    echo.
    pause & exit /b 1
)

for /f "usebackq eol=# tokens=1,* delims==" %%A in ("!CONF!") do (
    set "_k=%%A"
    set "_v=%%B"
    for /f "tokens=* delims= " %%C in ("!_k!") do set "_k=%%C"
    if not "!_k!"=="" if not "!_v!"=="" set "!_k!=!_v!"
)

if not defined PORT set "PORT=8091"

:: Verifier que la configuration a ete remplie
if "!WEBHOOK_SECRET!"=="changez-moi" (
    echo  [ERREUR] Vous devez configurer WEBHOOK_SECRET dans server.conf
    echo.
    echo  Ouvrez server.conf avec le Bloc-notes et modifiez la valeur.
    echo.
    pause & exit /b 1
)
if "!CLOUDFLARE_TOKEN!"=="votre-token-cloudflare-ici" (
    echo  [ERREUR] Vous devez configurer CLOUDFLARE_TOKEN dans server.conf
    echo.
    echo  Ouvrez server.conf avec le Bloc-notes et ajoutez votre token Cloudflare.
    echo.
    pause & exit /b 1
)

:: Trouver PHP
set "PHP_EXE="
where php >nul 2>&1
if not errorlevel 1 set "PHP_EXE=php"

if "!PHP_EXE!"=="" (
    if exist "%~dp0php\php.exe" set "PHP_EXE=%~dp0php\php.exe"
)
if "!PHP_EXE!"=="" (
    if exist "C:\php\php.exe" set "PHP_EXE=C:\php\php.exe"
)
if "!PHP_EXE!"=="" (
    if exist "C:\php8\php.exe" set "PHP_EXE=C:\php8\php.exe"
)

if "!PHP_EXE!"=="" (
    echo  [ERREUR] PHP n'est pas installe ou introuvable.
    echo.
    echo  Pour installer PHP :
    echo    1. Allez sur https://windows.php.net/download/
    echo    2. Telechargez "VS16 x64 Non Thread Safe" (fichier .zip)
    echo    3. Extrayez le contenu dans C:\php\
    echo    4. Relancez ce script.
    echo.
    echo  OU placez le dossier "php" directement a cote de ce script.
    echo.
    pause & exit /b 1
)

:: Trouver cloudflared
set "CF_EXE="
where cloudflared >nul 2>&1
if not errorlevel 1 set "CF_EXE=cloudflared"

if "!CF_EXE!"=="" (
    if exist "%~dp0cloudflared.exe" set "CF_EXE=%~dp0cloudflared.exe"
)

if "!CF_EXE!"=="" (
    echo  [ERREUR] cloudflared.exe introuvable.
    echo.
    echo  Pour l'obtenir :
    echo    1. Allez sur https://github.com/cloudflare/cloudflared/releases/latest
    echo    2. Telechargez "cloudflared-windows-amd64.exe"
    echo    3. Renommez-le "cloudflared.exe"
    echo    4. Placez-le dans le meme dossier que ce script.
    echo    5. Relancez ce script.
    echo.
    pause & exit /b 1
)

:: Verifier si le port est deja utilise
netstat -an 2>nul | find "127.0.0.1:!PORT!" >nul 2>&1
if not errorlevel 1 (
    echo  [ERREUR] Le port !PORT! est deja utilise.
    echo.
    echo  Le serveur est peut-etre deja en cours d'execution.
    echo  Fermez l'autre fenetre ou lancez ARRETER.bat, puis reessayez.
    echo.
    pause & exit /b 1
)

:: Creer les dossiers si necessaire
if not exist "!UPLOADS_DIR!" (
    echo  Creation du dossier photos : !UPLOADS_DIR!
    mkdir "!UPLOADS_DIR!" 2>nul
)
if not exist "!FINAL_DIR!" (
    echo  Creation du dossier selections : !FINAL_DIR!
    mkdir "!FINAL_DIR!" 2>nul
)

:: Afficher le resume
echo  [OK] PHP         : !PHP_EXE!
echo  [OK] cloudflared : !CF_EXE!
echo  [OK] Photos      : !UPLOADS_DIR!
echo  [OK] Selections  : !FINAL_DIR!
echo  [OK] Port        : !PORT!
echo.
echo  Demarrage en cours...
echo.

:: Demarrer le serveur PHP
set UPLOADS_DIR=!UPLOADS_DIR!
set FINAL_DIR=!FINAL_DIR!
set WEBHOOK_SECRET=!WEBHOOK_SECRET!

start "PHP-Photos" /min "!PHP_EXE!" -S localhost:!PORT! "%~dp0router.php"
timeout /t 2 /nobreak >nul

netstat -an 2>nul | find "127.0.0.1:!PORT!" >nul 2>&1
if errorlevel 1 (
    echo  [ERREUR] Le serveur PHP n'a pas pu demarrer.
    echo  Verifiez que PHP est correctement installe.
    echo.
    pause & exit /b 1
)
echo  [OK] Serveur PHP demarre sur le port !PORT!

:: Demarrer le tunnel Cloudflare
start "CF-Tunnel" /min "!CF_EXE!" tunnel run --token !CLOUDFLARE_TOKEN!
timeout /t 4 /nobreak >nul
echo  [OK] Tunnel Cloudflare demarre

:: Message final
echo.
echo  ============================================================
echo.
echo    Le serveur est EN COURS D'EXECUTION.
echo.
echo    Les familles peuvent maintenant faire leur selection.
echo.
echo    IMPORTANT : Ne fermez pas cette fenetre !
echo    Pour arreter : appuyez sur une touche,
echo                   ou double-cliquez sur ARRETER.bat
echo.
echo  ============================================================
echo.
pause >nul

:: Arreter au moment de quitter
echo  Arret du serveur...
taskkill /f /fi "WINDOWTITLE eq PHP-Photos" >nul 2>&1
taskkill /f /fi "WINDOWTITLE eq CF-Tunnel"  >nul 2>&1
echo  Serveur arrete. Vous pouvez fermer cette fenetre.
pause
