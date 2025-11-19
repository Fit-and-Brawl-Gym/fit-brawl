<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\get_members.php
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Require admin authentication
$user = ApiSecurityMiddleware::requireAuth(['role' => 'admin']);
if (!$user) {
    exit; // Already sent response
}

// Admin-only endpoint; no rate limiting necessary
$adminId = $user['user_id'];

try {
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'user_memberships'");
    if ($tableCheck->num_rows == 0) {
        throw new Exception('user_memberships table does not exist');
    }

    // Get all columns from user_memberships table
    $columns = $conn->query("SHOW COLUMNS FROM user_memberships");
    $availableColumns = [];
    while ($col = $columns->fetch_assoc()) {
        $availableColumns[] = $col['Field'];
    }

    // Check if users table exists
    $usersTableCheck = $conn->query("SHOW TABLES LIKE 'users'");
    $hasUsersTable = $usersTableCheck->num_rows > 0;

    // Build SELECT clause based on available columns
    $selectFields = [
        'um.id',
        'um.user_id',
        'um.name'
    ];

    // Add email (from users table or fallback)
    if ($hasUsersTable) {
        $selectFields[] = "COALESCE(u.email, 'N/A') as email";
    } else {
        $selectFields[] = "'N/A' as email";
    }

    // Add optional columns if they exist
    $optionalColumns = [
        'plan_name',
        'duration',
        'total_payment',
        'start_date',
        'end_date',
        'date_submitted',
        'country',
        'permanent_address',
        'qr_proof',
        'membership_status'
    ];

    foreach ($optionalColumns as $col) {
        if (in_array($col, $availableColumns)) {
            $selectFields[] = "um.$col";
        } else {
            $selectFields[] = "NULL as $col";
        }
    }

    $selectClause = implode(', ', $selectFields);

    // Build query based on available tables
    if ($hasUsersTable) {
        $sql = "SELECT $selectClause
                FROM user_memberships um
                LEFT JOIN users u ON um.user_id = u.id
                WHERE um.request_status = 'approved'
                ORDER BY um.date_submitted DESC";
    } else {
        $sql = "SELECT $selectClause
                FROM user_memberships um
                WHERE um.request_status = 'approved'
                ORDER BY um.date_submitted DESC";
    }

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception('Query failed: ' . $conn->error);
    }

    $members = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate total_payment based on plan_name and billing_type
        $planPrices = [
            'Gladiator' => ['monthly' => 14500, 'quarterly' => 36540],
            'Brawler' => ['monthly' => 11500, 'quarterly' => 32775],
            'Champion' => ['monthly' => 7000, 'quarterly' => 19950],
            'Clash' => ['monthly' => 6000, 'quarterly' => 17100],
            'Resolution Regular' => ['monthly' => 4000, 'quarterly' => 11400],
            'Resolution Student' => ['monthly' => 2500, 'quarterly' => 7125]
        ];

        $planName = $row['plan_name'];
        $billingType = $row['billing_type'] ?? 'monthly';

        if (isset($planPrices[$planName][$billingType])) {
            $row['total_payment'] = $planPrices[$planName][$billingType];
        } else {
            $row['total_payment'] = null;
        }

        $members[] = $row;
    }

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'members' => $members,
        'total' => count($members)
    ], 200);

} catch (Exception $e) {
    error_log("Error in get_members.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching members. Please try again.'
    ], 500);
}

if (isset($conn)) {
    $conn->close();
}
