# Build and test script for local development
# Verifies that everything compiles/installs correctly before deployment

Write-Host "🔧 Building Fit & Brawl project..." -ForegroundColor Cyan

# Check Node.js
Write-Host "`n→ Checking Node.js..." -ForegroundColor Blue
try {
    $nodeVersion = node --version
    Write-Host "✓ Node.js $nodeVersion installed" -ForegroundColor Green
} catch {
    Write-Host "✗ Node.js not found. Please install Node.js first." -ForegroundColor Red
    exit 1
}

# Install server-renderer dependencies
Write-Host "`n→ Installing server-renderer dependencies..." -ForegroundColor Blue
Push-Location server-renderer
try {
    npm ci --no-audit --no-fund
    Write-Host "✓ Dependencies installed" -ForegroundColor Green
} catch {
    Write-Host "⚠ npm ci failed, trying npm install..." -ForegroundColor Yellow
    npm install --no-audit --no-fund
}
Pop-Location

# Check Puppeteer/Chromium
Write-Host "`n→ Verifying Puppeteer/Chromium..." -ForegroundColor Blue
Push-Location server-renderer
try {
    $chromePath = node -e "import puppeteer from 'puppeteer';console.log(puppeteer.executablePath());"
    Write-Host "✓ Chromium installed at: $chromePath" -ForegroundColor Green
} catch {
    Write-Host "✗ Puppeteer/Chromium check failed" -ForegroundColor Red
}
Pop-Location

# Check .env file
Write-Host "`n→ Checking environment configuration..." -ForegroundColor Blue
if (Test-Path .env) {
    Write-Host "✓ .env file exists" -ForegroundColor Green
    
    # Read and validate basic env vars
    $envContent = Get-Content .env
    $hasDbConfig = $envContent | Where-Object { $_ -match "DB_HOST|DB_NAME" }
    $hasEmailConfig = $envContent | Where-Object { $_ -match "EMAIL_HOST|EMAIL_USER" }
    
    if ($hasDbConfig) {
        Write-Host "  ✓ Database configuration found" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Database configuration missing" -ForegroundColor Yellow
    }
    
    if ($hasEmailConfig) {
        Write-Host "  ✓ Email configuration found" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Email configuration missing" -ForegroundColor Yellow
    }
} else {
    Write-Host "⚠ .env file not found. Copy .env.example to .env and configure it." -ForegroundColor Yellow
}

# Check critical directories
Write-Host "`n→ Checking upload directories..." -ForegroundColor Blue
$uploadDirs = @("uploads/avatars", "uploads/receipts", "uploads/equipment", "uploads/products")
foreach ($dir in $uploadDirs) {
    if (Test-Path $dir) {
        Write-Host "  ✓ $dir exists" -ForegroundColor Green
    } else {
        Write-Host "  ⚠ Creating $dir..." -ForegroundColor Yellow
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Host "  ✓ $dir created" -ForegroundColor Green
    }
}

Write-Host "`n✅ Build completed successfully!" -ForegroundColor Green
Write-Host "`n📋 Next steps:" -ForegroundColor Cyan
Write-Host "  1. Ensure your database is running and seeded" -ForegroundColor White
Write-Host "  2. Configure your web server (Apache/Nginx)" -ForegroundColor White
Write-Host "  3. Start the renderer service: cd server-renderer && node server.js" -ForegroundColor White
Write-Host "  4. Visit your application in a browser" -ForegroundColor White
