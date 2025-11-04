@echo off
REM Check deployment status

echo ========================================
echo   CHECKING DEPLOYMENT STATUS
echo ========================================
echo.

echo [1] Checking if gcloud is installed...
where gcloud >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: gcloud not found in PATH
    echo.
    echo Searching for gcloud installation...
    dir "C:\Program Files (x86)\Google\Cloud SDK" /s /b 2>nul | findstr "gcloud.cmd" | findstr /v "alpha beta"
    echo.
    echo If found above, use that path to deploy.
    echo Otherwise, install from: https://cloud.google.com/sdk/docs/install
    pause
    exit /b 1
)
echo OK - gcloud found
echo.

echo [2] Checking Google Cloud authentication...
gcloud auth list
echo.

echo [3] Checking current project...
gcloud config get-value project
echo.

echo [4] Checking App Engine versions...
echo.
gcloud app versions list --project=fit-and-brawl-gym 2>&1
echo.

echo ========================================
echo   DEPLOYMENT STATUS CHECK COMPLETE
echo ========================================
echo.
echo If no versions are listed above, you need to deploy!
echo Run: gcloud app deploy
echo.

pause
