<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

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
            ur.booking_status,
            ur.booked_at,
            ur.cancelled_at,
            CASE 
                WHEN ur.session_time = 'Morning' THEN '7-11 AM'
                WHEN ur.session_time = 'Afternoon' THEN '1-5 PM'
                WHEN ur.session_time = 'Evening' THEN '6-10 PM'
            END AS session_hours,
            CASE 
                WHEN ur.booking_date < CURDATE() THEN 'past'
                WHEN ur.booking_date = CURDATE() THEN 'today'
                ELSE 'upcoming'
            END AS booking_period
        FROM user_reservations ur
        JOIN trainers t ON ur.trainer_id = t.id
        WHERE ur.user_id = ?
        AND ur.booking_status IN ('confirmed', 'completed', 'cancelled')
        ORDER BY ur.booking_date DESC, ur.session_time ASC
    ");
    $stmt->bind_param("i", $user_id);
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

        $bookings[] = [
            'id' => $row['booking_id'],
            'trainer_id' => $row['trainer_id'],
            'trainer_name' => $row['trainer_name'],
            'trainer_photo' => $row['trainer_photo'] ?? 'default-trainer.jpg',
            'class_type' => $row['class_type'],
            'date' => $row['booking_date'],
            'date_formatted' => date('F j, Y', strtotime($row['booking_date'])),
            'day_of_week' => date('l', strtotime($row['booking_date'])),
            'session_time' => $row['session_time'],
            'session_hours' => $row['session_hours'],
            'status' => $row['booking_status'],
            'booked_at' => $row['booked_at'],
            'cancelled_at' => $row['cancelled_at'],
            'booking_period' => $row['booking_period'],
            'can_cancel' => $can_cancel
        ];
    }
    $stmt->close();

    // Calculate weekly bookings (rolling 7-day window from today)
    $window_start = date('Y-m-d', strtotime('-6 days'));
    $window_end = date('Y-m-d');

    $weekly_stmt = $conn->prepare("
        SELECT COUNT(*) as booking_count
        FROM user_reservations 
        WHERE user_id = ? 
        AND booking_date BETWEEN ? AND ?
        AND booking_status IN ('confirmed', 'completed', 'cancelled')
    ");
    $weekly_stmt->bind_param("iss", $user_id, $window_start, $window_end);
    $weekly_stmt->execute();
    $weekly_result = $weekly_stmt->get_result();
    $weekly_row = $weekly_result->fetch_assoc();
    $weekly_count = (int) $weekly_row['booking_count'];
    $weekly_stmt->close();

    // Group bookings by period
    $grouped = [
        'upcoming' => array_filter($bookings, fn($b) => $b['booking_period'] === 'upcoming' && $b['status'] !== 'cancelled'),
        'today' => array_filter($bookings, fn($b) => $b['booking_period'] === 'today' && $b['status'] !== 'cancelled'),
        'past' => array_filter($bookings, fn($b) => $b['booking_period'] === 'past' || $b['status'] === 'cancelled')
    ];

    echo json_encode([
        'success' => true,
        'bookings' => array_values($bookings),
        'grouped' => [
            'upcoming' => array_values($grouped['upcoming']),
            'today' => array_values($grouped['today']),
            'past' => array_values($grouped['past'])
        ],
        'summary' => [
            'total' => count($bookings),
            'upcoming' => count($grouped['upcoming']),
            'today' => count($grouped['today']),
            'past' => count($grouped['past']),
            'weekly_count' => $weekly_count,
            'weekly_limit' => 12,
            'weekly_remaining' => max(0, 12 - $weekly_count)
        ]
    ]);

} catch (Exception $e) {
    error_log("Error fetching user bookings: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching your bookings'
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>