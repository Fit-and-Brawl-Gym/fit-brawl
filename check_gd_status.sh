#!/bin/bash

echo "=========================================="
echo "  GD Extension Status Check"
echo "=========================================="
echo ""

# Check XAMPP php.ini (the one Apache uses)
XAMPP_PHP_INI="/c/xampp/php/php.ini"

echo "Checking XAMPP php.ini configuration..."
if grep -q "^extension=gd$" "$XAMPP_PHP_INI"; then
    echo "✓ GD extension is ENABLED in XAMPP php.ini"
else
    echo "✗ GD extension is DISABLED in XAMPP php.ini"
    echo "  Location: $XAMPP_PHP_INI"
    echo "  Run: sed -i 's/^;extension=gd$/extension=gd/' $XAMPP_PHP_INI"
fi

echo ""
echo "Checking if GD is loaded in PHP..."
if php -r "exit(extension_loaded('gd') ? 0 : 1);" 2>/dev/null; then
    echo "✓ GD extension is LOADED and working"
    echo ""
    echo "EXIF metadata stripping is ACTIVE!"
    echo ""
    echo "To verify, check error logs after uploading an image."
    echo "You should see: 'SUCCESS: EXIF metadata stripped from image'"
else
    echo "✗ GD extension is NOT LOADED"
    echo ""
    echo "ACTION REQUIRED:"
    echo "1. Ensure php.ini has 'extension=gd' (not commented)"
    echo "2. Restart Apache via XAMPP Control Panel"
    echo "3. Run this script again to verify"
fi

echo ""
echo "=========================================="
