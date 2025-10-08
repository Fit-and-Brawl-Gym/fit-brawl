<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');

$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$class_filter = isset($_GET['class']) ? $_GET['class'] : 'all';
$coach_filter = isset($_GET['coach']) ? $_GET['coach'] : 'all';

// Build query
$query = "SELECT r.id, r.class_type, r.date, r.start_time, r.end_time, r.max_slots,
                 t.id as trainer_id, t.name as trainer_name,
                 (r.max_slots - COUNT(ur.id)) as remaining_slots
          FROM reservations r
          JOIN trainers t ON r.trainer_id = t.id
          LEFT JOIN user_reservations ur ON r.id = ur.reservation_id AND ur.booking_status != 'cancelled'
          WHERE YEAR(r.date) = ? AND MONTH(r.date) = ? AND r.status = 'available'";

$params = [$year, $month];
$types = "ii";

// Add class filter
if ($class_filter !== 'all') {
    $class_map = [
        'muay-thai' => 'Muay Thai',
        'boxing' => 'Boxing',
        'mma' => 'MMA'
    ];
    $query .= " AND r.class_type = ?";
    $params[] = $class_map[$class_filter];
    $types .= "s";
}

// Add coach filter
if ($coach_filter !== 'all') {
    $coach_map = [
        'coach-carlo' => 1,
        'coach-rieze' => 2,
        'coach-thei' => 3
    ];
    $query .= " AND t.id = ?";
    $params[] = $coach_map[$coach_filter];
    $types .= "i";
}

$query .= " GROUP BY r.id ORDER BY r.date, r.start_time";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $day = date('j', strtotime($row['date']));

    if (!isset($reservations[$day])) {
        $reservations[$day] = [];
    }

    $reservations[$day][] = [
        'id' => $row['id'],
        'class' => $row['class_type'],
        'trainer' => $row['trainer_name'],
        'trainer_id' => $row['trainer_id'],
        'date' => $row['date'],
        'time' => date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])),
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'slots' => intval($row['remaining_slots']),
        'max_slots' => intval($row['max_slots'])
    ];
}

echo json_encode(['success' => true, 'reservations' => $reservations]);
?>
