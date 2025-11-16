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

    // Note: Time cutoff validation (30 minutes before end) is now done 
    // in book_session.php with actual selected start/end times

    // Get facility capacity info
    $facility_check = $validator->validateFacilityCapacity($class_type, $date, $session_time);
    $facility_slots_used = $facility_check['count'];
    $facility_slots_max = 2;
    $facility_available = $facility_slots_used < $facility_slots_max;

    // Get day of week for day-off checking
    $day_of_week = date('l', strtotime($date));

    // Note: Session parameter is legacy - kept for backward compatibility
    // For trainer availability checks, use shift-based time ranges
    $session_times = [
        'Morning' => ['start' => '07:00:00', 'end' => '15:00:00'],
        'Afternoon' => ['start' => '11:00:00', 'end' => '19:00:00'],
        'Evening' => ['start' => '14:00:00', 'end' => '22:00:00']
    ];
    $session_start_time = $date . ' ' . $session_times[$session_time]['start'];
    $session_end_time = $date . ' ' . $session_times[$session_time]['end'];

    // Query to get all trainers with matching specialization and their shift info
    $query = "
        SELECT
            t.id,
            t.name,
            t.specialization,
            t.photo,
            t.status AS trainer_status,
            ts.shift_type,
            ts.custom_start_time,
            ts.custom_end_time,
            ts.break_start_time,
            ts.break_end_time
        FROM trainers t
        LEFT JOIN trainer_shifts ts ON t.id = ts.trainer_id AND ts.day_of_week = ?
        WHERE t.specialization = ?
        AND t.deleted_at IS NULL
        AND t.status = 'Active'
        ORDER BY t.name
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $day_of_week, $class_type);
    $stmt->execute();
    $result = $stmt->get_result();

    // Get user's weekly limit and current usage
    $user_id = $_SESSION['user_id'] ?? null;
    $weekly_limit_hours = 48; // Default
    $current_week_usage_minutes = 0;
    
    if ($user_id) {
        // Get user's membership plan weekly limit
        $limit_query = "SELECT m.weekly_hours_limit 
                        FROM user_memberships um
                        JOIN memberships m ON um.plan_id = m.id
                        WHERE um.user_id = ? 
                        AND um.membership_status = 'active'
                        ORDER BY um.start_date DESC
                        LIMIT 1";
        $limit_stmt = $conn->prepare($limit_query);
        $limit_stmt->bind_param("i", $user_id);
        $limit_stmt->execute();
        $limit_result = $limit_stmt->get_result();
        if ($limit_row = $limit_result->fetch_assoc()) {
            $weekly_limit_hours = (int)$limit_row['weekly_hours_limit'];
        }
        $limit_stmt->close();
        
        // Get current week usage (Sunday to Saturday)
        $usage_query = "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
                        FROM user_reservations
                        WHERE user_id = ?
                        AND booking_status IN ('confirmed', 'pending')
                        AND YEARWEEK(booking_date, 0) = YEARWEEK(CURDATE(), 0)
                        AND booking_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) + 1 DAY)";
        $usage_stmt = $conn->prepare($usage_query);
        $usage_stmt->bind_param("i", $user_id);
        $usage_stmt->execute();
        $usage_result = $usage_stmt->get_result();
        if ($usage_row = $usage_result->fetch_assoc()) {
            $current_week_usage_minutes = (int)($usage_row['total_minutes'] ?? 0);
        }
        $usage_stmt->close();
    }

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
            $block_check = $validator->validateAdminBlock($trainer_id, $session_start_time, $session_end_time);
            if (!$block_check['valid']) {
                $trainer_status = 'unavailable';
                $unavailable_reason = 'blocked';
            }
        }

        // Check if trainer already has booking
        if ($trainer_status === 'available') {
            $availability_check = $validator->validateTrainerAvailability($trainer_id, $session_start_time, $session_end_time);
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

        // Get all bookings for this trainer on this date (for timeline display)
        $bookings_query = "
            SELECT start_time, end_time, class_type, booking_status
            FROM user_reservations
            WHERE trainer_id = ?
            AND booking_date = ?
            AND booking_status = 'confirmed'
            AND start_time IS NOT NULL
            AND end_time IS NOT NULL
            ORDER BY start_time
        ";
        
        $bookings_stmt = $conn->prepare($bookings_query);
        $bookings_stmt->bind_param("is", $trainer_id, $date);
        $bookings_stmt->execute();
        $bookings_result = $bookings_stmt->get_result();
        
        $available_slots = [];
        while ($booking = $bookings_result->fetch_assoc()) {
            $available_slots[] = [
                'start_time' => date('H:i', strtotime($booking['start_time'])),
                'end_time' => date('H:i', strtotime($booking['end_time'])),
                'status' => 'booked',
                'class_type' => $booking['class_type']
            ];
        }
        $bookings_stmt->close();

        $trainers[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'specialization' => $row['specialization'],
            'avatar' => $row['photo'] ? '../../../uploads/trainers/' . $row['photo'] : null,
            'photo' => $row['photo'] ?? 'default-trainer.jpg',
            'status' => $trainer_status,
            'shift' => $row['shift_type'] ?? 'Morning',
            'shift_start' => $row['custom_start_time'] ?? null,
            'shift_end' => $row['custom_end_time'] ?? null,
            'break_start' => $row['break_start_time'] ?? null,
            'break_end' => $row['break_end_time'] ?? null,
            'available_slots' => $available_slots
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
        'available_count' => count(array_filter($trainers, fn($t) => $t['status'] === 'available')),
        'weekly_limit_hours' => $weekly_limit_hours,
        'current_week_usage_minutes' => $current_week_usage_minutes
    ]);

} catch (Exception $e) {
    error_log("Error in get_available_trainers.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching available trainers',
        'debug_error' => $e->getMessage(),
        'debug_file' => $e->getFile(),
        'debug_line' => $e->getLine()
    ]);
} finally{
    if (isset($conn)) {
        $conn->close();
    }
}
?>