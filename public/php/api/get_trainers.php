<?php
// Don't display errors in JSON API - log them instead
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';
require_once __DIR__ . '/../../../includes/input_validator.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Rate limiting - 60 requests per minute per IP (public endpoint, used frequently)
$identifier = 'get_trainers:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
ApiSecurityMiddleware::applyRateLimit($conn, $identifier, 60, 60);

try {
    // Validate and sanitize input
    $validation = ApiSecurityMiddleware::validateInput([
        'plan' => [
            'type' => 'whitelist',
            'required' => false,
            'default' => 'all',
            'allowed' => ['all', 'boxing', 'muay-thai', 'mma', 'gym']
        ]
    ], $_GET);

    if (!$validation['valid']) {
        $errors = implode(', ', $validation['errors']);
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Validation failed: ' . $errors
        ], 400);
    }

    $data = $validation['data'];
    $class_type = $data['plan'] ?? 'all';

    // Map class types to trainer specializations
    $class_to_spec_map = [
        'boxing' => 'Boxing',
        'muay-thai' => 'Muay Thai',
        'mma' => 'MMA',
        'gym' => 'Gym'
    ];

    // Build query - only get active trainers
    if ($class_type !== 'all' && isset($class_to_spec_map[$class_type])) {
        $specialization = $class_to_spec_map[$class_type];
        $sql = "SELECT id, name, specialization FROM trainers WHERE status = 'Active' AND specialization = ? ORDER BY name ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: Unable to prepare statement');
        }
        $stmt->bind_param("s", $specialization);
    } else {
        $sql = "SELECT id, name, specialization FROM trainers WHERE status = 'Active' ORDER BY name ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: Unable to prepare statement');
        }
    }

    if (!$stmt->execute()) {
        throw new Exception('Database error: Query execution failed');
    }

    $result = $stmt->get_result();

    $trainers = [];
    while ($row = $result->fetch_assoc()) {
        $key = strtolower(preg_replace('/[^a-z0-9]+/', '-', $row['specialization']));
        $trainers[$key][] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'specialization' => $row['specialization']
        ];
    }

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'trainers' => $trainers
    ], 200);

} catch (Exception $e) {
    error_log("Error fetching trainers: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching trainers. Please try again.'
    ], 500);
} finally {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
