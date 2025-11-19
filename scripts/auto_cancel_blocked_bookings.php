<?php
/**
 * Auto-Cancel Blocked Bookings Cron Job
 * 
 * This script automatically cancels bookings that have been marked as blocked
 * for more than 24 hours without user action.
 * 
 * Setup Instructions:
 * 
 * Windows (Task Scheduler):
 * 1. Open Task Scheduler
 * 2. Create Basic Task
 * 3. Name: "Auto Cancel Blocked Bookings"
 * 4. Trigger: Daily at a specific time (e.g., every hour)
 * 5. Action: Start a program
 * 6. Program: C:\xampp\php\php.exe
 * 7. Arguments: "C:\xampp\htdocs\fit-brawl\scripts\auto_cancel_blocked_bookings.php"
 * 
 * Linux (Crontab):
 * Add this line to crontab (runs every hour):
 * 0 * * * * /usr/bin/php /path/to/fit-brawl/scripts/auto_cancel_blocked_bookings.php
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    // Allow access via web for testing purposes (remove in production)
    if (!isset($_GET['manual_run']) || $_GET['manual_run'] !== 'true') {
        die('This script can only be run from command line or with ?manual_run=true for testing');
    }
}

// Start execution
$start_time = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Starting auto-cancel blocked bookings job...\n";

// Include required files
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/booking_conflict_notifier.php';

// Initialize the notifier
BookingConflictNotifier::init($conn);

try {
    // Run auto-cancellation
    $cancelled_count = BookingConflictNotifier::autoCancelExpiredBlocks();
    
    $execution_time = round(microtime(true) - $start_time, 2);
    
    echo "[" . date('Y-m-d H:i:s') . "] Job completed successfully\n";
    echo "  - Bookings auto-cancelled: {$cancelled_count}\n";
    echo "  - Execution time: {$execution_time} seconds\n";
    
    // Log to file
    $log_file = __DIR__ . '/../logs/auto_cancel_blocked_bookings.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_message = sprintf(
        "[%s] Auto-cancel job completed: %d bookings cancelled (%.2fs)\n",
        date('Y-m-d H:i:s'),
        $cancelled_count,
        $execution_time
    );
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
} catch (Exception $e) {
    $error_message = "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    echo $error_message;
    
    // Log error
    $log_file = __DIR__ . '/../logs/auto_cancel_blocked_bookings.log';
    file_put_contents($log_file, $error_message, FILE_APPEND);
    
    exit(1);
}

exit(0);
