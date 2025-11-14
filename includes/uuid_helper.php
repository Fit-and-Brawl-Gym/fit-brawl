<?php
/**
 * UUID Helper Functions
 * Provides utilities for generating and validating UUIDs (v4)
 * 
 * @package FitBrawlGym
 * @version 2.0
 * @created 2025-11-13
 */

/**
 * Generate a UUID v4 (random)
 * 
 * @return string A properly formatted UUID v4 string
 */
function generateUUID() {
    // Generate 16 random bytes
    $data = random_bytes(16);
    
    // Set version to 0100 (UUID v4)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    
    // Set variant to 10xx
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    // Format as UUID string
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Validate a UUID string format
 * 
 * @param string $uuid The UUID string to validate
 * @return bool True if valid UUID format, false otherwise
 */
function isValidUUID($uuid) {
    if (!is_string($uuid)) {
        return false;
    }
    
    // UUID v4 pattern: 8-4-4-4-12 hex characters
    $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
    
    return preg_match($pattern, $uuid) === 1;
}

/**
 * Format a UUID string (normalize to lowercase with dashes)
 * 
 * @param string $uuid The UUID string to format
 * @return string|false Formatted UUID or false if invalid
 */
function formatUUID($uuid) {
    // Remove any whitespace
    $uuid = trim($uuid);
    
    // Remove dashes for validation
    $clean = str_replace('-', '', $uuid);
    
    // Must be 32 hex characters
    if (strlen($clean) !== 32 || !ctype_xdigit($clean)) {
        return false;
    }
    
    // Reformat with dashes
    $formatted = sprintf(
        '%s-%s-%s-%s-%s',
        substr($clean, 0, 8),
        substr($clean, 8, 4),
        substr($clean, 12, 4),
        substr($clean, 16, 4),
        substr($clean, 20, 12)
    );
    
    return strtolower($formatted);
}

/**
 * Generate a short UUID (22 characters, base64-encoded)
 * Useful for shorter identifiers while maintaining uniqueness
 * 
 * @return string A base64-encoded UUID (22 characters)
 */
function generateShortUUID() {
    $data = random_bytes(16);
    
    // Set version to 0100 (UUID v4)
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    
    // Set variant to 10xx
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    
    // Base64 encode and make URL-safe
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Bulk generate UUIDs
 * 
 * @param int $count Number of UUIDs to generate
 * @return array Array of UUID strings
 */
function generateUUIDs($count) {
    $uuids = [];
    for ($i = 0; $i < $count; $i++) {
        $uuids[] = generateUUID();
    }
    return $uuids;
}

/**
 * Convert UUID to binary format (for more efficient storage)
 * Note: Only use if you plan to store UUIDs as BINARY(16) instead of CHAR(36)
 * 
 * @param string $uuid The UUID string to convert
 * @return string|false Binary representation or false if invalid
 */
function uuidToBinary($uuid) {
    $formatted = formatUUID($uuid);
    if ($formatted === false) {
        return false;
    }
    
    // Remove dashes and convert hex to binary
    $hex = str_replace('-', '', $formatted);
    return hex2bin($hex);
}

/**
 * Convert binary UUID back to string format
 * 
 * @param string $binary The binary UUID data
 * @return string|false Formatted UUID string or false if invalid
 */
function binaryToUUID($binary) {
    if (strlen($binary) !== 16) {
        return false;
    }
    
    // Convert binary to hex
    $hex = bin2hex($binary);
    
    // Format with dashes
    return sprintf(
        '%s-%s-%s-%s-%s',
        substr($hex, 0, 8),
        substr($hex, 8, 4),
        substr($hex, 12, 4),
        substr($hex, 16, 4),
        substr($hex, 20, 12)
    );
}
