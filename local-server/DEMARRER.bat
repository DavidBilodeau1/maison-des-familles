@echo off

rem ===========================================================
rem  CONFIGURATION - Ouvrez ce fichier avec le Bloc-notes
rem  et modifiez les lignes ci-dessous avant de lancer
rem ===========================================================
set UPLOADS_DIR=C:\Photos\uploads
set FINAL_DIR=C:\Photos\final_choices
set WEBHOOK_SECRET=changez-moi
set CLOUDFLARE_TOKEN=votre-token-cloudflare-ici
set PORT=8091
rem ===========================================================

cls
echo.
echo  SERVEUR DE PHOTOS - MAISON DES FAMILLES
echo  ===========================================================
echo.

rem Verifier que la configuration a ete remplie
if "%WEBHOOK_SECRET%"=="changez-moi" goto err_config
if "%CLOUDFLARE_TOKEN%"=="votre-token-cloudflare-ici" goto err_config

rem ----------------------------------------------------------
rem Trouver PHP
rem ----------------------------------------------------------
set PHP_EXE=
where php >nul 2>&1
if not errorlevel 1 set PHP_EXE=php
if "%PHP_EXE%"=="" if exist "%~dp0php\php.exe" set PHP_EXE=%~dp0php\php.exe
if "%PHP_EXE%"=="" if exist "C:\php\php.exe" set PHP_EXE=C:\php\php.exe
if "%PHP_EXE%"=="" goto err_php

rem ----------------------------------------------------------
rem Trouver cloudflared
rem ----------------------------------------------------------
set CF_EXE=
where cloudflared >nul 2>&1
if not errorlevel 1 set CF_EXE=cloudflared
if "%CF_EXE%"=="" if exist "%~dp0cloudflared.exe" set CF_EXE=%~dp0cloudflared.exe
if "%CF_EXE%"=="" goto err_cf

rem ----------------------------------------------------------
rem Creer les dossiers si necessaire
rem ----------------------------------------------------------
if not exist "%UPLOADS_DIR%" mkdir "%UPLOADS_DIR%"
if not exist "%FINAL_DIR%" mkdir "%FINAL_DIR%"

rem ----------------------------------------------------------
rem Afficher le resume
rem ----------------------------------------------------------
echo  [OK] PHP         : %PHP_EXE%
echo  [OK] cloudflared : %CF_EXE%
echo  [OK] Photos      : %UPLOADS_DIR%
echo  [OK] Selections  : %FINAL_DIR%
echo  [OK] Port        : %PORT%
echo.
echo  Demarrage en cours...
echo.

rem ----------------------------------------------------------
rem Demarrer le serveur PHP
rem ----------------------------------------------------------
start "PHP-Photos" /min "%PHP_EXE%" -S localhost:%PORT% "%~dp0router.php"
timeout /t 2 /nobreak >nul

netstat -an 2>nul | find ":%PORT%" >nul 2>&1
if errorlevel 1 goto err_php_start

echo  [OK] Serveur PHP demarre sur le port %PORT%

rem ----------------------------------------------------------
rem Demarrer le tunnel Cloudflare
rem ----------------------------------------------------------
start "CF-Tunnel" /min "%CF_EXE%" tunnel run --token %CLOUDFLARE_TOKEN% --config "%~dp0cloudflared-config.yml"
timeout /t 4 /nobreak >nul
echo  [OK] Tunnel Cloudflare demarre

echo.
echo  ===========================================================
echo.
echo    Le serveur EST EN COURS D EXECUTION
echo.
echo    Ne fermez pas cette fenetre !
echo.
echo    Appuyez sur une touche pour arreter le serveur.
echo.
echo  ===========================================================
echo.
pause >nul

echo  Arret du serveur...
taskkill /f /fi "WINDOWTITLE eq PHP-Photos" >nul 2>&1
taskkill /f /fi "WINDOWTITLE eq CF-Tunnel" >nul 2>&1
echo  Serveur arrete.
pause
exit /b 0

rem ----------------------------------------------------------
rem Messages d erreur
rem ----------------------------------------------------------
:err_config
echo  [ERREUR] La configuration n est pas remplie.
echo.
echo  Faites un clic droit sur DEMARRER.bat et choisissez
echo  "Modifier" pour ouvrir le fichier avec le Bloc-notes.
echo  Remplissez les lignes WEBHOOK_SECRET et CLOUDFLARE_TOKEN.
echo.
pause
exit /b 1

:err_php
echo  [ERREUR] PHP introuvable.
echo.
echo  Verifiez que le dossier "php" contenant php.exe
echo  est bien place dans le meme dossier que ce script.
echo.
echo  Chemin verifie : %~dp0php\php.exe
echo.
echo  Si PHP est ailleurs, modifiez ce script et ajoutez :
echo    set PHP_EXE=C:\votre\chemin\vers\php.exe
echo.
pause
exit /b 1

:err_cf
echo  [ERREUR] cloudflared.exe introuvable.
echo.
echo  Placez cloudflared.exe dans le meme dossier que ce script.
echo  Chemin attendu : %~dp0cloudflared.exe
echo.
echo  Telechargement : https://github.com/cloudflare/cloudflared/releases/latest
echo  Fichier a choisir : cloudflared-windows-amd64.exe
echo  Renommez-le en : cloudflared.exe
echo.
pause
exit /b 1

:err_php_start
echo  [ERREUR] Le serveur PHP n a pas pu demarrer sur le port %PORT%.
echo.
echo  Le port est peut-etre deja utilise. Lancez ARRETER.bat
echo  puis reessayez.
echo.
pause
exit /b 1
