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
$date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$trainer_id || !$date) {
    echo json_encode(['success' => false, 'message' => 'Trainer ID and date required']);
    exit;
}

// Function to categorize time into morning, afternoon, or evening
function getTimeCategory($time) {
    $hour = intval(date('H', strtotime($time)));

    if ($hour >= 6 && $hour < 12) {
        return 'morning';
    } elseif ($hour >= 12 && $hour < 18) {
        return 'afternoon';
    } else {
        return 'evening';
    }
}

// Get all bookings for this trainer on this date
$query = "
    SELECT
        ur.id,
        ur.booking_status,
        ur.class_type,
        ur.start_time,
        ur.end_time,
        u.username as member_name,
        u.email as member_email,
        r.max_slots
    FROM user_reservations ur
    JOIN reservations r ON ur.reservation_id = r.id
    JOIN users u ON ur.user_id = u.id
    WHERE r.trainer_id = ?
    AND r.date = ?
    AND ur.booking_status != 'cancelled'
    ORDER BY ur.start_time
";

$stmt = $conn->prepare($query);
$stmt->bind_param("is", $trainer_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$morning = [];
$afternoon = [];
$evening = [];

while ($row = $result->fetch_assoc()) {
    $category = getTimeCategory($row['start_time']);

    if ($category === 'morning') {
        $morning[] = $row;
    } elseif ($category === 'afternoon') {
        $afternoon[] = $row;
    } else {
        $evening[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'morning' => $morning,
    'afternoon' => $afternoon,
    'evening' => $evening
]);

$stmt->close();
$conn->close();
?>
