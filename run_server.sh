#!/usr/bin/env bash
# ======================================================================
# SOET MGM University Portal — Linux & macOS 1-Click Server Launcher
# ======================================================================

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

echo "======================================================================"
echo "SOET MGM UNIVERSITY PORTAL - 1-CLICK LAUNCHER (Linux / macOS)"
echo "======================================================================"
echo ""

PHP_CMD=$(which php)

if [ -z "$PHP_CMD" ]; then
    echo "❌ PHP is not installed. Please install PHP 8.0+ to run this server."
    exit 1
fi

echo "[1/2] Auto-Initializing Database & System Setup..."
$PHP_CMD "$DIR/setup.php"

echo ""
echo "[2/2] Starting Web Server on http://localhost:8000 ..."
echo ""
echo "======================================================================"
echo " PORTAL URL : http://localhost:8000"
echo " ADMIN URL  : http://localhost:8000/admin/login.php"
echo "======================================================================"
echo ""

if which xdg-open > /dev/null; then
    xdg-open "http://localhost:8000" &
elif which open > /dev/null; then
    open "http://localhost:8000" &
fi

$PHP_CMD -S localhost:8000 -t "$DIR"
