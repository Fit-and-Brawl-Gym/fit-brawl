<?php
session_start();
require_once '../../includes/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;

if ($user_id) {
    $stmt = $conn->prepare("DELETE FROM remember_password WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}


session_unset();
session_destroy();

// Redirect to login
header("Location: login.php");
exit;
?>
