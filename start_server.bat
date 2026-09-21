@echo off
title Resort Booking & Activity Management System
color 0b

echo ================================================================
echo    RESORT BOOKING & MANAGEMENT SYSTEM (PHP + MySQL)
echo ================================================================
echo.

:: 1. Locate PHP executable
set PHP_CMD=php
where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" (
        set PHP_CMD=C:\xampp\php\php.exe
    ) else (
        echo [ERROR] PHP executable was not found on your system or in C:\xampp\php\
        echo Please ensure XAMPP is installed or PHP is in your system PATH.
        echo.
        pause
        exit /b 1
    )
)

echo [OK] Using PHP: %PHP_CMD%
echo [INFO] Starting Resort Booking System on http://localhost:8000 ...
echo [TIP] Make sure MySQL is running in your XAMPP Control Panel!
echo.
echo Press Ctrl+C in this window anytime to stop the server.
echo.

:: Open default browser in 2 seconds
start "" http://localhost:8000

:: Start PHP built-in server
"%PHP_CMD%" -S localhost:8000
pause
