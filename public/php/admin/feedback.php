<?php
include_once('../../../includes/init.php');
require_once __DIR__ . '/../../../includes/csrf_protection.php';

// Allow only admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token
$csrfToken = CSRFProtection::generateToken();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
    <title>Feedback Management - Fit & Brawl Gym</title>
    <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/feedback.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <header class="page-header">
            <div>
                <h1>Feedback Management</h1>
                <p class="subtitle">Read and manage feedback submitted by members</p>
            </div>
        </header>

        <!-- Toolbar with Stats & Filters -->
        <div class="feedback-toolbar">
            <div class="feedback-stats">
                <div class="stat-item">
                    <div class="stat-icon total">
                        <i class="fa-solid fa-comments"></i>
                    </div>
                    <div class="stat-content">
                        <span>Total</span>
                        <strong id="totalCount">0</strong>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon visible">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <span>Visible</span>
                        <strong id="visibleCount">0</strong>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon hidden">
                        <i class="fa-solid fa-eye-slash"></i>
                    </div>
                    <div class="stat-content">
                        <span>Hidden</span>
                        <strong id="hiddenCount">0</strong>
                    </div>
                </div>
            </div>

            <div class="toolbar-actions">
                <select id="dateFilter" class="date-filter">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                </select>

                <div class="view-toggle">
                    <button class="view-btn active" data-view="table" title="Table View">
                        <i class="fa-solid fa-table"></i>
                    </button>
                    <button class="view-btn" data-view="card" title="Card View">
                        <i class="fa-solid fa-grip"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Table View -->
    <div class="feedback-table-view active" id="tableView">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Message</th>
                        <th>Rating</th>
                        <th>Date</th>
                        <th>Visibility</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="feedbackTableBody">
                    <!-- Table rows will be loaded here by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Cards View -->
    <div class="feedback-cards-view" id="cardsView">
        <!-- Feedback Grid (Card View) -->
        <div id="feedbackGrid" class="feedback-grid">
            <!-- Feedback cards will be loaded here by JavaScript -->
            <div class="empty-state">
                <i class="fa-solid fa-comments"></i>
                <h3>No Feedback Yet</h3>
                <p>Member feedback will appear here</p>
            </div>
        </div>
        </div>
    </main>

    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/feedback.js?v=<?= time() ?>"></script>
</body>

</html>
