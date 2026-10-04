@echo off
title SOET MGM University Portal - 1-Click Server Launcher
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

echo [1/3] Checking Database Connection and Auto-Initializing System...
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
