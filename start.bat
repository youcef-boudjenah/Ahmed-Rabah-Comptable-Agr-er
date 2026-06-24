@echo off
set PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
cd /d "%~dp0"
"%PHP%" -S localhost:8080 -t public
