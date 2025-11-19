<?php
// Prevent any output before headers
ob_start();

// Disable error display for API
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/booking_validator.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';

// Set JSON header immediately
header('Content-Type: application/json');

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

    // Note: 30-minute time cutoff validation is done in book_session.php
    // with actual user-selected start/end times, not here at the session period level.
    // Users should be able to view available trainers even if the session period 
    // is ending soon, as they may select earlier time slots within that period.

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

        // Check if trainer has ANY availability during the session period
        // Note: For time-based booking, we don't mark trainer as "booked" entirely
        // We let users select them and then show available time slots
        // Only mark as unavailable if their ENTIRE shift is outside the session period or fully booked
        if ($trainer_status === 'available') {
            // Check if trainer has any shift time during this session
            $shift_start = $row['custom_start_time'];
            $shift_end = $row['custom_end_time'];
            
            // If no custom times, use default shift times
            if (!$shift_start || !$shift_end) {
                $default_shifts = [
                    'morning' => ['07:00:00', '15:00:00'],
                    'afternoon' => ['11:00:00', '19:00:00'],
                    'night' => ['14:00:00', '22:00:00']
                ];
                if (isset($row['shift_type']) && isset($default_shifts[$row['shift_type']])) {
                    $shift_start = $default_shifts[$row['shift_type']][0];
                    $shift_end = $default_shifts[$row['shift_type']][1];
                }
            }
            
            // For time-based booking, always show trainers as available if they have a shift
            // Let the time slot selection handle showing which specific times are free
            // This allows partial availability instead of all-or-nothing
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

        $booked_slots = [];
        while ($booking = $bookings_result->fetch_assoc()) {
            $booked_slots[] = [
                'start_time' => date('H:i', strtotime($booking['start_time'])),
                'end_time' => date('H:i', strtotime($booking['end_time'])),
                'status' => 'booked',
                'class_type' => $booking['class_type']
            ];
        }
        $bookings_stmt->close();
        
        // Check if trainer has ANY available time slots (not fully booked)
        // Get shift times for this trainer
        $shift_start_check = $row['custom_start_time'];
        $shift_end_check = $row['custom_end_time'];
        $break_start = $row['break_start_time'];
        $break_end = $row['break_end_time'];
        
        if (!$shift_start_check || !$shift_end_check) {
            $default_shifts = [
                'morning' => ['07:00:00', '15:00:00', '12:00:00', '13:00:00'],
                'afternoon' => ['11:00:00', '19:00:00', '15:00:00', '16:00:00'],
                'night' => ['14:00:00', '22:00:00', '18:00:00', '19:00:00']
            ];
            $shift_type = $row['shift_type'] ?? 'morning';
            if (isset($default_shifts[$shift_type])) {
                $shift_start_check = $default_shifts[$shift_type][0];
                $shift_end_check = $default_shifts[$shift_type][1];
                if (!$break_start) $break_start = $default_shifts[$shift_type][2];
                if (!$break_end) $break_end = $default_shifts[$shift_type][3];
            }
        }
        
        // Calculate if there are any available 30-minute slots
        $has_available_slots = false;
        if ($trainer_status === 'available' && $shift_start_check && $shift_end_check) {
            $shift_start_minutes = (int)substr($shift_start_check, 0, 2) * 60 + (int)substr($shift_start_check, 3, 2);
            $shift_end_minutes = (int)substr($shift_end_check, 0, 2) * 60 + (int)substr($shift_end_check, 3, 2);
            
            // Check each 30-minute slot in the shift
            for ($slot_start = $shift_start_minutes; $slot_start < $shift_end_minutes; $slot_start += 30) {
                $slot_end = $slot_start + 30;
                
                // Skip if in break time
                if ($break_start && $break_end) {
                    $break_start_minutes = (int)substr($break_start, 0, 2) * 60 + (int)substr($break_start, 3, 2);
                    $break_end_minutes = (int)substr($break_end, 0, 2) * 60 + (int)substr($break_end, 3, 2);
                    if ($slot_start >= $break_start_minutes && $slot_end <= $break_end_minutes) {
                        continue;
                    }
                }
                
                // Check if slot overlaps with any booking
                $is_available = true;
                foreach ($booked_slots as $booking) {
                    $booking_start_minutes = (int)substr($booking['start_time'], 0, 2) * 60 + (int)substr($booking['start_time'], 3, 2);
                    $booking_end_minutes = (int)substr($booking['end_time'], 0, 2) * 60 + (int)substr($booking['end_time'], 3, 2);
                    
                    // Check overlap
                    if ($slot_start < $booking_end_minutes && $slot_end > $booking_start_minutes) {
                        $is_available = false;
                        break;
                    }
                }
                
                if ($is_available) {
                    $has_available_slots = true;
                    break;
                }
            }
        }
        
        // Mark as fully-booked if no available slots
        if ($trainer_status === 'available' && !$has_available_slots && count($booked_slots) > 0) {
            $trainer_status = 'fully-booked';
            $unavailable_reason = 'fully_booked';
        }

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
            'booked_slots' => $booked_slots,
            'has_available_slots' => $has_available_slots
        ];
    }

    $stmt->close();

    // Sort: available first, then fully-booked, then unavailable
    usort($trainers, function ($a, $b) {
        $order = ['available' => 0, 'fully-booked' => 1, 'unavailable' => 2];
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
        'available_count' => count(array_filter($trainers, function($t) { return $t['status'] === 'available'; }))
    ], 200);

} catch (Exception $e) {
    error_log("Error in get_available_trainers.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching available trainers',
        'debug_error' => $e->getMessage(),
        'debug_file' => $e->getFile(),
        'debug_line' => $e->getLine()
    ], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
    ob_end_flush();
}
?>
