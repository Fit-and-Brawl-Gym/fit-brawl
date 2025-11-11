<?php
include_once('../../../includes/init.php');

// Only admins can access
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
    <title>Members Management - Fit & Brawl Gym</title>
    <link rel="icon" type="image/png" href="<?= PUBLIC_PATH ?>/images/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/users.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <header class="page-header">
            <div>
                <h1>Members Management</h1>
                <p class="subtitle">View and manage gym members and their memberships</p>
            </div>
        </header>

        <!-- Search Bar -->
        <div class="toolbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search members by name...">
            </div>
            <div class="stats-summary">
                <span class="stat-label">Total Members:</span>
                <strong id="totalMembers">0</strong>
            </div>
        </div>

        <!-- Plan Filter Tabs -->
        <div class="tabs">
            <button class="tab active" data-plan="all">All</button>
            <button class="tab" data-plan="Gladiator">Gladiator</button>
            <button class="tab" data-plan="Brawler">Brawler</button>
            <button class="tab" data-plan="Champion">Champion</button>
            <button class="tab" data-plan="Clash">Clash</button>
            <button class="tab" data-plan="Resolution Regular">Resolution</button>
        </div>

        <!-- Members List -->
        <div class="members-container">
            <div id="membersList" class="members-list">
                <!-- Members will be loaded here via JavaScript -->
                <div class="loading-state">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Loading members...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- History Side Panel -->
    <div id="historyPanel" class="side-panel">
        <div class="side-panel-overlay" onclick="closeHistoryPanel()"></div>
        <div class="side-panel-content">
            <div class="side-panel-header">
                <h2>Membership History</h2>
                <button class="close-btn" onclick="closeHistoryPanel()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="side-panel-body" id="historyContent">
                <!-- History will be loaded here -->
            </div>
        </div>
    </div>

    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/users.js"></script>
</body>

</html>
