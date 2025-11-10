<?php
require_once __DIR__ . '/../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/session_manager.php';

// Initialize session
SessionManager::initialize();

// Set JSON header
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        throw new Exception('Invalid request data');
    }

    $message = isset($data['message']) ? trim($data['message']) : '';

    // Validate message
    if (empty($message)) {
        throw new Exception('Message cannot be empty');
    }

    if (strlen($message) > 1000) {
        throw new Exception('Message exceeds maximum length of 1000 characters');
    }

    // Check if user is logged in
    $isLoggedIn = isset($_SESSION['user_id']);

    if ($isLoggedIn) {
        // Logged-in user submission
        $user_id = $_SESSION['user_id'];

        // Get user info from database
        $stmt = $conn->prepare("SELECT username, avatar FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $username = $row['username'];
            $email = $_SESSION['email'] ?? '';

            // Determine avatar
            if (empty($row['avatar']) || $row['avatar'] === 'default-avatar.png') {
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
        $name = isset($data['name']) ? trim($data['name']) : '';
        $email = isset($data['email']) ? trim($data['email']) : '';

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

        // Validate email format if provided
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        $avatar = "../../images/account-icon.svg";
    }

    // Sanitize inputs
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    // Insert feedback into database
    $sql = "INSERT INTO feedback (user_id, username, email, avatar, message, date, is_visible, helpful_count, not_helpful_count)
            VALUES (?, ?, ?, ?, ?, NOW(), 1, 0, 0)";

    $stmt = $conn->prepare($sql);

    if ($isLoggedIn) {
        $stmt->bind_param("issss", $user_id, $username, $email, $avatar, $message);
    } else {
        $null_user_id = null;
        $stmt->bind_param("issss", $null_user_id, $username, $email, $avatar, $message);
    }

    if ($stmt->execute()) {
        $feedback_id = $stmt->insert_id;

        echo json_encode([
            'status' => 'success',
            'message' => 'Thank you for your feedback!',
            'feedback_id' => $feedback_id
        ]);
    } else {
        throw new Exception('Failed to submit feedback: ' . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
