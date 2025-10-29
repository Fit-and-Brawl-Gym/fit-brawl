<?php
// filepath: c:\xampp\htdocs\fit-brawl\includes\activity_logger.php
/**
 * Activity Logger - Automatically logs admin actions
 */

class ActivityLogger
{
    private static $conn;

    public static function init($connection) {
        self::$conn = $connection;
        if (!self::$conn) {
            error_log('ActivityLogger: Connection is NULL!');
        }
    }

    /**
     * Log an admin activity
     */
    public static function log($actionType, $targetUser = null, $targetId = null, $details = '')
    {
        // DEBUG: Log to PHP error log
        error_log("ActivityLogger::log called - Action: {$actionType}, User: {$targetUser}, Details: {$details}");
        
        if (!self::$conn) {
            error_log('ActivityLogger: Database connection not initialized');
            return false;
        }

        // Get admin info from session
        $adminId = $_SESSION['user_id'] ?? null;
        $adminName = $_SESSION['username'] ?? 'System';
        
        error_log("ActivityLogger: Admin ID: {$adminId}, Admin Name: {$adminName}");
        
        if (!$adminId) {
            error_log('ActivityLogger: No admin ID in session');
            return false; // Not logged in as admin
        }

        try {
            $sql = "INSERT INTO admin_logs (admin_id, admin_name, action_type, target_user, target_id, details, timestamp) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $stmt = self::$conn->prepare($sql);

            if (!$stmt) {
                error_log('ActivityLogger: Failed to prepare statement - ' . self::$conn->error);
                return false;
            }

            $stmt->bind_param("isssis", $adminId, $adminName, $actionType, $targetUser, $targetId, $details);
            $result = $stmt->execute();
            
            if ($result) {
                error_log('ActivityLogger: Successfully logged activity');
            } else {
                error_log('ActivityLogger: Execute failed - ' . $stmt->error);
            }
            
            $stmt->close();

            return $result;

        } catch (Exception $e) {
            error_log('ActivityLogger: Error - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent activities with optional filters
     */
    public static function getActivities($limit = 10, $actionType = null, $dateRange = null) {
        if (!self::$conn) {
            error_log('ActivityLogger::getActivities - Connection not initialized');
            return [];
        }

        $sql = "SELECT * FROM admin_logs WHERE 1=1";
        $params = [];
        $types = "";

        // Filter by action type
        if ($actionType && $actionType !== 'all') {
            $sql .= " AND action_type LIKE ?";
            $params[] = $actionType . '%';
            $types .= "s";
        }

        // Filter by date range
        if ($dateRange) {
            switch ($dateRange) {
                case 'today':
                    $sql .= " AND DATE(timestamp) = CURDATE()";
                    break;
                case 'week':
                    $sql .= " AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $sql .= " AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $sql .= " AND YEAR(timestamp) = YEAR(CURDATE())";
                    break;
            }
        }

        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";

        $stmt = self::$conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }

        $stmt->close();
        return $activities;
    }

    /**
     * Get activity icon and color
     */
    public static function getActivityIcon($actionType)
    {
        $icons = [
            'subscription_approved' => ['icon' => 'fa-check-circle', 'color' => '#0b8454'],
            'subscription_rejected' => ['icon' => 'fa-times-circle', 'color' => '#c0392b'],
            'equipment_add' => ['icon' => 'fa-plus-circle', 'color' => '#0066cc'],
            'equipment_edit' => ['icon' => 'fa-pen-to-square', 'color' => '#f39c12'],
            'equipment_delete' => ['icon' => 'fa-trash', 'color' => '#c0392b'],
            'product_add' => ['icon' => 'fa-box', 'color' => '#0066cc'],
            'product_edit' => ['icon' => 'fa-pen-to-square', 'color' => '#f39c12'],
            'product_delete' => ['icon' => 'fa-trash', 'color' => '#c0392b'],
            'member_activated' => ['icon' => 'fa-user-check', 'color' => '#0b8454'],
            'member_deactivated' => ['icon' => 'fa-user-slash', 'color' => '#95a5a6'],
            'plan_changed' => ['icon' => 'fa-exchange-alt', 'color' => '#9b59b6'],
            'bulk_delete' => ['icon' => 'fa-layer-group', 'color' => '#c0392b'],
        ];

        return $icons[$actionType] ?? ['icon' => 'fa-circle-info', 'color' => '#3498db'];
    }
}