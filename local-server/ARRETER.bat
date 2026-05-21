@echo off
chcp 65001 >nul 2>&1
title Arret du serveur

echo.
echo  Arret du serveur de photos...
echo.

taskkill /f /fi "WINDOWTITLE eq PHP-Photos" >nul 2>&1
taskkill /f /fi "WINDOWTITLE eq CF-Tunnel"  >nul 2>&1
taskkill /f /im php.exe        >nul 2>&1
taskkill /f /im cloudflared.exe >nul 2>&1

echo  [OK] Serveur arrete.
echo.
pause
