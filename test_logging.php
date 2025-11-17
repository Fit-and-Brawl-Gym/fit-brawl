<?php
/**
 * Test Activity Logging Fix
 * Run this to test if logging works after the fix
 */

session_start();

require_once __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/activity_logger.php';

// Set up a test session (simulate logged-in admin)
$_SESSION['user_id'] = 'ADM-25-0001';
$_SESSION['name'] = 'Test Admin';
$_SESSION['role'] = 'admin';

echo "========================================\n";
echo "  Activity Logger Test\n";
echo "========================================\n\n";

// Initialize logger
ActivityLogger::init($conn);

echo "Testing activity logging...\n\n";

// Test 1: Simple log
echo "Test 1: Simple action log\n";
$result1 = ActivityLogger::log(
    'test_action',
    'Test User',
    123,
    'This is a test log entry to verify logging is working'
);

if ($result1) {
    echo "✅ PASS: Log entry created successfully!\n\n";
} else {
    echo "❌ FAIL: Could not create log entry\n\n";
}

// Test 2: Profile update log
echo "Test 2: Profile update log\n";
$result2 = ActivityLogger::log(
    'profile_updated',
    'John Doe',
    'MBR-25-0001',
    "User 'John Doe' (member) updated profile: profile picture updated"
);

if ($result2) {
    echo "✅ PASS: Profile update logged successfully!\n\n";
} else {
    echo "❌ FAIL: Could not log profile update\n\n";
}

// Test 3: Equipment action log
echo "Test 3: Equipment action log\n";
$result3 = ActivityLogger::log(
    'equipment_edit',
    null,
    456,
    'Updated Equipment: Test Equipment (Category: Cardio, Status: Available)'
);

if ($result3) {
    echo "✅ PASS: Equipment action logged successfully!\n\n";
} else {
    echo "❌ FAIL: Could not log equipment action\n\n";
}

// Show recent logs
echo "========================================\n";
echo "  Recent Activity Logs (Last 5)\n";
echo "========================================\n\n";

$recentLogs = ActivityLogger::getActivities(5);

if (empty($recentLogs)) {
    echo "No logs found.\n";
} else {
    foreach ($recentLogs as $log) {
        echo "ID: {$log['id']}\n";
        echo "Admin: {$log['admin_name']} (ID: {$log['admin_id']})\n";
        echo "Action: {$log['action_type']}\n";
        echo "Target: {$log['target_user']}\n";
        echo "Details: {$log['details']}\n";
        echo "Time: {$log['timestamp']}\n";
        echo "----------------------------------------\n";
    }
}

echo "\n✅ Test completed!\n";
echo "\nCheck your admin panel activity logs to see the new entries.\n\n";
