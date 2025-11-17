<?php
require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/session_manager.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../includes/input_validator.php';

// Initialize session
SessionManager::initialize();

ApiSecurityMiddleware::setSecurityHeaders();

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    exit; // Already sent response
}

try {
    // Get JSON body
    $data = ApiSecurityMiddleware::getJsonBody();

    // Require CSRF token (from JSON body)
    $csrfToken = $data['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        ApiSecurityMiddleware::sendJsonResponse([
            'status' => 'error',
            'message' => 'CSRF token validation failed'
        ], 403);
    }

    // Rate limiting - 10 feedback submissions per minute (per user if logged in, per IP if anonymous)
    $identifier = isset($_SESSION['user_id']) ? 'feedback:' . $_SESSION['user_id'] : 'feedback:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    ApiSecurityMiddleware::applyRateLimit($conn, $identifier, 10, 60);

    // Validate and sanitize input
    $validation = ApiSecurityMiddleware::validateInput([
        'message' => [
            'type' => 'string',
            'required' => true,
            'max_length' => 1000
        ],
        'name' => [
            'type' => 'string',
            'required' => false,
            'max_length' => 255
        ],
        'email' => [
            'type' => 'email',
            'required' => false
        ]
    ], $data);

    if (!$validation['valid']) {
        $errors = implode(', ', $validation['errors']);
        ApiSecurityMiddleware::sendJsonResponse([
            'status' => 'error',
            'message' => 'Validation failed: ' . $errors
        ], 400);
    }

    $validatedData = $validation['data'];
    $message = $validatedData['message'];

    // Check if user is logged in
    $isLoggedIn = isset($_SESSION['user_id']);

    if ($isLoggedIn) {
        // Logged-in user submission
        $user_id = $_SESSION['user_id'];

        // Get user info from database
        $stmt = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $username = $row['username'];
            $email = $_SESSION['email'] ?? '';

            // Determine avatar
            if (empty($row['avatar']) || $row['avatar'] === 'account-icon.svg') {
                $avatar = "../../images/account-icon.svg";
            } else {
                $avatar = $row['avatar'];
            }
        } else {
            throw new Exception('User not found');
        }

        $stmt->close();

    } else {
        // Non-logged in user submission
        $user_id = null;
        $name = $validatedData['name'] ?? '';
        $email = $validatedData['email'] ?? '';

        // Generate anonymous name if not provided
        if (empty($name)) {
            // Get anonymous count
            if (!isset($_SESSION['anonymous_index'])) {
                $_SESSION['anonymous_index'] = 1;
            }
            $username = "Anonymous " . $_SESSION['anonymous_index'];
            $_SESSION['anonymous_index']++;
        } else {
            $username = $name;
        }

        // Use anonymous email if not provided
        if (empty($email)) {
            $email = "anonymous@fitxbrawl.com";
        }

        $avatar = "../../images/account-icon.svg";
    }

    // Sanitize inputs (already validated, but double-check for XSS)
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    // Insert feedback into database
    $sql = "INSERT INTO feedback (user_id, username, email, avatar, message, date, is_visible, helpful_count, not_helpful_count)
            VALUES (?, ?, ?, ?, ?, NOW(), 1, 0, 0)";

    $stmt = $conn->prepare($sql);

    // Bind parameters - use "sssss" for all cases (user_id is now VARCHAR)
    // For anonymous users, $user_id will be NULL
    $stmt->bind_param("sssss", $user_id, $username, $email, $avatar, $message);

    if ($stmt->execute()) {
        $feedback_id = $stmt->insert_id;

        ApiSecurityMiddleware::sendJsonResponse([
            'status' => 'success',
            'message' => 'Thank you for your feedback!',
            'feedback_id' => $feedback_id
        ], 200);
    } else {
        error_log("Database error in submit_feedback.php: " . $stmt->error);
        throw new Exception('Failed to submit feedback');
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Error in submit_feedback.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'status' => 'error',
        'message' => $e->getMessage()
    ], 400);
}

$conn->close();
?>
