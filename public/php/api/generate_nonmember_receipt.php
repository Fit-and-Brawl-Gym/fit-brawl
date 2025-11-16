<?php
require_once '../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/input_validator.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Don't display errors in JSON API - log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    exit; // Already sent response
}

// Rate limiting - 10 requests per minute per IP (public endpoint for non-members)
$identifier = 'generate_nonmember_receipt:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
ApiSecurityMiddleware::applyRateLimit($conn, $identifier, 10, 60);

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
    'email' => [
        'type' => 'email',
        'required' => true
    ],
    'phone' => [
        'type' => 'string',
        'required' => true,
        'max_length' => 20
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
$service = $data['service'];
$name = $data['name'];
$email = $data['email'];
$phone = $data['phone'];
$service_date_obj = $data['service_date']; // DateTime object
$service_date_mysql = $service_date_obj instanceof DateTime ? $service_date_obj->format('Y-m-d') : $service_date_obj;

// Service configurations
$services = [
    'daypass-gym' => ['name' => 'Day Pass: Gym Access', 'price' => 150, 'code' => 'DPG'],
    'daypass-gym-student' => ['name' => 'Day Pass: Student Gym Access', 'price' => 120, 'code' => 'DPGS'],
    'training-boxing' => ['name' => 'Training: Boxing', 'price' => 380, 'code' => 'TBX'],
    'training-muaythai' => ['name' => 'Training: Muay Thai', 'price' => 530, 'code' => 'TMT'],
    'training-mma' => ['name' => 'Training: MMA', 'price' => 630, 'code' => 'TMMA']
];

// Service already validated by whitelist
$selectedService = $services[$service];

// Generate unique receipt ID
$receipt_id = strtoupper($selectedService['code'] . '-' . date('Ymd') . '-' . substr(uniqid(), -6));

try {
    // Check if table exists, if not create it
    $tableCheck = $conn->query("SHOW TABLES LIKE 'non_member_bookings'");
    if ($tableCheck->num_rows == 0) {
        // Create the table
        $createTable = "CREATE TABLE `non_member_bookings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `receipt_id` VARCHAR(50) UNIQUE NOT NULL,
            `service_key` VARCHAR(100) NOT NULL,
            `service_name` VARCHAR(255) NOT NULL,
            `price` DECIMAL(10,2) NOT NULL,
            `customer_name` VARCHAR(255) NOT NULL,
            `customer_email` VARCHAR(255) NOT NULL,
            `customer_phone` VARCHAR(20) NOT NULL,
            `service_date` DATE NOT NULL,
            `booking_date` DATETIME NOT NULL,
            `status` ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_email` (`customer_email`),
            INDEX `idx_service_date` (`service_date`),
            INDEX `idx_receipt` (`receipt_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (!$conn->query($createTable)) {
            throw new Exception('Failed to create non_member_bookings table: ' . $conn->error);
        }
    }

    // Insert into database
    $stmt = $conn->prepare("
        INSERT INTO non_member_bookings
        (receipt_id, service_key, service_name, price, customer_name, customer_email, customer_phone, service_date, booking_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
    ");

    if (!$stmt) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }

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
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => true,
            'message' => 'Receipt generated successfully',
            'receipt_id' => $receipt_id
        ], 200);
    } else {
        error_log("Database insert failed in generate_nonmember_receipt.php: " . $stmt->error);
        throw new Exception('Database insert failed');
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Error in generate_nonmember_receipt.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Failed to generate receipt. Please try again or contact support.'
    ], 500);
}

$conn->close();
