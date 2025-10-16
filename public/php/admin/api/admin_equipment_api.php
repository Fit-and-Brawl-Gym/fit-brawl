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

$action = $_GET['action'] ?? ($_POST['action'] ?? 'fetch');

try {
    if ($action === 'fetch') {
        $stmt = $conn->prepare("SELECT id, name, IFNULL(category, '') AS category, IFNULL(description, '') AS description, status FROM equipment ORDER BY id DESC");
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        echo json_encode($rows);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid method']);
        exit;
    }

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $status = trim($_POST['status'] ?? 'Available');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Name required']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO equipment (name, category, description, status) VALUES (?,?,?,?)");
        $stmt->bind_param('ssss', $name, $category, $description, $status);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conn->insert_id, 'name' => $name, 'category' => $category, 'description' => $description, 'status' => $status]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit;
    }

    if ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if ($id <= 0 || $status === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE equipment SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }

    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid id']); exit; }
        $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>

