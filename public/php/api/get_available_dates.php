<?php
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $class_type = isset($_GET['class']) ? trim($_GET['class']) : '';

    if (empty($class_type)) {
        echo json_encode(['success' => false, 'message' => 'Class type required']);
        exit;
    }

    // Map service class keys to database class types
    $class_map = [
        'boxing' => 'Boxing',
        'muay-thai' => 'Muay Thai',
        'mma' => 'MMA',
        'gym' => 'Gym'
    ];

    if (!isset($class_map[$class_type])) {
        echo json_encode(['success' => false, 'message' => 'Invalid class type']);
        exit;
    }

    $db_class_type = $class_map[$class_type];

    // Get current date and time for filtering
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');

    // Calculate max booking date (30 days from now)
    $maxBookingDate = date('Y-m-d', strtotime('+30 days'));

    // Query to get all available dates for this class type
    // Get dates that have at least one reservation with available slots
    $query = "
        SELECT DISTINCT r.date
        FROM reservations r
        LEFT JOIN user_reservations ur ON r.id = ur.reservation_id
            AND ur.booking_status = 'confirmed'
        WHERE r.class_type = ?
          AND r.status = 'available'
          AND r.date >= ?
          AND r.date <= ?
          AND (
              r.date > ?
              OR (r.date = ? AND r.start_time > ?)
          )
        GROUP BY r.id
        HAVING (r.max_slots - COALESCE(COUNT(ur.id), 0)) > 0
        ORDER BY r.date
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("SQL prepare error: " . $conn->error);
    }

    $stmt->bind_param("ssssss", $db_class_type, $currentDate, $maxBookingDate, $currentDate, $currentDate, $currentTime);
    $stmt->execute();
    $result = $stmt->get_result();

    $available_dates = [];
    while ($row = $result->fetch_assoc()) {
        $available_dates[] = $row['date'];
    }

    echo json_encode([
        'success' => true,
        'available_dates' => $available_dates
    ]);

} catch (Throwable $e) {
    error_log("Error fetching available dates: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching available dates.']);
} finally {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>

