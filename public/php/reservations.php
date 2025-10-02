<?php
header('Content-Type: application/json');
include '../../includes/db_connect.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch all reservations
    $sql = "SELECT * FROM reservations";
    $result = $conn->query($sql);

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        $reservations[] = $row;
    }

    echo json_encode($reservations);
}

elseif ($method === 'POST') {
    // Create a reservation
    $data = json_decode(file_get_contents("php://input"), true);
    $user_id = $data['user_id'];
    $trainer_id = $data['trainer_id'];
    $class_type = $data['class_type'];
    $datetime = $data['datetime'];

    $sql = "INSERT INTO reservations (user_id, trainer_id, class_type, datetime) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $user_id, $trainer_id, $class_type, $datetime);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Reservation created"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}
?>
