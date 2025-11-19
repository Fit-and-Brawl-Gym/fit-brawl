<?php
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';

$trainerId = intval($_GET['trainer_id'] ?? 0);
if (!$trainerId) {
    echo json_encode(['success' => false, 'message' => 'Trainer ID required']);
    exit;
}

// Get the date from query param (for block creation) or use current date
$date = $_GET['date'] ?? date('Y-m-d');
$day = date('l', strtotime($date)); // Get day of week for the specified date

// Check if trainer has day off on this date
$dayOffStmt = $conn->prepare("SELECT is_day_off FROM trainer_day_offs WHERE trainer_id = ? AND day_of_week = ?");
$dayOffStmt->bind_param('is', $trainerId, $day);
$dayOffStmt->execute();
$dayOffResult = $dayOffStmt->get_result()->fetch_assoc();

if ($dayOffResult && $dayOffResult['is_day_off'] == 1) {
    echo json_encode(['success' => false, 'message' => 'Trainer has day off on ' . $day]);
    exit;
}

// Get trainer shift for this day
$stmt = $conn->prepare("SELECT custom_start_time AS start, custom_end_time AS end, break_start_time AS breakStart, break_end_time AS breakEnd FROM trainer_shifts WHERE trainer_id=? AND day_of_week=? AND is_active=1 LIMIT 1");
$stmt->bind_param('is', $trainerId, $day);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    echo json_encode(['success'=>false, 'message'=>'No shift found for ' . $day]);
    exit;
}

echo json_encode(['success'=>true, 'shift'=>$result, 'date'=>$date, 'day_of_week'=>$day]);
