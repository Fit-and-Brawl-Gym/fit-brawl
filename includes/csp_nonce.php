<?php
/**
 * CSP Nonce Generator
 * Generates and manages Content Security Policy nonces for inline scripts and styles
 *
 * Usage:
 * 1. Call CSPNonce::generate() at the start of your page
 * 2. Use CSPNonce::getScriptNonce() in script tags: <script nonce="<?= CSPNonce::getScriptNonce() ?>">
 * 3. Use CSPNonce::getStyleNonce() in style tags: <style nonce="<?= CSPNonce::getStyleNonce() ?>">
 */
class CSPNonce {
    private static $scriptNonce = null;
    private static $styleNonce = null;
    private static $generated = false;

    /**
     * Generate nonces for the current request
     * Should be called once at the start of page rendering
     */
    public static function generate() {
        if (self::$generated) {
            return;
        }

        self::$scriptNonce = base64_encode(random_bytes(16));
        self::$styleNonce = base64_encode(random_bytes(16));
        self::$generated = true;
    }

    /**
     * Get the script nonce for the current request
     * @return string The nonce value
     */
    public static function getScriptNonce() {
        if (!self::$generated) {
            self::generate();
        }
        return self::$scriptNonce;
    }

    /**
     * Get the style nonce for the current request
     * @return string The nonce value
     */
    public static function getStyleNonce() {
        if (!self::$generated) {
            self::generate();
        }
        return self::$styleNonce;
    }

    /**
     * Get nonce attribute for script tags
     * @return string The complete nonce attribute
     */
    public static function getScriptNonceAttr() {
        return 'nonce="' . htmlspecialchars(self::getScriptNonce(), ENT_QUOTES, 'UTF-8') . '"';
    }

    /**
     * Get nonce attribute for style tags
     * @return string The complete nonce attribute
     */
    public static function getStyleNonceAttr() {
        return 'nonce="' . htmlspecialchars(self::getStyleNonce(), ENT_QUOTES, 'UTF-8') . '"';
    }

    /**
     * Apply CSP headers with nonces
     * Should be called after nonces are generated
     */
    public static function applyCSPHeaders() {
        if (!self::$generated) {
            self::generate();
        }

        if (headers_sent()) {
            return false;
        }

        // Build CSP with nonces instead of unsafe-inline
        $cspDirectives = [
            "default-src 'self'",
            "img-src 'self' data: blob: https://*",
            "script-src 'self' 'nonce-" . self::$scriptNonce . "' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "style-src 'self' 'nonce-" . self::$styleNonce . "' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
            "connect-src 'self'",
            "frame-ancestors 'none'"
        ];

        header('Content-Security-Policy: ' . implode('; ', $cspDirectives));
        return true;
    }

    /**
     * Reset nonces (useful for testing)
     */
    public static function reset() {
        self::$scriptNonce = null;
        self::$styleNonce = null;
        self::$generated = false;
    }
}
