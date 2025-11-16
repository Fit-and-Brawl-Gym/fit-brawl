<?php
/**
 * Open Redirect Prevention Helper
 * Validates redirect URLs to prevent open redirect vulnerabilities
 */
class RedirectValidator {
    /**
     * Allowed redirect domains (whitelist)
     * Only redirects to these domains are allowed
     */
    private static $allowedDomains = [];

    /**
     * Allowed paths for same-origin redirects
     * These paths are considered safe for same-domain redirects
     */
    private static $allowedPaths = [
        '/',
        '/index.php',
        '/public/php/',
        '/public/php/login.php',
        '/public/php/sign-up.php',
        '/public/php/loggedin-index.php',
        '/public/php/admin/',
        '/public/php/admin/admin.php',
        '/public/php/trainer/',
        '/public/php/trainer/schedule.php',
        '/public/php/user_profile.php',
        '/public/php/membership.php',
        '/public/php/feedback.php',
        '/public/php/reservations.php',
        '/public/php/equipment.php',
        '/public/php/products.php',
    ];

    /**
     * Initialize allowed domains from environment or config
     */
    public static function init($domains = []) {
        if (!empty($domains)) {
            self::$allowedDomains = $domains;
        } else {
            // Default: only allow same domain
            $currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
            self::$allowedDomains = [$currentDomain];
        }
    }

    /**
     * Validate and sanitize a redirect URL
     *
     * @param string $url The redirect URL to validate
     * @param string $defaultUrl Default URL if validation fails
     * @return string Validated URL or default URL
     */
    public static function validate($url, $defaultUrl = '/') {
        if (empty($url)) {
            return $defaultUrl;
        }

        // Decode URL encoding
        $url = urldecode($url);

        // Remove leading/trailing whitespace
        $url = trim($url);

        // Check if URL is relative (starts with /)
        if (substr($url, 0, 1) === '/') {
            // Relative URL - check if path is allowed
            return self::validateRelativePath($url, $defaultUrl);
        }

        // Check if URL is protocol-relative (starts with //)
        if (substr($url, 0, 2) === '//') {
            // Protocol-relative URLs are dangerous - reject
            return $defaultUrl;
        }

        // Parse full URL
        $parsed = parse_url($url);

        if ($parsed === false) {
            // Invalid URL format
            return $defaultUrl;
        }

        // Extract host
        $host = $parsed['host'] ?? null;

        if ($host === null) {
            // No host means relative URL - validate path
            $path = $parsed['path'] ?? '/';
            return self::validateRelativePath($path, $defaultUrl);
        }

        // Validate host is in allowed domains
        if (!in_array($host, self::$allowedDomains, true)) {
            // Domain not in whitelist - reject
            return $defaultUrl;
        }

        // Validate scheme (only http/https allowed)
        $scheme = $parsed['scheme'] ?? 'http';
        if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
            // Invalid scheme (e.g., javascript:, data:) - reject
            return $defaultUrl;
        }

        // Reconstruct safe URL
        $safeUrl = $scheme . '://' . $host;
        if (isset($parsed['path'])) {
            $safeUrl .= $parsed['path'];
        }
        if (isset($parsed['query'])) {
            $safeUrl .= '?' . $parsed['query'];
        }
        if (isset($parsed['fragment'])) {
            $safeUrl .= '#' . $parsed['fragment'];
        }

        return $safeUrl;
    }

    /**
     * Validate a relative path
     *
     * @param string $path The path to validate
     * @param string $defaultUrl Default URL if validation fails
     * @return string Validated path or default URL
     */
    private static function validateRelativePath($path, $defaultUrl) {
        // Remove query string and fragment for path validation
        $path = strtok($path, '?#');

        // Check for path traversal attempts
        if (strpos($path, '..') !== false || strpos($path, './') !== false) {
            // Path traversal attempt - reject
            return $defaultUrl;
        }

        // Check if path starts with any allowed path
        foreach (self::$allowedPaths as $allowedPath) {
            if ($path === $allowedPath || strpos($path, $allowedPath) === 0) {
                return $path;
            }
        }

        // Check if path is a simple relative path (no directory traversal)
        // Allow paths like /some-page.php, /admin/page.php, etc.
        if (preg_match('#^/[a-zA-Z0-9_/-]+\.php#', $path) ||
            preg_match('#^/[a-zA-Z0-9_/-]+/?$#', $path)) {
            // Simple relative path - allow
            return $path;
        }

        // Path not in whitelist - use default
        return $defaultUrl;
    }

    /**
     * Safely redirect to a URL
     * Validates the URL and redirects if safe, otherwise redirects to default
     *
     * @param string $url The redirect URL
     * @param string $defaultUrl Default URL if validation fails
     * @param int $code HTTP status code for redirect (default 302)
     */
    public static function redirect($url, $defaultUrl = '/', $code = 302) {
        $safeUrl = self::validate($url, $defaultUrl);
        header("Location: $safeUrl", true, $code);
        exit;
    }

    /**
     * Check if a URL is safe for redirect
     *
     * @param string $url The URL to check
     * @return bool True if safe, false otherwise
     */
    public static function isSafe($url) {
        $validated = self::validate($url, null);
        return $validated !== null && $validated === $url;
    }
}
