<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "SELECT ur.id, ur.booking_status, r.class_type, r.date, r.start_time, r.end_time, t.name as trainer_name
          FROM user_reservations ur
          JOIN reservations r ON ur.reservation_id = r.id
          JOIN trainers t ON r.trainer_id = t.id
          WHERE ur.user_id = ? AND r.date >= CURDATE()
          ORDER BY r.date ASC, r.start_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = [
        'id' => $row['id'],
        'class_type' => $row['class_type'],
        'trainer_name' => $row['trainer_name'],
        'date' => $row['date'],
        'datetime' => $row['date'] . ' ' . $row['start_time'],
        'time' => date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])),
        'status' => ucfirst($row['booking_status'])
    ];
}

echo json_encode(['success' => true, 'bookings' => $bookings]);
?>
