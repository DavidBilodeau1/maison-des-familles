@echo off
set CLOUDFLARE_TOKEN=votre-token-cloudflare-ici

"%~dp0cloudflared.exe" tunnel run --token %CLOUDFLARE_TOKEN%
pause
