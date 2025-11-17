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
    <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
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
                <h1>User Management</h1>
                <p class="subtitle">Manage all user accounts, roles, and permissions</p>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card stat-primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Accounts</div>
                    <div class="stat-value" id="totalUsers">0</div>
                </div>
            </div>

            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Non-Members</div>
                    <div class="stat-value" id="regularMembers">0</div>
                </div>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Subscribed Members</div>
                    <div class="stat-value" id="subscribedMembers">0</div>
                </div>
            </div>

            <div class="stat-card stat-info">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Trainers</div>
                    <div class="stat-value" id="trainerCount">0</div>
                </div>
            </div>

            <div class="stat-card stat-warning">
                <div class="stat-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Admins</div>
                    <div class="stat-value" id="adminCount">0</div>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name, email, or username...">
            </div>
            <div class="toolbar-actions">
                <button class="btn-filter" id="filterBtn">
                    <i class="fas fa-filter"></i>
                    Filters
                </button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section" id="filterSection">
            <div class="filter-group">
                <label>Role</label>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-role="all">
                        <i class="fas fa-users"></i> All
                    </button>
                    <button class="filter-tab" data-role="member">
                        <i class="fas fa-user"></i> Members
                    </button>
                    <button class="filter-tab" data-role="trainer">
                        <i class="fas fa-user-tie"></i> Trainers
                    </button>
                    <button class="filter-tab" data-role="admin">
                        <i class="fas fa-user-shield"></i> Admins
                    </button>
                </div>
            </div>

            <div class="filter-group">
                <label>Account Status</label>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-status="all">
                        <i class="fas fa-circle"></i> All
                    </button>
                    <button class="filter-tab" data-status="active">
                        <i class="fas fa-check-circle"></i> Active
                    </button>
                    <button class="filter-tab" data-status="suspended">
                        <i class="fas fa-pause-circle"></i> Suspended
                    </button>
                    <button class="filter-tab" data-status="pending">
                        <i class="fas fa-circle"></i> Pending
                    </button>
                </div>
            </div>

            <div class="filter-group">
                <label>Verification</label>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-verified="all">All</button>
                    <button class="filter-tab" data-verified="1">Verified</button>
                    <button class="filter-tab" data-verified="0">Unverified</button>
                </div>
            </div>

            <div class="filter-group">
                <label>Membership Status</label>
                <div class="filter-tabs">
                    <button class="filter-tab active" data-membership="all">
                        <i class="fas fa-circle"></i> All
                    </button>
                    <button class="filter-tab" data-membership="active">
                        <i class="fas fa-check-circle"></i> Active Subscription
                    </button>
                    <button class="filter-tab" data-membership="expired">
                        <i class="fas fa-times-circle"></i> Expired/None
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="content-card">
            <div class="table-header">
                <h3>User Accounts</h3>
                <div class="table-actions">
                    <select id="sortBy" class="sort-select">
                        <option value="created_at_desc">Newest First</option>
                        <option value="created_at_asc">Oldest First</option>
                        <option value="name_asc">Name A-Z</option>
                        <option value="name_desc">Name Z-A</option>
                    </select>
                </div>
            </div>
            
            <div class="table-container">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="8" class="loading-state">
                                <i class="fas fa-spinner fa-spin"></i>
                                <p>Loading users...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- User Details Side Panel -->
    <div id="userDetailsPanel" class="side-panel">
        <div class="side-panel-overlay" onclick="closeUserDetailsPanel()"></div>
        <div class="side-panel-content">
            <div class="side-panel-header">
                <h2>User Details</h2>
                <button class="close-btn" onclick="closeUserDetailsPanel()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="side-panel-body" id="userDetailsContent">
                <!-- User details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/users-secure.js"></script>
</body>

</html>
