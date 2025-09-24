<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

$sql = "SELECT id, name, specialization, schedule FROM trainers";
$result = $conn->query($sql);

$trainers = [];
while ($row = $result->fetch_assoc()) {
    $trainers[] = $row;
}

echo json_encode($trainers);
?>
