<?php
require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/input_validator.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    exit; // Already sent response
}

// Rate limiting - 20 requests per minute per IP (prevent username enumeration)
$identifier = 'check_username:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
ApiSecurityMiddleware::applyRateLimit($conn, $identifier, 20, 60);

// Get JSON body
$data = ApiSecurityMiddleware::getJsonBody();

// Validate and sanitize input
$validation = ApiSecurityMiddleware::validateInput([
    'username' => [
        'type' => 'string',
        'required' => true,
        'min_length' => 3,
        'max_length' => 50,
        'pattern' => '/^[a-zA-Z0-9_]+$/' // Only alphanumeric and underscore
    ]
], $data);

if (!$validation['valid']) {
    $errors = implode(', ', $validation['errors']);
    ApiSecurityMiddleware::sendJsonResponse([
        'available' => false,
        'message' => 'Validation failed: ' . $errors
    ], 400);
}

$username = $validation['data']['username'];

// Check if username exists in database
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    ApiSecurityMiddleware::sendJsonResponse([
        'available' => false,
        'message' => 'Username is already taken'
    ], 200);
} else {
    ApiSecurityMiddleware::sendJsonResponse([
        'available' => true,
        'message' => 'Username is available'
    ], 200);
}

$stmt->close();
$conn->close();
