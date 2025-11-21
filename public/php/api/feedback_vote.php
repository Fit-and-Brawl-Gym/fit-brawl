<?php
session_start();
require_once '../../../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to vote']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$feedback_id = isset($data['feedback_id']) ? intval($data['feedback_id']) : 0;
$vote_type = isset($data['vote_type']) ? trim($data['vote_type']) : '';
$user_id = $_SESSION['user_id'];

// Validate inputs
if ($feedback_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid feedback ID']);
    exit;
}

if (!in_array($vote_type, ['helpful', 'not_helpful', 'remove'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid vote type']);
    exit;
}

try {
    $conn->begin_transaction();

    // Check if user already voted
    $check_stmt = $conn->prepare("SELECT vote_type FROM feedback_votes WHERE feedback_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $feedback_id, $user_id);
    $check_stmt->execute();
    $existing_vote = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($vote_type === 'remove') {
        // Remove vote
        if ($existing_vote) {
            $delete_stmt = $conn->prepare("DELETE FROM feedback_votes WHERE feedback_id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $feedback_id, $user_id);
            $delete_stmt->execute();
            $delete_stmt->close();

            // Update count
            $count_field = $existing_vote['vote_type'] === 'helpful' ? 'helpful_count' : 'not_helpful_count';
            $update_stmt = $conn->prepare("UPDATE feedback SET $count_field = GREATEST(0, $count_field - 1) WHERE id = ?");
            $update_stmt->bind_param("i", $feedback_id);
            $update_stmt->execute();
            $update_stmt->close();
        }

        // Get updated counts after removal
        $count_stmt = $conn->prepare("SELECT helpful_count, not_helpful_count FROM feedback WHERE id = ?");
        $count_stmt->bind_param("i", $feedback_id);
        $count_stmt->execute();
        $counts = $count_stmt->get_result()->fetch_assoc();
        $count_stmt->close();

        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Vote removed',
            'helpful_count' => (int)$counts['helpful_count'],
            'not_helpful_count' => (int)$counts['not_helpful_count'],
            'user_vote' => null
        ]);
        exit;
    }

    if ($existing_vote) {
        // Update existing vote
        if ($existing_vote['vote_type'] !== $vote_type) {
            $update_vote_stmt = $conn->prepare("UPDATE feedback_votes SET vote_type = ? WHERE feedback_id = ? AND user_id = ?");
            $update_vote_stmt->bind_param("sii", $vote_type, $feedback_id, $user_id);
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
        $insert_stmt->bind_param("iis", $feedback_id, $user_id, $vote_type);
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

    echo json_encode([
        'success' => true,
        'message' => 'Vote recorded',
        'helpful_count' => (int)$counts['helpful_count'],
        'not_helpful_count' => (int)$counts['not_helpful_count'],
        'user_vote' => $vote_type
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error recording vote: ' . $e->getMessage()]);
}

$conn->close();
?>
