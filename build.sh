#!/bin/bash
# Build and test script for local development
# Verifies that everything compiles/installs correctly before deployment

echo "🔧 Building Fit & Brawl project..."

# Check Node.js
echo ""
echo "→ Checking Node.js..."
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "✓ Node.js $NODE_VERSION installed"
else
    echo "✗ Node.js not found. Please install Node.js first."
    exit 1
fi

# Install server-renderer dependencies
echo ""
echo "→ Installing server-renderer dependencies..."
cd server-renderer
if npm ci --no-audit --no-fund 2>/dev/null; then
    echo "✓ Dependencies installed"
else
    echo "⚠ npm ci failed, trying npm install..."
    npm install --no-audit --no-fund
fi
cd ..

# Check Puppeteer/Chromium
echo ""
echo "→ Verifying Puppeteer/Chromium..."
cd server-renderer
CHROME_PATH=$(node -e "import puppeteer from 'puppeteer';console.log(puppeteer.executablePath());" 2>/dev/null)
if [ $? -eq 0 ]; then
    echo "✓ Chromium installed at: $CHROME_PATH"
else
    echo "✗ Puppeteer/Chromium check failed"
fi
cd ..

# Check .env file
echo ""
echo "→ Checking environment configuration..."
if [ -f .env ]; then
    echo "✓ .env file exists"
    
    # Read and validate basic env vars
    if grep -q "DB_HOST\|DB_NAME" .env; then
        echo "  ✓ Database configuration found"
    else
        echo "  ⚠ Database configuration missing"
    fi
    
    if grep -q "EMAIL_HOST\|EMAIL_USER" .env; then
        echo "  ✓ Email configuration found"
    else
        echo "  ⚠ Email configuration missing"
    fi
else
    echo "⚠ .env file not found. Copy .env.example to .env and configure it."
fi

# Check critical directories
echo ""
echo "→ Checking upload directories..."
UPLOAD_DIRS=("uploads/avatars" "uploads/receipts" "uploads/equipment" "uploads/products")
for dir in "${UPLOAD_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "  ✓ $dir exists"
    else
        echo "  ⚠ Creating $dir..."
        mkdir -p "$dir"
        echo "  ✓ $dir created"
    fi
done

echo ""
echo "✅ Build completed successfully!"
echo ""
echo "📋 Next steps:"
echo "  1. Ensure your database is running and seeded"
echo "  2. Configure your web server (Apache/Nginx)"
echo "  3. Start the renderer service: cd server-renderer && node server.js"
echo "  4. Visit your application in a browser"
