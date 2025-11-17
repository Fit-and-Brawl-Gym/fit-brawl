<?php
/**
 * Database Migration Script - Encrypt Existing Data
 *
 * This script encrypts existing sensitive data in the database
 *
 * ⚠️  IMPORTANT: BACKUP DATABASE BEFORE RUNNING!
 *
 * Usage: php migrate_encrypt_data.php
 *
 * What it does:
 * 1. Adds new encrypted columns to tables
 * 2. Encrypts existing data from plaintext columns
 * 3. Stores encrypted data in new columns
 * 4. Verifies encryption worked correctly
 *
 * After verification, you can manually drop old plaintext columns:
 * ALTER TABLE users DROP COLUMN email, DROP COLUMN phone;
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/encryption.php';
require_once __DIR__ . '/includes/db_connect.php';

// ANSI color codes for better output
define('COLOR_RESET', "\033[0m");
define('COLOR_RED', "\033[31m");
define('COLOR_GREEN', "\033[32m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_BOLD', "\033[1m");

function colorOutput($text, $color) {
    return $color . $text . COLOR_RESET;
}

echo "\n";
echo colorOutput("============================================", COLOR_BOLD) . "\n";
echo colorOutput("  Data Encryption Migration", COLOR_BOLD) . "\n";
echo colorOutput("============================================", COLOR_BOLD) . "\n\n";

// Check if encryption is configured
if (!Encryption::isConfigured()) {
    echo colorOutput("❌ ERROR: Encryption is not configured!", COLOR_RED) . "\n";
    echo "   Run: php generate_encryption_key.php\n\n";
    exit(1);
}

if (!Encryption::selfTest()) {
    echo colorOutput("❌ ERROR: Encryption self-test failed!", COLOR_RED) . "\n\n";
    exit(1);
}

echo colorOutput("✅ Encryption is properly configured", COLOR_GREEN) . "\n\n";

// Confirmation prompt
echo colorOutput("⚠️  WARNING:", COLOR_YELLOW . COLOR_BOLD) . "\n";
echo "   This script will modify your database structure.\n";
echo "   Make sure you have a backup before proceeding!\n\n";
echo "   1. Backup your database\n";
echo "   2. Test in development environment first\n";
echo "   3. Review the changes after migration\n\n";

echo "Type 'YES' to continue or anything else to cancel: ";
$confirmation = trim(fgets(STDIN));

if ($confirmation !== 'YES') {
    echo colorOutput("\n❌ Migration cancelled.\n\n", COLOR_YELLOW);
    exit(0);
}

echo "\n";
echo colorOutput("Starting migration...", COLOR_BLUE) . "\n\n";

$totalEncrypted = 0;
$totalErrors = 0;

// ============================================
// Step 1: Add encrypted columns to users table
// ============================================
echo colorOutput("Step 1: Adding encrypted columns", COLOR_BLUE) . "\n";
echo "─────────────────────────────────────────\n";

// Only add email_encrypted since phone and address columns don't exist yet
$alterQueries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_encrypted TEXT AFTER email"
];

foreach ($alterQueries as $query) {
    if ($conn->query($query)) {
        echo colorOutput("✅ ", COLOR_GREEN) . "Executed: " . substr($query, 0, 60) . "...\n";
    } else {
        echo colorOutput("⚠️  ", COLOR_YELLOW) . "Already exists or error: " . $conn->error . "\n";
    }
}

echo "\n";

// ============================================
// Step 2: Encrypt existing user data
// ============================================
echo colorOutput("Step 2: Encrypting user data", COLOR_BLUE) . "\n";
echo "─────────────────────────────────────────\n";

// Only select email since phone and address don't exist
$result = $conn->query("SELECT id, email FROM users WHERE email IS NOT NULL");

if ($result) {
    $userCount = $result->num_rows;
    echo "Found $userCount users to process\n\n";

    $encryptedCount = 0;

    while ($row = $result->fetch_assoc()) {
        $userId = $row['id'];  // Changed from user_id to id

        try {
            // Encrypt email (required field)
            $encryptedEmail = null;
            if (!empty($row['email'])) {
                $encryptedEmail = Encryption::encrypt($row['email']);
            }

            // Update database - only email (phone and address don't exist in table)
            $stmt = $conn->prepare("
                UPDATE users
                SET email_encrypted = ?
                WHERE id = ?
            ");

            $stmt->bind_param("ss", $encryptedEmail, $userId);

            if ($stmt->execute()) {
                echo colorOutput("✅ ", COLOR_GREEN) . "Encrypted data for user: $userId\n";
                $encryptedCount++;
                $totalEncrypted++;
            } else {
                echo colorOutput("❌ ", COLOR_RED) . "Failed to update user: $userId - " . $stmt->error . "\n";
                $totalErrors++;
            }

        } catch (Exception $e) {
            echo colorOutput("❌ ", COLOR_RED) . "Error encrypting user $userId: " . $e->getMessage() . "\n";
            $totalErrors++;
        }
    }

    echo "\n";
    echo "Processed: $encryptedCount / $userCount users\n";

} else {
    echo colorOutput("❌ Failed to query users: " . $conn->error, COLOR_RED) . "\n";
    $totalErrors++;
}

echo "\n";

// ============================================
// Step 3: Verify encryption
// ============================================
echo colorOutput("Step 3: Verifying encryption", COLOR_BLUE) . "\n";
echo "─────────────────────────────────────────\n";

$result = $conn->query("
    SELECT id, email, email_encrypted
    FROM users
    WHERE email_encrypted IS NOT NULL
    LIMIT 5
");

if ($result) {
    $verified = 0;
    $failed = 0;

    while ($row = $result->fetch_assoc()) {
        $userId = $row['id'];  // Changed from user_id to id
        $originalEmail = $row['email'];
        $encryptedEmail = $row['email_encrypted'];        try {
            $decryptedEmail = Encryption::decrypt($encryptedEmail);

            if ($decryptedEmail === $originalEmail) {
                echo colorOutput("✅ ", COLOR_GREEN) . "User $userId: Encryption verified\n";
                $verified++;
            } else {
                echo colorOutput("❌ ", COLOR_RED) . "User $userId: Decryption mismatch!\n";
                echo "   Original: $originalEmail\n";
                echo "   Decrypted: $decryptedEmail\n";
                $failed++;
            }
        } catch (Exception $e) {
            echo colorOutput("❌ ", COLOR_RED) . "User $userId: Decryption failed - " . $e->getMessage() . "\n";
            $failed++;
        }
    }

    echo "\n";
    echo "Verified: $verified / " . ($verified + $failed) . " samples\n";

    if ($failed > 0) {
        echo colorOutput("⚠️  WARNING: Some verifications failed!", COLOR_YELLOW) . "\n";
        $totalErrors += $failed;
    }

} else {
    echo colorOutput("⚠️  Could not verify: " . $conn->error, COLOR_YELLOW) . "\n";
}

echo "\n";

// ============================================
// Summary
// ============================================
echo colorOutput("============================================", COLOR_BOLD) . "\n";
echo colorOutput("  Migration Summary", COLOR_BOLD) . "\n";
echo colorOutput("============================================", COLOR_BOLD) . "\n\n";

echo "Total records encrypted: " . colorOutput($totalEncrypted, COLOR_GREEN) . "\n";
echo "Total errors: " . colorOutput($totalErrors, $totalErrors > 0 ? COLOR_RED : COLOR_GREEN) . "\n\n";

if ($totalErrors === 0) {
    echo colorOutput("✅ Migration completed successfully!", COLOR_GREEN . COLOR_BOLD) . "\n\n";

    echo colorOutput("Next Steps:", COLOR_BLUE) . "\n";
    echo "──────────\n";
    echo "1. Verify encrypted data in database\n";
    echo "2. Update application code to use encrypted columns\n";
    echo "3. Test thoroughly in development\n";
    echo "4. After confirming everything works, drop old columns:\n\n";
    echo "   " . colorOutput("ALTER TABLE users DROP COLUMN email;", COLOR_YELLOW) . "\n";
    echo "   " . colorOutput("ALTER TABLE users DROP COLUMN phone;", COLOR_YELLOW) . "\n";
    echo "   " . colorOutput("ALTER TABLE users DROP COLUMN address;", COLOR_YELLOW) . "\n\n";

    echo colorOutput("⚠️  WARNING:", COLOR_YELLOW . COLOR_BOLD) . "\n";
    echo "   Don't drop old columns until you've verified everything works!\n";
    echo "   Keep backups for at least 30 days after migration.\n\n";

} else {
    echo colorOutput("⚠️  Migration completed with errors!", COLOR_YELLOW . COLOR_BOLD) . "\n\n";
    echo "Please review the errors above and fix them before proceeding.\n";
    echo "Check error logs for more details.\n\n";
}

echo colorOutput("============================================", COLOR_BOLD) . "\n\n";
