<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$plan = isset($_POST['plan']) ? strtolower(trim($_POST['plan'])) : '';
$billing = isset($_POST['billing']) ? $_POST['billing'] : 'monthly';
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$country = isset($_POST['country']) ? trim($_POST['country']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';

// Validate required fields
if (empty($plan) || empty($name) || empty($country) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Handle file upload
if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Please upload a payment receipt']);
    exit;
}

$receipt = $_FILES['receipt'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
$maxSize = 10 * 1024 * 1024; // 10MB

// Validate file type
if (!in_array($receipt['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF allowed']);
    exit;
}

// Validate file size
if ($receipt['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
    exit;
}

// Create uploads directory if it doesn't exist
$uploadDir = '../../../uploads/receipts/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique filename
$extension = pathinfo($receipt['name'], PATHINFO_EXTENSION);
$filename = 'receipt_' . $user_id . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $filename;

// Move uploaded file
if (!move_uploaded_file($receipt['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload receipt']);
    exit;
}

// Map plan names to membership IDs - Updated to match actual plans
$planMapping = [
    'brawler' => 1,
    'gladiator' => 2,
    'champion' => 3,
    'clash' => 1,
    'resolution-student' => 1,
    'resolution-regular' => 1,
    'resolution' => 1 // Fallback for resolution plan
];

// Check if plan exists in mapping
if (!isset($planMapping[$plan])) {
    // Log the plan name for debugging
    error_log("Unknown plan: " . $plan);
    echo json_encode(['success' => false, 'message' => 'Invalid membership plan: ' . $plan]);
    exit;
}

$membership_id = $planMapping[$plan];

// Get membership details
$membership_query = "SELECT * FROM memberships WHERE id = ?";
$stmt = $conn->prepare($membership_query);
$stmt->bind_param("i", $membership_id);
$stmt->execute();
$membership = $stmt->get_result()->fetch_assoc();

if (!$membership) {
    echo json_encode(['success' => false, 'message' => 'Membership plan not found in database']);
    exit;
}

// Calculate dates
$start_date = date('Y-m-d');
if ($billing === 'yearly') {
    $end_date = date('Y-m-d', strtotime('+1 year'));
} else {
    $end_date = date('Y-m-d', strtotime('+1 month'));
}

// Check if user already has an active membership
$check_query = "SELECT id FROM user_memberships
                WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    // Update existing membership
    $update_query = "UPDATE user_memberships
                     SET membership_id = ?, start_date = ?, end_date = ?,
                         billing_type = ?, status = 'active'
                     WHERE user_id = ? AND status = 'active'";
    $stmt->prepare($update_query);
    $stmt->bind_param("isssi", $membership_id, $start_date, $end_date, $billing, $user_id);
} else {
    // Insert new membership
    $insert_query = "INSERT INTO user_memberships
                     (user_id, membership_id, start_date, end_date, billing_type, status)
                     VALUES (?, ?, ?, ?, ?, 'active')";
    $stmt->prepare($insert_query);
    $stmt->bind_param("iisss", $user_id, $membership_id, $start_date, $end_date, $billing);
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Subscription processed successfully',
        'membership' => [
            'plan' => $membership['plan_name'],
            'end_date' => $end_date,
            'billing_type' => $billing
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process subscription: ' . $stmt->error]);
}
?>
