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
if ($method === 'POST') {
    $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $existingImage = trim($_POST['existing_image'] ?? '');

    if (empty($name) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Name and category are required']);
        exit;
    }

    // Auto-calculate status based on stock - FIX: ensure correct logic
    if ($stock === 0) {
        $status = 'Out of Stock';
    } elseif ($stock >= 1 && $stock <= 10) {
        $status = 'Low Stock';
    } else {
        $status = 'In Stock';
    }

    // Debug: log the calculated status
    error_log("Stock: $stock, Calculated Status: $status");

    // Handle image upload
    $imageName = $existingImage;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../../uploads/products/';

        // Create directory if not exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('prod_') . '.' . $extension;
        $targetPath = $uploadDir . $imageName;

        // Validate image type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($extension), $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type. Allowed: jpg, png, gif, webp']);
            exit;
        }

        // Move uploaded file
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }

        // Delete old image if updating
        if ($id && $existingImage && file_exists($uploadDir . $existingImage)) {
            unlink($uploadDir . $existingImage);
        }
    }

    if ($id) {
        // UPDATE - IMPORTANT: Make sure status is updated
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, brand = ?, price = ?, stock = ?, status = ?, description = ?, image = ? WHERE id = ?");
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param('sssdiissi', $name, $category, $brand, $price, $stock, $status, $description, $imageName, $id);
    } else {
        // CREATE
        $stmt = $conn->prepare("INSERT INTO products (name, category, brand, price, stock, status, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param('sssdisss', $name, $category, $brand, $price, $stock, $status, $description, $imageName);
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Product saved successfully',
            'debug' => "Stock: $stock, Status: $status" // Debug info
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// DELETE
if ($method === 'DELETE') {
    // Bulk delete
    if (isset($_GET['bulk'])) {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];

        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No IDs provided']);
            exit;
        }

        // Get images to delete
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("SELECT image FROM products WHERE id IN ($placeholders)");
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $uploadDir = __DIR__ . '/../../../../uploads/products/';
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['image']) && file_exists($uploadDir . $row['image'])) {
                unlink($uploadDir . $row['image']);
            }
        }
        $stmt->close();

        // Delete products
        $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => count($ids) . ' product(s) deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete products: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // Single delete
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    // Get image to delete
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if ($product && !empty($product['image'])) {
        $imagePath = __DIR__ . '/../../../../uploads/products/' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Delete product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
?>