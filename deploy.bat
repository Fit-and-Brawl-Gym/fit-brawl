@echo off
REM Deployment script for Windows

echo ========================================
echo   FIT AND BRAWL - DEPLOY TO GOOGLE CLOUD
echo ========================================
echo.

cd /d %~dp0

echo [1/3] Checking gcloud installation...
where gcloud >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: gcloud is not installed or not in PATH!
    echo.
    echo Please install Google Cloud SDK from:
    echo https://cloud.google.com/sdk/docs/install
    echo.
    pause
    exit /b 1
)
echo OK - gcloud found
echo.

echo [2/3] Checking project files...
if not exist "app.yaml" (
    echo ERROR: app.yaml not found!
    pause
    exit /b 1
)
if not exist "health.php" (
    echo ERROR: health.php not found!
    pause
    exit /b 1
)
if not exist "test.php" (
    echo ERROR: test.php not found!
    pause
    exit /b 1
)
echo OK - Required files found
echo.

echo [3/3] Deploying to App Engine...
echo This will take 5-10 minutes...
echo.
gcloud app deploy

if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo   DEPLOYMENT SUCCESSFUL!
    echo ========================================
    echo.
    echo Test these URLs:
    echo.
    echo 1. Health check:
    echo    https://fit-and-brawl-gym.appspot.com/health.php
    echo.
    echo 2. Test page:
    echo    https://fit-and-brawl-gym.appspot.com/test.php
    echo.
    echo 3. Homepage:
    echo    https://fit-and-brawl-gym.appspot.com/
    echo.
) else (
    echo.
    echo ========================================
    echo   DEPLOYMENT FAILED!
    echo ========================================
    echo.
    echo Check the error messages above.
    echo.
)

pause
