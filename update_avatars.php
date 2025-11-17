<?php
require_once 'includes/db_connect.php';

echo "=== Replacing default-avatar.png with account-icon-white.svg ===\n\n";

// Update users table
$stmt = $conn->prepare("UPDATE users SET avatar = 'account-icon-white.svg' WHERE avatar = 'default-avatar.png'");
$stmt->execute();
$affected = $stmt->affected_rows;
echo "Updated $affected users from 'default-avatar.png' to 'account-icon-white.svg'\n";
$stmt->close();

// Verify the change
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE avatar = 'default-avatar.png'");
$row = $result->fetch_assoc();
echo "\nUsers still with 'default-avatar.png': " . $row['count'] . "\n";

$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE avatar = 'account-icon-white.svg'");
$row = $result->fetch_assoc();
echo "Users now with 'account-icon-white.svg': " . $row['count'] . "\n";

echo "\n✓ Database update complete!\n";
