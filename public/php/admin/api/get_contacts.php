<?php
session_start();
require_once __DIR__ . '/../../../../includes/db_connect.php';
require_once __DIR__ . '/../../../../includes/api_security_middleware.php';

ApiSecurityMiddleware::setSecurityHeaders();

// Disable HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Require admin authentication
$user = ApiSecurityMiddleware::requireAuth(['role' => 'admin']);
if (!$user) {
    exit; // Already sent response
}

// Admins are trusted; no rate limiting required on this endpoint
$adminId = $user['user_id'];

try {
    // Detect status and archived columns (for backward compatibility)
    $columns = $conn->query("SHOW COLUMNS FROM contact");
    $hasStatus = false;
    $hasArchived = false;
    while ($col = $columns->fetch_assoc()) {
        if ($col['Field'] === 'status') {
            $hasStatus = true;
        }
        if ($col['Field'] === 'archived') {
            $hasArchived = true;
        }
    }

    $statusSelect = $hasStatus ? 'status' : "'unread' AS status";
    $orderBy = $hasStatus
        ? "CASE WHEN status = 'unread' THEN 0 ELSE 1 END, date_submitted DESC"
        : "date_submitted DESC";

    $activeSql = "SELECT id, first_name, last_name, email, phone_number, message, {$statusSelect}, date_submitted
                  FROM contact
                  WHERE " . ($hasArchived ? '(archived = 0 OR archived IS NULL) AND ' : '') . "deleted_at IS NULL
                  ORDER BY {$orderBy}";

    $activeResult = $conn->query($activeSql);
    if (!$activeResult) {
        throw new Exception('Active contacts query failed: ' . $conn->error);
    }

    $activeContacts = [];
    while ($row = $activeResult->fetch_assoc()) {
        $activeContacts[] = $row;
    }

    $archivedContacts = [];
    if ($hasArchived) {
        $archivedSql = "SELECT id, first_name, last_name, email, phone_number, message, {$statusSelect}, date_submitted
                        FROM contact
                        WHERE archived = 1 AND deleted_at IS NULL
                        ORDER BY date_submitted DESC";

        $archivedResult = $conn->query($archivedSql);
        if (!$archivedResult) {
            throw new Exception('Archived contacts query failed: ' . $conn->error);
        }

        while ($row = $archivedResult->fetch_assoc()) {
            $archivedContacts[] = $row;
        }
    }

    $unreadCount = $hasStatus
        ? array_reduce($activeContacts, fn($carry, $contact) => $carry + ($contact['status'] === 'unread' ? 1 : 0), 0)
        : count($activeContacts);

    ApiSecurityMiddleware::sendJsonResponse([
        'success' => true,
        'contacts' => $activeContacts, // backward compatibility
        'active_contacts' => $activeContacts,
        'archived_contacts' => $archivedContacts,
        'total_active' => count($activeContacts),
        'total_archived' => count($archivedContacts),
        'unread_total' => $unreadCount,
        'has_status_column' => $hasStatus,
        'has_archived_column' => $hasArchived
    ], 200);

} catch (Exception $e) {
    error_log("Error in get_contacts.php: " . $e->getMessage());
    ApiSecurityMiddleware::sendJsonResponse([
        'success' => false,
        'message' => 'An error occurred while fetching contacts. Please try again.'
    ], 500);
}

if (isset($conn)) {
    $conn->close();
}
