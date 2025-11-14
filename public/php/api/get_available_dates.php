<?php
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Don't display errors in JSON API - log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

    // Query to get all available dates based on trainer schedules
    // Since we don't have a reservations table, we'll get dates from trainer_schedules
    $query = "
        SELECT DISTINCT DATE_ADD(?, INTERVAL seq.n DAY) as date
        FROM (
            SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION
            SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION
            SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
        ) seq
        WHERE DATE_ADD(?, INTERVAL seq.n DAY) <= ?
          AND DATE_ADD(?, INTERVAL seq.n DAY) >= ?
          AND DAYOFWEEK(DATE_ADD(?, INTERVAL seq.n DAY)) BETWEEN 2 AND 7
        ORDER BY date
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("SQL prepare error: " . $conn->error);
    }

    $stmt->bind_param("sssss", $currentDate, $currentDate, $maxBookingDate, $currentDate, $currentDate);
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
    error_log("Error in get_available_dates.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching available dates.'
    ]);
} finally {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>

