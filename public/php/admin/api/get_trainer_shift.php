<?php
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';

$trainerId = intval($_GET['trainer_id'] ?? 0);
if (!$trainerId) {
    echo json_encode(['success' => false, 'message' => 'Trainer ID required']);
    exit;
}


$day = date('l'); // Monday, Tuesday...
$stmt = $conn->prepare("SELECT custom_start_time AS start, custom_end_time AS end, break_start_time AS breakStart, break_end_time AS breakEnd FROM trainer_shifts WHERE trainer_id=? AND day_of_week=? AND is_active=1 LIMIT 1");
$stmt->bind_param('is', $trainerId, $day);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if (!$result) {
    echo json_encode(['success'=>false, 'message'=>'No shift found']);
    exit;
}

echo json_encode(['success'=>true, 'shift'=>$result]);
