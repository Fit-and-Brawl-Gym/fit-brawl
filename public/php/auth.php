<?php
header('Content-Type: application/json');
require_once '../../includes/db_connect.php';
require_once '../../includes/user_id_generator.php';

$method = $_SERVER['REQUEST_METHOD'];

function validatePassword($password)
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    if (!preg_match('/[?!@#$%^&*]/', $password)) {
        $errors[] = "Password must contain at least one special character (?!@#$%^&*)";
    }

    return $errors;
}

// password strength (weak, medium, strong)
function getPasswordStrength($password)
{
    $strength = 0;

    if (strlen($password) >= 8)
        $strength++;
    if (preg_match('/[A-Z]/', $password))
        $strength++;
    if (preg_match('/[a-z]/', $password))
        $strength++;
    if (preg_match('/[0-9]/', $password))
        $strength++;
    if (preg_match('/[?!@#$%^&*]/', $password))
        $strength++;

    if ($strength <= 2)
        return 'weak';
    if ($strength <= 3)
        return 'medium';
    return 'strong';
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (isset($data['action']) && $data['action'] === 'login') {
        // LOGIN
        $email = $data['email'];
        $password = $data['password'];

        $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            echo json_encode(["status" => "success", "user" => $user]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid login"]);
        }
    } elseif (isset($data['action']) && $data['action'] === 'signup') {
        // SIGNUP
        $username = $data['username'];
        $email = $data['email'];
        $password = $data['password'];

        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'member')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $email, $password);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "User registered"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    }
}
?>
