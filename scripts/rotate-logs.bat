@echo off
REM Log Rotation Script for Fit & Brawl (Windows)
REM Rotates PHP error logs, application logs, and centralized logs

setlocal enabledelayedexpansion

REM Configuration
set "SCRIPT_DIR=%~dp0"
set "LOG_DIR=%SCRIPT_DIR%..\logs"
set "MAX_LOG_SIZE=10485760"
set "KEEP_DAYS=30"

echo =========================================
echo Log Rotation Script (Windows)
echo =========================================
echo Log Directory: %LOG_DIR%
echo Max Log Size: 10MB
echo Keep Days: %KEEP_DAYS%
echo =========================================
echo.

REM Create logs directory if it doesn't exist
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

REM Function to get file size and rotate if needed
call :rotate_log "%LOG_DIR%\php_errors.log"
call :rotate_log "%LOG_DIR%\application.log"
call :rotate_log "%LOG_DIR%\security.log"
call :rotate_log "%LOG_DIR%\activity.log"
call :rotate_log "%LOG_DIR%\database.log"
call :rotate_log "%LOG_DIR%\email.log"

echo.
echo Cleaning logs older than %KEEP_DAYS% days...

REM Delete old rotated logs (*.log.1, *.log.2, etc.) older than KEEP_DAYS
forfiles /P "%LOG_DIR%" /M *.log.* /D -%KEEP_DAYS% /C "cmd /c echo [DELETED] @file & del @path" 2>nul
if errorlevel 1 (
    echo [OK] No old logs to delete
)

echo.
echo =========================================
echo Log rotation completed successfully!
echo =========================================

goto :end

:rotate_log
set "log_file=%~1"
if not exist "%log_file%" (
    echo [SKIP] %~nx1 ^(file not found^)
    goto :eof
)

for %%A in ("%log_file%") do set "file_size=%%~zA"
set /a "size_kb=!file_size! / 1024"
set /a "size_mb=!file_size! / 1048576"

if !file_size! LSS %MAX_LOG_SIZE% (
    echo [OK] %~nx1 ^(!size_kb!KB - no rotation needed^)
    goto :eof
)

echo [ROTATE] %~nx1 ^(!size_mb!MB^)

REM Rotate existing numbered logs (shift them up)
for /L %%i in (9,-1,1) do (
    if exist "%log_file%.%%i" (
        set /a "next=%%i + 1"
        move /Y "%log_file%.%%i" "%log_file%.!next!" >nul 2>&1
    )
)

REM Rotate current log to .1
move /Y "%log_file%" "%log_file%.1" >nul 2>&1

REM Create new empty log file
type nul > "%log_file%"

echo [SUCCESS] %~nx1 rotated
goto :eof

:end
pause
exit /b 0
