<?php
/**
 * Sensitive Change Service
 * Handles changes to sensitive user data (email, phone) with user confirmation
 */

require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/enhanced_audit_logger.php';

class SensitiveChangeService
{
    private static $conn;

        public static function init($connection)
    {
        self::$conn = $connection;
    }

    /**
     * Initiate sensitive data change
     * Requires user confirmation before applying
     * 
     * @param string $changeType Type: email, phone, recovery_email
     * @param string $userId Target user ID
     * @param string $newValue New value to set
     * @return array Result
     */
    public static function initiateSensitiveChange($changeType, $userId, $newValue)
    {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Database connection error'];
        }

        // Validate change type
        $validTypes = ['email', 'phone', 'recovery_email', 'security_question'];
        if (!in_array($changeType, $validTypes)) {
            return ['success' => false, 'message' => 'Invalid change type'];
        }

        // Get user and current value
        $userQuery = "SELECT u.id, u.email, u.username, u.contact_number, 
                             COALESCE(um.name, u.username) as name 
                      FROM users u 
                      LEFT JOIN user_memberships um ON u.id = um.user_id 
                      WHERE u.id = ? LIMIT 1";
        $stmt = self::$conn->prepare($userQuery);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        // Get old value
        $oldValue = null;
        switch ($changeType) {
            case 'email':
                $oldValue = $user['email'];
                break;
            case 'phone':
                $oldValue = $user['contact_number'];
                break;
        }

        // Validate new value
        if ($changeType === 'email' && !filter_var($newValue, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        if ($changeType === 'phone' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $newValue)) {
            return ['success' => false, 'message' => 'Invalid phone format'];
        }

        // Check for duplicates (email)
        if ($changeType === 'email') {
            $checkQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
            $checkStmt = self::$conn->prepare($checkQuery);
            $checkStmt->bind_param('ss', $newValue, $userId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                return ['success' => false, 'message' => 'Email already in use'];
            }
        }

        // Generate confirmation token
        $token = bin2hex(random_bytes(32));
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);
        
        // Expires in 24 hours
        $expiresAt = date('Y-m-d H:i:s', time() + 86400);

        $adminId = $_SESSION['user_id'] ?? 'SYSTEM';
        $ipAddress = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Insert change request
        $query = "INSERT INTO sensitive_change_requests 
                  (user_id, admin_id, change_type, old_value, new_value, confirmation_token, expires_at, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('sssssssss', $userId, $adminId, $changeType, $oldValue, $newValue, $hashedToken, $expiresAt, $ipAddress, $userAgent);

        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Failed to create change request'];
        }

        $requestId = $stmt->insert_id;

        // Send confirmation email
        $emailSent = self::sendConfirmationEmail($user['email'], $user['name'], $changeType, $newValue, $token);

        if (!$emailSent) {
            return ['success' => false, 'message' => 'Failed to send confirmation email'];
        }

        // Log the action
        EnhancedAuditLogger::log(
            'INITIATE_SENSITIVE_CHANGE',
            $userId,
            $user['name'],
            $oldValue,
            $newValue,
            'high',
            "Initiated {$changeType} change. Awaiting user confirmation."
        );

        // Notify user
        self::notifyUserChangeRequest($userId, $user['name'], $changeType);

        return [
            'success' => true,
            'message' => 'Confirmation email sent to user. Change will apply after user confirms.',
            'request_id' => $requestId
        ];
    }

    /**
     * Confirm sensitive change
     * Called when user clicks confirmation link
     */
    public static function confirmSensitiveChange($token)
    {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Database connection error'];
        }

        // Find valid pending request
        $query = "SELECT scr.*, u.email, COALESCE(um.name, u.username) as name 
                  FROM sensitive_change_requests scr
                  INNER JOIN users u ON scr.user_id = u.id
                  LEFT JOIN user_memberships um ON u.id = um.user_id
                  WHERE scr.status = 'pending' 
                  AND scr.expires_at > NOW()
                  ORDER BY scr.created_at DESC 
                  LIMIT 20";

        $result = self::$conn->query($query);

        $matchedRequest = null;
        while ($row = $result->fetch_assoc()) {
            if (password_verify($token, $row['confirmation_token'])) {
                $matchedRequest = $row;
                break;
            }
        }

        if (!$matchedRequest) {
            return ['success' => false, 'message' => 'Invalid or expired confirmation link'];
        }

        // Apply the change
        $success = self::applyChange($matchedRequest);

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to apply change'];
        }

        // Mark as confirmed
        $updateQuery = "UPDATE sensitive_change_requests SET status = 'confirmed', confirmed_at = NOW() WHERE id = ?";
        $stmt = self::$conn->prepare($updateQuery);
        $stmt->bind_param('i', $matchedRequest['id']);
        $stmt->execute();

        // Log completion
        EnhancedAuditLogger::log(
            'SENSITIVE_CHANGE_CONFIRMED',
            $matchedRequest['user_id'],
            $matchedRequest['name'],
            $matchedRequest['old_value'],
            $matchedRequest['new_value'],
            'high',
            "User confirmed {$matchedRequest['change_type']} change"
        );

        // Notify user
        self::notifyChangeCompleted($matchedRequest['user_id'], $matchedRequest['name'], $matchedRequest['change_type']);

        return [
            'success' => true,
            'message' => ucfirst($matchedRequest['change_type']) . ' updated successfully'
        ];
    }

    /**
     * Apply the actual change to user record
     */
    private static function applyChange($request)
    {
        $query = null;
        $stmt = null;

        switch ($request['change_type']) {
            case 'email':
                $query = "UPDATE users SET email = ? WHERE id = ?";
                $stmt = self::$conn->prepare($query);
                $stmt->bind_param('ss', $request['new_value'], $request['user_id']);
                break;

            case 'phone':
                $query = "UPDATE users SET contact_number = ? WHERE id = ?";
                $stmt = self::$conn->prepare($query);
                $stmt->bind_param('ss', $request['new_value'], $request['user_id']);
                break;

            default:
                return false;
        }

        return $stmt && $stmt->execute();
    }

    /**
     * Send confirmation email to user
     */
    private static function sendConfirmationEmail($email, $name, $changeType, $newValue, $token)
    {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = getenv('EMAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('EMAIL_USER');
            $mail->Password = getenv('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = getenv('EMAIL_PORT');

            $mail->setFrom(getenv('EMAIL_USER'), 'FitXBrawl Security');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Confirm Account Change - FitXBrawl';

            $confirmLink = self::getBaseUrl() . "/public/php/confirm-change.php?token=" . urlencode($token);

            $changeLabel = ucfirst(str_replace('_', ' ', $changeType));

            $html = "
            <h2>Confirm Account Change</h2>
            <p>Hello {$name},</p>
            <p>An administrator initiated a change to your account:</p>
            <p><strong>{$changeLabel}:</strong> {$newValue}</p>
            <p><strong>To confirm this change, click the link below:</strong></p>
            <p><a href='{$confirmLink}' style='display:inline-block;padding:10px 20px;background:#1a1d2e;color:white;text-decoration:none;border-radius:5px;'>Confirm Change</a></p>
            <p>Or copy and paste this link:<br><code>{$confirmLink}</code></p>
            <p><strong>This link expires in 24 hours.</strong></p>
            <p>If you did not request this change, please ignore this email or contact support.</p>
            ";

            applyEmailTemplate($mail, $html);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Confirmation email failed: " . $e->getMessage());
            return false;
        }
    }

    private static function notifyUserChangeRequest($userId, $userName, $changeType)
    {
        if (!self::$conn) return;

        $adminIdentifier = $_SESSION['username'] ?? 'Administrator';
        $message = "An administrator initiated a {$changeType} change for your account. Check your email to confirm this change.";

        $query = "INSERT INTO user_notifications 
                  (user_id, notification_type, title, message, admin_identifier, sent_via_email) 
                  VALUES (?, 'SENSITIVE_CHANGE_REQUEST', 'Account Change Request', ?, ?, 1)";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('sss', $userId, $message, $adminIdentifier);
        $stmt->execute();
    }

    private static function notifyChangeCompleted($userId, $userName, $changeType)
    {
        if (!self::$conn) return;

        $message = "Your {$changeType} was successfully updated.";

        $query = "INSERT INTO user_notifications 
                  (user_id, notification_type, title, message, admin_identifier, sent_via_email) 
                  VALUES (?, 'SENSITIVE_CHANGE_COMPLETED', 'Account Updated', ?, 'System', 0)";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('ss', $userId, $message);
        $stmt->execute();
    }

    private static function getClientIp()
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }

    private static function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/fit-brawl';
    }
}
