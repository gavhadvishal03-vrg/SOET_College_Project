@echo off
title SOET MGM University Portal Server Launcher
color 0A

echo ======================================================================
echo SOET MGM UNIVERSITY PORTAL - SERVER LAUNCHER
echo ======================================================================
echo.

if exist "C:\xampp\php\php.exe" (
    set PHP_CMD=C:\xampp\php\php.exe
) else (
    set PHP_CMD=php
)

echo [1/3] Checking MySQL Database Service...
"%PHP_CMD%" -r "try { new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); echo 'MySQL Status: CONNECTED OK' . PHP_EOL; } catch(Exception $e) { @exec('start C:\xampp\mysql_start.bat'); echo 'MySQL Status: Auto-Started XAMPP MySQL Service!' . PHP_EOL; }"

echo.
echo [2/3] Starting Web Server on http://localhost:8000 ...
start "SOET Web Server" /B "%PHP_CMD%" -S localhost:8000 -t "%~dp0"

echo.
echo [3/3] Launching Web Browser...
start "" "http://localhost:8000/index.php"
start "" "http://localhost:8000/admin/dashboard.php"

echo.
echo ======================================================================
echo SUCCESS: Server is active at http://localhost:8000
echo ======================================================================
