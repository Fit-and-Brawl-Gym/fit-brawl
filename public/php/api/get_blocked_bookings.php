<?php
/**
 * Get Blocked Bookings API
 * Returns list of user's bookings that are blocked and require action
 */

require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/session_manager.php';
require_once __DIR__ . '/../../../includes/booking_conflict_notifier.php';

header('Content-Type: application/json');

// Initialize session
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Initialize booking conflict notifier
    BookingConflictNotifier::init($conn);

    // Get blocked bookings requiring action
    $blocked_bookings = BookingConflictNotifier::getBlockedBookingsRequiringAction($user_id);

    echo json_encode([
        'success' => true,
        'blocked_bookings' => $blocked_bookings,
        'count' => count($blocked_bookings)
    ]);

} catch (Exception $e) {
    error_log("Error fetching blocked bookings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching blocked bookings'
    ]);
}
