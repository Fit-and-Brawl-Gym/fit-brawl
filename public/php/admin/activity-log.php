<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\activity-log.php
include_once('../../../includes/init.php');
require_once('../../../includes/activity_logger.php');

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Initialize activity logger
ActivityLogger::init($conn);

// Get filter parameters
$actionFilter = $_GET['action'] ?? 'all';
$dateFilter = $_GET['date'] ?? 'all';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;

// Get activities
$activities = ActivityLogger::getActivities($limit, $actionFilter, $dateFilter);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Fit & Brawl Gym</title>
    <link rel="icon" type="image/png" href="<?= PUBLIC_PATH ?>/images/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
        }

        .filter-select {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            min-width: 180px;
        }
    </style>
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <header class="page-header">
            <div>
                <h1>Activity Log</h1>
                <p class="subtitle">Complete history of admin actions and system events</p>
            </div>
        </header>

        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label>Activity Type</label>
                <select name="action" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?= $actionFilter === 'all' ? 'selected' : '' ?>>All Activities</option>
                    <option value="trainer" <?= $actionFilter === 'trainer' ? 'selected' : '' ?>>Trainer Management
                    </option>
                    <option value="reservation" <?= $actionFilter === 'reservation' ? 'selected' : '' ?>>Reservation
                        Management</option>
                    <option value="subscription" <?= $actionFilter === 'subscription' ? 'selected' : '' ?>>Subscription
                        Management</option>
                    <option value="equipment" <?= $actionFilter === 'equipment' ? 'selected' : '' ?>>Equipment Management
                    </option>
                    <option value="product" <?= $actionFilter === 'product' ? 'selected' : '' ?>>Product Management
                    </option>
                    <option value="member" <?= $actionFilter === 'member' ? 'selected' : '' ?>>Member Management</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Date Range</label>
                <select name="date" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Time</option>
                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="year" <?= $dateFilter === 'year' ? 'selected' : '' ?>>This Year</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Show Results</label>
                <select name="limit" class="filter-select" onchange="this.form.submit()">
                    <option value="20" <?= $limit === 20 ? 'selected' : '' ?>>20</option>
                    <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100</option>
                    <option value="500" <?= $limit === 500 ? 'selected' : '' ?>>500</option>
                </select>
            </div>
        </form>

        <!-- Activity Table -->
        <section class="logs">
            <table>
                <thead>
                    <tr>
                        <th width="40"></th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target User</th>
                        <th>Details</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($activities)): ?>
                        <?php foreach ($activities as $activity):
                            $iconData = ActivityLogger::getActivityIcon($activity['action_type']);
                            ?>
                            <tr>
                                <td>
                                    <i class="fa-solid <?= $iconData['icon'] ?>"
                                        style="color: <?= $iconData['color'] ?>; font-size: 18px;"></i>
                                </td>
                                <td><strong><?= htmlspecialchars($activity['admin_name']) ?></strong></td>
                                <td><?= ucwords(str_replace('_', ' ', $activity['action_type'])) ?></td>
                                <td><?= htmlspecialchars($activity['target_user'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($activity['details']) ?></td>
                                <td style="color: #666; font-size: 13px;">
                                    <?= date('M d, Y h:i A', strtotime($activity['timestamp'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #999; padding: 60px;">
                                <i class="fa-solid fa-inbox"
                                    style="font-size: 48px; margin-bottom: 12px; display: block;"></i>
                                No activities found for selected filters
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
</body>

</html>
