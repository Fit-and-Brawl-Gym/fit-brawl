<?php
/**
 * Get active sessions for the current user
 */
session_start();

require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/session_tracker.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require authentication
$user = ApiSecurityMiddleware::requireAuth();
if (!$user) {
    exit; // Already sent response
}

// Rate limiting - 30 requests per minute per user
$userId = $user['user_id'];
ApiSecurityMiddleware::applyRateLimit($conn, 'get_sessions:' . $userId, 30, 60);

$currentSessionId = session_id();

// Initialize session tracker
SessionTracker::init($conn);

try {
    $sessions = SessionTracker::getUserSessions($userId);

    // Format sessions for response
    $formattedSessions = [];
    foreach ($sessions as $session) {
        $formattedSessions[] = [
            'id' => $session['id'],
            'session_id' => substr($session['session_id'], 0, 8) . '...', // Partial ID for display
            'device' => SessionTracker::getDeviceInfo($session['user_agent'] ?? ''),
            'ip_address' => $session['ip_address'] ?? 'Unknown',
            'login_time' => $session['login_time'],
            'last_activity' => $session['last_activity'],
            'minutes_inactive' => (int)($session['minutes_inactive'] ?? 0),
            'is_current' => (bool)($session['is_current'] ?? false),
            'full_session_id' => $session['session_id'] // For revocation
        ];
    }

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'sessions' => $formattedSessions
    ], 200);

} catch (Exception $e) {
    error_log("Error in get_sessions.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching sessions.'
    ], 500);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

