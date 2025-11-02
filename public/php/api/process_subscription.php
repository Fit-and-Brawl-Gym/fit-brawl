<?php
session_start();

require_once '../../../includes/db_connect.php';
require_once '../../../includes/file_upload_security.php';
header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$plan = isset($_POST['plan']) ? strtolower(trim($_POST['plan'])) : '';
$billing = isset($_POST['billing']) ? $_POST['billing'] : 'monthly';
$name = trim($_POST['name'] ?? '');
$country = trim($_POST['country'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate required fields
if (empty($plan) || empty($name) || empty($country) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate and upload file securely
if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Please upload a payment receipt']);
    exit;
}

$uploadDir = __DIR__ . '/../../../uploads/receipts/';
$uploadHandler = SecureFileUpload::receiptUpload($uploadDir, 10);

$result = $uploadHandler->uploadFile($_FILES['receipt']);

if (!$result['success']) {
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit;
}

$filename = 'receipt_' . $user_id . '_' . time() . '.' . pathinfo($result['filename'], PATHINFO_EXTENSION);
$uploadPath = $uploadDir . $filename;
rename($result['path'], $uploadPath);

// Plan mapping
$planMapping = [
    'gladiator' => 1,
    'brawler' => 2,
    'champion' => 3,
    'clash' => 4,
    'resolution-regular' => 5,
    'resolution-student' => 6
];

if (!isset($planMapping[$plan])) {
    echo json_encode(['success' => false, 'message' => 'Invalid membership plan: ' . htmlspecialchars($plan)]);
    exit;
}

$plan_id = $planMapping[$plan];

// Fetch membership info if it exists
$stmt = $conn->prepare("SELECT * FROM memberships WHERE id = ?");
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$membership = $stmt->get_result()->fetch_assoc();

// If resolution plans are not in DB, create a temporary plan name
if (!$membership) {
    if ($plan === 'resolution-student') {
        $membership = ['plan_name' => 'Resolution Student'];
    } elseif ($plan === 'resolution-regular') {
        $membership = ['plan_name' => 'Resolution Regular'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Membership plan not found']);
        exit;
    }
}


$check_existing = $conn->prepare("
    SELECT * FROM user_memberships
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$check_existing->bind_param("i", $user_id);
$check_existing->execute();
$existing = $check_existing->get_result()->fetch_assoc();


if ($existing && $existing['request_status'] === 'pending') {
    echo json_encode(['success' => false, 'message' => 'Upgrade or membership request already pending approval.']);
    exit;
}


$start_date = date('Y-m-d');
$end_date = ($billing === 'yearly') ? date('Y-m-d', strtotime('+1 year')) : date('Y-m-d', strtotime('+1 month'));
$duration = ($billing === 'yearly') ? 365 : 30;


if ($existing && $existing['membership_status'] === 'active') {
    $update_query = "
    UPDATE user_memberships
    SET
        plan_id = ?,
        name = ?,
        country = ?,
        permanent_address = ?,
        plan_name = ?,
        qr_proof = ?,
        start_date = ?,
        end_date = ?,
        billing_type = ?,
        membership_status = 'active',
        request_status = 'pending',
        duration = ?,
        source_table = ?,
        source_id = ?
    WHERE user_id = ? AND id = ?
";

$stmt = $conn->prepare($update_query);
$stmt->bind_param(
    "issssssssisiis",
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
    $existing['id']
);

    $action = 'upgrade';
} else {

// Insert into user_memberships
$insert_query = "
    INSERT INTO user_memberships
    (user_id, plan_id, name, country, permanent_address, plan_name, qr_proof, start_date, end_date, billing_type, membership_status, request_status, duration, source_table, source_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'pending', ?, ?, ?)
";

$stmt = $conn->prepare($insert_query);
$stmt->bind_param(
    "iissssssssisi",
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
    $source_id
);



    $action = 'new';
}

// Execute
if ($stmt->execute()) {
    // Attempt to send acknowledgement email to the user
    try {
        // fetch user email and username
        $userStmt = $conn->prepare("SELECT email, username FROM users WHERE id = ? LIMIT 1");
        if ($userStmt) {
            $userStmt->bind_param('i', $user_id);
            $userStmt->execute();
            $userRow = $userStmt->get_result()->fetch_assoc();
            $userStmt->close();

            if ($userRow && !empty($userRow['email'])) {
                include_once __DIR__ . '/../../../includes/membership_mailer.php';
                // Send application acknowledgement (status pending)
                sendMembershipApplicationEmail($userRow['email'], $userRow['username'] ?? $name, $membership['plan_name'] ?? $plan, 'pending');
            }
        }
    } catch (Exception $e) {
        error_log('Failed to send membership application email: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => $action === 'upgrade'
            ? 'Your existing membership has been upgraded and is now pending admin approval.'
            : 'Your subscription has been submitted for review. Please wait for admin approval.',
        'membership' => [
            'plan' => $membership['plan_name'],
            'end_date' => $end_date,
            'billing_type' => $billing,
            'status' => 'pending'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
