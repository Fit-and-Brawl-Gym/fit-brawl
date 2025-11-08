<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/booking_validator.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get parameters
$date = $_GET['date'] ?? '';
$session_time = $_GET['session'] ?? '';
$class_type = $_GET['class'] ?? '';

// Validate required parameters
if (empty($date) || empty($session_time) || empty($class_type)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Validate session_time
$valid_sessions = ['Morning', 'Afternoon', 'Evening'];
if (!in_array($session_time, $valid_sessions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid session time']);
    exit;
}

// Validate class_type
$valid_classes = ['Boxing', 'Muay Thai', 'MMA', 'Gym'];
if (!in_array($class_type, $valid_classes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid class type']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

try {
    $validator = new BookingValidator($conn);

    // Check if date is valid (not past, not too far future)
    $date_check = $validator->validateBookingDate($date);
    if (!$date_check['valid']) {
        echo json_encode(['success' => false, 'message' => $date_check['message']]);
        exit;
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
            echo json_encode([
                'success' => false,
                'message' => 'Cannot book this session. Less than 30 minutes remaining before session ends.',
                'time_cutoff' => true
            ]);
            exit;
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

    echo json_encode([
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
    ]);

} catch (Exception $e) {
    error_log("Error in get_available_trainers.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching available trainers'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
