<?php
// Start output buffering to catch any unwanted output
ob_start();

session_start();

require_once '../../../includes/db_connect.php';

// Check if file_upload_security exists, if not, skip it
$uploadSecurityExists = file_exists('../../../includes/file_upload_security.php');
if ($uploadSecurityExists) {
    require_once '../../../includes/file_upload_security.php';
}

// Clear any output that might have been generated
ob_end_clean();

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
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'online';
$name = trim($_POST['name'] ?? '');
$country = trim($_POST['country'] ?? '');
$address = trim($_POST['address'] ?? '');

// Validate required fields
if (empty($plan) || empty($name) || empty($country) || empty($address)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate payment method
if (!in_array($payment_method, ['online', 'cash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit;
}

// Handle file upload - only required for online payments
$filename = null;
if ($payment_method === 'online') {
    // Validate and upload file securely
    if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Please upload a payment receipt']);
        exit;
    }

$uploadDir = __DIR__ . '/../../../uploads/receipts/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle file upload with or without SecureFileUpload class
// Check if finfo_open function exists (required by SecureFileUpload)
if ($uploadSecurityExists && class_exists('SecureFileUpload') && function_exists('finfo_open')) {
    $uploadHandler = SecureFileUpload::receiptUpload($uploadDir, 10);
    $result = $uploadHandler->uploadFile($_FILES['receipt']);

    if (!$result['success']) {
        echo json_encode(['success' => false, 'message' => $result['message']]);
        exit;
    }

    $filename = 'receipt_' . $user_id . '_' . time() . '.' . pathinfo($result['filename'], PATHINFO_EXTENSION);
    $uploadPath = $uploadDir . $filename;
    rename($result['path'], $uploadPath);
} else {
    // Simple file upload without security class
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $fileType = $_FILES['receipt']['type'];
    $fileSize = $_FILES['receipt']['size'];

    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and PDF are allowed.']);
        exit;
    }

    if ($fileSize > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File size must be less than 10MB']);
        exit;
    }

    $extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
    $filename = 'receipt_' . $user_id . '_' . time() . '.' . $extension;
    $uploadPath = $uploadDir . $filename;

    if (!move_uploaded_file($_FILES['receipt']['tmp_name'], $uploadPath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit;
    }
}
} else {
    // Cash payment - no receipt needed, will be marked as unpaid
    $filename = null;
}

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
$check_existing->bind_param("s", $user_id);
$check_existing->execute();
$existing = $check_existing->get_result()->fetch_assoc();

// Only block if there's a pending ONLINE payment (not cash payments which are handled separately)
if ($existing && $existing['request_status'] === 'pending') {
    // Allow if it's a cash payment that hasn't been paid yet (user might be resubmitting)
    $payment_method_col_check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'payment_method'");
    $has_payment_method = $payment_method_col_check->num_rows > 0;
    
    if ($has_payment_method && $existing['payment_method'] === 'online') {
        echo json_encode(['success' => false, 'message' => 'Your online payment is still pending approval.']);
        exit;
    } elseif (!$has_payment_method) {
        // Old schema without payment_method column
        echo json_encode(['success' => false, 'message' => 'Upgrade or membership request already pending approval.']);
        exit;
    }
    // If it's a cash payment that's pending, allow new submission (will be treated as upgrade/new)
}

// Calculate dates based on billing type
$start_date = date('Y-m-d');
if ($billing === 'quarterly') {
    $end_date = date('Y-m-d', strtotime('+3 months'));
    $duration = 90;
} else {
    // Default to monthly
    $end_date = date('Y-m-d', strtotime('+1 month'));
    $duration = 30;
}

// Define source table and ID (set to NULL if not applicable)
$source_table = null;
$source_id = null;

if ($existing && $existing['membership_status'] === 'active') {
    // Check if the table has source_table and source_id columns
    $columns_check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'source_table'");
    $has_source_columns = $columns_check->num_rows > 0;

    // Check if payment_method column exists
    $payment_columns_check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'payment_method'");
    $has_payment_columns = $payment_columns_check->num_rows > 0;

    if ($has_source_columns && $has_payment_columns) {
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
            payment_method = ?,
            cash_payment_status = ?,
            membership_status = 'active',
            request_status = 'pending',
            duration = ?,
            source_table = ?,
            source_id = ?
        WHERE user_id = ? AND id = ?
        ";

        $cash_status = ($payment_method === 'cash') ? 'unpaid' : null;
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param(
            "issssssssssisisi",
            $plan_id,
            $name,
            $country,
            $address,
            $membership['plan_name'],
            $filename,
            $start_date,
            $end_date,
            $billing,
            $payment_method,
            $cash_status,
            $duration,
            $source_table,
            $source_id,
            $user_id,
            $existing['id']
        );
    } elseif ($has_payment_columns) {
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
            payment_method = ?,
            cash_payment_status = ?,
            membership_status = 'active',
            request_status = 'pending',
            duration = ?
        WHERE user_id = ? AND id = ?
        ";

        $cash_status = ($payment_method === 'cash') ? 'unpaid' : null;
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param(
            "issssssssssis",
            $plan_id,
            $name,
            $country,
            $address,
            $membership['plan_name'],
            $filename,
            $start_date,
            $end_date,
            $billing,
            $payment_method,
            $cash_status,
            $duration,
            $user_id,
            $existing['id']
        );
    } elseif ($has_source_columns) {
        // Update with source columns but no payment columns
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
            "issssssssisssi",
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
    } else {
        // Fallback for tables without payment columns
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
            duration = ?
        WHERE user_id = ? AND id = ?
        ";

        $stmt = $conn->prepare($update_query);
        $stmt->bind_param(
            "issssssssiss",
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
            $existing['id']
        );
    }

    $action = 'upgrade';
} else {
    // Check if the table has source_table and source_id columns
    $columns_check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'source_table'");
    $has_source_columns = $columns_check->num_rows > 0;

    // Check if payment_method column exists
    $payment_columns_check = $conn->query("SHOW COLUMNS FROM user_memberships LIKE 'payment_method'");
    $has_payment_columns = $payment_columns_check->num_rows > 0;

    error_log("Cash payment debug - has_source_columns: " . ($has_source_columns ? 'true' : 'false') . ", has_payment_columns: " . ($has_payment_columns ? 'true' : 'false'));

    if ($has_source_columns && $has_payment_columns) {
        error_log("Using branch: source AND payment columns");
        // Insert into user_memberships with source and payment columns
        $insert_query = "
        INSERT INTO user_memberships
        (user_id, plan_id, name, country, permanent_address, plan_name, qr_proof, start_date, end_date, billing_type, payment_method, cash_payment_status, membership_status, request_status, duration, source_table, source_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'pending', ?, ?, ?)
        ";

        $cash_status = ($payment_method === 'cash') ? 'unpaid' : null;
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param(
            "sississsssssiss",
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
            $payment_method,
            $cash_status,
            $duration,
            $source_table,
            $source_id
        );
    } elseif ($has_payment_columns) {
        // Insert into user_memberships with payment columns only
        $insert_query = "
        INSERT INTO user_memberships
        (user_id, plan_id, name, country, permanent_address, plan_name, qr_proof, start_date, end_date, billing_type, payment_method, cash_payment_status, membership_status, request_status, duration)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'pending', ?)
        ";

        $cash_status = ($payment_method === 'cash') ? 'unpaid' : null;
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param(
            "sissssssssssi",
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
            $payment_method,
            $cash_status,
            $duration
        );
    } elseif ($has_source_columns) {
        // Insert into user_memberships with source columns but no payment columns
        $insert_query = "
        INSERT INTO user_memberships
        (user_id, plan_id, name, country, permanent_address, plan_name, qr_proof, start_date, end_date, billing_type, membership_status, request_status, duration, source_table, source_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'pending', ?, ?, ?)
        ";

        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param(
            "sissssssssisi",
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
    } else {
        // Fallback: Insert into user_memberships without source or payment columns
        $insert_query = "
        INSERT INTO user_memberships
        (user_id, plan_id, name, country, permanent_address, plan_name, qr_proof, start_date, end_date, billing_type, membership_status, request_status, duration)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 'pending', ?)
        ";

        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param(
            "sissssssssi",
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
            $duration
        );
    }

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