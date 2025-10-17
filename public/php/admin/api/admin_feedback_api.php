<?php
include_once('../../../../includes/init.php');
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'fetch':
        $sql = "SELECT f.id, u.username, f.message, f.date
                FROM feedback f
                LEFT JOIN users u ON f.user_id = u.id
                ORDER BY f.date DESC";
        $result = $conn->query($sql);
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);
        break;

    case 'delete':
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $conn->prepare("DELETE FROM feedback WHERE id=?");
            $stmt->bind_param('i', $id);
            echo json_encode(['success' => $stmt->execute()]);
        } else {
            echo json_encode(['error' => 'Invalid ID']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
