<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Fit and Brawl</title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/pages/homepage.css">
    <link rel="stylesheet" href="../../css/components/footer.css">
    <link rel="stylesheet" href="../../css/components/header.css">
</head>
<body>
    <header>
        <div class="wrapper">
            <div class="title">
                <a href="dashboard.php"><img src="../../images/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo"></a>
                <a href="dashboard.php"><img src="../../images/header-title.svg" alt="FITXBRAWL" class="logo-title"></a>
            </div>
            <nav class="nav-bar">
                <ul>
                    <li><a href="users.php">Users</a></li>
                    <li><a href="memberships.php">Memberships</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="content.php">Content</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                    <li><a href="logs.php">Logs</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main>
        <section>
            <h1>Welcome, Admin!</h1>
            <p>Select a section from the navigation bar to manage your website.</p>
        </section>
    </main>
    <footer>
        <div class="container footer-flex"></div>
        <div class="copyright"></div>
    </footer>
</body>
</html>