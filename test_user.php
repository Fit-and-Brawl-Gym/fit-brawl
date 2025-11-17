<?php
/**
 * Quick Test Script - Check User Encryption
 *
 * Usage: php test_user.php [email]
 * Example: php test_user.php admin@fitxbrawl.com
 */

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db_connect.php';
require __DIR__ . '/includes/encryption.php';

// Get email from command line or use default
$email = $argv[1] ?? 'admin@fitxbrawl.com';

echo "\n========================================\n";
echo "  User Encryption Check\n";
echo "========================================\n\n";

// Query user
$stmt = $conn->prepare("SELECT id, email, email_encrypted FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo "❌ User not found: $email\n\n";
    exit(1);
}

// Display results
echo "✅ User found!\n\n";
echo "ID:                " . $row['id'] . "\n";
echo "Email (plaintext): " . $row['email'] . "\n";
echo "Encrypted:         " . substr($row['email_encrypted'], 0, 50) . "...\n\n";

// Test decryption
if (!empty($row['email_encrypted'])) {
    try {
        $decrypted = Encryption::decrypt($row['email_encrypted']);
        echo "Decrypted:         " . $decrypted . "\n\n";

        if ($decrypted === $row['email']) {
            echo "✅ Decryption PASSED - Matches original!\n";
        } else {
            echo "❌ Decryption FAILED - Does not match!\n";
            echo "   Expected: " . $row['email'] . "\n";
            echo "   Got:      " . $decrypted . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Decryption ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  No encrypted email found for this user\n";
}

echo "\n========================================\n\n";
