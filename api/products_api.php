<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

$sql = "SELECT id, name, stock, status FROM products";
$result = $conn->query($sql);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>
