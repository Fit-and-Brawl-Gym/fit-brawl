<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once('../../../includes/db_connect.php');
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../../../images/fnb-icon.png" alt="FitXBrawl">
        <h2>FitXBrawl</h2>
        <p>Admin Panel</p>
    </div>
    <nav>
        <a href="admin.php" class="<?= $current_page == 'admin.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Members</span>
        </a>
        <a href="trainers.php"
            class="<?= in_array($current_page, ['trainers.php', 'trainer_add.php', 'trainer_edit.php', 'trainer_view.php']) ? 'active' : '' ?>">
            <i class="fas fa-dumbbell"></i>
            <span>Trainers</span>
        </a>
        <a href="memberships.php" class="<?= $current_page == 'memberships.php' ? 'active' : '' ?>">
            <i class="fas fa-id-card"></i>
            <span>Memberships</span>
        </a>
        <a href="reservations.php" class="<?= $current_page == 'reservations.php' ? 'active' : '' ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Reservations</span>
        </a>
        <a href="equipment.php" class="<?= $current_page == 'equipment.php' ? 'active' : '' ?>">
            <i class="fas fa-tools"></i>
            <span>Equipment</span>
        </a>
        <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>Products</span>
        </a>
        <a href="feedback.php" class="<?= $current_page == 'feedback.php' ? 'active' : '' ?>">
            <i class="fas fa-comment-dots"></i>
            <span>Feedback</span>
        </a>
        <a href="activity-log.php" class="<?= $current_page == 'activity-log.php' ? 'active' : '' ?>">
            <i class="fas fa-history"></i>
            <span>Activity Log</span>
        </a>
        <a href="system_status.php" class="<?= $current_page == 'system_status.php' ? 'active' : '' ?>">
            <i class="fas fa-server"></i>
            <span>System Status</span>
        </a>
        <a href="announcements.php" class="<?= $current_page == 'announcements.php' ? 'active' : '' ?>">
            <i class="fas fa-bullhorn"></i>
            <span>Announcements</span>
        </a>
        <a href="contacts.php" class="<?= $current_page == 'contacts.php' ? 'active' : '' ?>">
            <i class="fas fa-address-book"></i>
            <span>Contacts</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>