<?php
include_once('../../../includes/init.php');
header('Content-Type: application/json');

// Collect form inputs
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$first_name || !$last_name || !$email || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Save to database
$stmt = $conn->prepare("INSERT INTO inquiries (first_name, last_name, email, phone, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('sssss', $first_name, $last_name, $email, $phone, $message);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
