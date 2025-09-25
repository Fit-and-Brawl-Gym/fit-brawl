<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'login') {
        // LOGIN
        $username = $data['username'];
        $password = $data['password'];

            $sql = "SELECT * FROM users WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    echo json_encode(["status" => "success", "user" => $user]);
                } else {
                    echo json_encode(["status" => "error", "message" => "Invalid login"]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Invalid login"]);
            }
    }

    elseif (isset($data['action']) && $data['action'] === 'signup') {
        // SIGNUP
        $username = $data['username'];
        $password = $data['password'];

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, 'member')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $username, $hashedPassword);

            if ($stmt->execute()) {
                echo json_encode(["status" => "success", "message" => "User registered"]);
            } else {
                echo json_encode(["status" => "error", "message" => $conn->error]);
            }
    }
}
?>
