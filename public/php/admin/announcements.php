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
    <link rel="icon" type="image/png" href="<?= PUBLIC_PATH ?>/images/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include_once('admin_sidebar.php'); ?>

    <main class="admin-main">
        <header class="page-header">
            <div>
                <h1>Announcements</h1>
                <p class="subtitle">Manage gym announcements and notifications</p>
            </div>
        </header>

        <div class="content-area">
            <div class="card">
                <div class="card-header">
                    <h3>Announcements Management</h3>
                    <button class="btn-primary">
                        <i class="fas fa-plus"></i> New Announcement
                    </button>
                </div>
                <div class="card-body">
                    <p class="text-muted">Announcements feature coming soon...</p>
                </div>
            </div>
        </div>
    </main>

    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
</body>

</html>
