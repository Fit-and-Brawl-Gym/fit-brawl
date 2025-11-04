<?php
// ===========================================
// admin.php — Main Admin Dashboard
// ===========================================

include_once('../../../includes/init.php');


// Optional: Check admin privileges
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Admin Panel</title>
    <link rel="icon" type="image/png" href="../../../images/favicon-admin.png">
</head>

<body>
    <? include_once('admin_header.php'); ?>
    <? include_once('admin_sidebar.php'); ?>

</body>
<script src="js/sidebar.js"></script>
<? include_once('admin_footer.php'); ?>

</html>