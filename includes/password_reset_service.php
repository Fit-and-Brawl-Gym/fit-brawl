<?php
/**
 * Password Reset Service
 * Implements secure password reset workflow WITHOUT exposing passwords to admins
 */

require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/enhanced_audit_logger.php';

class PasswordResetService
{
    private static $conn;

    public static function init($connection)
    {
        self::$conn = $connection;
    }

    /**
     * Trigger password reset workflow
     * Admin can trigger this, but password is set by USER only
     * 
     * @param string $userId Target user ID
     * @param bool $isAdminTriggered Whether admin triggered (true) or user self-service (false)
     * @return array Result with success/failure
     */
    public static function triggerPasswordReset($userId, $isAdminTriggered = false)
    {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Database connection not initialized'];
        }

        // Get user details
        $userQuery = "SELECT u.id, u.email, u.username, COALESCE(um.name, u.username) as name 
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

        if (empty($user['email'])) {
            return ['success' => false, 'message' => 'User has no email address'];
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32)); // 64 character token
        $hashedToken = password_hash($token, PASSWORD_DEFAULT);
        
        // Get IP and user agent
        $ipAddress = self::getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Get admin ID if admin-triggered
        $createdBy = $isAdminTriggered ? ($_SESSION['user_id'] ?? null) : null;

        // Insert reset token with MySQL NOW() + INTERVAL to avoid timezone issues
        $query = "INSERT INTO password_reset_tokens 
                  (user_id, token, expires_at, created_by, ip_address, user_agent) 
                  VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?, ?)";
        
        $stmt = self::$conn->prepare($query);
        
        if (!$stmt) {
            error_log("Failed to prepare password reset query: " . self::$conn->error);
            return ['success' => false, 'message' => 'Database error: failed to prepare statement'];
        }
        
        $stmt->bind_param('sssss', $userId, $hashedToken, $createdBy, $ipAddress, $userAgent);

        if (!$stmt->execute()) {
            error_log("Failed to insert password reset token: " . $stmt->error);
            return ['success' => false, 'message' => 'Failed to create reset token: ' . $stmt->error];
        }
        
        error_log("Password reset token created for user $userId");

        // Generate reset link (plain token, NOT hashed)
        $resetLink = self::getBaseUrl() . "/public/php/reset-password.php?token=" . urlencode($token);

        // Send email
        $emailSent = self::sendResetEmail($user['email'], $user['name'], $resetLink, $isAdminTriggered);

        if (!$emailSent) {
            error_log("Failed to send password reset email to {$user['email']}");
            return ['success' => false, 'message' => 'Failed to send reset email. Please check email configuration.'];
        }
        
        error_log("Password reset email sent successfully to {$user['email']}");

        // Log the action
        $adminName = $isAdminTriggered ? ($_SESSION['username'] ?? 'Admin') : 'Self-Service';
        EnhancedAuditLogger::log(
            'TRIGGER_PASSWORD_RESET',
            $userId,
            $user['name'],
            null,
            null,
            'high',
            $isAdminTriggered ? 'Admin triggered password reset' : 'User requested password reset'
        );

        // Send notification to user
        self::notifyUserPasswordReset($userId, $user['name'], $isAdminTriggered);

        return [
            'success' => true,
            'message' => 'Password reset link sent to user email',
            'email' => self::maskEmail($user['email'])
        ];
    }

    /**
     * Verify reset token and allow user to set new password
     * This is called when user clicks the reset link
     */
    public static function verifyResetToken($token)
    {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Database connection error'];
        }

        if (empty($token)) {
            return ['success' => false, 'message' => 'No token provided'];
        }

        error_log("Verifying token: " . substr($token, 0, 10) . "... (length: " . strlen($token) . ")");

        // Find valid unused tokens (check multiple in case of hash collision)
        $query = "SELECT prt.id as token_id, prt.user_id, prt.token, prt.expires_at, prt.created_at,
                         u.email, u.username, COALESCE(um.name, u.username) as name 
                  FROM password_reset_tokens prt
                  INNER JOIN users u ON prt.user_id = u.id
                  LEFT JOIN user_memberships um ON u.id = um.user_id
                  WHERE prt.expires_at > NOW() 
                  AND prt.used_at IS NULL
                  ORDER BY prt.created_at DESC 
                  LIMIT 20"; // Check recent tokens

        $result = self::$conn->query($query);

        if (!$result) {
            error_log("Password reset token query failed: " . self::$conn->error);
            return ['success' => false, 'message' => 'Database query error'];
        }

        $rowCount = $result->num_rows;
        error_log("Found $rowCount valid unused tokens in database");

        if ($rowCount === 0) {
            return ['success' => false, 'message' => 'No valid reset tokens found. The link may have expired.'];
        }

        $matchCount = 0;
        while ($row = $result->fetch_assoc()) {
            $matchCount++;
            error_log("Checking token $matchCount: expires at " . $row['expires_at']);
            
            if (password_verify($token, $row['token'])) {
                error_log("Token verified successfully for user: " . $row['user_id']);
                return [
                    'success' => true,
                    'user_id' => $row['user_id'],
                    'username' => $row['username'],
                    'name' => $row['name'],
                    'token_id' => $row['token_id']
                ];
            }
        }

        error_log("Token did not match any of the $matchCount tokens checked");
        return ['success' => false, 'message' => 'Invalid or expired reset token'];
    }

    /**
     * Complete password reset - User sets their own password
     * NEVER allow admin to see or set passwords
     */
    public static function completePasswordReset($token, $newPassword)
    {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Database connection error'];
        }

        // Verify token first
        $tokenData = self::verifyResetToken($token);

        if (!$tokenData['success']) {
            return $tokenData;
        }

        // Validate password strength
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        // Hash the new password (NEVER store plaintext)
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update user password
        $query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('ss', $hashedPassword, $tokenData['user_id']);

        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Failed to update password'];
        }

        // Mark token as used
        $markQuery = "UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?";
        $markStmt = self::$conn->prepare($markQuery);
        $markStmt->bind_param('i', $tokenData['token_id']);
        $markStmt->execute();

        // Don't log to admin_logs for user self-service password resets
        // This is a user action, not an admin action
        // The password_reset_tokens table already tracks this activity

        // Notify user
        self::notifyPasswordChanged($tokenData['user_id'], $tokenData['name']);

        return ['success' => true, 'message' => 'Password reset successfully'];
    }

    /**
     * Send password reset email
     */
    private static function sendResetEmail($email, $name, $resetLink, $isAdminTriggered)
    {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            configureMailerSMTP($mail);
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request - FitXBrawl';

            $triggeredBy = $isAdminTriggered ? 'an administrator' : 'you';

            $html = "
            <h2>Password Reset Request</h2>
            <p>Hello {$name},</p>
            <p>A password reset was requested for your FitXBrawl account by {$triggeredBy}.</p>
            <p>If you did not request this reset, please ignore this email or contact support immediately.</p>
            <p><strong>To reset your password, click the link below:</strong></p>
            <p><a href='{$resetLink}' style='display:inline-block;padding:10px 20px;background:#1a1d2e;color:white;text-decoration:none;border-radius:5px;'>Reset Password</a></p>
            <p>Or copy and paste this link into your browser:<br><code>{$resetLink}</code></p>
            <p><strong>This link expires in 1 hour.</strong></p>
            <p style='color:#666;font-size:12px;'>Date: " . date('Y-m-d H:i:s') . "<br>IP Address: " . self::getClientIp() . "</p>
            ";

            applyEmailTemplate($mail, $html);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Password reset email failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify user that password reset was triggered
     */
    private static function notifyUserPasswordReset($userId, $userName, $isAdminTriggered)
    {
        if (!self::$conn) return;

        $adminIdentifier = $isAdminTriggered ? ($_SESSION['username'] ?? 'Administrator') : 'Self-Service';
        $message = $isAdminTriggered 
            ? "A password reset was initiated for your account by an administrator. Check your email for the reset link."
            : "You requested a password reset. Check your email for the reset link.";

        $query = "INSERT INTO user_notifications 
                  (user_id, notification_type, title, message, admin_identifier, sent_via_email) 
                  VALUES (?, 'PASSWORD_RESET', 'Password Reset Initiated', ?, ?, 1)";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('sss', $userId, $message, $adminIdentifier);
        $stmt->execute();
    }

    /**
     * Notify user that password was changed
     */
    private static function notifyPasswordChanged($userId, $userName)
    {
        if (!self::$conn) return;

        $message = "Your password was successfully changed. If you did not make this change, please contact support immediately.";

        $query = "INSERT INTO user_notifications 
                  (user_id, notification_type, title, message, admin_identifier, sent_via_email) 
                  VALUES (?, 'PASSWORD_CHANGED', 'Password Changed', ?, 'System', 0)";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('ss', $userId, $message);
        $stmt->execute();

        // Also send email notification
        self::sendPasswordChangedEmail($userId);
    }

    /**
     * Send email notification that password was changed
     */
    private static function sendPasswordChangedEmail($userId)
    {
        // Get user email
        $query = "SELECT email, COALESCE(um.name, u.username) as name FROM users u LEFT JOIN user_memberships um ON u.id = um.user_id WHERE u.id = ?";
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user || empty($user['email'])) return;

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            configureMailerSMTP($mail);
            $mail->addAddress($user['email']);

            $mail->isHTML(true);
            $mail->Subject = 'Password Changed - FitXBrawl';

            $html = "
            <h2>Password Changed</h2>
            <p>Hello {$user['name']},</p>
            <p>Your FitXBrawl account password was successfully changed.</p>
            <p><strong>If you did not make this change, your account may be compromised.</strong></p>
            <p>Please contact support immediately if this was not you.</p>
            <p style='color:#666;font-size:12px;'>Date: " . date('Y-m-d H:i:s') . "<br>IP Address: " . self::getClientIp() . "</p>
            ";

            applyEmailTemplate($mail, $html);
            $mail->send();
        } catch (Exception $e) {
            error_log("Password changed notification failed: " . $e->getMessage());
        }
    }

    private static function getClientIp()
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }

    private static function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        
        $local = $parts[0];
        $domain = $parts[1];
        
        if (strlen($local) <= 2) {
            return $local[0] . '***@' . $domain;
        }
        
        return $local[0] . str_repeat('*', strlen($local) - 2) . $local[strlen($local) - 1] . '@' . $domain;
    }

    private static function getBaseUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/fit-brawl';
    }
}
