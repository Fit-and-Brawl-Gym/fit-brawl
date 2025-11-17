<?php

require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/session_manager.php';

SessionManager::initialize();

header('Content-Type: application/json');

if (!SessionManager::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = intval($_GET['booking_id'] ?? 0);

if (!$booking_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID']);
    exit;
}

try {
    // Fetch booking with duration calculation
    $stmt = $conn->prepare("
        SELECT 
            id,
            TIMESTAMPDIFF(MINUTE, start_time, end_time) as duration_minutes
        FROM user_reservations
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param('ii', $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'duration_minutes' => (int)$data['duration_minutes']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>