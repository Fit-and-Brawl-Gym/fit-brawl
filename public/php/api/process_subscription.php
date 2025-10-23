<?php
session_start();
require_once '../../../includes/db_connect.php';
header('Content-Type: application/json');

// Check login
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
$name = trim($_POST['name'] ?? '');
$country = trim($_POST['country'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate required fields
if (empty($plan) || empty($name) || empty($country) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate file upload
if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Please upload a payment receipt']);
    exit;
}

$receipt = $_FILES['receipt'];
$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
$maxSize = 10 * 1024 * 1024; // 10MB

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $receipt['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF allowed']);
    exit;
}

if ($receipt['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
    exit;
}

// Save uploaded file
$uploadDir = __DIR__ . '/../../../uploads/receipts/';
if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

$extension = pathinfo($receipt['name'], PATHINFO_EXTENSION);
$filename = 'receipt_' . $user_id . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $filename;

if (!move_uploaded_file($receipt['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload receipt']);
    exit;
}

// Plan mapping
$planMapping = [
    'gladiator' => 1,
    'brawler' => 2,
    'champion' => 3,
    'clash' => 4,
    'resolution-regular' => 5,
    'resolution-student' => 6
];

if (!isset($planMapping[$plan])) {
    echo json_encode(['success' => false, 'message' => 'Invalid membership plan: ' . htmlspecialchars($plan)]);
    exit;
}

$plan_id = $planMapping[$plan];

// Fetch membership info if it exists
$stmt = $conn->prepare("SELECT * FROM memberships WHERE id = ?");
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$membership = $stmt->get_result()->fetch_assoc();

// If resolution plans are not in DB, create a temporary plan name
if (!$membership) {
    if ($plan === 'resolution-student') {
        $membership = ['plan_name' => 'Resolution Student'];
    } elseif ($plan === 'resolution-regular') {
        $membership = ['plan_name' => 'Resolution Regular'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Membership plan not found']);
        exit;
    }
}

// Compute dates
$start_date = date('Y-m-d');
$end_date = ($billing === 'yearly') ? date('Y-m-d', strtotime('+1 year')) : date('Y-m-d', strtotime('+1 month'));
$duration = ($billing === 'yearly') ? 365 : 30;
$source_table = 'user_memberships';
$source_id = null;

// Insert into user_memberships
$insert_query = "
    INSERT INTO user_memberships 
    (user_id, plan_id, name, country, permanent_address, plan_name, qr_proof, start_date, end_date, billing_type, membership_status, request_status, duration, source_table, source_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'pending', ?, ?, ?)
";

$stmt = $conn->prepare($insert_query);
$stmt->bind_param(
    "iissssssssisi",
    $user_id,
    $plan_id,
    $name,
    $country,
    $address,
    $membership['plan_name'],
    $filename,
    $start_date,
    $end_date,
    $billing,
    $duration,
    $source_table,
    $source_id
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Subscription submitted for review. Please wait for admin approval.',
        'membership' => [
            'plan' => $membership['plan_name'],
            'end_date' => $end_date,
            'billing_type' => $billing,
            'status' => 'pending'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
