<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/booking_validator.php';
require_once '../../../includes/activity_logger.php';
require_once '../../../includes/mail_config.php'; // make sure this defines sendTrainerBookingNotification()
require_once __DIR__ . '/../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// --- DEV: enable during development only ---
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

try {
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

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        echo json_encode(['success' => false, 'message' => 'Your session expired. Please refresh and try again.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
        $rateCheck = ApiRateLimiter::checkAndIncrement($conn, 'book_session:' . $user_id, 8, 60);
        if ($rateCheck['blocked']) {
            $minutes = ceil($rateCheck['retry_after'] / 60);
            echo json_encode([
                'success' => false,
                'message' => "Too many booking attempts. Please wait {$minutes} minute(s) and try again.",
                'failed_check' => 'rate_limit'
            ]);
            exit;
        }
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

    // Initialize validator and activity logger
    $validator = new BookingValidator($conn);
    ActivityLogger::init($conn);

    // Check if booking date is within membership expiration + grace period
    $grace_period_days = 3;
    $membership_check = $conn->prepare("
        SELECT end_date, plan_name
        FROM user_memberships
        WHERE user_id = ?
        AND request_status = 'approved'
        AND membership_status = 'active'
        ORDER BY end_date DESC
        LIMIT 1
    ");
    $membership_check->bind_param("s", $user_id);
    $membership_check->execute();
    $membership_result = $membership_check->get_result();

    if ($membership_result && $membership_result->num_rows > 0) {
        $membership_data = $membership_result->fetch_assoc();
        $end_date = $membership_data['end_date'];
        $plan_name = $membership_data['plan_name'];

        // Calculate max booking date (end_date + grace period)
        $end_date_obj = new DateTime($end_date);
        $max_booking_date = clone $end_date_obj;
        $max_booking_date->modify("+{$grace_period_days} days");

        $booking_date_obj = new DateTime($booking_date);

        if ($booking_date_obj > $max_booking_date) {
            $membership_check->close();
            echo json_encode([
                'success' => false,
                'message' => "Cannot book beyond your membership expiration. Your {$plan_name} plan expires on " .
                    $end_date_obj->format('F d, Y') . " (booking allowed until " .
                    $max_booking_date->format('F d, Y') . " with grace period). Please visit the gym to renew or upgrade your membership.",
                'failed_check' => 'membership_expiration'
            ]);
            exit;
        }
    }
    $membership_check->close();

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

    // Get trainer info for response & email (name + email)
    $trainer_stmt = $conn->prepare("SELECT name, email FROM trainers WHERE id = ?");
    if ($trainer_stmt === false) {
        throw new Exception('Failed to prepare trainer query');
    }
    $trainer_stmt->bind_param("i", $trainer_id);
    $trainer_stmt->execute();
    $trainer_result = $trainer_stmt->get_result();
    $trainer_data = $trainer_result ? $trainer_result->fetch_assoc() : null;
    $trainer_stmt->close();

    if (!$trainer_data) {
        echo json_encode(['success' => false, 'message' => 'Trainer not found']);
        exit;
    }
    $trainer_name = $trainer_data['name'] ?? 'Unknown';

    // Get member (user) info for email
    $member_stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
    if ($member_stmt === false) {
        throw new Exception('Failed to prepare member query');
    }
    $member_stmt->bind_param("s", $user_id);
    $member_stmt->execute();
    $member_result = $member_stmt->get_result();
    $member_data = $member_result ? $member_result->fetch_assoc() : null;
    $member_stmt->close();

    if (!$member_data) {
        echo json_encode(['success' => false, 'message' => 'Member not found']);
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert booking
        $insert_stmt = $conn->prepare("
            INSERT INTO user_reservations
            (user_id, trainer_id, session_time, class_type, booking_date, booking_status)
            VALUES (?, ?, ?, ?, ?, 'confirmed')
        ");
        if ($insert_stmt === false) {
            throw new Exception('Failed to prepare insert statement');
        }
        $insert_stmt->bind_param("sisss", $user_id, $trainer_id, $session_time, $class_type, $booking_date);

        if (!$insert_stmt->execute()) {
            $err = $insert_stmt->error ?: 'unknown';
            $insert_stmt->close();
            throw new Exception('Failed to create booking: ' . $err);
        }

        $booking_id = $conn->insert_id;
        $insert_stmt->close();

        // Commit transaction (booking is now saved)
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
        if ($count_stmt === false) {
            throw new Exception('Failed to prepare count query');
        }
        $count_stmt->bind_param("sss", $user_id, $week_start, $week_end);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result ? $count_result->fetch_assoc() : ['booking_count' => 0];
        $weekly_count = (int) $count_row['booking_count'];
        $count_stmt->close();

        // Attempt to send email notification to trainer (after commit)
        try {
            if (function_exists('sendTrainerBookingNotification')) {
                $email_sent = sendTrainerBookingNotification(
                    $trainer_data['email'],
                    $trainer_data['name'],
                    $member_data['username'],
                    $booking_date,
                    $session_time,
                    $class_type
                );

                if (!$email_sent) {
                    error_log("Failed to send booking notification to trainer: {$trainer_data['email']}");
                }
            } else {
                // Mail function not defined in mail_config.php
                error_log('sendTrainerBookingNotification() not defined. Check mail_config.php');
            }
        } catch (Exception $mailEx) {
            // Make sure mail failures don't break the main flow
            error_log('Mail sending error: ' . $mailEx->getMessage());
        }

        // Respond success (booking done)
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
        exit;

    } catch (Exception $e) {
        // Rollback and bubble up to outer catch
        if ($conn) {
            $conn->rollback();
        }
        throw $e;
    }

} catch (Exception $e) {
    // Log the actual error for debugging
    $user_id_log = $_SESSION['user_id'] ?? 'unknown';
    error_log("Booking error for user $user_id_log: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your booking. Please try again.'
    ]);
    exit;
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
