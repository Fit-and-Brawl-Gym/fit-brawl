<?php
include_once('../../../includes/init.php');

// Allow only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Feedback Management | Fit & Brawl Admin</title>
    <link rel="stylesheet" href="/fit-brawl/public/php/admin/css/admin.css">
</head>

<body>
    <?php include_once('admin_header.php'); ?>
    <?php include_once('admin_sidebar.php'); ?>

    <main class="admin-main">
        <header>
            <h1>Feedback Management</h1>
            <p>Read and manage feedback submitted by members.</p>
        </header>

        <section id="feedbackList" class="feedback-section">
            <p>Loading feedback...</p>
        </section>
    </main>

    <?php include_once('admin_footer.php'); ?>
    <script src="/fit-brawl/public/php/admin/js/feedback.js"></script>
</body>

</html>