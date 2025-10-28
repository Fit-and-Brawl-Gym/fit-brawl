<?php
require_once '../../../includes/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get form data
$service = trim($_POST['service'] ?? '');
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$service_date = trim($_POST['service_date'] ?? '');

// Validate required fields
if (empty($service) || empty($name) || empty($email) || empty($phone) || empty($service_date)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Service configurations
$services = [
    'daypass-gym' => ['name' => 'Day Pass: Gym Access', 'price' => 150, 'code' => 'DPG'],
    'daypass-gym-student' => ['name' => 'Day Pass: Student Gym Access', 'price' => 120, 'code' => 'DPGS'],
    'training-boxing' => ['name' => 'Training: Boxing', 'price' => 380, 'code' => 'TBX'],
    'training-muaythai' => ['name' => 'Training: Muay Thai', 'price' => 530, 'code' => 'TMT'],
    'training-mma' => ['name' => 'Training: MMA', 'price' => 630, 'code' => 'TMMA']
];

if (!isset($services[$service])) {
    echo json_encode(['success' => false, 'message' => 'Invalid service selected']);
    exit;
}

$selectedService = $services[$service];

// Convert service date to MySQL format
try {
    $date = new DateTime($service_date);
    $service_date_mysql = $date->format('Y-m-d');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Generate unique receipt ID
$receipt_id = strtoupper($selectedService['code'] . '-' . date('Ymd') . '-' . substr(uniqid(), -6));

// Insert into database
$stmt = $conn->prepare("
    INSERT INTO non_member_bookings
    (receipt_id, service_key, service_name, price, customer_name, customer_email, customer_phone, service_date, booking_date, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
");

$stmt->bind_param(
    "sssdssss",
    $receipt_id,
    $service,
    $selectedService['name'],
    $selectedService['price'],
    $name,
    $email,
    $phone,
    $service_date_mysql
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Receipt generated successfully',
        'receipt_id' => $receipt_id
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
