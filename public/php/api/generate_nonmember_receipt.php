<?php
session_start();
// Suppress PHP warnings/notices from leaking HTML
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
require_once $_SERVER['DOCUMENT_ROOT'] . '/fit-brawl/includes/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/fit-brawl/includes/api_security_middleware.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/fit-brawl/includes/api_rate_limiter.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/fit-brawl/includes/csrf_protection.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/fit-brawl/includes/phone_utils.php';
$csrfToken = $_POST['csrf_token'] ?? '';
if (!CSRFProtection::validateToken($csrfToken)) {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
}

ApiSecurityMiddleware::setSecurityHeaders();

// Helper: get mysqli handle if available
$db = null;
if (isset($conn) && $conn) {
    $db = $conn;
} elseif (isset($mysqli) && $mysqli) {
    $db = $mysqli;
}

$required = ['service', 'name', 'email', 'phone', 'service_date'];
foreach ($required as $f) {
    if (empty($_POST[$f])) {
        ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => "Missing required field: $f"], 400);
    }
}

$service = preg_replace('/[^a-z0-9_-]/i', '', $_POST['service']);
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone_raw = trim($_POST['phone']);
$phone = format_phone_standard($phone_raw);
$serviceDate = trim($_POST['service_date']);

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid email address.'], 400);
}

if (!$phone) {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid Philippine phone number. Use +63 9XX XXX XXXX format.'], 400);
}

// Basic date validation
$dateObj = DateTime::createFromFormat('F j, Y', $serviceDate) ?: DateTime::createFromFormat('Y-m-d', $serviceDate);
if (!$dateObj) {
    // Try to parse generic format
    try {
        $dateObj = new DateTime($serviceDate);
    } catch (Exception $ex) {
        ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid service date.'], 400);
    }
}
$serviceDateFormatted = $dateObj->format('Y-m-d');

// Price mapping (must match the page mapping)
$services = [
    'daypass-gym' => 150,
    'daypass-gym-student' => 120,
    'training-boxing' => 380,
    'training-muaythai' => 530,
    'training-mma' => 630
];

$price = isset($services[$service]) ? $services[$service] : 0;

// Try DB insert (if table exists), otherwise fallback to a JSON file
$receiptId = null;
$createdAt = (new DateTime())->format('Y-m-d H:i:s');

// Rate limit non-member receipt generation to avoid abuse
ApiSecurityMiddleware::applyRateLimit($conn, 'generate_nonmember_receipt:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 30, 60, true);

if ($db && method_exists($db, 'prepare')) {
    // Try a common table name. Adjust to your schema if you have one.
    $tableName = 'nonmember_receipts'; // change if your schema uses a different table
    $receiptUniqueId = 'NM-' . uniqid();
    $sql = "INSERT INTO {$tableName} (service, name, email, phone, service_date, amount, created_at, receipt_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = @$db->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ssssssss', $service, $name, $email, $phone, $serviceDateFormatted, $price, $createdAt, $receiptUniqueId);
        $ok = $stmt->execute();
        if ($ok) {
            ApiSecurityMiddleware::sendJsonResponse(['success' => true, 'receipt_id' => $receiptUniqueId], 200);
            // sendJsonResponse already exits
        }
        // If insertion failed, continue to fallback
    }
}

// Fallback to JSON file storage if DB not present
$storageDir = __DIR__ . '/../../data';
if (!file_exists($storageDir)) {
    if (!@mkdir($storageDir, 0755, true)) {
        error_log('Failed to create data directory: ' . $storageDir);
        ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Server error: cannot create data directory.'], 500);
    }
}
$storageFile = $storageDir . '/receipts.json';
$receipts = [];

if (file_exists($storageFile)) {
    $content = @file_get_contents($storageFile);
    if ($content === false) {
        error_log('Failed to read receipts file: ' . $storageFile);
        ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Server error: cannot read receipts file.'], 500);
    }
    $receipts = json_decode($content, true) ?: [];
}

// Compose new receipt
$newReceipt = [
    'id' => 'NM-' . uniqid(),
    'service' => $service,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'service_date' => $serviceDateFormatted,
    'amount' => $price,
    'created_at' => $createdAt
];

$receipts[] = $newReceipt;
if (@file_put_contents($storageFile, json_encode($receipts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    ApiSecurityMiddleware::sendJsonResponse(['success' => true, 'receipt_id' => $newReceipt['id']], 200);
    // sendJsonResponse exits
} else {
    error_log('Failed to write receipts file: ' . $storageFile);
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Server error: cannot write receipts file.'], 500);
}


// If we reach here, fall through to error response (log then return HTTP 500). Some older code
// appended below was removed during cleanup because it duplicated logic and caused parse
// errors on some PHP configurations. Close DB connection if open.
if (isset($stmt) && $stmt) {
    $stmt->close();
}
if (isset($conn) && $conn) {
    $conn->close();
}

// Already returned by now, but ensure a proper server response if execution continues
http_response_code(500);
echo json_encode(['success' => false, 'message' => 'Unable to create receipt.']);
exit;
