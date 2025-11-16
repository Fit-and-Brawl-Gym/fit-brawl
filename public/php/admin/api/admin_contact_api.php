<?php
require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../../includes/api_rate_limiter.php';
require_once __DIR__ . '/../../../../includes/input_validator.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';

// Ensure email template is available to wrap replies
include_once __DIR__ . '/../../../../includes/email_template.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require admin authentication
$user = ApiSecurityMiddleware::requireAuth(['role' => 'admin']);
if (!$user) {
    exit; // Already sent response
}

// Rate limiting for admin APIs - 20 requests per minute per admin
$adminId = $user['user_id'];
ApiSecurityMiddleware::applyRateLimit($conn, 'admin_contact_api:' . $adminId, 20, 60);

$action = $_GET['action'] ?? '';

if ($action === 'fetch') {
    try {
        $stmt = $conn->prepare("SELECT * FROM inquiries ORDER BY date_sent DESC");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            ApiSecurityMiddleware::sendJsonResponse([
                'success' => true,
                'data' => $data
            ], 200);
        } else {
            throw new Exception('Database error');
        }
    } catch (Exception $e) {
        error_log("Error in admin_contact_api.php (fetch): " . $e->getMessage());
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'An error occurred while fetching inquiries.'
        ], 500);
    }
    exit;
}

if ($action === 'mark_read') {
    // Require POST method
    if (!ApiSecurityMiddleware::requireMethod('POST')) {
        exit; // Already sent response
    }

    // Require CSRF token
    if (!ApiSecurityMiddleware::requireCSRF()) {
        exit; // Already sent response
    }

    // Validate input
    $validation = ApiSecurityMiddleware::validateInput([
        'id' => [
            'type' => 'integer',
            'required' => true,
            'min' => 1
        ]
    ]);

    if (!$validation['valid']) {
        $errors = implode(', ', $validation['errors']);
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Validation failed: ' . $errors
        ], 400);
    }

    $id = $validation['data']['id'];

    $stmt = $conn->prepare("UPDATE inquiries SET status = 'Read' WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed in admin_contact_api.php (mark_read)");
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Database error. Please try again.'
        ], 500);
    }

    $stmt->bind_param('i', $id);
    $success = $stmt->execute();
    $stmt->close();

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => $success,
        'message' => $success ? 'Inquiry marked as read' : 'Failed to update inquiry'
    ], $success ? 200 : 500);
    exit;
}

if ($action === 'delete') {
    // Require POST method
    if (!ApiSecurityMiddleware::requireMethod('POST')) {
        exit; // Already sent response
    }

    // Require CSRF token
    if (!ApiSecurityMiddleware::requireCSRF()) {
        exit; // Already sent response
    }

    // Validate input
    $validation = ApiSecurityMiddleware::validateInput([
        'id' => [
            'type' => 'integer',
            'required' => true,
            'min' => 1
        ]
    ]);

    if (!$validation['valid']) {
        $errors = implode(', ', $validation['errors']);
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Validation failed: ' . $errors
        ], 400);
    }

    $id = $validation['data']['id'];

    $stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed in admin_contact_api.php (delete)");
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Database error. Please try again.'
        ], 500);
    }

    $stmt->bind_param('i', $id);
    $success = $stmt->execute();
    $stmt->close();

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => $success,
        'message' => $success ? 'Inquiry deleted successfully' : 'Failed to delete inquiry'
    ], $success ? 200 : 500);
    exit;
}

if ($action === 'reply') {
    // Require POST method
    if (!ApiSecurityMiddleware::requireMethod('POST')) {
        exit; // Already sent response
    }

    // Require CSRF token
    if (!ApiSecurityMiddleware::requireCSRF()) {
        exit; // Already sent response
    }

    // Validate input
    $validation = ApiSecurityMiddleware::validateInput([
        'email' => [
            'type' => 'email',
            'required' => true
        ],
        'subject' => [
            'type' => 'string',
            'required' => false,
            'default' => 'Response from Fit & Brawl',
            'max_length' => 255
        ],
        'message' => [
            'type' => 'string',
            'required' => true,
            'max_length' => 5000
        ]
    ]);

    if (!$validation['valid']) {
        $errors = implode(', ', $validation['errors']);
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Validation failed: ' . $errors
        ], 400);
    }

    $data = $validation['data'];
    $email = $data['email'];
    $subject = $data['subject'] ?? 'Response from Fit & Brawl';
    $message = $data['message'];

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        // sanitize and convert newlines for HTML, then apply template
        $bodyHtml = '<div>' . nl2br(htmlspecialchars($message)) . '</div>';
        applyEmailTemplate($mail, $bodyHtml);

        $mail->send();
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => true,
            'message' => 'Reply sent successfully!'
        ], 200);
    } catch (Exception $e) {
        error_log("Mailer error in admin_contact_api.php: " . $mail->ErrorInfo);
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => false,
            'message' => 'Failed to send email. Please try again.'
        ], 500);
    }
    exit;
}

ApiSecurityMiddleware::sendJsonResponse([
    'success' => false,
    'message' => 'Invalid action'
], 400);
?>
