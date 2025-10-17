<?php
session_start();
require_once '../../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'member'; 

if ($user_id) {
    $stmt = $conn->prepare("DELETE FROM remember_password WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, role, action) VALUES (?, ?, 'logout')");
    $log_stmt->bind_param("is", $user_id, $role);
    $log_stmt->execute();
    $log_stmt->close();
}


$_SESSION = [];
session_unset();
session_destroy();

// Redirect to login
header("Location: login.php");
exit;
?>
