<?php
/**
 * Get Session Capacity API
 * Returns the current capacity for a specific session on a date
 */

// Prevent output before headers
ob_start();

// Disable error display for API
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start session BEFORE any output
session_start();

header('Content-Type: application/json');
require_once '../../../includes/db_connect.php';
require_once '../../../includes/api_security_middleware.php';

// Set security headers
ApiSecurityMiddleware::setSecurityHeaders();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

// Get parameters
$date = $_GET['date'] ?? '';
$session = $_GET['session'] ?? '';

// Validate inputs
if (empty($date) || empty($session)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters'
    ]);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid date format'
    ]);
    exit;
}

// Define session limits
$sessionLimits = [
    'Morning' => 15,
    'Afternoon' => 15,
    'Evening' => 15
];

// Get session name without number suffix
$sessionName = preg_replace('/:.*$/', '', $session);

if (!isset($sessionLimits[$sessionName])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid session'
    ]);
    exit;
}

$maxCapacity = $sessionLimits[$sessionName];

try {
    // Count bookings for this date and session
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM user_reservations
        WHERE booking_date = ?
        AND session_time = ?
        AND booking_status IN ('confirmed')
    ");

    $stmt->bind_param("ss", $date, $sessionName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();    $currentBookings = (int)$row['count'];
    $availableSlots = max(0, $maxCapacity - $currentBookings);
    $percentageFull = ($currentBookings / $maxCapacity) * 100;

    // Determine status
    $status = 'available';
    if ($availableSlots === 0) {
        $status = 'full';
    } elseif ($percentageFull >= 80) {
        $status = 'limited';
    }

    echo json_encode([
        'success' => true,
        'capacity' => [
            'max' => $maxCapacity,
            'current' => $currentBookings,
            'available' => $availableSlots,
            'percentage' => round($percentageFull, 1),
            'status' => $status
        ]
    ]);

} catch (Exception $e) {
    error_log("Session capacity error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error'
    ]);
}
