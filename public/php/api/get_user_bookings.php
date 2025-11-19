<?php
// Prevent output before headers
ob_start();

// Disable error display for API
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();

// Set JSON header immediately
header('Content-Type: application/json');

require_once '../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require authentication
$user = ApiSecurityMiddleware::requireAuth();
if (!$user) {
    exit; // Already sent response
}

$user_id = $user['user_id'];

// Rate limiting - 120 requests per minute (read endpoint, called frequently on page load)
ApiSecurityMiddleware::applyRateLimit($conn, 'get_bookings:' . $user_id, 120, 60);

try {
    // Get all user bookings
    $stmt = $conn->prepare("
        SELECT
            ur.id AS booking_id,
            ur.trainer_id,
            t.name AS trainer_name,
            t.photo AS trainer_photo,
            ur.session_time,
            ur.class_type,
            ur.booking_date,
            ur.start_time,
            ur.end_time,
            ur.booking_status,
            ur.booked_at,
            ur.cancelled_at,
            CASE
                WHEN ur.booking_date < CURDATE() THEN 'past'
                WHEN ur.booking_date = CURDATE() THEN 'today'
                ELSE 'upcoming'
            END AS booking_period,
            CASE
                WHEN ur.booking_date = CURDATE() AND ur.session_time = 'Evening'
                AND HOUR(NOW()) BETWEEN 18 AND 22 THEN 'ongoing'
                ELSE ur.booking_status
            END AS session_status
        FROM user_reservations ur
        JOIN trainers t ON ur.trainer_id = t.id
        WHERE ur.user_id = ?
        AND ur.booking_status IN ('confirmed', 'completed', 'cancelled', 'blocked')
        ORDER BY ur.booking_date DESC, ur.session_time ASC
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        // Check if cancellation is allowed (> 24 hours before session)
        $can_cancel = false;
        if ($row['booking_status'] === 'confirmed' && $row['booking_period'] !== 'past') {
            $session_starts = [
                'Morning' => '07:00:00',
                'Afternoon' => '13:00:00',
                'Evening' => '18:00:00'
            ];
            $session_datetime = $row['booking_date'] . ' ' . $session_starts[$row['session_time']];
            $session_timestamp = strtotime($session_datetime);
            $now_timestamp = time();
            $hours_until_session = ($session_timestamp - $now_timestamp) / 3600;
            $can_cancel = $hours_until_session >= 24;
        }

        // Determine session status
        $session_status = $row['session_status'];
        if (
            $row['booking_date'] === date('Y-m-d') && $row['session_time'] === 'Evening' &&
            intval(date('H')) >= 18 && intval(date('H')) <= 22
        ) {
            $session_status = 'ongoing';
        }

        $bookings[] = [
            'id' => $row['booking_id'],
            'trainer_id' => $row['trainer_id'],
            'trainer_name' => $row['trainer_name'],
            'trainer_photo' => !empty($row['trainer_photo']) ? $row['trainer_photo'] : 'account-icon.svg',
            'class_type' => $row['class_type'],
            'date' => $row['booking_date'],
            'date_formatted' => date('F j, Y', strtotime($row['booking_date'])),
            'day_of_week' => date('l', strtotime($row['booking_date'])),
            'session_time' => $row['session_time'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'status' => $session_status,
            'session_status' => $session_status,
            'booked_at' => $row['booked_at'],
            'cancelled_at' => $row['cancelled_at'],
            'booking_period' => $row['booking_period'],
            'can_cancel' => $session_status !== 'ongoing' && $can_cancel
        ];
    }
    $stmt->close();

    // Calculate weekly hours usage (Sunday to Saturday)
    // Get the Sunday of the current week (go back to previous/current Sunday)
    $week_start = new DateTime();
    $day_of_week = (int)$week_start->format('w'); // 0 (Sunday) to 6 (Saturday)
    if ($day_of_week > 0) {
        // If not Sunday, go back to previous Sunday
        $week_start->modify("-$day_of_week days");
    }
    $week_start->setTime(0, 0, 0);
    
    $week_end = clone $week_start;
    $week_end->modify('+6 days')->setTime(23, 59, 59);
    
    error_log("📊 Week calculation - Start: " . $week_start->format('Y-m-d H:i:s') . ", End: " . $week_end->format('Y-m-d H:i:s'));

    // Get user's membership plan weekly limit
    $membership_query = "SELECT m.weekly_hours_limit, m.plan_name
                         FROM user_memberships um
                         JOIN memberships m ON um.plan_id = m.id
                         WHERE um.user_id = ?
                         AND um.membership_status = 'active'
                         AND DATE_ADD(um.end_date, INTERVAL 3 DAY) >= CURDATE()
                         ORDER BY um.end_date DESC
                         LIMIT 1";

    $mem_stmt = $conn->prepare($membership_query);
    $mem_stmt->bind_param('s', $user_id);
    $mem_stmt->execute();
    $mem_result = $mem_stmt->get_result();
    $membership = $mem_result->fetch_assoc();
    $mem_stmt->close();

    $weekly_hours_limit = $membership ? (int)$membership['weekly_hours_limit'] : 48;
    $plan_name = $membership ? $membership['plan_name'] : 'Unknown';

    $week_query = "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
                   FROM user_reservations
                   WHERE user_id = ?
                   AND start_time >= ?
                   AND start_time <= ?
                   AND booking_status IN ('confirmed', 'completed')
                   AND start_time IS NOT NULL
                   AND end_time IS NOT NULL";

    $week_stmt = $conn->prepare($week_query);
    $week_start_str = $week_start->format('Y-m-d H:i:s');
    $week_end_str = $week_end->format('Y-m-d H:i:s');
    error_log("📊 Query params - User: $user_id, Start: $week_start_str, End: $week_end_str");
    $week_stmt->bind_param('sss', $user_id, $week_start_str, $week_end_str);
    $week_stmt->execute();
    $week_result = $week_stmt->get_result();
    $week_row = $week_result->fetch_assoc();
    $week_stmt->close();

    $total_minutes = (int)($week_row['total_minutes'] ?? 0);
    error_log("📊 Total minutes from query: $total_minutes");
    $limit_minutes = $weekly_hours_limit * 60;
    $remaining_minutes = max(0, $limit_minutes - $total_minutes);

    $weekly_usage = [
        'total_minutes' => $total_minutes,
        'limit_hours' => $weekly_hours_limit,
        'limit_minutes' => $limit_minutes,
        'remaining_minutes' => $remaining_minutes,
        'plan_name' => $plan_name,
        'week_start' => $week_start->format('M j'),
        'week_end' => $week_end->format('M j')
    ];

    // Group bookings by period
    $grouped = [
        'upcoming' => array_filter($bookings, fn($b) => $b['booking_period'] === 'upcoming' && $b['status'] !== 'cancelled' && $b['status'] !== 'blocked'),
        'today'    => array_filter($bookings, fn($b) => $b['booking_period'] === 'today' && $b['status'] !== 'cancelled' && $b['status'] !== 'blocked'),
        'past'     => array_filter($bookings, fn($b) => $b['booking_period'] === 'past' || $b['status'] === 'cancelled'),
        'blocked'  => array_filter($bookings, fn($b) => $b['status'] === 'blocked')
    ];
    // Debug info
    $debug = [
        'current_time' => date('H:i:s'),
        'current_hour' => (int) date('H'),
        'server_time' => date('Y-m-d H:i:s'),
        'today_bookings' => array_values(array_filter($bookings, fn($b) => $b['booking_period'] === 'today'))
    ];

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'bookings' => array_values($bookings),
        'grouped' => [
            'upcoming' => array_values($grouped['upcoming']),
            'today' => array_values($grouped['today']),
            'past' => array_values($grouped['past']),
            'blocked' => array_values($grouped['blocked'])
        ],
        'weekly_usage' => $weekly_usage,
        'summary' => [
            'total' => count($bookings),
            'upcoming' => count($grouped['upcoming']),
            'today' => count($grouped['today']),
            'past' => count($grouped['past']),
            'blocked' => count($grouped['blocked'])
        ],
        'debug' => $debug
    ], 200);

} catch (Exception $e) {
    error_log("Error fetching user bookings: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching your bookings'
    ], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
