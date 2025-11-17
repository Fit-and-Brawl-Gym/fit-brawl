<?php
/**
 * Quick Stats - Encryption Statistics
 *
 * Usage: php test_stats.php
 */

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db_connect.php';
require __DIR__ . '/includes/encryption.php';

echo "\n========================================\n";
echo "  Encryption Statistics\n";
echo "========================================\n\n";

// Get counts
$result = $conn->query("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN email_encrypted IS NOT NULL THEN 1 ELSE 0 END) as encrypted,
        SUM(CASE WHEN email_encrypted IS NULL THEN 1 ELSE 0 END) as not_encrypted
    FROM users
");
$stats = $result->fetch_assoc();

// Display stats
echo "Total Users:       " . $stats['total'] . "\n";
echo "Encrypted:         " . $stats['encrypted'] . " (" . round($stats['encrypted']/$stats['total']*100, 1) . "%)\n";
echo "Not Encrypted:     " . $stats['not_encrypted'] . "\n\n";

// Check configuration
$configured = Encryption::isConfigured();
echo "Key Configured:    " . ($configured ? "✅ YES" : "❌ NO") . "\n";

// List sample encrypted users
echo "\n========================================\n";
echo "  Sample Encrypted Users\n";
echo "========================================\n\n";

$result = $conn->query("
    SELECT id, email, LEFT(email_encrypted, 30) as enc
    FROM users
    WHERE email_encrypted IS NOT NULL
    LIMIT 5
");

printf("%-15s | %-30s | %s\n", "ID", "Email", "Encrypted (preview)");
echo str_repeat("-", 80) . "\n";

while ($row = $result->fetch_assoc()) {
    printf("%-15s | %-30s | %s...\n", $row['id'], $row['email'], $row['enc']);
}

echo "\n========================================\n\n";
