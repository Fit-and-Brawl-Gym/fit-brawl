<?php
/**
 * Encryption Key Generator
 *
 * Run this script to generate a secure AES-256 encryption key
 *
 * Usage: php generate_encryption_key.php
 */

require_once __DIR__ . '/includes/encryption.php';

echo "\n";
echo "========================================\n";
echo "  AES-256 Encryption Key Generator\n";
echo "========================================\n\n";

// Generate key
$key = Encryption::generateKey();

echo "✅ Generated 256-bit encryption key:\n\n";
echo "   " . $key . "\n\n";

echo "📋 Next Steps:\n\n";

echo "1. Add to .env file (RECOMMENDED):\n";
echo "   ─────────────────────────────────\n";
echo "   ENCRYPTION_KEY=$key\n\n";

echo "2. OR add to config.php (Development only):\n";
echo "   ──────────────────────────────────────────\n";
echo "   define('ENCRYPTION_KEY', hex2bin('$key'));\n\n";

echo "⚠️  SECURITY WARNINGS:\n";
echo "   • Never commit this key to Git\n";
echo "   • Add .env to .gitignore\n";
echo "   • Use different keys for dev/staging/production\n";
echo "   • Store keys securely (password manager, key vault)\n\n";

echo "✅ Test encryption:\n";
echo "   ─────────────────\n";
echo "   php test_encryption.php\n\n";

echo "========================================\n\n";
