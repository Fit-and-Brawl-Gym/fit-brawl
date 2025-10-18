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
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $status = trim($_POST['status'] ?? 'Available');
    $description = trim($_POST['description'] ?? '');
    $imagePath = null;

    if (empty($name) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Name and category are required']);
        exit;
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = __DIR__ . '/../../../../uploads/equipment/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $filename = uniqid() . "_" . basename($_FILES['image']['name']);
        $targetFile = $targetDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $imagePath = '../../uploads/equipment/' . $filename;
        }
    }

    // If updating
    if ($id) {
        if ($imagePath) {
            $stmt = $conn->prepare("UPDATE equipment SET name=?, category=?, status=?, description=?, image_path=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $category, $status, $description, $imagePath, $id);
        } else {
            $stmt = $conn->prepare("UPDATE equipment SET name=?, category=?, status=?, description=? WHERE id=?");
            $stmt->bind_param("ssssi", $name, $category, $status, $description, $id);
        }
    } 
    // If adding new
    else {
        $stmt = $conn->prepare("INSERT INTO equipment (name, category, status, description, image_path) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $category, $status, $description, $imagePath);
    }
     if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }   
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
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => $stmt->error]);
            }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

