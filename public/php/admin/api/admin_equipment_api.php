<?php
// Admin Equipment API
// Supports: ?action=fetch (GET), POST actions: add, update, delete
// Use absolute include relative to this file for reliability
require_once __DIR__ . '/../../../../includes/init.php';

header('Content-Type: application/json');

// Only admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// CREATE or UPDATE
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $id = isset($input['id']) && $input['id'] !== '' ? (int) $input['id'] : null;
    $name = trim($input['name'] ?? '');
    $category = trim($input['category'] ?? '');
    $status = trim($input['status'] ?? 'Available');
    $description = trim($input['description'] ?? '');

    if (empty($name) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Name and category are required']);
        exit;
    }

    if ($id) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE equipment SET name = ?, category = ?, status = ?, description = ? WHERE id = ?");
        $stmt->bind_param('ssssi', $name, $category, $status, $description, $id);
    } else {
        // CREATE
        $stmt = $conn->prepare("INSERT INTO equipment (name, category, status, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $category, $status, $description);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Equipment saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// DELETE
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Equipment deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

