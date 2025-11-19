<?php
// Start output buffering to catch any unexpected output
ob_start();

session_start();

require_once __DIR__ . '/../../../../includes/mail_config.php';
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../../includes/input_validator.php';
require_once __DIR__ . '/../../../../includes/activity_logger.php';

// Initialize activity logger
ActivityLogger::init($conn);

// Disable HTML error output and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

ApiSecurityMiddleware::setSecurityHeaders();

// Require admin authentication
$user = ApiSecurityMiddleware::requireAuth(['role' => 'admin']);
if (!$user) {
    ob_end_clean();
    exit; // Already sent response
}

// Admin users can send replies without rate limiting
$adminId = $user['user_id'];

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    ob_end_clean();
    exit; // Already sent response
}

// Get JSON body
$input = ApiSecurityMiddleware::getJsonBody();

// Get CSRF token from header or JSON body
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');
if (!CSRFProtection::validateToken($csrfToken)) {
    ob_end_clean();
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Invalid or missing CSRF token'
    ], 403);
}

try {
    // Validate and sanitize input
    $validation = ApiSecurityMiddleware::validateInput([
        'contact_id' => [
            'type' => 'integer',
            'required' => false
        ],
        'to' => [
            'type' => 'email',
            'required' => true
        ],
        'subject' => [
            'type' => 'string',
            'required' => true,
            'max_length' => 255
        ],
        'message' => [
            'type' => 'string',
            'required' => true,
            'max_length' => 5000
        ],
        'original_message' => [
            'type' => 'string',
            'required' => false,
            'max_length' => 5000
        ],
        'send_copy' => [
            'type' => 'boolean',
            'required' => false,
            'default' => false
        ]
    ], $input);

    if (!$validation['valid']) {
        $errors = implode(', ', $validation['errors']);
        ob_end_clean();
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Validation failed: ' . $errors
        ], 400);
    }

    $data = $validation['data'];
    $contactId = $data['contact_id'] ?? null;
    $to = $data['to'];
    $subject = $data['subject'];
    $replyMessage = $data['message'];
    $originalMessage = $data['original_message'] ?? '';
    $sendCopy = $data['send_copy'] ?? false;

    // Send email to customer using the standard email template
    // (this will throw exception if it fails)
    sendContactReply($to, $subject, $replyMessage, $originalMessage);

    // Send copy to admin if requested (don't fail if this fails)
    if ($sendCopy && isset($_SESSION['email'])) {
        try {
            $adminSubject = "[Copy] " . $subject;
            sendContactReply($_SESSION['email'], $adminSubject, $replyMessage, $originalMessage);
        } catch (Exception $e) {
            error_log("Failed to send admin copy: " . $e->getMessage());
            // Continue even if admin copy fails
        }
    }

    // Log admin action using ActivityLogger
    if ($contactId) {
        ActivityLogger::log(
            'contact_reply',
            $to,
            $contactId,
            "Replied to contact inquiry - Subject: {$subject}"
        );
    }

    // Clear any unexpected output and send clean JSON
    ob_end_clean();
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'message' => 'Reply sent successfully'
    ], 200);

} catch (Exception $e) {
    // Clear any unexpected output
    ob_end_clean();
    error_log("Error in send_reply.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while sending the reply. Please try again.'
    ], 500);
}

if (isset($conn)) {
    $conn->close();
}
