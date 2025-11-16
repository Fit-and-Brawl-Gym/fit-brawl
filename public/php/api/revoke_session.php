<?php
/**
 * Revoke a user session
 */
session_start();

require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/session_tracker.php';
require_once __DIR__ . '/../../../includes/csrf_protection.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require authentication
$user = ApiSecurityMiddleware::requireAuth();
if (!$user) {
    exit; // Already sent response
}

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    exit; // Already sent response
}

// Require CSRF token
if (!ApiSecurityMiddleware::requireCSRF()) {
    exit; // Already sent response
}

$userId = $user['user_id'];
$currentSessionId = session_id();

// Rate limiting - 10 requests per minute per user (revocation is sensitive)
ApiSecurityMiddleware::applyRateLimit($conn, 'revoke_session:' . $userId, 10, 60);

// Get JSON body
$input = ApiSecurityMiddleware::getJsonBody();

// Validate input
$validation = ApiSecurityMiddleware::validateInput([
    'session_id' => [
        'type' => 'string',
        'required' => true
    ],
    'revoke_all' => [
        'type' => 'boolean',
        'required' => false,
        'default' => false
    ]
], $input);

if (!$validation['valid']) {
    $errors = implode(', ', $validation['errors']);
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Validation failed: ' . $errors
    ], 400);
    exit;
}

$data = $validation['data'];
$revokeAll = $data['revoke_all'] ?? false;

// Initialize session tracker
SessionTracker::init($conn);

try {
    if ($revokeAll) {
        // Revoke all other sessions (not current)
        $result = SessionTracker::revokeAllOtherSessions($userId, $currentSessionId);
        $message = 'All other sessions revoked successfully';
    } else {
        $targetSessionId = $data['session_id'];

        // Prevent revoking current session
        if ($targetSessionId === $currentSessionId) {
            ApiSecurityMiddleware::sendJsonResponse([
                'success' => false,
                'message' => 'Cannot revoke your current session. Please log out instead.'
            ], 400);
            exit;
        }

        // Revoke specific session
        $result = SessionTracker::revokeSession($userId, $targetSessionId);
        $message = 'Session revoked successfully';
    }

    if ($result) {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => true,
            'message' => $message
        ], 200);
    } else {
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Failed to revoke session'
        ], 500);
    }

} catch (Exception $e) {
    error_log("Error in revoke_session.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while revoking the session.'
    ], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

