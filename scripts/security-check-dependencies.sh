#!/bin/bash
# Dependency Vulnerability Scanning Script
# Scans for known vulnerabilities in project dependencies

echo "============================================="
echo "🔍 Dependency Vulnerability Scan"
echo "============================================="
echo ""

# Check if composer.json exists
if [ -f "composer.json" ]; then
    echo "📦 Checking PHP dependencies (Composer)..."
    if command -v composer &> /dev/null; then
        echo "Running: composer audit"
        composer audit --format=table
        if [ $? -eq 0 ]; then
            echo "✅ Composer audit completed"
        else
            echo "⚠️  Composer audit found vulnerabilities or failed"
        fi
    else
        echo "⚠️  Composer not installed. Install it to scan PHP dependencies."
        echo "   Visit: https://getcomposer.org/"
    fi
    echo ""
fi

# Check if package.json exists
if [ -f "server-renderer/package.json" ]; then
    echo "📦 Checking Node.js dependencies (npm)..."
    cd server-renderer
    if command -v npm &> /dev/null; then
        echo "Running: npm audit"
        npm audit --audit-level=moderate
        if [ $? -eq 0 ]; then
            echo "✅ npm audit completed"
        else
            echo "⚠️  npm audit found vulnerabilities"
        fi
    else
        echo "⚠️  npm not installed. Install Node.js to scan dependencies."
    fi
    cd ..
    echo ""
fi

# Check for outdated packages
if [ -f "composer.json" ] && command -v composer &> /dev/null; then
    echo "📋 Checking for outdated Composer packages..."
    composer outdated --direct
    echo ""
fi

if [ -f "server-renderer/package.json" ] && command -v npm &> /dev/null; then
    echo "📋 Checking for outdated npm packages..."
    cd server-renderer
    npm outdated
    cd ..
    echo ""
fi

echo "============================================="
echo "✅ Dependency scan complete"
echo "============================================="
echo ""
echo "📝 Recommendations:"
echo "  1. Review and update packages with known vulnerabilities"
echo "  2. Run this script regularly (weekly/monthly)"
echo "  3. Consider adding to CI/CD pipeline"
echo "  4. Document any exceptions for vulnerable packages"

