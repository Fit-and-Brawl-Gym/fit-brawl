<?php
/**
 * Cancel Booking API
 * Allows users to cancel their bookings at any time (no time restrictions)
 *
 * Request: POST
 * Parameters:
 *   - booking_id: int (required) - ID of the booking to cancel
 *
 * Response: JSON
 *   - success: boolean
 *   - message: string
 *   - details: object with cancelled booking information
 */

session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/activity_logger.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';
require_once __DIR__ . '/../../../includes/input_validator.php';
require_once '../../../includes/timezone_helper.php';
require_once '../../../includes/booking_validator.php';

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
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'member';
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$from_blocked = isset($_POST['from_blocked']) && $_POST['from_blocked'] === 'true';

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
    // Initialize activity logger
    ActivityLogger::init($conn);

    // Initialize booking validator
    $validator = new BookingValidator($conn);

    // Skip validation for blocked bookings - they need to be cancelled urgently
    if (!$from_blocked) {
        // Validate cancellation (must be >24 hours before session)
        $validation = $validator->validateCancellation($booking_id, $user_id);

        if (!$validation['valid']) {
            ApiSecurityMiddleware::sendJsonResponse([
                'success' => false,
                'message' => $validation['message'],
                'hours_remaining' => $validation['hours_remaining'] ?? null
            ], 400);
            exit;
        }
    }

    // Get booking details before cancellation
    // Get booking details before cancellation (supports both time-based and legacy formats)
    $stmt = $conn->prepare("
        SELECT
            ur.user_id,
            ur.trainer_id,
            ur.session_time,
            ur.class_type,
            ur.booking_date,
            ur.start_time,
            ur.end_time,
            ur.booking_status,
            t.name AS trainer_name,
            u.username,
            u.username AS user_name
        FROM user_reservations ur
        JOIN trainers t ON ur.trainer_id = t.id
        JOIN users u ON ur.user_id = u.id
        WHERE ur.id = ?
    ");
    $stmt->bind_param("i", $booking_id);
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
    if ($booking['user_id'] !== $user_id && !in_array($user_role, ['admin', 'trainer'])) {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Unauthorized: This booking does not belong to you'
        ], 403);
        exit;
    }

    // Check if booking can be cancelled (pending/confirmed/blocked bookings)
    $cancellable_statuses = ['pending', 'confirmed'];
    if ($from_blocked) {
        $cancellable_statuses[] = 'blocked';
    }
    
    if (!in_array($booking['booking_status'], $cancellable_statuses)) {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => "Cannot cancel {$booking['booking_status']} booking"
        ], 400);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update booking status
        $update_stmt = $conn->prepare("
            UPDATE user_reservations
            SET booking_status = 'cancelled',
                cancelled_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $update_stmt->bind_param("i", $booking_id);

        if (!$update_stmt->execute()) {
            throw new Exception('Failed to cancel booking');
        }

        $update_stmt->close();

        // Commit transaction
        $conn->commit();

        // Determine booking time display (time-based vs legacy)
        if ($booking['start_time'] && $booking['end_time']) {
            // Time-based booking
            $start_dt = TimezoneHelper::create($booking['start_time']);
            $end_dt = TimezoneHelper::create($booking['end_time']);
            $time_display = $start_dt->format('M d, Y g:i A') . ' - ' . $end_dt->format('g:i A');
            $date_display = $start_dt->format('F j, Y');
            $time_range = $start_dt->format('g:i A') . ' - ' . $end_dt->format('g:i A');
        } else {
            // Legacy booking
            $session_hours = $booking['session_time'] === 'Morning' ? '7-11 AM' :
                ($booking['session_time'] === 'Afternoon' ? '1-5 PM' : '6-10 PM');
            $time_display = $booking['booking_date'] . ' (' . $booking['session_time'] . ': ' . $session_hours . ')';
            $date_display = date('F j, Y', strtotime($booking['booking_date']));
            $time_range = $session_hours;
        }

        // Log activity
        $log_details = "Cancelled {$booking['class_type']} session with {$booking['trainer_name']} on {$time_display}";
        if ($user_role === 'admin' && $booking['user_id'] != $user_id) {
            $log_details = "Admin cancelled booking for {$booking['user_name']}: " . $log_details;
        }
        ActivityLogger::log('session_cancelled', $booking['username'], $booking_id, $log_details);

        ApiSecurityMiddleware::sendJsonResponse([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'details' => [
                'booking_id' => $booking_id,
                'user_name' => $booking['user_name'],
                'trainer' => $booking['trainer_name'],
                'class' => $booking['class_type'],
                'date' => $date_display,
                'time' => $time_range
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
