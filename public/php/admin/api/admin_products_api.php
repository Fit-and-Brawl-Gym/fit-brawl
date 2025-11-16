<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\api\admin_products_api.php
require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/../../../../includes/activity_logger.php';
require_once __DIR__ . '/../../../../includes/file_upload_security.php';
require_once __DIR__ . '/../../../../includes/csrf_protection.php';
require_once __DIR__ . '/../../../../includes/api_rate_limiter.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';

// Initialize activity logger
ActivityLogger::init($conn);

ApiSecurityMiddleware::setSecurityHeaders();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Unauthorized'], 403);
    exit;
}

// Rate limiting for admin APIs - 20 requests per minute per admin
$adminId = $_SESSION['user_id'] ?? 'unknown';
$rateCheck = ApiRateLimiter::checkAndIncrement($conn, 'admin_api:' . $adminId, 20, 60);
if ($rateCheck['blocked']) {
    http_response_code(429);
    header('X-RateLimit-Limit: 20');
    header('X-RateLimit-Remaining: 0');
    header('Retry-After: ' . $rateCheck['retry_after']);
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Too many requests. Please try again later.'], 429);
    exit;
}
header('X-RateLimit-Limit: 20');
header('X-RateLimit-Remaining: ' . $rateCheck['remaining']);
header('X-RateLimit-Reset: ' . (time() + $rateCheck['retry_after']));

$method = $_SERVER['REQUEST_METHOD'];

// CREATE or UPDATE
try {
    if ($method === 'POST') {
        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFProtection::validateToken($csrfToken)) {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'CSRF token validation failed'], 403);
            exit;
        }

        $id = $_POST['id'] ?? null;
        $name = test_input($_POST['name'] ?? '');
        $category = ($_POST['category'] ?? '');
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
            $uploadDir = __DIR__ . '/../../../../uploads/products/';
            $uploadHandler = SecureFileUpload::imageUpload($uploadDir, 5);

            $result = $uploadHandler->uploadFile($_FILES['image']);

            if ($result['success']) {
                $imagePath = '../../uploads/products/' . $result['filename'];
            } else {
                ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => $result['message']], 400);
                exit;
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
            // LOG THE ACTIVITY
            if (empty($id)) {
                $newId = $conn->insert_id;
                ActivityLogger::log(
                    'product_add',
                    null,
                    $newId,
                    "Added product: {$name} (Category: {$category}, Stock: {$stock}, Status: {$status})"
                );
            } else {
                ActivityLogger::log(
                    'product_edit',
                    null,
                    $id,
                    "Updated product: {$name} (Category: {$category}, Stock: {$stock}, Status: {$status})"
                );
            }

            ApiSecurityMiddleware::sendJsonResponse(['success' => true], 200);
        } else {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => $stmt->error], 500);
        }
    } elseif ($method === 'DELETE') {
        // Validate CSRF token (from query string or request body for DELETE requests)
        $csrfToken = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
        if (empty($csrfToken)) {
            $input = json_decode(file_get_contents('php://input'), true);
            $csrfToken = $input['csrf_token'] ?? '';
        }
        if (!CSRFProtection::validateToken($csrfToken)) {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'CSRF token validation failed'], 403);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $ids = [];

        if (isset($_GET['id'])) {
            $ids[] = intval($_GET['id']);
        } elseif (!empty($input['ids'])) {
            $ids = array_map('intval', $input['ids']);
        }

        if (!empty($ids)) {
            // Get product names before deleting
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $nameStmt = $conn->prepare("SELECT id, name, category FROM products WHERE id IN ($placeholders)");
            $nameStmt->bind_param($types, ...$ids);
            $nameStmt->execute();
            $nameResult = $nameStmt->get_result();
            $productData = $nameResult->fetch_all(MYSQLI_ASSOC);
            $nameStmt->close();

            // Delete
            $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
            $stmt->bind_param($types, ...$ids);

            if ($stmt->execute()) {
                // LOG THE ACTIVITY
                if (count($ids) === 1 && !empty($productData)) {
                    ActivityLogger::log(
                        'product_delete',
                        null,
                        $ids[0],
                        "Deleted product: {$productData[0]['name']} (Category: {$productData[0]['category']})"
                    );
                } elseif (count($ids) > 1) {
                    ActivityLogger::log(
                        'bulk_delete',
                        null,
                        null,
                        "Bulk deleted " . count($ids) . " products"
                    );
                }

                ApiSecurityMiddleware::sendJsonResponse(['success' => true], 200);
            } else {
                ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => $stmt->error], 500);
            }
        } else {
            ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'No IDs provided'], 400);
        }
    } else {
        ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
    }
} catch (Exception $e) {
    ApiSecurityMiddleware::sendJsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

?>
