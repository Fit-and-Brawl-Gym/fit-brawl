<?php
// Admin Equipment API
// Supports: ?action=fetch (GET), POST actions: add, update, delete
// Use absolute include relative to this file for reliability
require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/../../../../includes/activity_logger.php';
require_once __DIR__ . '/../../../../includes/file_upload_security.php';

// Initialize activity logger
ActivityLogger::init($conn);

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
    $maintenanceStartDate = !empty($_POST['maintenance_start_date']) ? $_POST['maintenance_start_date'] : null;
    $maintenanceEndDate = !empty($_POST['maintenance_end_date']) ? $_POST['maintenance_end_date'] : null;
    $maintenanceReason = !empty($_POST['maintenance_reason']) ? trim($_POST['maintenance_reason']) : null;
    $imagePath = null;

    if (empty($name) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'Name and category are required']);
        exit;
    }

    // Validate maintenance fields if status is Maintenance
    if ($status === 'Maintenance') {
        if (empty($maintenanceStartDate) || empty($maintenanceEndDate)) {
            echo json_encode(['success' => false, 'message' => 'Maintenance dates are required when status is Maintenance']);
            exit;
        }
        if (strtotime($maintenanceEndDate) < strtotime($maintenanceStartDate)) {
            echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
            exit;
        }
    } else {
        // Clear maintenance fields if status is not Maintenance
        $maintenanceStartDate = null;
        $maintenanceEndDate = null;
        $maintenanceReason = null;
    }

    // Handle image upload securely
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../../../uploads/equipment/';
        $uploadHandler = SecureFileUpload::imageUpload($uploadDir, 5);

        $result = $uploadHandler->uploadFile($_FILES['image']);

        if ($result['success']) {
            $imagePath = '../../uploads/equipment/' . $result['filename'];
        } else {
            echo json_encode(['success' => false, 'message' => $result['message']]);
            exit;
        }
    }

    // If updating
    if ($id) {
        if ($imagePath) {
            $stmt = $conn->prepare("UPDATE equipment SET name=?, category=?, status=?, description=?, image_path=?,
                                    maintenance_start_date=?, maintenance_end_date=?, maintenance_reason=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $name, $category, $status, $description, $imagePath,
                             $maintenanceStartDate, $maintenanceEndDate, $maintenanceReason, $id);
        } else {
            $stmt = $conn->prepare("UPDATE equipment SET name=?, category=?, status=?, description=?,
                                    maintenance_start_date=?, maintenance_end_date=?, maintenance_reason=? WHERE id=?");
            $stmt->bind_param("sssssssi", $name, $category, $status, $description,
                             $maintenanceStartDate, $maintenanceEndDate, $maintenanceReason, $id);
        }
    }
    // If adding new
    else {
        $stmt = $conn->prepare("INSERT INTO equipment (name, category, status, description, image_path,
                                maintenance_start_date, maintenance_end_date, maintenance_reason)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $name, $category, $status, $description, $imagePath,
                         $maintenanceStartDate, $maintenanceEndDate, $maintenanceReason);
    }
    if ($stmt->execute()) {
        // LOG THE ACTIVITY
        $logMessage = "Equipment: {$name} (Category: {$category}, Status: {$status})";
        if ($status === 'Maintenance' && $maintenanceStartDate) {
            $logMessage .= " - Maintenance: " . date('M d', strtotime($maintenanceStartDate)) .
                          " to " . date('M d, Y', strtotime($maintenanceEndDate));
            if ($maintenanceReason) {
                $logMessage .= " - Reason: {$maintenanceReason}";
            }
        }

        if ($id) {
            ActivityLogger::log(
                'equipment_edit',
                null,
                $id,
                "Updated {$logMessage}"
            );
        } else {
            $newId = $conn->insert_id;
            ActivityLogger::log(
                'equipment_add',
                null,
                $newId,
                "Added {$logMessage}"
            );
        }

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

    // Get equipment name before deleting
    $nameStmt = $conn->prepare("SELECT name, category FROM equipment WHERE id = ?");
    $nameStmt->bind_param('i', $id);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    $equipmentData = $nameResult->fetch_assoc();
    $nameStmt->close();

    $stmt = $conn->prepare("DELETE FROM equipment WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        // LOG THE ACTIVITY
        if ($equipmentData) {
            ActivityLogger::log(
                'equipment_delete',
                null,
                $id,
                "Deleted equipment: {$equipmentData['name']} (Category: {$equipmentData['category']})"
            );
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);

