<?php
/**
 * API Security Middleware
 * Provides consistent security checks for all API endpoints
 */
class ApiSecurityMiddleware {
    /**
     * Require authentication (logged-in user)
     *
     * @param array $options Options ['role' => 'admin|trainer|member', 'return_json' => bool]
     * @return array|false Returns user data if authenticated, false otherwise
     */
    public static function requireAuth($options = []) {
        if (!isset($_SESSION['user_id'])) {
            self::logSecurityEvent('unauthorized_access', 'high', ['reason' => 'not_authenticated']);
            self::sendUnauthorized($options['return_json'] ?? true);
            return false;
        }

        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'] ?? 'member';

        // Check role if specified
        if (isset($options['role'])) {
            $allowedRoles = is_array($options['role']) ? $options['role'] : [$options['role']];
            if (!in_array($role, $allowedRoles, true)) {
                self::logSecurityEvent('unauthorized_access', 'high', [
                    'reason' => 'insufficient_permissions',
                    'required_role' => $options['role'],
                    'user_role' => $role
                ]);
                self::sendForbidden($options['return_json'] ?? true);
                return false;
            }
        }

        return [
            'user_id' => $userId,
            'role' => $role,
            'email' => $_SESSION['email'] ?? null,
            'username' => $_SESSION['username'] ?? null
        ];
    }

    /**
     * Require CSRF token validation
     *
     * @param bool $returnJson Whether to return JSON response
     * @return bool True if valid, false otherwise (already sent response)
     */
    public static function requireCSRF($returnJson = true) {
        require_once __DIR__ . '/csrf_protection.php';

        $csrfToken = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');

        // Try to get from JSON body if POST with JSON
        if (empty($csrfToken) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $csrfToken = $input['csrf_token'] ?? '';
        }

        if (!CSRFProtection::validateToken($csrfToken)) {
            // Log CSRF failure
            self::logSecurityEvent('csrf_failure', 'high');
            self::sendCSRFFailed($returnJson);
            return false;
        }

        return true;
    }

    /**
     * Require specific HTTP method
     *
     * @param string|array $methods Allowed methods ('GET', 'POST', etc.)
     * @param bool $returnJson Whether to return JSON response
     * @return bool True if valid, false otherwise
     */
    public static function requireMethod($methods, $returnJson = true) {
        $allowedMethods = is_array($methods) ? $methods : [$methods];
        $currentMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!in_array($currentMethod, $allowedMethods, true)) {
            self::sendMethodNotAllowed($allowedMethods, $returnJson);
            return false;
        }

        return true;
    }

    /**
     * Validate and sanitize input using InputValidator
     *
     * @param array $rules Validation rules (same format as InputValidator::validateArray)
     * @param array $data Input data (defaults to $_POST or $_GET based on method)
     * @return array ['valid' => bool, 'data' => array, 'errors' => array]
     */
    public static function validateInput($rules, $data = null) {
        require_once __DIR__ . '/input_validator.php';

        if ($data === null) {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $data = strtoupper($method) === 'POST' ? $_POST : $_GET;
        }

        return InputValidator::validateArray($data, $rules);
    }

    /**
     * Check resource ownership (user can only access their own resources)
     *
     * @param mysqli $conn Database connection
     * @param string $table Table name
     * @param int|string $resourceId Resource ID to check
     * @param int|string $userId User ID to verify ownership
     * @param string $idColumn Column name for resource ID (default: 'id')
     * @param string $userColumn Column name for user ID (default: 'user_id')
     * @return bool True if user owns the resource
     */
    public static function checkResourceOwnership($conn, $table, $resourceId, $userId, $idColumn = 'id', $userColumn = 'user_id') {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `{$table}` WHERE `{$idColumn}` = ? AND `{$userColumn}` = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("ss", $resourceId, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Require resource ownership (user can only access their own resources)
     *
     * @param mysqli $conn Database connection
     * @param string $table Table name
     * @param int|string $resourceId Resource ID to check
     * @param int|string $userId User ID to verify ownership
     * @param string $idColumn Column name for resource ID
     * @param string $userColumn Column name for user ID
     * @param bool $returnJson Whether to return JSON response
     * @return bool True if user owns the resource, false otherwise
     */
    public static function requireResourceOwnership($conn, $table, $resourceId, $userId, $idColumn = 'id', $userColumn = 'user_id', $returnJson = true) {
        if (!self::checkResourceOwnership($conn, $table, $resourceId, $userId, $idColumn, $userColumn)) {
            self::sendForbidden($returnJson, 'You do not have permission to access this resource');
            return false;
        }

        return true;
    }

    /**
     * Apply rate limiting
     *
     * @param mysqli $conn Database connection
     * @param string $identifier Rate limit identifier
     * @param int $maxRequests Maximum requests allowed
     * @param int $windowSeconds Time window in seconds
     * @param bool $returnJson Whether to return JSON response
     * @return array|false Rate limit result or false if blocked
     */
    public static function applyRateLimit($conn, $identifier, $maxRequests, $windowSeconds, $returnJson = true) {
        require_once __DIR__ . '/api_rate_limiter.php';

        $rateCheck = ApiRateLimiter::checkAndIncrement($conn, $identifier, $maxRequests, $windowSeconds);

        // Set rate limit headers
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . $rateCheck['remaining']);
        header('X-RateLimit-Reset: ' . (time() + $rateCheck['retry_after']));

        if ($rateCheck['blocked']) {
            // Log rate limit violation
            self::logSecurityEvent('rate_limit_exceeded', 'medium', [
                'identifier' => $identifier,
                'max_requests' => $maxRequests,
                'window_seconds' => $windowSeconds
            ]);

            http_response_code(429);
            header('Retry-After: ' . $rateCheck['retry_after']);

            if ($returnJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $rateCheck['retry_after']
                ]);
            } else {
                echo 'Too many requests. Please try again later.';
            }
            exit;
        }

        return $rateCheck;
    }

    /**
     * Send unauthorized response
     */
    private static function sendUnauthorized($json = true) {
        http_response_code(401);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        } else {
            echo 'Unauthorized. Please log in.';
        }
        exit;
    }

    /**
     * Send forbidden response
     */
    private static function sendForbidden($json = true, $message = 'Forbidden') {
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            echo $message;
        }
        exit;
    }

    /**
     * Send CSRF validation failed response
     */
    private static function sendCSRFFailed($json = true) {
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
        } else {
            echo 'CSRF token validation failed';
        }
        exit;
    }

    /**
     * Send method not allowed response
     */
    private static function sendMethodNotAllowed($allowedMethods, $json = true) {
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowedMethods));
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed. Allowed methods: ' . implode(', ', $allowedMethods)
            ]);
        } else {
            echo 'Method not allowed. Allowed methods: ' . implode(', ', $allowedMethods);
        }
        exit;
    }

    /**
     * Log a security event
     *
     * @param string $eventType Event type
     * @param string $severity Severity level
     * @param array $context Additional context
     */
    private static function logSecurityEvent($eventType, $severity = 'medium', $context = []) {
        // Try to initialize security event logger if available
        if (file_exists(__DIR__ . '/security_event_logger.php')) {
            require_once __DIR__ . '/security_event_logger.php';

            // Try to get database connection from global scope or session
            global $conn;
            if (isset($conn) && $conn instanceof mysqli) {
                SecurityEventLogger::init($conn);
                $context['endpoint'] = $_SERVER['REQUEST_URI'] ?? null;
                SecurityEventLogger::log($eventType, $severity, $context);
            } else {
                // Fallback to error_log
                error_log("Security Event [{$severity}]: {$eventType} - " . json_encode($context));
            }
        } else {
            // Fallback to error_log
            error_log("Security Event [{$severity}]: {$eventType} - " . json_encode($context));
        }
    }

    /**
     * Set standard security headers for API responses
     */
    public static function setSecurityHeaders() {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
    }

    /**
     * Validate and sanitize JSON request body
     *
     * @param int $maxSize Maximum JSON size in bytes (default: 1MB)
     * @return array Parsed JSON data or false on error
     */
    public static function getJsonBody($maxSize = 1048576) {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (strtoupper($method) !== 'POST' && strtoupper($method) !== 'PUT' && strtoupper($method) !== 'PATCH') {
            return [];
        }

        $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        if ($contentLength > $maxSize) {
            http_response_code(413);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Request body too large']);
            exit;
        }

        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
            exit;
        }

        return $data ?? [];
    }

    /**
     * Send safe JSON response (ensures proper encoding)
     *
     * @param mixed $data Data to encode
     * @param int $statusCode HTTP status code
     */
    public static function sendJsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        // Ensure all string values in data are safe
        $safeData = self::sanitizeForJson($data);

        echo json_encode($safeData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }

    /**
     * Recursively sanitize data for JSON output (prevent JSON injection)
     *
     * @param mixed $data Data to sanitize
     * @return mixed Sanitized data
     */
    private static function sanitizeForJson($data) {
        if (is_string($data)) {
            // Already safe in JSON encoding, but ensure no control characters that could break JSON
            return $data;
        } elseif (is_array($data)) {
            return array_map([self::class, 'sanitizeForJson'], $data);
        } elseif (is_object($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $result[$key] = self::sanitizeForJson($value);
            }
            return $result;
        }

        return $data;
    }
}
