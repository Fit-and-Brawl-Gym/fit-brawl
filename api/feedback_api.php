<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $data['user_id'];
    $message = $data['message'];

    $sql = "INSERT INTO feedback (user_id, message) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $message);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Feedback submitted"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}

elseif ($method === 'GET') {
    $sql = "SELECT f.id, u.username, f.message, f.date 
            FROM feedback f 
            JOIN users u ON f.user_id = u.id
            ORDER BY f.date DESC";
    $result = $conn->query($sql);

    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }

    echo json_encode($feedbacks);
}
?>
