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
        r.id,
        r.booking_status,
        r.start_time,
        r.end_time,
        u.name as member_name,
        u.email as member_email,
        ct.class_name as class_type,
        CASE 
            WHEN r.reservation_date = CURDATE() 
            AND TIME(NOW()) BETWEEN r.start_time AND r.end_time 
            THEN 'ongoing'
            WHEN r.reservation_date < CURDATE() OR (r.reservation_date = CURDATE() AND TIME(NOW()) > r.end_time)
            THEN 'completed'
            ELSE r.booking_status 
        END as session_status
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    LEFT JOIN class_types ct ON r.class_type_id = ct.id
    WHERE r.trainer_id = ?
    AND r.reservation_date = ?
    AND r.booking_status = 'confirmed'
    ORDER BY r.start_time
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
