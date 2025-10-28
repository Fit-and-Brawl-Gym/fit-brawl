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
$service_key = isset($_POST['service']) ? trim($_POST['service']) : '';
$name = trim($_POST['name'] ?? '');
$country = trim($_POST['country'] ?? '');
$address = trim($_POST['address'] ?? '');
$service_date = trim($_POST['service_date'] ?? '');

// Validate required fields
if (empty($service_key) || empty($name) || empty($country) || empty($address) || empty($service_date)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required including service date']);
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
$filename = 'receipt_service_' . $user_id . '_' . time() . '.' . $extension;
$uploadPath = $uploadDir . $filename;

if (!move_uploaded_file($receipt['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload receipt']);
    exit;
}

// Service configurations with codes for receipt ID
$services = [
    'daypass-gym' => ['name' => 'Day Pass: Gym Access', 'member_price' => 90, 'non_member_price' => 150, 'code' => 'DPG'],
    'daypass-gym-student' => ['name' => 'Day Pass: Student Gym Access', 'member_price' => 70, 'non_member_price' => 120, 'code' => 'DPGS'],
    'training-boxing' => ['name' => 'Training: Boxing', 'member_price' => 350, 'non_member_price' => 380, 'code' => 'TBX'],
    'training-muaythai' => ['name' => 'Training: Muay Thai', 'member_price' => 400, 'non_member_price' => 530, 'code' => 'TMT'],
    'training-mma' => ['name' => 'Training: MMA', 'member_price' => 500, 'non_member_price' => 630, 'code' => 'TMMA']
];

if (!isset($services[$service_key])) {
    echo json_encode(['success' => false, 'message' => 'Invalid service selected']);
    exit;
}

$service = $services[$service_key];

// Check if user has active membership (to determine pricing)
$stmt = $conn->prepare("
    SELECT COUNT(*) as has_membership
    FROM user_memberships
    WHERE user_id = ?
    AND membership_status = 'active'
    AND end_date >= CURDATE()
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$is_member = $result['has_membership'] > 0;

$price = $is_member ? $service['member_price'] : $service['non_member_price'];

// Convert service date to MySQL format
try {
    $date = new DateTime($service_date);
    $service_date_mysql = $date->format('Y-m-d');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Generate unique receipt ID (format: CODE-YYYYMMDD-XXXXXX)
$receipt_id = strtoupper($service['code'] . '-' . date('Ymd') . '-' . substr(uniqid(), -6));

// Insert into member_service_bookings table - NO APPROVAL NEEDED
$stmt = $conn->prepare("
    INSERT INTO member_service_bookings
    (receipt_id, user_id, service_key, service_name, price, is_member, name, country, permanent_address, service_date, booking_date, qr_proof, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'confirmed')
");

$stmt->bind_param(
    "sissdisssss",
    $receipt_id,
    $user_id,
    $service_key,
    $service['name'],
    $price,
    $is_member,
    $name,
    $country,
    $address,
    $service_date_mysql,
    $filename
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Service booked successfully! Your receipt is ready.',
        'receipt_id' => $receipt_id,
        'booking' => [
            'service' => $service['name'],
            'service_date' => $service_date,
            'price' => $price,
            'status' => 'confirmed',
            'is_member' => $is_member
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
