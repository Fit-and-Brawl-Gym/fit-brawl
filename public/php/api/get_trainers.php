<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../../../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $plan = isset($_GET['plan']) ? trim($_GET['plan']) : '';

    // Build query
    if (!empty($plan) && $plan !== 'all') {
        $plan = strtolower($plan);
        $sql = "
            SELECT id, name, specialization 
            FROM trainers 
            WHERE LOWER(REPLACE(specialization, ' ', '-')) LIKE '%$plan%'
        ";
    } else {
        $sql = "SELECT id, name, specialization FROM trainers";
    }

    $result = $conn->query($sql);
    if (!$result) throw new Exception($conn->error);

    $trainers = [];
    while ($row = $result->fetch_assoc()) {
        $key = strtolower(preg_replace('/[^a-z0-9]+/', '-', $row['specialization']));
        $trainers[$key][] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'specialization' => $row['specialization']
        ];
    }

    echo json_encode(['success' => true, 'trainers' => $trainers]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
