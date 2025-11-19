<?php
/**
 * Get Trainer Availability API
 * Returns available 30-minute time slots for a trainer on a specific date
 * 
 * Request: POST
 * Parameters:
 *   - trainer_id: int (required)
 *   - date: string (Y-m-d format, required)
 *   - class_type: string (optional, for specialization validation)
 * 
 * Response: JSON
 *   - success: boolean
 *   - available_slots: array of {start_time, end_time, formatted_time}
 *   - booked_slots: array of occupied time ranges
 *   - shift_info: object with shift details
 */

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/booking_validator.php';
require_once '../../../includes/timezone_helper.php';

// Input validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_POST['trainer_id']) || !isset($_POST['date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters: trainer_id, date']);
    exit;
}

$trainer_id = (int)$_POST['trainer_id'];
$date = $_POST['date'];
$class_type = $_POST['class_type'] ?? null;
$exclude_booking_id = isset($_POST['exclude_booking_id']) ? (int)$_POST['exclude_booking_id'] : null;

// Debug logging
error_log("🔍 get_trainer_availability.php - exclude_booking_id: " . ($exclude_booking_id ?? 'NULL'));

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Use Y-m-d']);
    exit;
}

// Verify trainer exists
$trainer_stmt = $conn->prepare("SELECT id, specialization FROM trainers WHERE id = ? AND deleted_at IS NULL");
$trainer_stmt->bind_param("i", $trainer_id);
$trainer_stmt->execute();
$trainer_result = $trainer_stmt->get_result();

if ($trainer_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Trainer not found']);
    exit;
}

$trainer = $trainer_result->fetch_assoc();
$trainer_stmt->close();

// Check specialization match if class_type provided
if ($class_type && $trainer['specialization'] !== $class_type) {
    echo json_encode([
        'success' => false,
        'message' => "Trainer specializes in {$trainer['specialization']}, not {$class_type}"
    ]);
    exit;
}

// Get trainer's shift for this day
$day_of_week = TimezoneHelper::getDayOfWeek($date);
$shift_stmt = $conn->prepare("
    SELECT shift_type, custom_start_time, custom_end_time, 
           break_start_time, break_end_time, is_active
    FROM trainer_shifts
    WHERE trainer_id = ? AND day_of_week = ? AND is_active = 1
");
$shift_stmt->bind_param("is", $trainer_id, $day_of_week);
$shift_stmt->execute();
$shift_result = $shift_stmt->get_result();

// Check if trainer has a shift or use legacy day-off check
$shift_info = null;
$shift_start_time = null;
$shift_end_time = null;
$break_start = null;
$break_end = null;

if ($shift_result->num_rows > 0) {
    $shift = $shift_result->fetch_assoc();
    $shift_stmt->close();
    
    // Check if day off
    if ($shift['shift_type'] === 'none') {
        echo json_encode([
            'success' => false,
            'message' => "Trainer is not available on {$day_of_week}s",
            'is_day_off' => true
        ]);
        exit;
    }
    
    // Determine shift hours
    if ($shift['custom_start_time'] && $shift['custom_end_time']) {
        $shift_start_time = $shift['custom_start_time'];
        $shift_end_time = $shift['custom_end_time'];
    } else {
        // Default shift times
        $shift_times = [
            'morning' => ['07:00:00', '15:00:00'],    // 7am - 3pm
            'afternoon' => ['11:00:00', '19:00:00'],  // 11am - 7pm
            'night' => ['15:00:00', '22:00:00']       // 3pm - 10pm
        ];
        $shift_start_time = $shift_times[$shift['shift_type']][0];
        $shift_end_time = $shift_times[$shift['shift_type']][1];
    }
    
    // Break times
    if ($shift['break_start_time'] && $shift['break_end_time']) {
        $break_start = $shift['break_start_time'];
        $break_end = $shift['break_end_time'];
    }
    
    $shift_info = [
        'shift_type' => $shift['shift_type'],
        'start_time' => $shift_start_time,
        'end_time' => $shift_end_time,
        'start_time_formatted' => date('g:i A', strtotime($shift_start_time)),
        'end_time_formatted' => date('g:i A', strtotime($shift_end_time)),
        'break_start' => $break_start,
        'break_end' => $break_end,
        'break_formatted' => $break_start ? date('g:i A', strtotime($break_start)) . ' - ' . date('g:i A', strtotime($break_end)) : null
    ];
} else {
    $shift_stmt->close();
    
    // Check legacy day-off system
    $dayoff_stmt = $conn->prepare("
        SELECT is_day_off FROM trainer_day_offs 
        WHERE trainer_id = ? AND day_of_week = ? AND is_day_off = 1
    ");
    $dayoff_stmt->bind_param("is", $trainer_id, $day_of_week);
    $dayoff_stmt->execute();
    $dayoff_result = $dayoff_stmt->get_result();
    
    if ($dayoff_result->num_rows > 0) {
        $dayoff_stmt->close();
        echo json_encode([
            'success' => false,
            'message' => "Trainer is not available on {$day_of_week}s",
            'is_day_off' => true
        ]);
        exit;
    }
    $dayoff_stmt->close();
    
    // No shift configured, use default hours (7am - 10pm)
    $shift_start_time = '07:00:00';
    $shift_end_time = '22:00:00';
    $shift_info = [
        'shift_type' => 'default',
        'start_time' => $shift_start_time,
        'end_time' => $shift_end_time,
        'start_time_formatted' => '7:00 AM',
        'end_time_formatted' => '10:00 PM',
        'break_start' => null,
        'break_end' => null,
        'break_formatted' => null
    ];
}

// Get admin blocks for this trainer on this date
$blocks = [];
$block_stmt = $conn->prepare("
    SELECT is_all_day, block_start_time, block_end_time, reason
    FROM trainer_availability_blocks
    WHERE trainer_id = ? AND date = ? AND block_status = 'blocked'
");
$block_stmt->bind_param("is", $trainer_id, $date);
$block_stmt->execute();
$block_result = $block_stmt->get_result();

while ($block = $block_result->fetch_assoc()) {
    if ($block['is_all_day']) {
        echo json_encode([
            'success' => false,
            'message' => 'Trainer is blocked for the entire day' . ($block['reason'] ? ': ' . $block['reason'] : ''),
            'is_blocked' => true
        ]);
        exit;
    }
    
    if ($block['block_start_time'] && $block['block_end_time']) {
        $blocks[] = [
            'start' => $block['block_start_time'],
            'end' => $block['block_end_time'],
            'reason' => $block['reason']
        ];
    }
}
$block_stmt->close();

// Get existing bookings for this trainer on this date
$booked_slots = [];

// Build query with optional booking exclusion for reschedule scenarios
if ($exclude_booking_id) {
    $booking_stmt = $conn->prepare("
        SELECT start_time, end_time, class_type, buffer_minutes
        FROM user_reservations
        WHERE trainer_id = ? 
        AND DATE(start_time) = ?
        AND booking_status = 'confirmed'
        AND start_time IS NOT NULL
        AND end_time IS NOT NULL
        AND id != ?
        ORDER BY start_time
    ");
    $booking_stmt->bind_param("isi", $trainer_id, $date, $exclude_booking_id);
} else {
    $booking_stmt = $conn->prepare("
        SELECT start_time, end_time, class_type, buffer_minutes
        FROM user_reservations
        WHERE trainer_id = ? 
        AND DATE(start_time) = ?
        AND booking_status = 'confirmed'
        AND start_time IS NOT NULL
        AND end_time IS NOT NULL
        ORDER BY start_time
    ");
    $booking_stmt->bind_param("is", $trainer_id, $date);
}

$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();

error_log("🔍 Found " . $booking_result->num_rows . " bookings for trainer $trainer_id on $date" . ($exclude_booking_id ? " (excluding booking #$exclude_booking_id)" : ""));

while ($booking = $booking_result->fetch_assoc()) {
    $buffer = $booking['buffer_minutes'] ?? 10;
    
    // Add buffer time
    $start_with_buffer = TimezoneHelper::create($booking['start_time']);
    $start_with_buffer->modify("-{$buffer} minutes");
    $end_with_buffer = TimezoneHelper::create($booking['end_time']);
    $end_with_buffer->modify("+{$buffer} minutes");
    
    $booked_slots[] = [
        'start' => TimezoneHelper::toMySQLDateTime($start_with_buffer),
        'end' => TimezoneHelper::toMySQLDateTime($end_with_buffer),
        'start_formatted' => $start_with_buffer->format('g:i A'),
        'end_formatted' => $end_with_buffer->format('g:i A'),
        'class_type' => $booking['class_type'],
        'includes_buffer' => true
    ];
}
$booking_stmt->close();

// Generate 30-minute time slots within shift hours
$available_slots = [];
$slot_start = TimezoneHelper::create($date . ' ' . $shift_start_time);
$shift_end_dt = TimezoneHelper::create($date . ' ' . $shift_end_time);

// Create break time objects if exists
$break_start_dt = $break_start ? TimezoneHelper::create($date . ' ' . $break_start) : null;
$break_end_dt = $break_end ? TimezoneHelper::create($date . ' ' . $break_end) : null;

while ($slot_start < $shift_end_dt) {
    $slot_end = clone $slot_start;
    $slot_end->modify('+30 minutes');
    
    // Don't generate slots past shift end
    if ($slot_end > $shift_end_dt) {
        break;
    }
    
    $slot_start_str = TimezoneHelper::toMySQLDateTime($slot_start);
    $slot_end_str = TimezoneHelper::toMySQLDateTime($slot_end);
    
    $is_available = true;
    
    // Check if slot overlaps with break time
    if ($break_start_dt && $break_end_dt) {
        if (!($slot_end <= $break_start_dt || $slot_start >= $break_end_dt)) {
            $is_available = false;
        }
    }
    
    // Check if slot overlaps with admin blocks
    foreach ($blocks as $block) {
        $block_start_dt = TimezoneHelper::create($block['start']);
        $block_end_dt = TimezoneHelper::create($block['end']);
        
        if (!($slot_end <= $block_start_dt || $slot_start >= $block_end_dt)) {
            $is_available = false;
            break;
        }
    }
    
    // Check if slot overlaps with existing bookings
    foreach ($booked_slots as $booked) {
        $booked_start = TimezoneHelper::create($booked['start']);
        $booked_end = TimezoneHelper::create($booked['end']);
        
        if (!($slot_end <= $booked_start || $slot_start >= $booked_end)) {
            $is_available = false;
            break;
        }
    }
    
    if ($is_available) {
        $available_slots[] = [
            'start_time' => $slot_start_str,
            'end_time' => $slot_end_str,
            'start_time_formatted' => $slot_start->format('g:i A'),
            'end_time_formatted' => $slot_end->format('g:i A'),
            'formatted' => $slot_start->format('g:i A') . ' - ' . $slot_end->format('g:i A')
        ];
    }
    
    $slot_start = $slot_end;
}

echo json_encode([
    'success' => true,
    'trainer_id' => $trainer_id,
    'date' => $date,
    'day_of_week' => $day_of_week,
    'specialization' => $trainer['specialization'],
    'shift_info' => $shift_info,
    'available_slots' => $available_slots,
    'available_count' => count($available_slots),
    'booked_slots' => $booked_slots,
    'booked_count' => count($booked_slots),
    'admin_blocks' => $blocks,
    'excluded_booking_id' => $exclude_booking_id
]);
