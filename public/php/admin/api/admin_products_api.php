<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\admin_products_api.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../includes/init.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// CREATE or UPDATE
try {
    if ($method === 'POST') {
        $id = $_POST['id'] ?? null;
        $name = test_input($_POST['name'] ?? '');
        $category = test_input($_POST['category']?? '');
        $stock = test_input(intval($_POST['stock'] ?? 0));

        if ($stock == 0) {
            $status = 'out of stock';
        } elseif ($stock > 0 && $stock <= 10) {
            $status = 'low stock';
        } else {
            $status = 'in stock';
        }
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $targetDir = __DIR__ . '/../../../../uploads/products/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $filename = uniqid() . "_" . basename($_FILES['image']['name']);
            $targetFile = $targetDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                $imagePath = '../../uploads/products/' . $filename;
            }
        }

        if (empty($id)) {
            $stmt = $conn->prepare("INSERT INTO products (name, category, stock, status, image_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $name, $category, $stock, $status, $imagePath);
        } else {
            if ($imagePath) {
                $stmt = $conn->prepare("UPDATE products SET name=?, category=?, stock=?, status=?, image_path=? WHERE id=?");
                $stmt->bind_param("ssissi", $name, $category, $stock, $status, $imagePath, $id);
            } else {
                $stmt = $conn->prepare("UPDATE products SET name=?, category=?, stock=?, status=? WHERE id=?");
                $stmt->bind_param("ssisi", $name, $category, $stock, $status, $id);
            }
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $stmt->error]);
        }
    } elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = [];

        if (isset($_GET['id'])) {
            $ids[] = intval($_GET['id']);
        } elseif (!empty($input['ids'])) {
            $ids = array_map('intval', $input['ids']);
        }

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => $stmt->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No IDs provided']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function test_input($data){
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>