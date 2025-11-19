<?php
/**
 * Secure Admin Users API
 * Integrated with RBAC, Enhanced Audit Logging, and Security Services
 */

session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/enhanced_audit_logger.php';
require_once __DIR__ . '/../../../../includes/rbac_helper.php';
require_once __DIR__ . '/../../../../includes/password_reset_service.php';
require_once __DIR__ . '/../../../../includes/sensitive_change_service.php';
require_once __DIR__ . '/../../../../includes/user_profile_service.php';

// Initialize services
EnhancedAuditLogger::init($conn);
RBACHelper::init($conn);
PasswordResetService::init($conn);
SensitiveChangeService::init($conn);
UserProfileService::init($conn);

// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Require admin authentication
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['username'] ?? 'Unknown';

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

// Route to appropriate handler
try {
    switch ($action) {
        case 'getAllUsers':
            handleGetAllUsers($conn, $adminId);
            break;

        case 'getUserDetails':
            handleGetUserDetails($conn, $adminId);
            break;

        case 'updateUserProfile':
            handleUpdateUserProfile($conn, $adminId);
            break;

        case 'resetPassword':
            handleResetPassword($conn, $adminId);
            break;

        case 'initiateSensitiveChange':
            handleInitiateSensitiveChange($conn, $adminId);
            break;

        case 'suspendUser':
            handleSuspendUser($conn, $adminId);
            break;

        case 'activateUser':
            handleActivateUser($conn, $adminId);
            break;

        case 'deleteUser':
            handleDeleteUser($conn, $adminId);
            break;

        case 'generateSecurityCode':
            handleGenerateSecurityCode($conn, $adminId);
            break;

        case 'getEditableFields':
            handleGetEditableFields($conn, $adminId);
            break;

        case 'getAuditLogs':
            handleGetAuditLogs($conn, $adminId);
            break;

        case 'getUserNotifications':
            handleGetUserNotifications($conn, $adminId);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Error in admin_users_api.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

if (isset($conn)) {
    $conn->close();
}

// ==================== HANDLER FUNCTIONS ====================

/**
 * Get all users with basic info
 */
function handleGetAllUsers($conn, $adminId)
{
    // Check permission
    if (!RBACHelper::hasPermission($adminId, 'VIEW_USERS')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to view users',
            'requires_permission' => 'VIEW_USERS'
        ]);
        return;
    }

    $query = "SELECT 
                u.id,
                u.username,
                u.email,
                u.contact_number,
                u.role,
                u.is_verified,
                u.account_status,
                u.avatar,
                u.created_at,
                COALESCE(um.name, u.username) as full_name,
                um.plan_name,
                um.start_date as membership_start,
                um.end_date as membership_end,
                um.membership_status
              FROM users u
              LEFT JOIN (
                SELECT user_id, name, plan_name, start_date, end_date, membership_status
                FROM user_memberships
                WHERE id IN (
                    SELECT MAX(id) 
                    FROM user_memberships 
                    GROUP BY user_id
                )
              ) um ON u.id = um.user_id
              ORDER BY u.created_at DESC";

    $result = $conn->query($query);

    if (!$result) {
        throw new Exception('Failed to fetch users: ' . $conn->error);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode([
        'success' => true,
        'users' => $users,
        'total' => count($users)
    ]);
}

/**
 * Get detailed user information
 */
function handleGetUserDetails($conn, $adminId)
{
    if (!RBACHelper::hasPermission($adminId, 'VIEW_USERS')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $userId = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }

    $stmt = $conn->prepare("SELECT 
                              u.*,
                              COALESCE(um.name, u.username) as full_name,
                              um.plan_name,
                              um.start_date,
                              um.end_date,
                              um.membership_status,
                              um.billing_type
                            FROM users u
                            LEFT JOIN user_memberships um ON u.id = um.user_id
                            WHERE u.id = ?
                            LIMIT 1");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }

    // Get recent audit logs for this user
    $auditLogs = EnhancedAuditLogger::query([
        'target_user_id' => $userId,
        'limit' => 10
    ]);

    echo json_encode([
        'success' => true,
        'user' => $user,
        'recent_activity' => $auditLogs
    ]);
}

/**
 * Update user profile (with field restrictions)
 */
function handleUpdateUserProfile($conn, $adminId)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }

    unset($data['user_id']); // Remove user_id from updates array

    $result = UserProfileService::updateUserProfile($userId, $data, $adminId);

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
}

/**
 * Reset user password (secure - admin NEVER sees password)
 */
function handleResetPassword($conn, $adminId)
{
    if (!RBACHelper::hasPermission($adminId, 'RESET_PASSWORD')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to reset passwords',
            'requires_permission' => 'RESET_PASSWORD'
        ]);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? null;

        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            return;
        }

        $result = PasswordResetService::triggerPasswordReset($userId, true);

        http_response_code($result['success'] ? 200 : 400);
        echo json_encode($result);
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to process password reset: ' . $e->getMessage()
        ]);
    }
}

/**
 * Initiate sensitive data change (email/phone)
 */
function handleInitiateSensitiveChange($conn, $adminId)
{
    if (!RBACHelper::hasPermission($adminId, 'INITIATE_SENSITIVE_CHANGE')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to initiate sensitive changes',
            'requires_permission' => 'INITIATE_SENSITIVE_CHANGE'
        ]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? null;
    $changeType = $data['change_type'] ?? null;
    $newValue = $data['new_value'] ?? null;

    if (!$userId || !$changeType || !$newValue) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }

    $result = SensitiveChangeService::initiateSensitiveChange($changeType, $userId, $newValue);

    http_response_code($result['success'] ? 200 : 400);
    echo json_encode($result);
}

/**
 * Suspend user account
 */
function handleSuspendUser($conn, $adminId)
{
    if (!RBACHelper::hasPermission($adminId, 'SUSPEND_USER')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to suspend users',
            'requires_permission' => 'SUSPEND_USER'
        ]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? null;
    $reason = $data['reason'] ?? 'No reason provided';

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }

    // Get user info
    $stmt = $conn->prepare("SELECT COALESCE(um.name, u.username) as name, u.account_status 
                            FROM users u 
                            LEFT JOIN user_memberships um ON u.id = um.user_id 
                            WHERE u.id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }

    // Update account status
    $stmt = $conn->prepare("UPDATE users SET account_status = 'suspended' WHERE id = ?");
    $stmt->bind_param('s', $userId);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to suspend user']);
        return;
    }

    // Log action
    EnhancedAuditLogger::log(
        'SUSPEND_USER',
        $userId,
        $user['name'],
        $user['account_status'],
        'suspended',
        'high',
        "User suspended. Reason: {$reason}"
    );

    // Notify user
    notifyUser($conn, $userId, 'Account Suspended', "Your account has been suspended. Reason: {$reason}", $_SESSION['username'] ?? 'Administrator');

    echo json_encode(['success' => true, 'message' => 'User suspended successfully']);
}

/**
 * Activate user account
 */
function handleActivateUser($conn, $adminId)
{
    if (!RBACHelper::hasPermission($adminId, 'SUSPEND_USER')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to activate users',
            'requires_permission' => 'SUSPEND_USER'
        ]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }

    // Get user info
    $stmt = $conn->prepare("SELECT COALESCE(um.name, u.username) as name, u.account_status 
                            FROM users u 
                            LEFT JOIN user_memberships um ON u.id = um.user_id 
                            WHERE u.id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }

    // Update account status
    $stmt = $conn->prepare("UPDATE users SET account_status = 'active' WHERE id = ?");
    $stmt->bind_param('s', $userId);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to activate user']);
        return;
    }

    // Log action
    EnhancedAuditLogger::log(
        'ACTIVATE_USER',
        $userId,
        $user['name'],
        $user['account_status'],
        'active',
        'medium',
        "User account activated"
    );

    // Notify user
    notifyUser($conn, $userId, 'Account Activated', 'Your account has been activated. You can now access all services.', $_SESSION['username'] ?? 'Administrator');

    echo json_encode(['success' => true, 'message' => 'User activated successfully']);
}

/**
 * Delete user account
 */
function handleDeleteUser($conn, $adminId)
{
    if (!RBACHelper::hasPermission($adminId, 'DELETE_USER')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to delete users',
            'requires_permission' => 'DELETE_USER'
        ]);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $userId = $data['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }

    // Get user info before deletion
    $stmt = $conn->prepare("SELECT COALESCE(um.name, u.username) as name 
                            FROM users u 
                            LEFT JOIN user_memberships um ON u.id = um.user_id 
                            WHERE u.id = ?");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        return;
    }

    // Log BEFORE deletion
    EnhancedAuditLogger::log(
        'DELETE_USER',
        $userId,
        $user['name'],
        'active',
        'deleted',
        'critical',
        "User account permanently deleted"
    );

    // Delete user (cascades to related records based on foreign keys)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('s', $userId);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        return;
    }

    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
}

/**
 * Generate security code for sensitive operations
 */
function handleGenerateSecurityCode($conn, $adminId)
{
    $data = json_decode(file_get_contents('php://input'), true);
    $purpose = $data['purpose'] ?? 'CHANGE_USER_ROLE';

    $code = RBACHelper::generateSecurityCode($adminId, $purpose, 5); // 5 minute expiry

    if (!$code) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to generate security code']);
        return;
    }

    echo json_encode([
        'success' => true,
        'code' => $code,
        'expires_in_minutes' => 5,
        'purpose' => $purpose
    ]);
}

/**
 * Get editable fields for current admin
 */
function handleGetEditableFields($conn, $adminId)
{
    $fields = UserProfileService::getEditableFields($adminId);

    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
}

/**
 * Get audit logs
 */
function handleGetAuditLogs($conn, $adminId)
{
    if (!RBACHelper::hasPermission($adminId, 'VIEW_AUDIT_LOGS')) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to view audit logs',
            'requires_permission' => 'VIEW_AUDIT_LOGS'
        ]);
        return;
    }

    $filters = [
        'target_user_id' => $_GET['user_id'] ?? null,
        'action_type' => $_GET['action_type'] ?? null,
        'severity' => $_GET['severity'] ?? null,
        'start_date' => $_GET['start_date'] ?? null,
        'end_date' => $_GET['end_date'] ?? null,
        'limit' => $_GET['limit'] ?? 50
    ];

    // Remove null values
    $filters = array_filter($filters, function ($value) {
        return $value !== null;
    });

    $logs = EnhancedAuditLogger::query($filters);

    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total' => count($logs)
    ]);
}

/**
 * Get user notifications
 */
function handleGetUserNotifications($conn, $adminId)
{
    $userId = $_GET['user_id'] ?? null;

    if (!$userId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        return;
    }

    if (!RBACHelper::hasPermission($adminId, 'VIEW_USERS')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM user_notifications 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC 
                            LIMIT 20");
    $stmt->bind_param('s', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'total' => count($notifications)
    ]);
}

/**
 * Helper: Notify user
 */
function notifyUser($conn, $userId, $title, $message, $adminIdentifier)
{
    $stmt = $conn->prepare("INSERT INTO user_notifications 
                            (user_id, notification_type, title, message, admin_identifier, sent_via_email) 
                            VALUES (?, 'ADMIN_ACTION', ?, ?, ?, 1)");
    $stmt->bind_param('ssss', $userId, $title, $message, $adminIdentifier);
    $stmt->execute();
}
