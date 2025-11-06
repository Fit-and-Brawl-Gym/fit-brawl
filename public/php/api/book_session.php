<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/booking_validator.php';
require_once '../../../includes/activity_logger.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$trainer_id = isset($_POST['trainer_id']) ? intval($_POST['trainer_id']) : 0;
$class_type = $_POST['class_type'] ?? '';
$booking_date = $_POST['booking_date'] ?? '';
$session_time = $_POST['session_time'] ?? '';

// Validate required fields
if (!$trainer_id || empty($class_type) || empty($booking_date) || empty($session_time)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate enums
$valid_sessions = ['Morning', 'Afternoon', 'Evening'];
$valid_classes = ['Boxing', 'Muay Thai', 'MMA', 'Gym'];

if (!in_array($session_time, $valid_sessions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid session time']);
    exit;
}

if (!in_array($class_type, $valid_classes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid class type']);
    exit;
}

try {
    // Initialize validator and activity logger
    $validator = new BookingValidator($conn);
    ActivityLogger::init($conn);

    // Run all validations
    $validation = $validator->validateBooking($user_id, $trainer_id, $class_type, $booking_date, $session_time);

    if (!$validation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => $validation['message'],
            'failed_check' => $validation['failed_check'] ?? null
        ]);
        exit;
    }

    // Get trainer info for response
    $trainer_stmt = $conn->prepare("SELECT name FROM trainers WHERE id = ?");
    $trainer_stmt->bind_param("i", $trainer_id);
    $trainer_stmt->execute();
    $trainer_result = $trainer_stmt->get_result();
    $trainer = $trainer_result->fetch_assoc();
    $trainer_name = $trainer['name'];
    $trainer_stmt->close();

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert booking
        $insert_stmt = $conn->prepare("
            INSERT INTO user_reservations 
            (user_id, trainer_id, session_time, class_type, booking_date, booking_status) 
            VALUES (?, ?, ?, ?, ?, 'confirmed')
        ");
        $insert_stmt->bind_param("iisss", $user_id, $trainer_id, $session_time, $class_type, $booking_date);

        if (!$insert_stmt->execute()) {
            throw new Exception('Failed to create booking');
        }

        $booking_id = $conn->insert_id;
        $insert_stmt->close();

        // Commit transaction
        $conn->commit();

        // Get session hours for display
        $session_hours = $session_time === 'Morning' ? '7-11 AM' :
            ($session_time === 'Afternoon' ? '1-5 PM' : '6-10 PM');

        // Log activity
        $username = $_SESSION['username'] ?? 'User';
        $log_details = "Booked {$class_type} session with {$trainer_name} on {$booking_date} ({$session_time}: {$session_hours})";
        ActivityLogger::log('session_booked', $username, $booking_id, $log_details);

        // Calculate weekly bookings for the booked week (AFTER insertion)
        $booking_timestamp = strtotime($booking_date);
        $day_of_week = date('w', $booking_timestamp); // 0 (Sunday) to 6 (Saturday)
        $week_start = date('Y-m-d', strtotime($booking_date . ' -' . $day_of_week . ' days'));
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

        $count_stmt = $conn->prepare("
            SELECT COUNT(*) as booking_count
            FROM user_reservations 
            WHERE user_id = ? 
            AND booking_date BETWEEN ? AND ?
            AND booking_status IN ('confirmed', 'completed')
        ");
        $count_stmt->bind_param("iss", $user_id, $week_start, $week_end);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $weekly_count = (int) $count_row['booking_count'];
        $count_stmt->close();

        echo json_encode([
            'success' => true,
            'booking_id' => $booking_id,
            'message' => 'Session booked successfully!',
            'details' => [
                'trainer' => $trainer_name,
                'class' => $class_type,
                'date' => date('F j, Y', strtotime($booking_date)),
                'session' => $session_time,
                'session_hours' => $session_hours,
                'user_weekly_bookings' => $weekly_count,
                'weekly_limit' => 12,
                'facility_trainers' => $validation['facility_count'] + 1
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Booking error for user $user_id: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your booking. Please try again.'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>