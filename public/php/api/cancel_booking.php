<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get POST data
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

if ($booking_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Start transaction with explicit autocommit disable
    $conn->autocommit(false);
    $conn->begin_transaction();

    // Verify that the booking belongs to the user and is cancellable
    $check_query = "SELECT ur.id, ur.reservation_id, ur.booking_status, r.date, r.start_time
                    FROM user_reservations ur
                    JOIN reservations r ON ur.reservation_id = r.id
                    WHERE ur.id = ? AND ur.user_id = ?
                    FOR UPDATE";

    $stmt = $conn->prepare($check_query);
    if (!$stmt) {
        throw new Exception('Database error: Unable to prepare statement');
    }

    $stmt->bind_param("ii", $booking_id, $user_id);

    if (!$stmt->execute()) {
        throw new Exception('Database error: Query failed');
    }

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Booking not found or does not belong to you');
    }

    $booking = $result->fetch_assoc();

    // Check if already cancelled
    if ($booking['booking_status'] === 'cancelled') {
        throw new Exception('This session is already cancelled');
    }

    // Check if session has already passed
    $session_datetime = $booking['date'] . ' ' . $booking['start_time'];
    $session_timestamp = strtotime($session_datetime);
    $current_timestamp = time();

    if ($session_timestamp <= $current_timestamp) {
        throw new Exception('Cannot cancel past sessions or sessions that have already started');
    }

    // Check if cancellation is within allowed time (at least 2 hours before)
    $hours_before_session = ($session_timestamp - $current_timestamp) / 3600;
    if ($hours_before_session < 2) {
        throw new Exception('Cancellations must be made at least 2 hours before the session');
    }

    // Update booking status to cancelled
    $cancel_query = "UPDATE user_reservations
                     SET booking_status = 'cancelled'
                     WHERE id = ? AND user_id = ? AND booking_status = 'confirmed'";

    $update_stmt = $conn->prepare($cancel_query);
    if (!$update_stmt) {
        throw new Exception('Database error: Unable to prepare update statement');
    }

    $update_stmt->bind_param("ii", $booking_id, $user_id);

    if (!$update_stmt->execute()) {
        throw new Exception('Failed to cancel booking: ' . $update_stmt->error);
    }

    if ($update_stmt->affected_rows === 0) {
        throw new Exception('Booking could not be cancelled. It may have already been modified.');
    }

    // Commit transaction
    $conn->commit();
    $conn->autocommit(true);

    // Log successful cancellation for debugging
    error_log("Successfully cancelled booking ID $booking_id for user ID $user_id (reservation ID: {$booking['reservation_id']})");

    echo json_encode([
        'success' => true,
        'message' => 'Session cancelled successfully. Your slot has been freed up.'
    ]);

} catch (Exception $e) {
    // Rollback on error
    if ($conn->connect_errno) {
        error_log("Database connection error: " . $conn->connect_error);
    }
    if ($conn) {
        $conn->rollback();
        $conn->autocommit(true);
    }
    error_log("Cancellation error for user $user_id, booking $booking_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($update_stmt) && $update_stmt) {
        $update_stmt->close();
    }
    if ($conn) {
        $conn->close();
    }
}
?>

