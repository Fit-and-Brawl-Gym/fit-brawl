<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$reservation_id = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;

if (!$reservation_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid reservation ID']);
    exit;
}

// Check if user has active membership
$membership_check = $conn->prepare("SELECT id FROM user_memberships WHERE user_id = ? AND membership_status = 'active' AND end_date >= CURDATE()");
if (!$membership_check) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}
$membership_check->bind_param("i", $user_id);
$membership_check->execute();
if ($membership_check->get_result()->num_rows === 0) {
    $membership_check->close();
    echo json_encode(['success' => false, 'message' => 'You need an active membership to book sessions']);
    exit;
}
$membership_check->close();

// Check if session is in the future (not past) and within booking window
$date_check = $conn->prepare("SELECT date, start_time FROM reservations WHERE id = ? AND status = 'available'");
if (!$date_check) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}
$date_check->bind_param("i", $reservation_id);
$date_check->execute();
$date_result = $date_check->get_result()->fetch_assoc();

if (!$date_result) {
    $date_check->close();
    echo json_encode(['success' => false, 'message' => 'Session not found or unavailable']);
    exit;
}
$date_check->close();

$session_datetime = $date_result['date'] . ' ' . $date_result['start_time'];
$session_timestamp = strtotime($session_datetime);
$current_timestamp = time();
$max_booking_timestamp = strtotime('+1 month');

// Check if session has passed
if ($session_timestamp <= $current_timestamp) {
    echo json_encode(['success' => false, 'message' => 'Cannot book past sessions or sessions that have already started']);
    exit;
}

// Check if session is too far in advance (more than 1 month)
if ($session_timestamp > $max_booking_timestamp) {
    echo json_encode(['success' => false, 'message' => 'Bookings are limited to 1 month in advance. Please try again closer to the session date.']);
    exit;
}

// Check if user already booked this session
$duplicate_check = $conn->prepare("SELECT id FROM user_reservations WHERE user_id = ? AND reservation_id = ? AND booking_status != 'cancelled'");
if (!$duplicate_check) {
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}
$duplicate_check->bind_param("ii", $user_id, $reservation_id);
$duplicate_check->execute();
if ($duplicate_check->get_result()->num_rows > 0) {
    $duplicate_check->close();
    echo json_encode(['success' => false, 'message' => 'You have already booked this session']);
    exit;
}
$duplicate_check->close();

// Start transaction with explicit autocommit disable
$conn->autocommit(false);
$conn->begin_transaction();

try {
    // Check if slots are available (with row locking to prevent race conditions)
    $slot_check = $conn->prepare("SELECT r.max_slots, COUNT(ur.id) as booked
                                   FROM reservations r
                                   LEFT JOIN user_reservations ur ON r.id = ur.reservation_id AND ur.booking_status != 'cancelled'
                                   WHERE r.id = ?
                                   GROUP BY r.id
                                   FOR UPDATE");
    if (!$slot_check) {
        throw new Exception('Database error: Unable to prepare statement');
    }

    $slot_check->bind_param("i", $reservation_id);
    if (!$slot_check->execute()) {
        throw new Exception('Failed to check slot availability');
    }

    $slot_result = $slot_check->get_result()->fetch_assoc();

    if (!$slot_result || $slot_result['booked'] >= $slot_result['max_slots']) {
        throw new Exception('No slots available');
    }

    // Book the session
    $stmt = $conn->prepare("INSERT INTO user_reservations (user_id, reservation_id, booking_status) VALUES (?, ?, 'confirmed')");
    if (!$stmt) {
        throw new Exception('Database error: Unable to prepare insert statement');
    }

    $stmt->bind_param("ii", $user_id, $reservation_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to book session: ' . $stmt->error);
    }

    // Commit transaction
    $conn->commit();
    $conn->autocommit(true);

    // Log successful booking for debugging
    error_log("Successfully booked reservation ID $reservation_id for user ID $user_id");

    echo json_encode(['success' => true, 'message' => 'Session booked successfully']);
} catch (Exception $e) {
    // Rollback on any error
    if ($conn->connect_errno) {
        error_log("Database connection error: " . $conn->connect_error);
    }
    if ($conn) {
        $conn->rollback();
        $conn->autocommit(true);
    }
    error_log("Booking error for user $user_id, reservation $reservation_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    if (isset($slot_check) && $slot_check) {
        $slot_check->close();
    }
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if ($conn) {
        $conn->close();
    }
}
?>
