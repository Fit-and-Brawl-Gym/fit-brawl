<?php
require_once __DIR__ . '/../../../includes/init.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/input_validator.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    exit; // Already sent response
}

// Rate limiting - 5 contact submissions per minute per IP (public endpoint)
$identifier = 'contact:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
ApiSecurityMiddleware::applyRateLimit($conn, $identifier, 5, 60);

// Validate and sanitize input
$validation = ApiSecurityMiddleware::validateInput([
    'first_name' => [
        'type' => 'string',
        'required' => true,
        'max_length' => 100
    ],
    'last_name' => [
        'type' => 'string',
        'required' => true,
        'max_length' => 100
    ],
    'email' => [
        'type' => 'email',
        'required' => true
    ],
    'phone' => [
        'type' => 'string',
        'required' => false,
        'max_length' => 20
    ],
    'message' => [
        'type' => 'string',
        'required' => true,
        'max_length' => 2000
    ]
]);

if (!$validation['valid']) {
    $errors = implode(', ', $validation['errors']);
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'error' => 'Validation failed: ' . $errors
    ], 400);
}

$data = $validation['data'];
$first_name = $data['first_name'];
$last_name = $data['last_name'];
$email = $data['email'];
$phone = $data['phone'] ?? '';
$message = $data['message'];

// Save to database
$stmt = $conn->prepare("INSERT INTO inquiries (first_name, last_name, email, phone, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('sssss', $first_name, $last_name, $email, $phone, $message);

if ($stmt->execute()) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'message' => 'Your inquiry has been submitted successfully.'
    ], 200);
} else {
    error_log("Database error in contact_api.php: " . $stmt->error);
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'error' => 'Failed to submit inquiry. Please try again.'
    ], 500);
}

$stmt->close();
