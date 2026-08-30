@echo off
title 🚀 Push SOET MGM University Project to GitHub
color 0b

echo ======================================================================
echo 🚀 SOET MGM UNIVERSITY — 1-CLICK GITHUB REPOSITORY PUSHER
echo ======================================================================
echo Target GitHub Profile: https://github.com/gavhadvishal03-vrg
echo Local Repository     : C:\xampp\htdocs\project
echo ======================================================================
echo.

set PATH=C:\Users\morer\AppData\Local\Programs\Verdent\resources\app.asar.unpacked\node_modules\dugite\git\cmd;%PATH%

echo [1/4] Checking Git Status...
git status
echo.

echo [2/4] Verifying Remote Repository...
git remote -v
echo.

echo ======================================================================
echo PUSH OPTIONS:
echo 1. Push using standard Git login (Browser / Credential Manager)
echo 2. Push using GitHub Personal Access Token (PAT)
echo 3. Change target repository URL and push
echo ======================================================================
set /p opt="Select an option (1, 2, or 3) [Default 1]: "

if "%opt%"=="2" goto with_token
if "%opt%"=="3" goto change_repo
goto standard_push

:with_token
echo.
echo Please paste your GitHub Personal Access Token (starts with ghp_...):
echo (Generate token at: https://github.com/settings/tokens)
set /p ghtoken="GitHub Token: "
set /p repo_name="Repository name on GitHub (default: SOET_College_Project): "
if "%repo_name%"=="" set repo_name=SOET_College_Project

echo Pushing to https://github.com/gavhadvishal03-vrg/%repo_name%.git ...
git remote set-url origin https://gavhadvishal03-vrg:%ghtoken%@github.com/gavhadvishal03-vrg/%repo_name%.git
git push -u origin main --force
goto finish

:change_repo
echo.
set /p new_url="Enter full GitHub repository URL (e.g. https://github.com/gavhadvishal03-vrg/repo.git): "
git remote set-url origin %new_url%
git push -u origin main
goto finish

:standard_push
echo.
echo Pushing branch 'main' to GitHub remote origin...
git push -u origin main
goto finish

:finish
echo.
echo ======================================================================
echo PUSH PROCESS FINISHED
echo ======================================================================
pause
