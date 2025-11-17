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
