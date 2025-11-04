#!/bin/bash

# Fit & Brawl - Google Cloud Deployment Script
# This script automates the deployment process

set -e  # Exit on error

echo "==================================="
echo "Fit & Brawl - GCP Deployment Script"
echo "==================================="
echo ""

# Check if gcloud is installed
if ! command -v gcloud &> /dev/null; then
    echo "❌ Error: gcloud CLI is not installed"
    echo "Please install it from: https://cloud.google.com/sdk/docs/install"
    exit 1
fi

# Variables
PROJECT_ID="fit-and-brawl-gym"
REGION="us-central1"
DB_INSTANCE="fit-brawl-db"
DB_NAME="fit_and_brawl_gym"
CLOUD_RUN_SERVICE="receipt-renderer"
STORAGE_BUCKET="fit-brawl-uploads"

echo "Project Configuration:"
echo "  Project ID: $PROJECT_ID"
echo "  Region: $REGION"
echo "  Database: $DB_INSTANCE"
echo ""

# Prompt for confirmation
read -p "Continue with deployment? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Deployment cancelled."
    exit 0
fi

echo ""
echo "Step 1: Setting up Google Cloud Project"
echo "----------------------------------------"
gcloud config set project $PROJECT_ID

echo ""
echo "Step 2: Enabling required APIs"
echo "------------------------------"
gcloud services enable appengine.googleapis.com
gcloud services enable run.googleapis.com
gcloud services enable sqladmin.googleapis.com
gcloud services enable storage.googleapis.com
gcloud services enable containerregistry.googleapis.com
gcloud services enable cloudbuild.googleapis.com

echo ""
echo "Step 3: Deploying Receipt Renderer to Cloud Run"
echo "------------------------------------------------"
gcloud builds submit --config=cloudbuild.yaml

echo ""
echo "Getting Cloud Run service URL..."
CLOUD_RUN_URL=$(gcloud run services describe $CLOUD_RUN_SERVICE \
    --region=$REGION \
    --format='value(status.url)')
echo "Cloud Run URL: $CLOUD_RUN_URL"

echo ""
echo "⚠️  IMPORTANT: Update app.yaml with the following:"
echo "  RECEIPT_RENDERER_URL: '$CLOUD_RUN_URL'"
echo ""
read -p "Press Enter after updating app.yaml..."

echo ""
echo "Step 4: Deploying to App Engine"
echo "--------------------------------"
gcloud app deploy --quiet

echo ""
echo "Step 5: Creating Cloud Storage bucket"
echo "--------------------------------------"
gsutil mb -p $PROJECT_ID -l $REGION gs://$STORAGE_BUCKET/ 2>/dev/null || echo "Bucket already exists"
gsutil iam ch allUsers:objectViewer gs://$STORAGE_BUCKET

echo ""
echo "✅ Deployment Complete!"
echo "======================="
echo ""
echo "Your application is now running at:"
gcloud app browse --no-launch-uri
echo ""
echo "Cloud Run Service URL: $CLOUD_RUN_URL"
echo "Storage Bucket: gs://$STORAGE_BUCKET"
echo ""
echo "Next steps:"
echo "  1. Import database schema to Cloud SQL"
echo "  2. Test all features"
echo "  3. Set up monitoring alerts"
echo ""
