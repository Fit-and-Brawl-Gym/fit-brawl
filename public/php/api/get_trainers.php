<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../../../includes/db_connect.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

try {
    $class_type = isset($_GET['plan']) ? trim($_GET['plan']) : 'all';

    // Map class types to trainer specializations
    $class_to_spec_map = [
        'boxing' => 'Boxing',
        'muay-thai' => 'Muay Thai',
        'mma' => 'MMA',
        'gym' => 'Gym'
    ];

    // Build query - only get active trainers
    if ($class_type !== 'all' && isset($class_to_spec_map[$class_type])) {
        $specialization = $class_to_spec_map[$class_type];
        $sql = "SELECT id, name, specialization FROM trainers WHERE status = 'Active' AND specialization = ? ORDER BY name ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: Unable to prepare statement');
        }
        $stmt->bind_param("s", $specialization);
    } else {
        $sql = "SELECT id, name, specialization FROM trainers WHERE status = 'Active' ORDER BY name ASC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Database error: Unable to prepare statement');
        }
    }

    if (!$stmt->execute()) {
        throw new Exception('Database error: Query execution failed');
    }

    $result = $stmt->get_result();

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
    error_log("Error fetching trainers: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching trainers. Please try again.']);
} finally {
    if (isset($stmt) && $stmt) {
        $stmt->close();
    }
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
