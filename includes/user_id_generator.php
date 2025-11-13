<?php
/**
 * User ID Generator - Formatted IDs
 * Generates human-readable IDs like MBR-25-0012, TRN-25-0003, ADM-25-0001
 * 
 * Format: {ROLE_PREFIX}-{YEAR}-{SEQUENCE}
 * - MBR = Member
 * - TRN = Trainer
 * - ADM = Admin
 * - Year = Last 2 digits of current year (25 for 2025)
 * - Sequence = 4-digit zero-padded counter
 * 
 * @package FitBrawlGym
 * @version 3.0
 * @created 2025-11-13
 */

/**
 * Get the role prefix for formatted ID
 * 
 * @param string $role User role (member, trainer, admin)
 * @return string Role prefix (MBR, TRN, ADM)
 */
function getRolePrefix($role) {
    $prefixes = [
        'member' => 'MBR',
        'trainer' => 'TRN',
        'admin' => 'ADM'
    ];
    
    return $prefixes[strtolower($role)] ?? 'MBR';
}

/**
 * Generate a formatted user ID
 * 
 * @param mysqli $conn Database connection
 * @param string $role User role (member, trainer, admin)
 * @return string Formatted ID like MBR-25-0012
 */
function generateFormattedUserId($conn, $role) {
    $prefix = getRolePrefix($role);
    $year = date('y'); // Last 2 digits of year (25 for 2025)
    
    // Get the next sequence number for this role and year
    $pattern = $prefix . '-' . $year . '-%';
    
    $sql = "SELECT id FROM users WHERE id LIKE ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Extract the sequence number from the last ID
        // Example: MBR-25-0012 -> extract 0012 -> increment to 0013
        $lastId = $row['id'];
        $parts = explode('-', $lastId);
        $lastSequence = isset($parts[2]) ? intval($parts[2]) : 0;
        $nextSequence = $lastSequence + 1;
    } else {
        // First ID for this role/year
        $nextSequence = 1;
    }
    
    // Format: PREFIX-YY-NNNN (4-digit zero-padded)
    return sprintf('%s-%s-%04d', $prefix, $year, $nextSequence);
}

/**
 * Validate a formatted user ID
 * 
 * @param string $userId The formatted user ID to validate
 * @return bool True if valid format, false otherwise
 */
function isValidFormattedUserId($userId) {
    if (!is_string($userId)) {
        return false;
    }
    
    // Pattern: {MBR|TRN|ADM}-{YY}-{NNNN}
    $pattern = '/^(MBR|TRN|ADM)-\d{2}-\d{4}$/';
    
    return preg_match($pattern, $userId) === 1;
}

/**
 * Extract role from formatted user ID
 * 
 * @param string $userId Formatted user ID like MBR-25-0012
 * @return string|false Role (member, trainer, admin) or false if invalid
 */
function getRoleFromUserId($userId) {
    if (!isValidFormattedUserId($userId)) {
        return false;
    }
    
    $parts = explode('-', $userId);
    $prefix = $parts[0];
    
    $roles = [
        'MBR' => 'member',
        'TRN' => 'trainer',
        'ADM' => 'admin'
    ];
    
    return $roles[$prefix] ?? false;
}

/**
 * Get year from formatted user ID
 * 
 * @param string $userId Formatted user ID like MBR-25-0012
 * @return string|false Year (25 for 2025) or false if invalid
 */
function getYearFromUserId($userId) {
    if (!isValidFormattedUserId($userId)) {
        return false;
    }
    
    $parts = explode('-', $userId);
    return $parts[1] ?? false;
}

/**
 * Get sequence number from formatted user ID
 * 
 * @param string $userId Formatted user ID like MBR-25-0012
 * @return int|false Sequence number (12 from 0012) or false if invalid
 */
function getSequenceFromUserId($userId) {
    if (!isValidFormattedUserId($userId)) {
        return false;
    }
    
    $parts = explode('-', $userId);
    return isset($parts[2]) ? intval($parts[2]) : false;
}

/**
 * Bulk generate formatted user IDs
 * 
 * @param mysqli $conn Database connection
 * @param string $role User role
 * @param int $count Number of IDs to generate
 * @return array Array of formatted user IDs
 */
function generateBulkFormattedUserIds($conn, $role, $count) {
    $ids = [];
    
    for ($i = 0; $i < $count; $i++) {
        $ids[] = generateFormattedUserId($conn, $role);
    }
    
    return $ids;
}
