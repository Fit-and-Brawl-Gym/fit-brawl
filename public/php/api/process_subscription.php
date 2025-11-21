<?php
// Start output buffering
ob_start();
session_start();

require_once '../../../includes/db_connect.php';

// 1. Security & Env Loading
$uploadSecurityExists = file_exists(
  '../../../includes/file_upload_security.php',
);
if ($uploadSecurityExists) {
  require_once '../../../includes/file_upload_security.php';
}

$envLoaderPath = __DIR__ . '/../../../includes/env_loader.php';
if (file_exists($envLoaderPath)) {
  require_once $envLoaderPath;
  if (function_exists('loadEnv')) {
    loadEnv(__DIR__ . '/../../../.env');
  }
}

ob_end_clean();
header('Content-Type: application/json');

// 2. Basic Validation
if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false, 'message' => 'Please login first']);
  exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Invalid request method']);
  exit();
}

$user_id = $_SESSION['user_id'];
$plan = $_POST['plan'] ?? '';
$billing = $_POST['billing'] ?? 'monthly';
$name = $_POST['name'] ?? '';
$country = $_POST['country'] ?? '';
$address = $_POST['address'] ?? '';

if (empty($plan) || empty($name) || empty($country) || empty($address)) {
  echo json_encode([
    'success' => false,
    'message' => 'All fields are required',
  ]);
  exit();
}

// 3. File Upload
if (
  !isset($_FILES['receipt']) ||
  $_FILES['receipt']['error'] !== UPLOAD_ERR_OK
) {
  echo json_encode([
    'success' => false,
    'message' => 'Please upload a payment receipt',
  ]);
  exit();
}

$uploadDir = __DIR__ . '/../../../uploads/receipts/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

// Upload Logic
if (
  $uploadSecurityExists &&
  class_exists('SecureFileUpload') &&
  function_exists('finfo_open')
) {
  $uploadHandler = SecureFileUpload::receiptUpload($uploadDir, 10);
  $result = $uploadHandler->uploadFile($_FILES['receipt']);
  if (!$result['success']) {
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit();
  }
  $filename =
    'receipt_' .
    $user_id .
    '_' .
    time() .
    '.' .
    pathinfo($result['filename'], PATHINFO_EXTENSION);
  $uploadPath = $uploadDir . $filename;
  rename($result['path'], $uploadPath);
} else {
  // Fallback Simple Upload
  $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
  if (!in_array($_FILES['receipt']['type'], $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit();
  }
  $filename =
    'receipt_' .
    $user_id .
    '_' .
    time() .
    '.' .
    pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
  $uploadPath = $uploadDir . $filename;
  move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadPath);
}

// =========================================================
// AI CLASSIFICATION (GROQ VERSION)
// =========================================================
// Check for GROQ key first, fallback to GEMINI if that's all we have
$apiKey = getenv('GROQ_API_KEY') ?: getenv('GEMINI_API_KEY');

if ($apiKey) {
  require_once __DIR__ . '/../../../includes/ReceiptVerifier.php';

  $verifier = new ReceiptVerifier($apiKey);
  $result = $verifier->classify($uploadPath);

  // STRICT CHECK: Must be a receipt AND have high confidence
  if ($result['is_receipt'] === false || $result['confidence'] < 0.6) {
    // Delete invalid file
    if (file_exists($uploadPath)) {
      unlink($uploadPath);
    }

    $reason = $result['reason'] ?? 'Image verification failed.';
    echo json_encode([
      'success' => false,
      'message' => "Invalid Proof of Payment: $reason",
    ]);
    exit();
  }
}
// =========================================================
// END AI
// =========================================================

// 4. Database Logic
$planMapping = [
  'gladiator' => 1,
  'brawler' => 2,
  'champion' => 3,
  'clash' => 4,
  'resolution-regular' => 5,
  'resolution-student' => 6,
];
$plan_id = $planMapping[strtolower($plan)] ?? 0;

if ($plan_id === 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid plan']);
  exit();
}

// Fetch Membership Details
$stmt = $conn->prepare('SELECT * FROM memberships WHERE id = ?');
$stmt->bind_param('i', $plan_id);
$stmt->execute();
$membership = $stmt->get_result()->fetch_assoc();

if (!$membership) {
  if (strpos($plan, 'resolution') !== false) {
    $membership = ['plan_name' => ucwords(str_replace('-', ' ', $plan))];
  } else {
    echo json_encode(['success' => false, 'message' => 'Plan not found']);
    exit();
  }
}

// Check Existing Pending Request
$check = $conn->prepare(
  'SELECT * FROM user_memberships WHERE user_id = ? ORDER BY id DESC LIMIT 1',
);
$check->bind_param('i', $user_id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();

if ($existing && $existing['request_status'] === 'pending') {
  echo json_encode([
    'success' => false,
    'message' => 'Request already pending',
  ]);
  exit();
}

// Calculate Dates
$start_date = date('Y-m-d');
$end_date =
  $billing === 'quarterly'
    ? date('Y-m-d', strtotime('+3 months'))
    : date('Y-m-d', strtotime('+1 month'));
$duration = $billing === 'quarterly' ? 90 : 30;

// Define Source Columns (Handling DB Schema Variations)
$source_table = null;
$source_id = null;
$has_source_columns =
  $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'source_table'")
    ->num_rows > 0;

if ($existing && $existing['membership_status'] === 'active') {
  // UPGRADE
  $sql =
    "UPDATE user_memberships SET plan_id=?, name=?, country=?, permanent_address=?, plan_name=?, qr_proof=?, start_date=?, end_date=?, billing_type=?, request_status='pending', duration=?";
  if ($has_source_columns) {
    $sql .= ', source_table=?, source_id=?';
  }
  $sql .= ' WHERE user_id=? AND id=?';

  $stmt = $conn->prepare($sql);
  if ($has_source_columns) {
    $stmt->bind_param(
      'issssssssiissii',
      $plan_id,
      $name,
      $country,
      $address,
      $membership['plan_name'],
      $filename,
      $start_date,
      $end_date,
      $billing,
      $duration,
      $source_table,
      $source_id,
      $user_id,
      $existing['id'],
    );
  } else {
    $stmt->bind_param(
      'issssssssiis',
      $plan_id,
      $name,
      $country,
      $address,
      $membership['plan_name'],
      $filename,
      $start_date,
      $end_date,
      $billing,
      $duration,
      $user_id,
      $existing['id'],
    );
  }
  $action = 'upgrade';
} else {
  // NEW SUBSCRIPTION
  $sql =
    'INSERT INTO user_memberships (user_id, plan_id, name, country, permanent_address, plan_name, qr_proof, start_date, end_date, billing_type, membership_status, request_status, duration';
  if ($has_source_columns) {
    $sql .= ', source_table, source_id';
  }
  $sql .= ") VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'pending', ?";
  if ($has_source_columns) {
    $sql .= ', ?, ?';
  }
  $sql .= ')';

  $stmt = $conn->prepare($sql);
  if ($has_source_columns) {
    $stmt->bind_param(
      'iissssssssisi',
      $user_id,
      $plan_id,
      $name,
      $country,
      $address,
      $membership['plan_name'],
      $filename,
      $start_date,
      $end_date,
      $billing,
      $duration,
      $source_table,
      $source_id,
    );
  } else {
    $stmt->bind_param(
      'iissssssssi',
      $user_id,
      $plan_id,
      $name,
      $country,
      $address,
      $membership['plan_name'],
      $filename,
      $start_date,
      $end_date,
      $billing,
      $duration,
    );
  }
  $action = 'new';
}

if ($stmt->execute()) {
  // Send Email
  try {
    include_once __DIR__ . '/../../../includes/membership_mailer.php';
    $userQ = $conn->query(
      "SELECT email, username FROM users WHERE id=$user_id",
    );
    if ($u = $userQ->fetch_assoc()) {
      sendMembershipApplicationEmail(
        $u['email'],
        $u['username'],
        $membership['plan_name'],
        'pending',
      );
    }
  } catch (Exception $e) {
  }

  echo json_encode([
    'success' => true,
    'message' => 'Subscription submitted for review.',
    'membership' => ['status' => 'pending', 'plan' => $membership['plan_name']],
  ]);
} else {
  echo json_encode([
    'success' => false,
    'message' => 'Database error: ' . $stmt->error,
  ]);
}
?>
