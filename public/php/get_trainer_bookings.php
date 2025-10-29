<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is a trainer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$trainer_id = isset($_GET['trainer_id']) ? intval($_GET['trainer_id']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');

if (!$trainer_id) {
    echo json_encode(['success' => false, 'message' => 'Trainer ID required']);
    exit;
}

// Get all dates that have bookings for this trainer in the specified month
$start_date = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$end_date = date("Y-m-t", strtotime($start_date));

$query = "
    SELECT DISTINCT DATE(r.date) as booking_date
    FROM user_reservations ur
    JOIN reservations r ON ur.reservation_id = r.id
    WHERE r.trainer_id = ?
    AND r.date BETWEEN ? AND ?
    AND ur.booking_status != 'cancelled'
    ORDER BY r.date
";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $trainer_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row['booking_date'];
}

echo json_encode([
    'success' => true,
    'bookings' => $bookings
]);

$stmt->close();
$conn->close();
?>
