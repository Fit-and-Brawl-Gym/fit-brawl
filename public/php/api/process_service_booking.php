<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../includes/input_validator.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require authentication
$user = ApiSecurityMiddleware::requireAuth();
if (!$user) {
    exit; // Already sent response
}

$user_id = $user['user_id'];

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    exit; // Already sent response
}

// Require CSRF token
if (!ApiSecurityMiddleware::requireCSRF()) {
    exit; // Already sent response
}

// Rate limiting - 10 requests per minute per user
ApiSecurityMiddleware::applyRateLimit($conn, 'service_booking:' . $user_id, 10, 60);

// Validate and sanitize input
$validation = ApiSecurityMiddleware::validateInput([
    'service' => [
        'type' => 'whitelist',
        'required' => true,
        'allowed' => ['daypass-gym', 'daypass-gym-student', 'training-boxing', 'training-muaythai', 'training-mma']
    ],
    'name' => [
        'type' => 'string',
        'required' => true,
        'max_length' => 255
    ],
    'country' => [
        'type' => 'string',
        'required' => true,
        'max_length' => 100
    ],
    'address' => [
        'type' => 'string',
        'required' => true,
        'max_length' => 500
    ],
    'service_date' => [
        'type' => 'date',
        'required' => true,
        'format' => 'Y-m-d'
    ]
]);

if (!$validation['valid']) {
    $errors = implode(', ', $validation['errors']);
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Validation failed: ' . $errors
    ], 400);
}

$data = $validation['data'];
$service_key = $data['service'];
$name = $data['name'];
$country = $data['country'];
$address = $data['address'];

// Handle date (DateTime object from validator)
$service_date_obj = $data['service_date'];
if ($service_date_obj instanceof DateTime) {
    $service_date_mysql = $service_date_obj->format('Y-m-d');
} else {
    // Fallback for string date
    $service_date_mysql = $service_date_obj;
}

// Service configurations with codes for receipt ID
$services = [
    'daypass-gym' => ['name' => 'Day Pass: Gym Access', 'member_price' => 90, 'non_member_price' => 150, 'code' => 'DPG'],
    'daypass-gym-student' => ['name' => 'Day Pass: Student Gym Access', 'member_price' => 70, 'non_member_price' => 120, 'code' => 'DPGS'],
    'training-boxing' => ['name' => 'Training: Boxing', 'member_price' => 350, 'non_member_price' => 380, 'code' => 'TBX'],
    'training-muaythai' => ['name' => 'Training: Muay Thai', 'member_price' => 400, 'non_member_price' => 530, 'code' => 'TMT'],
    'training-mma' => ['name' => 'Training: MMA', 'member_price' => 500, 'non_member_price' => 630, 'code' => 'TMMA']
];

// Service already validated by whitelist, but double-check
if (!isset($services[$service_key])) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Invalid service selected'
    ], 400);
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
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$is_member = $result['has_membership'] > 0;

$price = $is_member ? $service['member_price'] : $service['non_member_price'];

// Generate unique receipt ID (format: CODE-YYYYMMDD-XXXXXX)
$receipt_id = strtoupper($service['code'] . '-' . date('Ymd') . '-' . substr(uniqid(), -6));

// Insert into member_service_bookings table - NO APPROVAL NEEDED, NO RECEIPT UPLOAD
$stmt = $conn->prepare("
    INSERT INTO member_service_bookings
    (receipt_id, user_id, service_key, service_name, price, is_member, name, country, permanent_address, service_date, booking_date, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'confirmed')
");

$stmt->bind_param(
    "sissdissss",
    $receipt_id,
    $user_id,
    $service_key,
    $service['name'],
    $price,
    $is_member,
    $name,
    $country,
    $address,
    $service_date_mysql
);

if ($stmt->execute()) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'message' => 'Service booked successfully! Your receipt is ready.',
        'receipt_id' => $receipt_id,
        'booking' => [
            'service' => $service['name'],
            'service_date' => $service_date_mysql,
            'price' => $price,
            'status' => 'confirmed',
            'is_member' => $is_member
        ]
    ], 200);
} else {
    error_log("Database error in process_service_booking.php: " . $stmt->error);
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Database error. Please try again.'
    ], 500);
}

$stmt->close();
$conn->close();
