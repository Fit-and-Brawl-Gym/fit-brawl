<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/booking_validator.php';
require_once '../../../includes/activity_logger.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';
require_once __DIR__ . '/../../../includes/input_validator.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require authentication
$user = ApiSecurityMiddleware::requireAuth();
if (!$user) {
    exit; // Already sent response
}

$user_id = $user['user_id'];

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    exit; // Already sent response
}

// Require CSRF token
if (!ApiSecurityMiddleware::requireCSRF()) {
    exit; // Already sent response
}

// Rate limiting - 6 requests per minute per user
ApiSecurityMiddleware::applyRateLimit($conn, 'cancel_booking:' . $user_id, 6, 60);

// Validate and sanitize input
$validation = ApiSecurityMiddleware::validateInput([
    'booking_id' => [
        'type' => 'integer',
        'required' => true,
        'min' => 1
    ]
]);

if (!$validation['valid']) {
    $errors = implode(', ', $validation['errors']);
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Validation failed: ' . $errors
    ], 400);
}

$booking_id = $validation['data']['booking_id'];

try {
    // Initialize validator and activity logger
    $validator = new BookingValidator($conn);
    ActivityLogger::init($conn);

    // Validate cancellation (must be >24 hours before session)
    $validation = $validator->validateCancellation($booking_id, $user_id);

    if (!$validation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => $validation['message'],
            'hours_remaining' => $validation['hours_remaining'] ?? null
        ]);
        exit;
    }

    // Get booking details before cancellation
    $stmt = $conn->prepare("
        SELECT
            ur.user_id,
            ur.trainer_id,
            ur.session_time,
            ur.class_type,
            ur.booking_date,
            t.name AS trainer_name,
            u.username
        FROM user_reservations ur
        JOIN trainers t ON ur.trainer_id = t.id
        JOIN users u ON ur.user_id = u.id
        WHERE ur.id = ? AND ur.user_id = ?
    ");
    $stmt->bind_param("is", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Booking not found'
        ], 404);
    }

    $booking = $result->fetch_assoc();
    $stmt->close();

    // Verify booking belongs to user (additional security check)
    if ($booking['user_id'] !== $user_id) {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Unauthorized: This booking does not belong to you'
        ], 403);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update booking status
        $update_stmt = $conn->prepare("
            UPDATE user_reservations
            SET booking_status = 'cancelled',
                cancelled_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        $update_stmt->bind_param("is", $booking_id, $user_id);

        if (!$update_stmt->execute()) {
            throw new Exception('Failed to cancel booking');
        }

        $update_stmt->close();

        // Commit transaction
        $conn->commit();

        // Log activity
        $session_hours = $booking['session_time'] === 'Morning' ? '7-11 AM' :
            ($booking['session_time'] === 'Afternoon' ? '1-5 PM' : '6-10 PM');
        $log_details = "Cancelled {$booking['class_type']} session with {$booking['trainer_name']} on {$booking['booking_date']} ({$booking['session_time']}: {$session_hours})";
        ActivityLogger::log('session_cancelled', $booking['username'], $booking_id, $log_details);

        ApiSecurityMiddleware::sendJsonResponse([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'details' => [
                'booking_id' => $booking_id,
                'trainer' => $booking['trainer_name'],
                'class' => $booking['class_type'],
                'date' => date('F j, Y', strtotime($booking['booking_date'])),
                'session' => $booking['session_time']
            ]
        ], 200);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Cancellation error for user $user_id: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while cancelling your booking. Please try again.'
    ], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
