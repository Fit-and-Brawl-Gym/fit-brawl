<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');


error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $class_filter = isset($_GET['class']) ? trim($_GET['class']) : 'all';
    $coach_filter = isset($_GET['coach']) ? trim($_GET['coach']) : 'all';
    $session_filter = isset($_GET['session']) ? trim($_GET['session']) : 'all';

    $query = "
        SELECT
            r.id, r.class_type, r.date, r.start_time, r.end_time, r.max_slots,
            t.id AS trainer_id, t.name AS trainer_name,
            (r.max_slots - COUNT(ur.id)) AS remaining_slots
        FROM reservations r
        JOIN trainers t ON r.trainer_id = t.id
        LEFT JOIN user_reservations ur
            ON r.id = ur.reservation_id
           AND ur.booking_status != 'cancelled'
        WHERE YEAR(r.date) = ?
          AND MONTH(r.date) = ?
          AND r.status = 'available'
    ";

    $params = [$year, $month];
    $types = "ii";


    $class_map = [
        'muay-thai' => 'Muay Thai',
        'boxing' => 'Boxing',
        'mma' => 'MMA'
    ];

    if ($class_filter !== 'all' && isset($class_map[$class_filter])) {
        $query .= " AND r.class_type = ?";
        $params[] = $class_map[$class_filter];
        $types .= "s";
    }

    // Session filter
    if ($session_filter === 'morning') {
        $query .= " AND TIME(r.start_time) >= '06:00:00' AND TIME(r.start_time) < '12:00:00'";
    } elseif ($session_filter === 'afternoon') {
        $query .= " AND TIME(r.start_time) >= '12:00:00' AND TIME(r.start_time) < '18:00:00'";
    } elseif ($session_filter === 'evening') {
        $query .= " AND TIME(r.start_time) >= '18:00:00' AND TIME(r.start_time) <= '22:00:00'";
    } // 'all' returns all sessions, so no filter


    if ($coach_filter !== 'all') {
    $stmtCoach = $conn->prepare("SELECT id FROM trainers WHERE slug = ?");
    $stmtCoach->bind_param("s", $coach_filter);
    $stmtCoach->execute();
    $resultCoach = $stmtCoach->get_result();

    if ($rowCoach = $resultCoach->fetch_assoc()) {
        $query .= " AND t.id = ?";
        $params[] = $rowCoach['id'];
        $types .= "i";
    }
}


    $query .= " GROUP BY r.id ORDER BY r.date, r.start_time";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("SQL prepare error: " . $conn->error);
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $day = date('j', strtotime($row['date']));
        if (!isset($reservations[$day])) {
            $reservations[$day] = [];
        }

        // Add slug for frontend filtering
        $class_slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $row['class_type']));

        $reservations[$day][] = [
            'id' => $row['id'],
            'class' => $row['class_type'],
            'class_slug' => $class_slug,
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

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
