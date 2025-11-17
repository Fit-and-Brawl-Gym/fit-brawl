<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once __DIR__ . '/../../../includes/api_security_middleware.php';
require_once __DIR__ . '/../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../includes/api_rate_limiter.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Require authentication
$user = ApiSecurityMiddleware::requireAuth();
if (!$user) {
    exit; // Already sent response
}

$user_id = $user['user_id'];

// Require POST method
if (!ApiSecurityMiddleware::requireMethod('POST')) {
    exit; // Already sent response
}

// Get JSON body
$data = ApiSecurityMiddleware::getJsonBody();

// Require CSRF token (from JSON body)
$csrfToken = $data['csrf_token'] ?? '';
if (!CSRFProtection::validateToken($csrfToken)) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'CSRF token validation failed'
    ], 403);
}

// Rate limiting - 20 votes per minute per user
ApiSecurityMiddleware::applyRateLimit($conn, 'feedback_vote:' . $user_id, 20, 60);

// Validate and sanitize input
$feedback_id = isset($data['feedback_id']) ? intval($data['feedback_id']) : 0;
$vote_type = isset($data['vote_type']) ? trim($data['vote_type']) : '';

// Validate inputs
if ($feedback_id <= 0) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Invalid feedback ID'
    ], 400);
}

if (!in_array($vote_type, ['helpful', 'not_helpful', 'remove'])) {
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Invalid vote type'
    ], 400);
}

try {
    $conn->begin_transaction();

    // Check if user already voted
    $check_stmt = $conn->prepare("SELECT vote_type FROM feedback_votes WHERE feedback_id = ? AND user_id = ?");
    $check_stmt->bind_param("is", $feedback_id, $user_id);
    $check_stmt->execute();
    $existing_vote = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($vote_type === 'remove') {
        // Remove vote
        if ($existing_vote) {
            $delete_stmt = $conn->prepare("DELETE FROM feedback_votes WHERE feedback_id = ? AND user_id = ?");
            $delete_stmt->bind_param("is", $feedback_id, $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();

            // Update count
            $count_field = $existing_vote['vote_type'] === 'helpful' ? 'helpful_count' : 'not_helpful_count';
            $update_stmt = $conn->prepare("UPDATE feedback SET $count_field = GREATEST(0, $count_field - 1) WHERE id = ?");
            $update_stmt->bind_param("i", $feedback_id);
            $update_stmt->execute();
            $update_stmt->close();
        }

        $conn->commit();
        ApiSecurityMiddleware::sendJsonResponse([
            'success' => true,
            'message' => 'Vote removed'
        ], 200);
    }

    if ($existing_vote) {
        // Update existing vote
        if ($existing_vote['vote_type'] !== $vote_type) {
            $update_vote_stmt = $conn->prepare("UPDATE feedback_votes SET vote_type = ? WHERE feedback_id = ? AND user_id = ?");
            $update_vote_stmt->bind_param("sis", $vote_type, $feedback_id, $user_id);
            $update_vote_stmt->execute();
            $update_vote_stmt->close();

            // Update counts (decrement old, increment new)
            $old_field = $existing_vote['vote_type'] === 'helpful' ? 'helpful_count' : 'not_helpful_count';
            $new_field = $vote_type === 'helpful' ? 'helpful_count' : 'not_helpful_count';

            $update_count_stmt = $conn->prepare("
                UPDATE feedback
                SET $old_field = GREATEST(0, $old_field - 1),
                    $new_field = $new_field + 1
                WHERE id = ?
            ");
            $update_count_stmt->bind_param("i", $feedback_id);
            $update_count_stmt->execute();
            $update_count_stmt->close();
        }
    } else {
        // Insert new vote
        $insert_stmt = $conn->prepare("INSERT INTO feedback_votes (feedback_id, user_id, vote_type) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iss", $feedback_id, $user_id, $vote_type);
        $insert_stmt->execute();
        $insert_stmt->close();

        // Update count
        $count_field = $vote_type === 'helpful' ? 'helpful_count' : 'not_helpful_count';
        $update_stmt = $conn->prepare("UPDATE feedback SET $count_field = $count_field + 1 WHERE id = ?");
        $update_stmt->bind_param("i", $feedback_id);
        $update_stmt->execute();
        $update_stmt->close();
    }

    // Get updated counts
    $count_stmt = $conn->prepare("SELECT helpful_count, not_helpful_count FROM feedback WHERE id = ?");
    $count_stmt->bind_param("i", $feedback_id);
    $count_stmt->execute();
    $counts = $count_stmt->get_result()->fetch_assoc();
    $count_stmt->close();

    $conn->commit();

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'message' => 'Vote recorded',
        'helpful_count' => $counts['helpful_count'],
        'not_helpful_count' => $counts['not_helpful_count'],
        'user_vote' => $vote_type
    ], 200);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Error in feedback_vote.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'Error recording vote. Please try again.'
    ], 500);
}

$conn->close();
?>
