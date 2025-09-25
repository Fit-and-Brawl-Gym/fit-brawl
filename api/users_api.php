<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get all users
    $sql = "SELECT id, username, role FROM users";
    $result = $conn->query($sql);

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode($users);
}

elseif ($method === 'POST') {
    // Add a new user (basic example for admin use)
    $data = json_decode(file_get_contents("php://input"), true);
    $username = $data['username'];
    $password = $data['password'];
    $role     = $data['role'];

    $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $password, $role);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "User added"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>
