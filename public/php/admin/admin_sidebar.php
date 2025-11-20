<?php
// Session and DB are already initialized by init.php in parent page
// Just get the current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <img src="<?= IMAGES_PATH ?>/header-title.svg" alt="FitXBrawl" class="logo-title"
            style="width: 220px !important; height: auto !important; max-width: 220px !important;">
        <button class="sidebar-close" id="sidebarClose" aria-label="Close Menu">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
    </div>
    <nav>
        <!-- OVERVIEW -->
        <div class="nav-section-title">Overview</div>
        <a href="<?= PUBLIC_PATH ?>/php/admin/admin.php" class="<?= $current_page == 'admin.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-home"></i>
            <span>Dashboard</span>
        </a>

        <!-- MEMBERSHIP MANAGEMENT -->
        <div class="nav-section-title">Membership Management</div>
        <a href="<?= PUBLIC_PATH ?>/php/admin/users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-id-card"></i>
            <span>Members</span>
        </a>
        <a href="<?= PUBLIC_PATH ?>/php/admin/subscriptions.php" class="<?= $current_page == 'subscriptions.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-credit-card"></i>
            <span>Manage Subscriptions</span>
        </a>

        <!-- TRAINER & SCHEDULE MANAGEMENT -->
        <div class="nav-section-title">Trainers & Schedules</div>
        <a href="<?= PUBLIC_PATH ?>/php/admin/trainers.php"
            class="<?= in_array($current_page, ['trainers.php', 'trainer_add.php', 'trainer_edit.php', 'trainer_view.php']) ? 'active' : '' ?>">
            <i class="fa-solid fa-dumbbell"></i>
            <span>Trainer Accounts</span>
        </a>
        <a href="<?= PUBLIC_PATH ?>/php/admin/trainer-schedules.php" class="<?= $current_page == 'trainer-schedules.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-xmark"></i>
            <span>Trainer Schedules</span>
        </a>
        <a href="<?= PUBLIC_PATH ?>/php/admin/reservations.php" class="<?= $current_page == 'reservations.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-calendar-check"></i>
            <span>Trainer Reservations</span>
        </a>

        <!-- FACILITY MANAGEMENT -->
        <div class="nav-section-title">Facility Management</div>
        <a href="<?= PUBLIC_PATH ?>/php/admin/equipment.php" class="<?= $current_page == 'equipment.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-tools"></i>
            <span>Gym Equipments</span>
        </a>
        <a href="<?= PUBLIC_PATH ?>/php/admin/products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-shopping-bag"></i>
            <span>Manage Products</span>
        </a>

        <!-- COMMUNICATIONS -->
        <div class="nav-section-title">Communications</div>
        <a href="<?= PUBLIC_PATH ?>/php/admin/feedback.php" class="<?= $current_page == 'feedback.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-comment-dots"></i>
            <span>Feedback</span>
        </a>
       <!-- <a href="<?= PUBLIC_PATH ?>/php/admin/announcements.php" class="<?= $current_page == 'announcements.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bullhorn"></i>
            <span>Announcements</span>
        </a> -->
        <a href="<?= PUBLIC_PATH ?>/php/admin/contacts.php" class="<?= $current_page == 'contacts.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-envelope"></i>
            <span>Inquiries</span>
        </a>

        <!-- SYSTEM -->
        <div class="nav-section-title">System</div>
        <a href="<?= PUBLIC_PATH ?>/php/admin/activity-log.php" class="<?= $current_page == 'activity-log.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-history"></i>
            <span>Activity Log</span>
        </a>
        <!-- <a href="<?= PUBLIC_PATH ?>/php/admin/system_status.php" class="<?= $current_page == 'system_status.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-server"></i>
            <span>System Status</span>
        </a> -->
    </nav>
    <div class="sidebar-footer">
        <a href="<?= PUBLIC_PATH ?>/php/logout.php" class="logout-btn">
            <i class="fa-solid fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
