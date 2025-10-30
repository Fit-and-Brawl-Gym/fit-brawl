<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');


error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
    $class_filter = isset($_GET['class']) ? trim($_GET['class']) : 'all';
    $coach_filter = isset($_GET['coach']) ? trim($_GET['coach']) : 'all';
    $session_filter = isset($_GET['session']) ? trim($_GET['session']) : 'all';

    // Validate year and month
    if ($year < 2020 || $year > 2100) {
        echo json_encode(['success' => false, 'message' => 'Invalid year']);
        exit;
    }
    if ($month < 1 || $month > 12) {
        echo json_encode(['success' => false, 'message' => 'Invalid month']);
        exit;
    }

    // Get current date and time for filtering
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');

    // Calculate max booking date (1 month from now)
    $maxBookingDate = date('Y-m-d', strtotime('+1 month'));

    $query = "
        SELECT SQL_NO_CACHE
            r.id, r.class_type, r.date, r.start_time, r.end_time, r.max_slots,
            t.id AS trainer_id, t.name AS trainer_name,
            (r.max_slots - COALESCE(COUNT(ur.id), 0)) AS remaining_slots
        FROM reservations r
        JOIN trainers t ON r.trainer_id = t.id
        LEFT JOIN user_reservations ur
            ON r.id = ur.reservation_id
           AND ur.booking_status = 'confirmed'
        WHERE YEAR(r.date) = ?
          AND MONTH(r.date) = ?
          AND r.status = 'available'
          AND (
              r.date > ?
              OR (r.date = ? AND r.start_time > ?)
          )
          AND r.date <= ?
    ";

    $params = [$year, $month, $currentDate, $currentDate, $currentTime, $maxBookingDate];
    $types = "iissss";

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


    if ($coach_filter !== 'all' && is_numeric($coach_filter)) {
        $query .= " AND t.id = ?";
        $params[] = intval($coach_filter);
        $types .= "i";
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

        $remaining = intval($row['remaining_slots']);
        $max = intval($row['max_slots']);

        // Debug log with date and time for tracking
        error_log("Reservation ID {$row['id']} ({$row['class_type']} - {$row['date']} {$row['start_time']}): {$remaining}/{$max} slots remaining");

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
            'slots' => $remaining,
            'max_slots' => $max
        ];
    }

    error_log("Total days with reservations: " . count($reservations));
    echo json_encode(['success' => true, 'reservations' => $reservations]);

} catch (Throwable $e) {
    error_log("Error fetching reservations: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching reservations. Please try again.']);
} finally {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
