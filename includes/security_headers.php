<?php
/**
 * Global security headers for all HTTP responses.
 * Applies once per request to avoid duplicate header warnings.
 */
if (!function_exists('applyGlobalSecurityHeaders')) {
    function applyGlobalSecurityHeaders(): void
    {
        static $applied = false;

        if ($applied || headers_sent()) {
            return;
        }

        $applied = true;

        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header('Cross-Origin-Opener-Policy: same-origin');

        // Mild CSP that still allows current CDN usage. Adjust as integrations evolve.
        $cspDirectives = [
            "default-src 'self'",
            "img-src 'self' data: blob: https://*",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
            "connect-src 'self'",
            "frame-ancestors 'none'"
        ];
        header('Content-Security-Policy: ' . implode('; ', $cspDirectives));

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
}

applyGlobalSecurityHeaders();
