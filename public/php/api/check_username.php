<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get the username from POST data
$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');

// Validate input
if (empty($username)) {
    echo json_encode([
        'available' => false,
        'message' => 'Username is required'
    ]);
    exit();
}

// Check minimum length
if (strlen($username) < 3) {
    echo json_encode([
        'available' => false,
        'message' => 'Username must be at least 3 characters'
    ]);
    exit();
}

// Sanitize username
$username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

// Check if username exists in database
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo json_encode([
        'available' => false,
        'message' => 'Username is already taken'
    ]);
} else {
    echo json_encode([
        'available' => true,
        'message' => 'Username is available'
    ]);
}

$stmt->close();
$conn->close();
