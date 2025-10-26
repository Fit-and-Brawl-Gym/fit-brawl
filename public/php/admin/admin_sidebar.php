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
        <img src="../../../images/logo.png" alt="Gym Logo">
        <h2>Fit & Brawl Gym</h2>
        <p>Admin Panel</p>
    </div>

    <nav>
        <a href="admin.php" class="<?= $current_page == 'admin.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-house icon"></i>
            <span>Dashboard</span>
        </a>
        <a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users icon"></i>
            <span>Members</span>
        </a>
        <a href="trainers.php" class="<?= $current_page == 'trainers.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-dumbbell icon"></i>
            <span>Trainers</span>
        </a>
        <a href="reservations.php" class="<?= $current_page == 'reservations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-check icon"></i>
            <span>Reservations</span>
        </a>
        <a href="subscriptions.php" class="<?= $current_page == 'subscriptions.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-credit-card icon"></i>
            <span>Subscriptions</span>
        </a>
        <a href="equipment.php" class="<?= $current_page == 'equipment.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-wrench icon"></i>
            <span>Equipment</span>
        </a>
        <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-box icon"></i>
            <span>Products</span>
        </a>
        <a href="feedback.php" class="<?= $current_page == 'feedback.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-comments icon"></i>
            <span>Feedback</span>
        </a>
        <a href="contacts.php" class="<?= $current_page == 'contacts.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-envelope icon"></i>
            <span>Contacts</span>
            <?= $unreadCount > 0 ? "<span class='badge'>$unreadCount</span>" : "" ?>
        </a>
        <a href="announcements.php" class="<?= $current_page == 'announcements.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bullhorn icon"></i>
            <span>Announcements</span>
        </a>
        <a href="system_status.php" class="<?= $current_page == 'system_status.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gear icon"></i>
            <span>System Status</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </div>
</aside>