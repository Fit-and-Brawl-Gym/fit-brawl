<?php
/**
 * CSRF Protection Module
 * Provides CSRF token generation and validation for forms
 */
class CSRFProtection {
    const TOKEN_NAME = 'csrf_token';
    const TOKEN_EXPIRY = 3600; // 1 hour

    /**
     * Generate a new CSRF token
     */
    public static function generateToken() {
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
            $_SESSION[self::TOKEN_NAME . '_time'] = time();
        }
        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * Validate a CSRF token
     */
    public static function validateToken($token) {
        // Check if token exists in session
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            return false;
        }

        // Check if token has expired
        if (isset($_SESSION[self::TOKEN_NAME . '_time'])) {
            if (time() - $_SESSION[self::TOKEN_NAME . '_time'] > self::TOKEN_EXPIRY) {
                self::destroyToken();
                return false;
            }
        }

        // Validate token
        if (!hash_equals($_SESSION[self::TOKEN_NAME], $token)) {
            return false;
        }

        return true;
    }

    /**
     * Destroy the CSRF token after successful use
     */
    public static function destroyToken() {
        unset($_SESSION[self::TOKEN_NAME]);
        unset($_SESSION[self::TOKEN_NAME . '_time']);
    }

    /**
     * Get token for form output
     */
    public static function getTokenField() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }
}