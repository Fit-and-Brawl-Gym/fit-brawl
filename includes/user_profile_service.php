<?php
/**
 * User Profile Service
 * Handles user profile updates with field restrictions
 * Admins can ONLY edit: name, contact_number, role, account_status
 * CANNOT edit: password, email (requires sensitive change), security settings
 */

require_once __DIR__ . '/enhanced_audit_logger.php';
require_once __DIR__ . '/rbac_helper.php';
require_once __DIR__ . '/mail_config.php';

class UserProfileService
{
    private static $conn;

    // Fields admins are allowed to edit directly
    private static $EDITABLE_FIELDS = [
        'name',
        'contact_number',
        'account_status' // active, suspended, locked
    ];

    // Fields that require special handling
    private static $RESTRICTED_FIELDS = [
        'role' // Requires CHANGE_USER_ROLE permission + security code
    ];

    // Fields that are NEVER editable by admin
    private static $FORBIDDEN_FIELDS = [
        'password',
        'email', // Must use SensitiveChangeService
        'phone', // Must use SensitiveChangeService
        'security_question',
        'security_answer',
        'mfa_secret',
        'mfa_enabled'
    ];

    public static function init($connection)
    {
        self::$conn = $connection;
    }

    /**
     * Update user profile
     * Enforces field-level permissions and RBAC
     * 
     * @param string $userId User ID to update
     * @param array $updates Associative array of field => value
     * @param string $adminId Admin making the change
     * @return array Result
     */
    public static function updateUserProfile($userId, $updates, $adminId)
    {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Database connection error'];
        }

        // Get user
        $userQuery = "SELECT u.*, COALESCE(um.name, u.username) as full_name 
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

        $allowedUpdates = [];
        $deniedFields = [];
        $roleChange = false;
        $securityCode = null;

        // Validate each field
        foreach ($updates as $field => $value) {
            // Check forbidden fields
            if (in_array($field, self::$FORBIDDEN_FIELDS)) {
                $deniedFields[] = $field;
                continue;
            }

            // Handle role change (special permission required)
            if ($field === 'role') {
                if (!RBACHelper::hasPermission($adminId, 'CHANGE_USER_ROLE')) {
                    return [
                        'success' => false,
                        'message' => 'You do not have permission to change user roles',
                        'requires_permission' => 'CHANGE_USER_ROLE'
                    ];
                }

                // Security code required
                if (empty($updates['security_code'])) {
                    return [
                        'success' => false,
                        'message' => 'Security code required for role changes',
                        'requires_security_code' => true
                    ];
                }

                $securityCode = $updates['security_code'];
                $roleChange = true;
                $allowedUpdates['role'] = $value;
                continue;
            }

            // Handle editable fields
            if (in_array($field, self::$EDITABLE_FIELDS)) {
                // Check specific permissions
                if ($field === 'account_status' && !RBACHelper::hasPermission($adminId, 'SUSPEND_USER')) {
                    return [
                        'success' => false,
                        'message' => 'You do not have permission to change account status',
                        'requires_permission' => 'SUSPEND_USER'
                    ];
                }

                if ($field === 'name' || $field === 'contact_number') {
                    if (!RBACHelper::hasPermission($adminId, 'EDIT_USER_PROFILE')) {
                        return [
                            'success' => false,
                            'message' => 'You do not have permission to edit user profiles',
                            'requires_permission' => 'EDIT_USER_PROFILE'
                        ];
                    }
                }

                $allowedUpdates[$field] = $value;
            } else {
                $deniedFields[] = $field;
            }
        }

        // If role change, verify security code
        if ($roleChange) {
            $codeValid = RBACHelper::verifySecurityCode($securityCode, 'CHANGE_USER_ROLE', $adminId);
            if (!$codeValid) {
                return [
                    'success' => false,
                    'message' => 'Invalid or expired security code',
                    'requires_security_code' => true
                ];
            }
        }

        if (empty($allowedUpdates)) {
            return [
                'success' => false,
                'message' => 'No valid updates provided',
                'denied_fields' => $deniedFields
            ];
        }

        // Apply updates
        $success = self::applyUpdates($userId, $user, $allowedUpdates, $adminId);

        if (!$success) {
            return ['success' => false, 'message' => 'Failed to update user profile'];
        }

        return [
            'success' => true,
            'message' => 'User profile updated successfully',
            'updated_fields' => array_keys($allowedUpdates),
            'denied_fields' => $deniedFields
        ];
    }

    /**
     * Apply allowed updates to user record
     */
    private static function applyUpdates($userId, $user, $updates, $adminId)
    {
        $userUpdates = [];
        $membershipUpdates = [];

        foreach ($updates as $field => $value) {
            switch ($field) {
                case 'name':
                    $membershipUpdates['name'] = $value;
                    break;
                case 'contact_number':
                    $userUpdates['contact_number'] = $value;
                    break;
                case 'account_status':
                    $userUpdates['account_status'] = $value;
                    break;
                case 'role':
                    $userUpdates['role'] = $value;
                    break;
            }

            // Log each change
            $oldValue = $user[$field] ?? null;
            if ($field === 'name') {
                $oldValue = $user['full_name'];
            }

            $severity = ($field === 'role') ? 'critical' : (($field === 'account_status') ? 'high' : 'medium');

            EnhancedAuditLogger::log(
                'USER_PROFILE_UPDATE',
                $userId,
                $user['full_name'],
                $oldValue,
                $value,
                $severity,
                "Updated {$field} from '{$oldValue}' to '{$value}'"
            );
        }

        // Update users table
        if (!empty($userUpdates)) {
            $setParts = [];
            $params = [];
            $types = '';

            foreach ($userUpdates as $field => $value) {
                $setParts[] = "{$field} = ?";
                $params[] = $value;
                $types .= 's';
            }

            $params[] = $userId;
            $types .= 's';

            $query = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?";
            $stmt = self::$conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            
            if (!$stmt->execute()) {
                return false;
            }
        }

        // Update user_memberships table
        if (!empty($membershipUpdates)) {
            $setParts = [];
            $params = [];
            $types = '';

            foreach ($membershipUpdates as $field => $value) {
                $setParts[] = "{$field} = ?";
                $params[] = $value;
                $types .= 's';
            }

            $params[] = $userId;
            $types .= 's';

            $query = "UPDATE user_memberships SET " . implode(', ', $setParts) . " WHERE user_id = ?";
            $stmt = self::$conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute(); // OK if fails (no membership record)
        }

        // Send notification to user
        self::notifyUserProfileUpdate($userId, $user['full_name'], array_keys($updates));

        return true;
    }

    /**
     * Notify user of profile changes
     */
    private static function notifyUserProfileUpdate($userId, $userName, $fields)
    {
        if (!self::$conn) return;

        $adminIdentifier = $_SESSION['username'] ?? 'Administrator';
        $fieldsText = implode(', ', $fields);
        $timestamp = date('F j, Y g:i A');

        $message = "Your profile was updated by {$adminIdentifier} on {$timestamp}. Fields changed: {$fieldsText}";

        $query = "INSERT INTO user_notifications 
                  (user_id, notification_type, title, message, admin_identifier, sent_via_email) 
                  VALUES (?, 'PROFILE_UPDATE', 'Profile Updated', ?, ?, 1)";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('sss', $userId, $message, $adminIdentifier);
        $stmt->execute();

        // Send email
        self::sendProfileUpdateEmail($userId, $userName, $fields, $adminIdentifier, $timestamp);
    }

    /**
     * Send email notification
     */
    private static function sendProfileUpdateEmail($userId, $userName, $fields, $adminIdentifier, $timestamp)
    {
        // Get user email
        $query = "SELECT email FROM users WHERE id = ?";
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('s', $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if (!$result || !$result['email']) return;

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = getenv('EMAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('EMAIL_USER');
            $mail->Password = getenv('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = getenv('EMAIL_PORT');

            $mail->setFrom(getenv('EMAIL_USER'), 'FitXBrawl Account');
            $mail->addAddress($result['email']);

            $mail->isHTML(true);
            $mail->Subject = 'Your Profile Was Updated - FitXBrawl';

            $fieldsText = implode(', ', array_map('ucfirst', $fields));

            $html = "
            <h2>Profile Updated</h2>
            <p>Hello {$userName},</p>
            <p>Your profile was updated by an administrator.</p>
            <p><strong>Date/Time:</strong> {$timestamp}</p>
            <p><strong>Administrator:</strong> {$adminIdentifier}</p>
            <p><strong>Fields Updated:</strong> {$fieldsText}</p>
            <p>If you have concerns about this change, please contact support.</p>
            ";

            applyEmailTemplate($mail, $html);
            $mail->send();
        } catch (Exception $e) {
            error_log("Profile update email failed: " . $e->getMessage());
        }
    }

    /**
     * Get list of editable fields for current admin
     */
    public static function getEditableFields($adminId)
    {
        $fields = [];

        if (RBACHelper::hasPermission($adminId, 'EDIT_USER_PROFILE')) {
            $fields = array_merge($fields, ['name', 'contact_number']);
        }

        if (RBACHelper::hasPermission($adminId, 'SUSPEND_USER')) {
            $fields[] = 'account_status';
        }

        if (RBACHelper::hasPermission($adminId, 'CHANGE_USER_ROLE')) {
            $fields[] = 'role';
        }

        return [
            'editable' => $fields,
            'restricted' => ['email', 'phone'], // Require SensitiveChangeService
            'forbidden' => self::$FORBIDDEN_FIELDS
        ];
    }
}
