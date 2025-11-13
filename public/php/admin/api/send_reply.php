<?php
// Start output buffering to catch any unexpected output
ob_start();

session_start();

// Disable HTML error output and log errors instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../../../includes/mail_config.php';
require_once '../../../../includes/db_connect.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('Invalid request data');
    }

    $contactId = $input['contact_id'] ?? null;
    $to = $input['to'] ?? null;
    $subject = $input['subject'] ?? null;
    $replyMessage = $input['message'] ?? null;
    $originalMessage = $input['original_message'] ?? '';
    $sendCopy = $input['send_copy'] ?? false;

    if (!$to || !$subject || !$replyMessage) {
        throw new Exception('Missing required fields');
    }

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

    // Log admin action
    if ($contactId) {
        $admin_id = $_SESSION['user_id'];
        $admin_name = $_SESSION['username'] ?? 'Admin';
        $details = "Replied to contact ID: $contactId - Sent to: $to";

        $log_sql = "INSERT INTO admin_logs (admin_id, admin_name, action_type, target_id, details) 
                    VALUES (?, ?, 'contact_reply', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("ssis", $admin_id, $admin_name, $contactId, $details);
        $log_stmt->execute();
    }

    // Clear any unexpected output and send clean JSON
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully'
    ]);

} catch (Exception $e) {
    // Clear any unexpected output
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
