<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔔 Count unread contact messages (safe: check if table exists)
include_once('../../../includes/db_connect.php');
$unreadCount = 0;
if ($conn->query("SHOW TABLES LIKE 'inquiries'")->num_rows) {
    $countResult = $conn->query("SELECT COUNT(*) AS unread FROM inquiries WHERE status='Unread'");
    if ($countResult) {
        $unreadCount = (int) ($countResult->fetch_assoc()['unread'] ?? 0);
    }
}

// Optional: check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Optional: get current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Container -->
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../../../images/logo.png" alt="Gym Logo" class="logo">
        <h2>Fit & Brawl Gym</h2>
    </div>

    <div class="sidebar-user">
        <p>Welcome, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></p>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <li class="<?= $current_page == 'admin.php' ? 'active' : '' ?>">
                <a href="admin.php">🏠 Dashboard</a>
            </li>
            <li class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
                <a href="users.php">👥 Members</a>
            </li>
            <li class="<?= $current_page == 'trainers.php' ? 'active' : '' ?>">
                <a href="trainers.php">🥋 Trainers</a>
            </li>
            <li class="<?= $current_page == 'reservations.php' ? 'active' : '' ?>">
                <a href="reservations.php">📅 Reservations</a>
            </li>
            <li class="<?= $current_page == 'subscriptions.php' ? 'active' : '' ?>">
                <a href="subscriptions.php">💳 Subscriptions</a>
            </li>
            <li class="<?= $current_page == 'equipment.php' ? 'active' : '' ?>">
                <a href="equipment.php">🏋️ Equipment</a>
            </li>
            <li class="<?= $current_page == 'products.php' ? 'active' : '' ?>">
                <a href="products.php">🛍️ Products</a>
            </li>
            <li class="<?= $current_page == 'feedback.php' ? 'active' : '' ?>">
                <a href="feedback.php">💬 Feedback</a>
            </li>
            <li class="<?= $current_page == 'contact.php' ? 'active' : '' ?>">
                <a href="contacts.php">📩 Contacts
                    <?= $unreadCount > 0 ? "<span class='badge'>$unreadCount</span>" : "" ?>
                </a>
            </li>
            <li class="<?= $current_page == 'announcements.php' ? 'active' : '' ?>">
                <a href="announcements.php">📢 Announcements</a>
            </li>
            <li class="<?= $current_page == 'system_status.php' ? 'active' : '' ?>">
                <a href="system_status.php">⚙️ System Status</a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</aside>
