<?php
require_once 'includes/db_connect.php';

echo "=== Replacing account-icon-white.svg with account-icon.svg ===\n\n";

// Update users table
$stmt = $conn->prepare("UPDATE users SET avatar = 'account-icon.svg' WHERE avatar = 'account-icon-white.svg'");
$stmt->execute();
$affected = $stmt->affected_rows;
echo "Updated $affected users from 'account-icon-white.svg' to 'account-icon.svg'\n";
$stmt->close();

// Verify the change
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE avatar = 'account-icon-white.svg'");
$row = $result->fetch_assoc();
echo "\nUsers still with 'account-icon-white.svg': " . $row['count'] . "\n";

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE avatar = 'account-icon.svg'");
$row = $result->fetch_assoc();
echo "Users now with 'account-icon.svg': " . $row['count'] . "\n";

echo "\n✓ Database update complete!\n";
