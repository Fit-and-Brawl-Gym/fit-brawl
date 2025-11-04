<?php
session_start();
header('Content-Type: application/json');

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

    // Build email content
    $emailBody = buildReplyEmail($replyMessage, $originalMessage);

    // Send email to customer
    $sent = sendContactReply($to, $subject, $emailBody);

    if (!$sent) {
        throw new Exception('Failed to send email');
    }

    // Send copy to admin if requested
    if ($sendCopy && isset($_SESSION['email'])) {
        $adminSubject = "[Copy] " . $subject;
        sendContactReply($_SESSION['email'], $adminSubject, $emailBody);
    }

    // Log admin action
    if ($contactId) {
        $admin_id = $_SESSION['user_id'];
        $admin_name = $_SESSION['username'] ?? 'Admin';
        $details = "Replied to contact ID: $contactId - Sent to: $to";

        $log_sql = "INSERT INTO admin_logs (admin_id, admin_name, action_type, target_id, details) 
                    VALUES (?, ?, 'contact_reply', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("isis", $admin_id, $admin_name, $contactId, $details);
        $log_stmt->execute();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function buildReplyEmail($replyMessage, $originalMessage)
{
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #002f3f; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .reply-message { background: white; padding: 20px; border-left: 4px solid #d5ba2b; margin: 20px 0; border-radius: 4px; }
            .original-message { background: #f0f0f0; padding: 15px; margin-top: 20px; border-radius: 4px; font-size: 0.9em; }
            .footer { background: #002f3f; color: white; padding: 20px; text-align: center; font-size: 0.85em; border-radius: 0 0 8px 8px; }
            .signature { margin-top: 30px; padding-top: 20px; border-top: 2px solid #d5ba2b; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1 style="margin: 0;">Fit & Brawl Gym</h1>
                <p style="margin: 5px 0 0 0;">Response to Your Inquiry</p>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>Thank you for contacting Fit & Brawl Gym. Here is our response to your inquiry:</p>
                
                <div class="reply-message">
                    ' . nl2br(htmlspecialchars($replyMessage)) . '
                </div>
                
                ' . ($originalMessage ? '
                <div class="original-message">
                    <strong>Your Original Message:</strong><br>
                    ' . nl2br(htmlspecialchars($originalMessage)) . '
                </div>
                ' : '') . '
                
                <div class="signature">
                    <p><strong>Best regards,</strong><br>
                    Fit & Brawl Gym Team</p>
                    <p style="font-size: 0.9em; color: #666;">
                        📍 Location: [Your Gym Address]<br>
                        📞 Phone: [Your Phone Number]<br>
                        📧 Email: [Your Email Address]<br>
                        🌐 Website: [Your Website]
                    </p>
                </div>
            </div>
            <div class="footer">
                <p>© 2025 Fit & Brawl Gym. All rights reserved.</p>
                <p style="font-size: 0.85em; margin-top: 10px;">
                    If you have any further questions, please don\'t hesitate to contact us.
                </p>
            </div>
        </div>
    </body>
    </html>
    ';

    return $html;
}

if (isset($conn)) {
    $conn->close();
}
