<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get active membership
$query = "SELECT um.*, m.plan_name, m.price, m.duration
          FROM user_memberships um
          JOIN memberships m ON um.membership_id = m.id
          WHERE um.user_id = ? AND um.status = 'active' AND um.end_date >= CURDATE()
          ORDER BY um.end_date DESC
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'membership' => [
            'plan_name' => $row['plan_name'],
            'end_date' => $row['end_date'],
            'billing_type' => $row['billing_type'],
            'price' => $row['price']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No active membership found'
    ]);
}
?>
