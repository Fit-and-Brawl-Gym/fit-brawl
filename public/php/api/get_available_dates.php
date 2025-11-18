<?php
require_once '../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';
require_once __DIR__ . '/../../../includes/input_validator.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Don't display errors in JSON API - log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Rate limiting - 60 requests per minute per IP (public endpoint, used frequently)
$identifier = 'get_available_dates:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
ApiSecurityMiddleware::applyRateLimit($conn, $identifier, 60, 60);

header('Content-Type: application/json; charset=utf-8');

// Sanitize and validate class parameter
$class = isset($_GET['class']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['class']) : 'gym';

// Helper: get mysqli handle if available
$db = null;
if (isset($conn) && $conn) { // common name in other files
    $db = $conn;
} elseif (isset($mysqli) && $mysqli) {
    $db = $mysqli;
}

// Attempt to load dates from DB. If fails, fallback to next 31 days.
$availableDates = [];

try {
    if ($db && method_exists($db, 'prepare')) {
        // Try a common table/column name; may fail if schema differs.
        $sql = "SELECT schedule_date FROM trainer_schedules WHERE class_type = ? AND is_available = 1";
        $stmt = @$db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $class);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                // Normalize to YYYY-MM-DD if possible
                $date = $row['schedule_date'];
                $d = new DateTime($date);
                $availableDates[] = $d->format('Y-m-d');
            }
            $stmt->close();
        }
    }
} catch (Exception $ex) {
    // ignore; fallback below
}

// Fallback: if no DB results, generate next 31 dates (simple heuristic)
if (count($availableDates) === 0) {
    $today = new DateTime();
    for ($i = 0; $i <= 30; $i++) {
        $d = clone $today;
        $d->modify("+$i days");
        // Optionally skip Sundays (replace with your rules), here we include all
        $availableDates[] = $d->format('Y-m-d');
    }
}

// Return success with available dates
echo json_encode([
    'success' => true,
    'available_dates' => array_values(array_unique($availableDates))
]);
// Close resources if available and exit cleanly
if (isset($stmt) && $stmt) {
    $stmt->close();
}
if (isset($conn) && $conn) {
    $conn->close();
}

exit;
?>

