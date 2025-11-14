<?php
require_once '../../../includes/db_connect.php';

// Don't display errors in JSON API - log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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
        echo json_encode([
            'success' => true,
            'message' => 'Receipt generated successfully',
            'receipt_id' => $receipt_id
        ]);
    } else {
        throw new Exception('Database insert failed: ' . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Error in generate_nonmember_receipt.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate receipt. Please try again or contact support.'
    ]);
}

$conn->close();
