<?php
// Log an admin action safely. Returns true on success, false on failure.
function logAction($conn, int $adminId, string $action): bool
{
    if (!($conn instanceof mysqli)) {
        error_log('logAction: invalid $conn');
        return false;
    }

    // Ensure table name matches your DB. Adjust if your table is named differently.
    $query = "INSERT INTO admin_logs (admin_id, action, created_at) VALUES (?, ?, NOW())";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log('logAction prepare failed: ' . $conn->error . ' | Query: ' . $query);
        return false;
    }

    if (!$stmt->bind_param('is', $adminId, $action)) {
        error_log('logAction bind_param failed: ' . $stmt->error);
        $stmt->close();
        return false;
    }

    $ok = $stmt->execute();
    if (!$ok) {
        error_log('logAction execute failed: ' . $stmt->error);
    }

    $stmt->close();
    return (bool) $ok;
}
?>