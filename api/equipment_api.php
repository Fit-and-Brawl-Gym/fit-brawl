<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

$sql = "SELECT id, name, status FROM equipment";
$result = $conn->query($sql);

$equipment = [];
while ($row = $result->fetch_assoc()) {
    $equipment[] = $row;
}

echo json_encode($equipment);
?>
