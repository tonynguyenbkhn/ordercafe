@echo off
setlocal enabledelayedexpansion

set "PHP=C:\laragon\bin\php\php-8.2.26-Win32-vs16-x64\php.exe"
set "WPPHAR=C:\laragon\bin\php\wp-cli.phar"

if not exist "%PHP%" (
  echo Error: PHP not found at %PHP%
  exit /b 1
)

if not exist "%WPPHAR%" (
  echo Error: wp-cli phar not found at %WPPHAR%
  exit /b 1
)

echo Backup DB...
call "%PHP%" "%WPPHAR%" db export backup.sql
if errorlevel 1 exit /b 1

echo Delete revisions...
for /f "usebackq delims=" %%I in (`"%PHP%" "%WPPHAR%" post list --post_type=revision --format=ids`) do set "REV_IDS=%%I"
if defined REV_IDS (
  call "%PHP%" "%WPPHAR%" post delete !REV_IDS! --force
  if errorlevel 1 exit /b 1
)

echo Delete trashed posts...
set "TRASHED_IDS="
for /f "usebackq delims=" %%I in (`"%PHP%" "%WPPHAR%" post list --post_status=trash --format=ids`) do set "TRASHED_IDS=%%I"
if defined TRASHED_IDS (
  call "%PHP%" "%WPPHAR%" post delete !TRASHED_IDS! --force
  if errorlevel 1 exit /b 1
)

echo Delete spam comments...
set "SPAM_IDS="
for /f "usebackq delims=" %%I in (`"%PHP%" "%WPPHAR%" comment list --status=spam --format=ids`) do set "SPAM_IDS=%%I"
if defined SPAM_IDS (
  call "%PHP%" "%WPPHAR%" comment delete !SPAM_IDS! --force
  if errorlevel 1 exit /b 1
)

echo Delete transients...
call "%PHP%" "%WPPHAR%" transient delete --all
if errorlevel 1 exit /b 1

echo Optimize DB...
call "%PHP%" "%WPPHAR%" db optimize
if errorlevel 1 exit /b 1

echo Done!
