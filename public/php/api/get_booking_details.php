<?php
// Prevent output before headers
ob_start();

// Disable error display for API
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Set JSON header immediately
header('Content-Type: application/json');

require_once '../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require authentication
$user = ApiSecurityMiddleware::requireAuth();
if (!$user) {
    exit; // Already sent response
}

$user_id = $user['user_id'];

// Get booking_id from query parameter
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Booking ID is required'
    ], 400);
    exit;
}

try {
    // Fetch booking details
    $stmt = $conn->prepare("
        SELECT 
            ur.id,
            ur.user_id,
            ur.trainer_id,
            ur.session_time,
            ur.class_type,
            ur.booking_date,
            ur.start_time,
            ur.end_time,
            ur.booking_status,
            ur.booked_at,
            t.name AS trainer_name,
            t.photo AS trainer_photo
        FROM user_reservations ur
        LEFT JOIN trainers t ON ur.trainer_id = t.id
        WHERE ur.id = ? AND ur.user_id = ?
    ");
    
    $stmt->bind_param("is", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($booking = $result->fetch_assoc()) {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => true,
            'booking' => $booking
        ]);
    } else {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Booking not found or access denied'
        ], 404);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    error_log('Error fetching booking details: ' . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Server error'
    ], 500);
}

$conn->close();
