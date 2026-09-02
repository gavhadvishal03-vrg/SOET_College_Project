@echo off
title SOET MGM University Portal — 1-Click Server Launcher
color 0A

echo ======================================================================
echo SOET MGM UNIVERSITY PORTAL - 1-CLICK LAUNCHER
echo ======================================================================
echo.

if exist "C:\xampp\php\php.exe" (
    set PHP_CMD=C:\xampp\php\php.exe
) else (
    set PHP_CMD=php
)

echo [1/3] Checking MySQL Database Service & Auto-Initializing...
"%PHP_CMD%" -r "try { new PDO('mysql:host=127.0.0.1;port=3307', 'root', ''); } catch(Exception $e) { @exec('start C:\xampp\mysql_start.bat'); }"
"%PHP_CMD%" "%~dp0setup.php"

echo.
echo [2/3] Starting Web Server on http://localhost:8000 ...
echo.
echo ======================================================================
echo  PORTAL URL : http://localhost:8000
echo  ADMIN URL  : http://localhost:8000/admin/login.php
echo ======================================================================
echo.

start "" "http://localhost:8000"

"%PHP_CMD%" -S localhost:8000 -t "%~dp0"
pause
