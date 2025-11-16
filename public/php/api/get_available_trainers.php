<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/booking_validator.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require GET method
if (!ApiSecurityMiddleware::requireMethod('GET')) {
    exit; // Already sent response
}

// Rate limiting - 60 requests per minute per IP (public endpoint, used frequently)
$identifier = 'get_available_trainers:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
ApiSecurityMiddleware::applyRateLimit($conn, $identifier, 60, 60);

// Get parameters
$date = $_GET['date'] ?? '';
$session_time = $_GET['session'] ?? '';
$class_type = $_GET['class'] ?? '';

// Validate and sanitize input
$validation = ApiSecurityMiddleware::validateInput([
    'date' => [
        'type' => 'date',
        'required' => true,
        'format' => 'Y-m-d'
    ],
    'session' => [
        'type' => 'whitelist',
        'required' => true,
        'allowed' => ['Morning', 'Afternoon', 'Evening']
    ],
    'class' => [
        'type' => 'whitelist',
        'required' => true,
        'allowed' => ['Boxing', 'Muay Thai', 'MMA', 'Gym']
    ]
], $_GET);

if (!$validation['valid']) {
    $errors = implode(', ', $validation['errors']);
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Validation failed: ' . $errors
    ], 400);
}

$data = $validation['data'];
$date_obj = $data['date']; // DateTime object
$date = $date_obj instanceof DateTime ? $date_obj->format('Y-m-d') : $data['date'];
$session_time = $data['session'];
$class_type = $data['class'];

try {
    $validator = new BookingValidator($conn);

    // Check if date is valid (not past, not too far future)
    $date_check = $validator->validateBookingDate($date);
    if (!$date_check['valid']) {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => $date_check['message']
        ], 400);
    }

    // Check if session is too close to ending (within 30 minutes)
    $session_end_times = [
        'Morning' => '11:00:00',
        'Afternoon' => '17:00:00',
        'Evening' => '22:00:00'
    ];

    $today = date('Y-m-d');
    $now = date('H:i:s');

    // If booking for today, check if there's at least 30 minutes left
    if ($date === $today) {
        $session_end = $session_end_times[$session_time];
        $end_time = strtotime($date . ' ' . $session_end);
        $current_time = strtotime($date . ' ' . $now);
        $minutes_remaining = ($end_time - $current_time) / 60;

        if ($minutes_remaining < 30) {
            ApiSecurityMiddleware::sendJsonResponse([
                'success' => false,
                'message' => 'Cannot book this session. Less than 30 minutes remaining before session ends.',
                'time_cutoff' => true
            ], 400);
        }
    }

    // Get facility capacity info
    $facility_check = $validator->validateFacilityCapacity($class_type, $date, $session_time);
    $facility_slots_used = $facility_check['count'];
    $facility_slots_max = 2;
    $facility_available = $facility_slots_used < $facility_slots_max;

    // Get day of week for day-off checking
    $day_of_week = date('l', strtotime($date));

    // Query to get all trainers with matching specialization
    $query = "
        SELECT
            t.id,
            t.name,
            t.specialization,
            t.photo,
            t.status AS trainer_status
        FROM trainers t
        WHERE t.specialization = ?
        AND t.deleted_at IS NULL
        AND t.status = 'Active'
        ORDER BY t.name
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $class_type);
    $stmt->execute();
    $result = $stmt->get_result();

    $trainers = [];

    while ($row = $result->fetch_assoc()) {
        $trainer_id = $row['id'];
        $trainer_status = 'available';
        $unavailable_reason = null;

        // Check day-off
        $dayoff_check = $validator->validateDayOff($trainer_id, $date);
        if (!$dayoff_check['valid']) {
            $trainer_status = 'unavailable';
            $unavailable_reason = 'day_off';
        }

        // Check admin block
        if ($trainer_status === 'available') {
            $block_check = $validator->validateAdminBlock($trainer_id, $date, $session_time);
            if (!$block_check['valid']) {
                $trainer_status = 'unavailable';
                $unavailable_reason = 'blocked';
            }
        }

        // Check if trainer already has booking
        if ($trainer_status === 'available') {
            $availability_check = $validator->validateTrainerAvailability($trainer_id, $date, $session_time);
            if (!$availability_check['valid']) {
                $trainer_status = 'booked';
                $unavailable_reason = 'already_booked';
            }
        }

        // Check facility capacity (only if trainer is available)
        if ($trainer_status === 'available' && !$facility_available) {
            $trainer_status = 'unavailable';
            $unavailable_reason = 'facility_full';
        }

        $trainers[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'specialization' => $row['specialization'],
            'photo' => $row['photo'] ?? 'default-trainer.jpg',
            'status' => $trainer_status
        ];
    }

    $stmt->close();

    // Sort: available first, then booked, then unavailable
    usort($trainers, function ($a, $b) {
        $order = ['available' => 0, 'booked' => 1, 'unavailable' => 2];
        return $order[$a['status']] - $order[$b['status']];
    });

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'date' => $date,
        'day_of_week' => $day_of_week,
        'session' => $session_time,
        'session_hours' => $session_time === 'Morning' ? '7-11 AM' : ($session_time === 'Afternoon' ? '1-5 PM' : '6-10 PM'),
        'class_type' => $class_type,
        'facility_slots_used' => $facility_slots_used,
        'facility_slots_max' => $facility_slots_max,
        'facility_available' => $facility_available,
        'trainers' => $trainers,
        'trainer_count' => count($trainers),
        'available_count' => count(array_filter($trainers, fn($t) => $t['status'] === 'available'))
    ], 200);

} catch (Exception $e) {
    error_log("Error in get_available_trainers.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching available trainers'
    ], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
