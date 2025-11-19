<?php
// Prevent output before headers
ob_start();

// Disable error display for API
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Set JSON header immediately
header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/db_connect.php';

if (!isset($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
    exit;
}

$booking_id = $_POST['booking_id'];
$booking_date = $_POST['booking_date'];
$class_type = $_POST['class_type'];
$trainer_id = $_POST['trainer_id'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$reason = $_POST['reschedule_reason'] ?? null;
$rescheduled_at = date("Y-m-d H:i:s");

// First, get the current booking details
$check_sql = "SELECT booking_date, class_type, trainer_id, start_time, end_time 
              FROM user_reservations 
              WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $booking_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    $check_stmt->close();
    $conn->close();
    exit;
}

$current_booking = $check_result->fetch_assoc();
$check_stmt->close();

// Check if the new booking is identical to the current one
if ($current_booking['booking_date'] === $booking_date &&
    $current_booking['class_type'] === $class_type &&
    $current_booking['trainer_id'] == $trainer_id &&
    $current_booking['start_time'] === $start_time &&
    $current_booking['end_time'] === $end_time) {
    echo json_encode([
        'success' => false, 
        'message' => 'No changes detected. The new booking details are identical to the current booking.'
    ]);
    $conn->close();
    exit;
}

// Update the booking
$sql = "UPDATE user_reservations SET
            booking_date = ?,
            class_type = ?,
            trainer_id = ?,
            start_time = ?,
            end_time = ?,
            reschedule_reason = ?,
            rescheduled_at = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssissssi",
    $booking_date,
    $class_type,
    $trainer_id,
    $start_time,
    $end_time,
    $reason,
    $rescheduled_at,
    $booking_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>
