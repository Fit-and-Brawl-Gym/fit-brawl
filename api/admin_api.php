<?php
header('Content-Type: application/json');
include '../includes/db_connect.php';

// Total users
$users = $conn->query("SELECT COUNT(*) AS total_users FROM users")->fetch_assoc();

// Total reservations
$reservations = $conn->query("SELECT COUNT(*) AS total_reservations FROM reservations")->fetch_assoc();

// Equipment status
$equipment = $conn->query("SELECT status, COUNT(*) AS count FROM equipment GROUP BY status");
$equipment_stats = [];
while ($row = $equipment->fetch_assoc()) {
    $equipment_stats[] = $row;
}

// Product stock status
$products = $conn->query("SELECT status, COUNT(*) AS count FROM products GROUP BY status");
$product_stats = [];
while ($row = $products->fetch_assoc()) {
    $product_stats[] = $row;
}

echo json_encode([
    "users" => $users["total_users"],
    "reservations" => $reservations["total_reservations"],
    "equipment" => $equipment_stats,
    "products" => $product_stats
]);
?>
