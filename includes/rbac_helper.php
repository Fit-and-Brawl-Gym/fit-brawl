<?php
/**
 * RBAC Helper - Role-Based Access Control
 * Manages permissions for admin actions
 */

class RBACHelper
{
    private static $conn;

    // Define all available permissions
    const PERMISSIONS = [
        'VIEW_USERS' => 'View user list',
        'EDIT_USER_PROFILE' => 'Edit user basic information',
        'CHANGE_USER_ROLE' => 'Change user roles (requires security code)',
        'SUSPEND_USER' => 'Suspend/activate user accounts',
        'DELETE_USER' => 'Delete user accounts',
        'RESET_PASSWORD' => 'Trigger password reset for users',
        'VIEW_AUDIT_LOGS' => 'View audit logs',
        'MANAGE_PERMISSIONS' => 'Grant/revoke admin permissions',
        'INITIATE_SENSITIVE_CHANGE' => 'Initiate email/phone changes',
    ];

    public static function init($connection)
    {
        self::$conn = $connection;
    }

    /**
     * Check if admin has specific permission
     * 
     * @param string $adminId Admin user ID
     * @param string $permission Permission name
     * @return bool Has permission
     */
    public static function hasPermission($adminId, $permission)
    {
        if (!self::$conn) {
            return false;
        }

        // Super admins have all permissions (check if user has MANAGE_PERMISSIONS)
        $query = "SELECT COUNT(*) as count FROM admin_permissions 
                  WHERE admin_id = ? 
                  AND permission_name = 'MANAGE_PERMISSIONS' 
                  AND is_active = 1 
                  AND (expires_at IS NULL OR expires_at > NOW())";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('s', $adminId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return true; // Super admin
        }

        // Check specific permission
        $query = "SELECT COUNT(*) as count FROM admin_permissions 
                  WHERE admin_id = ? 
                  AND permission_name = ? 
                  AND is_active = 1 
                  AND (expires_at IS NULL OR expires_at > NOW())";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('ss', $adminId, $permission);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['count'] > 0;
    }

    /**
     * Grant permission to admin
     */
    public static function grantPermission($adminId, $permission, $grantedBy, $expiresAt = null)
    {
        if (!self::$conn) {
            return false;
        }

        // Check if granter has MANAGE_PERMISSIONS
        if (!self::hasPermission($grantedBy, 'MANAGE_PERMISSIONS')) {
            return ['success' => false, 'message' => 'Unauthorized: Cannot grant permissions'];
        }

        // Check if permission exists
        if (!isset(self::PERMISSIONS[$permission])) {
            return ['success' => false, 'message' => 'Invalid permission'];
        }

        $query = "INSERT INTO admin_permissions (admin_id, permission_name, granted_by, expires_at) 
                  VALUES (?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE is_active = 1, granted_by = ?, expires_at = ?, granted_at = NOW()";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('ssssss', $adminId, $permission, $grantedBy, $expiresAt, $grantedBy, $expiresAt);
        
        if ($stmt->execute()) {
            // Log the permission grant
            EnhancedAuditLogger::log(
                'GRANT_PERMISSION',
                $adminId,
                null,
                null,
                $permission,
                'high',
                "Granted permission: {$permission}"
            );

            return ['success' => true, 'message' => 'Permission granted'];
        }

        return ['success' => false, 'message' => 'Failed to grant permission'];
    }

    /**
     * Revoke permission from admin
     */
    public static function revokePermission($adminId, $permission, $revokedBy)
    {
        if (!self::$conn) {
            return false;
        }

        // Check if revoker has MANAGE_PERMISSIONS
        if (!self::hasPermission($revokedBy, 'MANAGE_PERMISSIONS')) {
            return ['success' => false, 'message' => 'Unauthorized: Cannot revoke permissions'];
        }

        $query = "UPDATE admin_permissions SET is_active = 0 
                  WHERE admin_id = ? AND permission_name = ?";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('ss', $adminId, $permission);
        
        if ($stmt->execute()) {
            // Log the permission revocation
            EnhancedAuditLogger::log(
                'REVOKE_PERMISSION',
                $adminId,
                null,
                $permission,
                null,
                'high',
                "Revoked permission: {$permission}"
            );

            return ['success' => true, 'message' => 'Permission revoked'];
        }

        return ['success' => false, 'message' => 'Failed to revoke permission'];
    }

    /**
     * Get all permissions for an admin
     */
    public static function getAdminPermissions($adminId)
    {
        if (!self::$conn) {
            return [];
        }

        $query = "SELECT * FROM admin_permissions 
                  WHERE admin_id = ? 
                  AND is_active = 1 
                  AND (expires_at IS NULL OR expires_at > NOW())
                  ORDER BY granted_at DESC";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('s', $adminId);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Generate secure verification code for sensitive operations
     * (e.g., role changes)
     */
    public static function generateSecurityCode($adminId, $purpose, $validMinutes = 5)
    {
        if (!self::$conn) {
            return false;
        }

        // Generate random code
        $code = bin2hex(random_bytes(4)); // 8 character code
        $hashedCode = password_hash($code, PASSWORD_DEFAULT);
        
        $validUntil = date('Y-m-d H:i:s', time() + ($validMinutes * 60));

        $query = "INSERT INTO security_verification_codes (code, purpose, admin_id, valid_until) 
                  VALUES (?, ?, ?, ?)";
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('ssss', $hashedCode, $purpose, $adminId, $validUntil);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'code' => $code, // Plain code to show admin
                'valid_until' => $validUntil
            ];
        }

        return ['success' => false, 'message' => 'Failed to generate code'];
    }

    /**
     * Verify security code
     */
    public static function verifySecurityCode($code, $purpose, $adminId)
    {
        if (!self::$conn) {
            return false;
        }

        // Find valid unused codes
        $query = "SELECT id, code FROM security_verification_codes 
                  WHERE purpose = ? 
                  AND admin_id = ? 
                  AND valid_until > NOW() 
                  AND used_at IS NULL
                  ORDER BY created_at DESC 
                  LIMIT 5"; // Check last 5 codes
        
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param('ss', $purpose, $adminId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (password_verify($code, $row['code'])) {
                // Mark as used
                $updateQuery = "UPDATE security_verification_codes SET used_at = NOW() WHERE id = ?";
                $updateStmt = self::$conn->prepare($updateQuery);
                $updateStmt->bind_param('i', $row['id']);
                $updateStmt->execute();

                return true;
            }
        }

        return false;
    }
}
