<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

$sql = "SELECT id, plan_name, price, duration FROM memberships";
$result = $conn->query($sql);

$plans = [];
while ($row = $result->fetch_assoc()) {
    $plans[] = $row;
}

echo json_encode($plans);
?>
