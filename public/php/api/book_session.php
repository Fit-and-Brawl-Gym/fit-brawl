<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/booking_validator.php';
require_once '../../../includes/timezone_helper.php';
require_once '../../../includes/activity_logger.php';
require_once '../../../includes/mail_config.php'; // make sure this defines sendTrainerBookingNotification()
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';
require_once __DIR__ . '/../../../includes/input_validator.php';

ApiSecurityMiddleware::setSecurityHeaders();
require_once '../../../includes/mail_config.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// --- DEV: enable during development only ---
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

try {
    // Require authentication
    $user = ApiSecurityMiddleware::requireAuth();
    if (!$user) {
        exit; // Already sent response
    }

    $user_id = $user['user_id'];

    // Require POST method
    if (!ApiSecurityMiddleware::requireMethod('POST')) {
        exit; // Already sent response
    }

    // Require CSRF token
    if (!ApiSecurityMiddleware::requireCSRF()) {
        exit; // Already sent response
    }

    // Rate limiting - 8 requests per minute per user
    ApiSecurityMiddleware::applyRateLimit($conn, 'book_session:' . $user_id, 8, 60);

    // Validate and sanitize input
    $validation = ApiSecurityMiddleware::validateInput([
        'trainer_id' => [
            'type' => 'integer',
            'required' => true,
            'min' => 1
        ],
        'class_type' => [
            'type' => 'whitelist',
            'required' => true,
            'allowed' => ['Boxing', 'Muay Thai', 'MMA', 'Gym']
        ],
        'booking_date' => [
            'type' => 'date',
            'required' => true,
            'format' => 'Y-m-d'
        ],
        'session_time' => [
            'type' => 'whitelist',
            'required' => true,
            'allowed' => ['Morning', 'Afternoon', 'Evening']
        ]
    ]);

    if (!$validation['valid']) {
        $errors = implode(', ', $validation['errors']);
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Validation failed: ' . $errors
        ], 400);
    }

    $data = $validation['data'];
    $trainer_id = $data['trainer_id'];
    $class_type = $data['class_type'];
    $booking_date_obj = $data['booking_date']; // DateTime object
    $booking_date = $booking_date_obj instanceof DateTime ? $booking_date_obj->format('Y-m-d') : $booking_date_obj;
    $session_time = $data['session_time'];
    // Parse JSON input if Content-Type is application/json
    $input = $_POST;
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($content_type, 'application/json') !== false) {
        $json_input = file_get_contents('php://input');
        $decoded = json_decode($json_input, true);
        if ($decoded) {
            $input = $decoded;
        }
    }

    // Check if admin is booking for another user
    $is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    $target_user_id = null;
    
    if ($is_admin && isset($input['user_id'])) {
        // Admin booking for a specific user
        $target_user_id = $input['user_id'];
        $user_id = $target_user_id;
    } else {
        // Regular member booking for themselves
        $user_id = $_SESSION['user_id'];
    }

    $trainer_id = isset($input['trainer_id']) ? intval($input['trainer_id']) : 0;
    $class_type = $input['class_type'] ?? '';
    
    // Admin can override weekly limit
    $override_weekly_limit = $is_admin && isset($input['override_weekly_limit']) && $input['override_weekly_limit'] === true;
    
    // Time-based booking: accept start_time and end_time
    $start_time = $input['start_time'] ?? '';
    $end_time = $input['end_time'] ?? '';
    
    // Legacy support: if session_time and booking_date provided instead of start_time/end_time
    $session_time = $input['session_time'] ?? '';
    $booking_date = $input['booking_date'] ?? '';

    // Validate required fields (either new time-based or legacy session-based)
    if (!$trainer_id || empty($class_type)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields: trainer_id and class_type']);
        exit;
    }

    // Check if using time-based or legacy booking
    $is_time_based = !empty($start_time) && !empty($end_time);
    $is_legacy = !empty($session_time) && !empty($booking_date);

    if (!$is_time_based && !$is_legacy) {
        echo json_encode([
            'success' => false, 
            'message' => 'Must provide either start_time/end_time OR session_time/booking_date'
        ]);
        exit;
    }

    // Validate class type
    $valid_classes = ['Boxing', 'Muay Thai', 'MMA', 'Gym'];
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
            ApiSecurityMiddleware::sendJsonResponse([
                'success' => false,
                'message' => "Cannot book beyond your membership expiration. Your {$plan_name} plan expires on " .
                    $end_date_obj->format('F d, Y') . " (booking allowed until " .
                    $max_booking_date->format('F d, Y') . " with grace period). Please visit the gym to renew or upgrade your membership.",
                'failed_check' => 'membership_expiration'
            ], 400);
            exit;
        }
    }
    $membership_check->close();

    // Run all validations
    $validation = $validator->validateBooking($user_id, $trainer_id, $class_type, $booking_date, $session_time);

    if (!$validation['valid']) {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => $validation['message'],
            'failed_check' => $validation['failed_check'] ?? null
        ], 400);
    }
    // Run validation based on booking type
    if ($is_time_based) {
        // Time-based booking: validate with start_time and end_time
        // Skip weekly limit check if admin override is enabled
        $validation = $validator->validateBooking(
            $user_id, 
            $trainer_id, 
            $class_type, 
            $start_time, 
            $end_time, 
            null, 
            $override_weekly_limit
        );
        
        if (!$validation['valid']) {
            $response = [
                'success' => false,
                'message' => $validation['message'],
                'failed_check' => $validation['failed_check'] ?? null
            ];
            
            // TODO: Add suggested_slots logic in Task 26 by calling get_trainer_availability.php
            // For now, just return the validation error
            
            echo json_encode($response);
            exit;
        }
        
        // Extract booking_date from start_time for display
        $booking_date = substr($start_time, 0, 10);
        
    } else {
        // Legacy session-based booking: use old validation method
        $valid_sessions = ['Morning', 'Afternoon', 'Evening'];
        if (!in_array($session_time, $valid_sessions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid session time']);
            exit;
        }
        
        $validation = $validator->validateBookingLegacy($user_id, $trainer_id, $class_type, $booking_date, $session_time);
        
        if (!$validation['valid']) {
            echo json_encode([
                'success' => false,
                'message' => $validation['message'],
                'failed_check' => $validation['failed_check'] ?? null
            ]);
            exit;
        }
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
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Trainer not found'
        ], 404);
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
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Member not found'
        ], 404);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert booking with appropriate fields based on booking type
        if ($is_time_based) {
            // Time-based booking: insert with start_time and end_time
            $insert_stmt = $conn->prepare("
                INSERT INTO user_reservations
                (user_id, trainer_id, class_type, booking_date, start_time, end_time, buffer_minutes, booking_status)
                VALUES (?, ?, ?, ?, ?, ?, 10, 'confirmed')
            ");
            if ($insert_stmt === false) {
                throw new Exception('Failed to prepare insert statement');
            }
            
            $insert_stmt->bind_param("sissss", $user_id, $trainer_id, $class_type, $booking_date, $start_time, $end_time);
            
        } else {
            // Legacy session-based booking: insert with session_time
            $insert_stmt = $conn->prepare("
                INSERT INTO user_reservations
                (user_id, trainer_id, session_time, class_type, booking_date, booking_status)
                VALUES (?, ?, ?, ?, ?, 'confirmed')
            ");
            if ($insert_stmt === false) {
                throw new Exception('Failed to prepare insert statement');
            }
            $insert_stmt->bind_param("sisss", $user_id, $trainer_id, $session_time, $class_type, $booking_date);
        }

        if (!$insert_stmt->execute()) {
            $err = $insert_stmt->error ?: 'unknown';
            $insert_stmt->close();
            throw new Exception('Failed to create booking: ' . $err);
        }

        $booking_id = $conn->insert_id;
        $insert_stmt->close();

        // Commit transaction (booking is now saved)
        $conn->commit();

        // Prepare display information based on booking type
        if ($is_time_based) {
            // Format times for display
            $start_dt = TimezoneHelper::create($start_time);
            $end_dt = TimezoneHelper::create($end_time);
            $time_display = $start_dt->format('g:i A') . ' - ' . $end_dt->format('g:i A');
            $duration_minutes = TimezoneHelper::calculateDurationMinutes($start_time, $end_time);
            $duration_display = floor($duration_minutes / 60) . 'h ' . ($duration_minutes % 60) . 'm';
        } else {
            // Legacy session display
            $time_display = $session_time . ' (' . ($session_time === 'Morning' ? '7-11 AM' :
                ($session_time === 'Afternoon' ? '1-5 PM' : '6-10 PM')) . ')';
            $duration_display = '4 hours';
        }

        // Log activity
        if ($is_admin && $target_user_id) {
            // Admin booking for another user
            $admin_username = $_SESSION['username'] ?? 'Admin';
            $member_username = $member_data['username'] ?? 'User';
            $log_details = "Admin ({$admin_username}) booked {$class_type} session for {$member_username} with {$trainer_name} on {$booking_date} ({$time_display})";
            ActivityLogger::log('admin_booking_created', $admin_username, $booking_id, $log_details);
        } else {
            // Regular member booking
            $username = $_SESSION['username'] ?? 'User';
            $log_details = "Booked {$class_type} session with {$trainer_name} on {$booking_date} ({$time_display})";
            ActivityLogger::log('session_booked', $username, $booking_id, $log_details);
        }

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
        if ($is_time_based) {
            // Time-based: calculate total minutes used this week
            $count_stmt = $conn->prepare("
                SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
                FROM user_reservations 
                WHERE user_id = ? 
                AND booking_date BETWEEN ? AND ?
                AND booking_status IN ('confirmed', 'completed')
                AND start_time IS NOT NULL
                AND end_time IS NOT NULL
            ");
            if ($count_stmt === false) {
                throw new Exception('Failed to prepare weekly usage query');
            }
            $count_stmt->bind_param("sss", $user_id, $week_start, $week_end);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_row = $count_result ? $count_result->fetch_assoc() : ['total_minutes' => 0];
            $weekly_minutes = (int) ($count_row['total_minutes'] ?? 0);
            $weekly_hours = round($weekly_minutes / 60, 1);
            $weekly_limit_display = '48 hours';
            $count_stmt->close();
        } else {
            // Legacy: count number of bookings
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
            $weekly_hours = $weekly_count; // For display as count
            $weekly_limit_display = '12 bookings';
            $count_stmt->close();
        }

        // Attempt to send email notification to trainer (after commit)
        try {
            if (function_exists('sendTrainerBookingNotification')) {
                // For legacy bookings, use session_time; for time-based, use time_display
                $email_time_info = $is_time_based ? $time_display : $session_time;
                
                $email_sent = sendTrainerBookingNotification(
                    $trainer_data['email'],
                    $trainer_data['name'],
                    $member_data['username'],
                    $booking_date,
                    $email_time_info,
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
        ApiSecurityMiddleware::sendJsonResponse([
        $response = [
            'success' => true,
            'booking_id' => $booking_id,
            'message' => 'Session booked successfully!',
            'details' => [
                'trainer' => $trainer_name,
                'class' => $class_type,
                'date' => date('F j, Y', strtotime($booking_date)),
                'time' => $time_display,
                'duration' => $duration_display,
                'weekly_usage' => $weekly_hours,
                'weekly_limit' => $weekly_limit_display
            ]
        ], 200);
        ];
        
        // Add legacy fields for backwards compatibility
        if (!$is_time_based) {
            $response['details']['session'] = $session_time;
            $response['details']['session_hours'] = $session_time === 'Morning' ? '7-11 AM' :
                ($session_time === 'Afternoon' ? '1-5 PM' : '6-10 PM');
            $response['details']['user_weekly_bookings'] = $weekly_count;
            $response['details']['weekly_limit'] = 12;
            if (isset($validation['facility_count'])) {
                $response['details']['facility_trainers'] = $validation['facility_count'] + 1;
            }
        }
        
        echo json_encode($response);
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

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while processing your booking. Please try again.'
    ], 500);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
