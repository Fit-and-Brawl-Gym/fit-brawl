@echo off
REM Fit & Brawl - Google Cloud Deployment Helper Script
REM This script guides you through the deployment process

echo ========================================
echo   FIT ^& BRAWL - DEPLOYMENT HELPER
echo ========================================
echo.

REM Check if gcloud is installed
where gcloud >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Google Cloud SDK is not installed or not in PATH
    echo.
    echo Please install it from:
    echo https://cloud.google.com/sdk/docs/install
    echo.
    echo After installation, restart this script.
    pause
    exit /b 1
)

echo [OK] Google Cloud SDK is installed
gcloud --version
echo.

echo ========================================
echo   PHASE 1: GOOGLE CLOUD LOGIN
echo ========================================
echo.
echo This will open your browser to login to Google Cloud
pause

gcloud auth login

echo.
echo ========================================
echo   PHASE 2: CREATE PROJECT
echo ========================================
echo.
echo Creating project: fit-and-brawl-gym
pause

gcloud projects create fit-and-brawl-gym --name="Fit and Brawl Gym"
gcloud config set project fit-and-brawl-gym

echo.
echo [IMPORTANT] You need to enable billing for this project
echo.
echo 1. Open: https://console.cloud.google.com/billing
echo 2. Link a billing account to 'fit-and-brawl-gym'
echo 3. Press any key when done...
pause

echo.
echo ========================================
echo   PHASE 3: ENABLE APIS
echo ========================================
echo.
echo Enabling required APIs (this may take a few minutes)...

gcloud services enable appengine.googleapis.com
gcloud services enable run.googleapis.com
gcloud services enable sqladmin.googleapis.com
gcloud services enable storage.googleapis.com
gcloud services enable containerregistry.googleapis.com
gcloud services enable cloudbuild.googleapis.com

echo [OK] APIs enabled
echo.

echo ========================================
echo   PHASE 4: CREATE APP ENGINE
echo ========================================
echo.
echo Choose a region close to your users
echo Recommended: asia-southeast1 (Singapore)
pause

gcloud app create --region=asia-southeast1

echo.
echo ========================================
echo   PHASE 5: CLOUD SQL DATABASE
echo ========================================
echo.
echo [IMPORTANT] Choose a SECURE password for your database
echo Write it down! You'll need it for app.yaml
echo.
set /p DB_PASSWORD="Enter database password: "

echo.
echo Creating Cloud SQL instance (this takes 5-10 minutes)...
gcloud sql instances create fit-brawl-db --database-version=MYSQL_8_0 --tier=db-f1-micro --region=asia-southeast1 --root-password=%DB_PASSWORD% --backup --backup-start-time=03:00 --maintenance-window-day=SUN --maintenance-window-hour=3

echo.
echo Creating database...
gcloud sql databases create fit_and_brawl_gym --instance=fit-brawl-db

echo.
echo Uploading schema to Cloud Storage...
gsutil mb gs://fit-brawl-temp
gsutil cp docs/database/schema.sql gs://fit-brawl-temp/

echo.
echo Importing schema to Cloud SQL...
gcloud sql import sql fit-brawl-db gs://fit-brawl-temp/schema.sql --database=fit_and_brawl_gym

echo [OK] Database created and schema imported
echo.

echo ========================================
echo   PHASE 6: GMAIL APP PASSWORD
echo ========================================
echo.
echo [ACTION REQUIRED]
echo.
echo 1. Enable 2FA: https://myaccount.google.com/security
echo 2. Create App Password: https://myaccount.google.com/apppasswords
echo    - App: Mail
echo    - Device: Other (Fit & Brawl)
echo 3. Copy the 16-character password
echo.
set /p GMAIL_APP_PASSWORD="Enter Gmail App Password (with spaces): "
echo.

echo ========================================
echo   PHASE 7: DEPLOY RECEIPT RENDERER
echo ========================================
echo.
echo Installing Node.js dependencies...
cd server-renderer
call npm install
cd ..

echo.
echo Deploying to Cloud Run (this takes 5-8 minutes)...
gcloud builds submit --config=cloudbuild.yaml

echo.
echo Getting Cloud Run URL...
for /f "delims=" %%i in ('gcloud run services describe receipt-renderer --region^=asia-southeast1 --format^="value(status.url)"') do set CLOUD_RUN_URL=%%i

echo.
echo [OK] Cloud Run URL: %CLOUD_RUN_URL%
echo.

echo ========================================
echo   PHASE 8: UPDATE app.yaml
echo ========================================
echo.
echo IMPORTANT: Update app.yaml with these values:
echo.
echo DB_PASS: %DB_PASSWORD%
echo APP_URL: https://fit-and-brawl-gym.appspot.com
echo RECEIPT_RENDERER_URL: %CLOUD_RUN_URL%
echo EMAIL_PASS: %GMAIL_APP_PASSWORD%
echo.
echo Opening app.yaml in notepad...
start notepad app.yaml
echo.
echo Press any key after you've updated app.yaml...
pause

echo.
echo ========================================
echo   PHASE 9: INSTALL PHP DEPENDENCIES
echo ========================================
echo.
echo Installing Composer dependencies...
call composer install

echo.
echo ========================================
echo   PHASE 10: DEPLOY TO APP ENGINE
echo ========================================
echo.
echo Deploying application (this takes 5-10 minutes)...
gcloud app deploy

echo.
echo ========================================
echo   PHASE 11: CLOUD STORAGE
echo ========================================
echo.
echo Creating storage bucket...
gsutil mb gs://fit-brawl-uploads
gsutil iam ch allUsers:objectViewer gs://fit-brawl-uploads

echo.
echo ========================================
echo   DEPLOYMENT COMPLETE!
echo ========================================
echo.
echo Your application is now live at:
gcloud app browse --no-launch-uri
echo.
echo Cloud Run URL: %CLOUD_RUN_URL%
echo Storage Bucket: gs://fit-brawl-uploads
echo.
echo Next steps:
echo 1. Test your application
echo 2. Set up budget alerts
echo 3. Enable monitoring
echo.
echo See DEPLOYMENT_STEPS.md for detailed testing instructions
echo.
pause
