<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');

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
$membership_check = $conn->prepare("SELECT id FROM user_memberships WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()");
$membership_check->bind_param("i", $user_id);
$membership_check->execute();
if ($membership_check->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'You need an active membership to book sessions']);
    exit;
}

// Check if slots are available
$slot_check = $conn->prepare("SELECT r.max_slots, COUNT(ur.id) as booked
                               FROM reservations r
                               LEFT JOIN user_reservations ur ON r.id = ur.reservation_id AND ur.booking_status != 'cancelled'
                               WHERE r.id = ?
                               GROUP BY r.id");
$slot_check->bind_param("i", $reservation_id);
$slot_check->execute();
$slot_result = $slot_check->get_result()->fetch_assoc();

if (!$slot_result || $slot_result['booked'] >= $slot_result['max_slots']) {
    echo json_encode(['success' => false, 'message' => 'No slots available']);
    exit;
}

// Check if user already booked this session
$duplicate_check = $conn->prepare("SELECT id FROM user_reservations WHERE user_id = ? AND reservation_id = ? AND booking_status != 'cancelled'");
$duplicate_check->bind_param("ii", $user_id, $reservation_id);
$duplicate_check->execute();
if ($duplicate_check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already booked this session']);
    exit;
}

// Book the session
$stmt = $conn->prepare("INSERT INTO user_reservations (user_id, reservation_id, booking_status) VALUES (?, ?, 'confirmed')");
$stmt->bind_param("ii", $user_id, $reservation_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Session booked successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to book session']);
}
?>
