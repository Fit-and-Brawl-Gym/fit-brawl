<?php
session_start();
include_once('../../../includes/init.php');
require_once('../../../includes/activity_logger.php');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

// Initialize activity logger
ActivityLogger::init($conn);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'] ?? null;

    if ($action === 'add_block') {
        $trainer_id = intval($_POST['trainer_id'] ?? 0);
        $date = $_POST['date'] ?? '';
        $session_time = $_POST['session_time'] ?? 'All Day';
        $reason = trim($_POST['reason'] ?? '');

        if ($trainer_id && $date) {
            // Check if block already exists
            $check_stmt = $conn->prepare("SELECT id FROM trainer_availability_blocks WHERE trainer_id = ? AND date = ? AND session_time = ? AND block_status = 'blocked'");
            $check_stmt->bind_param('iss', $trainer_id, $date, $session_time);
            $check_stmt->execute();

            if ($check_stmt->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Block already exists for this trainer, date, and session']);
                exit;
            }

            $stmt = $conn->prepare("INSERT INTO trainer_availability_blocks (trainer_id, date, session_time, reason, blocked_by, block_status) VALUES (?, ?, ?, ?, ?, 'blocked')");
            $stmt->bind_param('isssi', $trainer_id, $date, $session_time, $reason, $_SESSION['user_id']);
            $success = $stmt->execute();

            if ($success) {
                // Log activity
                $trainer_query = $conn->prepare("SELECT name FROM trainers WHERE id = ?");
                $trainer_query->bind_param('i', $trainer_id);
                $trainer_query->execute();
                $trainer_name = $trainer_query->get_result()->fetch_assoc()['name'];

                $log_msg = "Blocked schedule for $trainer_name on $date ($session_time)" . ($reason ? " - Reason: $reason" : "");
                ActivityLogger::log('schedule_blocked', $trainer_name, $trainer_id, $log_msg);
            }

            echo json_encode(['success' => $success]);
            exit;
        }
    }

    if ($action === 'delete_block') {
        $block_id = intval($_POST['block_id'] ?? 0);

        if ($block_id) {
            // Get block info for logging
            $info_query = $conn->prepare("SELECT tab.*, t.name as trainer_name FROM trainer_availability_blocks tab JOIN trainers t ON tab.trainer_id = t.id WHERE tab.id = ?");
            $info_query->bind_param('i', $block_id);
            $info_query->execute();
            $block_info = $info_query->get_result()->fetch_assoc();

            $stmt = $conn->prepare("DELETE FROM trainer_availability_blocks WHERE id = ?");
            $stmt->bind_param('i', $block_id);
            $success = $stmt->execute();

            if ($success && $block_info) {
                $log_msg = "Unblocked schedule for {$block_info['trainer_name']} on {$block_info['date']} ({$block_info['session_time']})";
                ActivityLogger::log('schedule_unblocked', $block_info['trainer_name'], $block_info['trainer_id'], $log_msg);
            }

            echo json_encode(['success' => $success]);
            exit;
        }
    }

    if ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $conn->prepare("DELETE FROM trainer_availability_blocks WHERE id IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $success = $stmt->execute();

            if ($success) {
                ActivityLogger::log('schedule_bulk_unblocked', null, null, "Bulk unblocked " . count($ids) . " schedule blocks");
            }

            echo json_encode(['success' => $success]);
            exit;
        }
    }

    echo json_encode(['success' => false]);
    exit;
}

// Get filters
$trainer_filter = $_GET['trainer'] ?? 'all';
$session_filter = $_GET['session'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d', strtotime('+30 days'));

// Build query
$query = "
    SELECT tab.id, tab.trainer_id, tab.date, tab.session_time, tab.reason,
           tab.block_status, tab.created_at,
           t.name as trainer_name, t.specialization,
           u.username as blocked_by_name
    FROM trainer_availability_blocks tab
    JOIN trainers t ON tab.trainer_id = t.id
    LEFT JOIN users u ON tab.blocked_by = u.id
    WHERE tab.block_status = 'blocked'
";

$params = [];
$types = '';

if ($trainer_filter !== 'all') {
    $query .= " AND tab.trainer_id = ?";
    $params[] = intval($trainer_filter);
    $types .= 'i';
}

if ($session_filter !== 'all') {
    $query .= " AND tab.session_time = ?";
    $params[] = $session_filter;
    $types .= 's';
}

if ($date_from) {
    $query .= " AND tab.date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND tab.date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY tab.date ASC, t.name ASC, tab.session_time ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$blocks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get stats
$stats_query = "
    SELECT
        COUNT(*) as total_blocks,
        COUNT(DISTINCT trainer_id) as blocked_trainers,
        SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_blocks,
        SUM(CASE WHEN date < CURDATE() THEN 1 ELSE 0 END) as past_blocks
    FROM trainer_availability_blocks
    WHERE block_status = 'blocked'
";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get trainers for filter
$trainers = $conn->query("SELECT id, name FROM trainers WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Schedules - Fit & Brawl Admin</title>
    <link rel="icon" type="image/png" href="../../../images/favicon-admin.png">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/trainer-schedules.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <header class="page-header">
            <div>
                <h1>Trainer Schedule Management</h1>
                <p class="subtitle">Block trainer availability for vacations, meetings, and other events</p>
            </div>
            <button class="btn-primary" id="btnAddBlock">
                <i class="fa-solid fa-calendar-xmark"></i> Block Schedule
            </button>
        </header>

        <!-- Stats Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-ban"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_blocks']; ?></h3>
                    <p>Total Blocks</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['blocked_trainers']; ?></h3>
                    <p>Trainers with Blocks</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['upcoming_blocks']; ?></h3>
                    <p>Upcoming Blocks</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gray">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['past_blocks']; ?></h3>
                    <p>Past Blocks</p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <select id="trainerFilter" class="filter-dropdown">
                <option value="all">All Trainers</option>
                <?php foreach ($trainers as $trainer): ?>
                    <option value="<?php echo $trainer['id']; ?>" <?php echo $trainer_filter == $trainer['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($trainer['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select id="sessionFilter" class="filter-dropdown">
                <option value="all">All Sessions</option>
                <option value="Morning" <?php echo $session_filter === 'Morning' ? 'selected' : ''; ?>>Morning</option>
                <option value="Afternoon" <?php echo $session_filter === 'Afternoon' ? 'selected' : ''; ?>>Afternoon
                </option>
                <option value="Evening" <?php echo $session_filter === 'Evening' ? 'selected' : ''; ?>>Evening</option>
                <option value="All Day" <?php echo $session_filter === 'All Day' ? 'selected' : ''; ?>>All Day</option>
            </select>
            <input type="date" id="dateFrom" class="filter-dropdown" value="<?php echo $date_from; ?>">
            <input type="date" id="dateTo" class="filter-dropdown" value="<?php echo $date_to; ?>">
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActionsBar">
            <span id="selectedCount">0 selected</span>
            <button class="btn-delete">
                <i class="fa-solid fa-trash"></i> Delete Selected
            </button>
        </div>

        <!-- Table View -->
        <div class="table-container">
            <table class="schedules-table">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th>Trainer</th>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Reason</th>
                        <th>Blocked By</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($blocks)): ?>
                        <tr>
                            <td colspan="7" class="no-results">
                                <i class="fa-solid fa-calendar-check"
                                    style="font-size: 48px; color: #ccc; margin-bottom: 12px;"></i>
                                <p>No schedule blocks found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($blocks as $block): ?>
                            <?php
                            $is_past = strtotime($block['date']) < strtotime('today');
                            $row_class = $is_past ? 'past-block' : '';

                            $session_hours = [
                                'Morning' => '7-11 AM',
                                'Afternoon' => '1-5 PM',
                                'Evening' => '6-10 PM',
                                'All Day' => '7 AM - 10 PM'
                            ];
                            ?>
                            <tr class="block-row <?php echo $row_class; ?>" data-id="<?php echo $block['id']; ?>">
                                <td>
                                    <input type="checkbox" class="block-checkbox" value="<?php echo $block['id']; ?>">
                                </td>
                                <td>
                                    <div class="trainer-info">
                                        <strong><?php echo htmlspecialchars($block['trainer_name']); ?></strong>
                                        <span
                                            class="specialization"><?php echo htmlspecialchars($block['specialization']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <?php echo date('M d, Y', strtotime($block['date'])); ?>
                                        <span class="day-name"><?php echo date('l', strtotime($block['date'])); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="session-info">
                                        <strong><?php echo htmlspecialchars($block['session_time']); ?></strong>
                                        <span
                                            class="session-hours"><?php echo $session_hours[$block['session_time']] ?? ''; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $block['reason'] ? htmlspecialchars($block['reason']) : '<em style="color: #999;">No reason provided</em>'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($block['blocked_by_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <button class="btn-icon btn-delete-single" data-id="<?php echo $block['id']; ?>"
                                        title="Remove block">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Block Modal -->
    <div class="modal-overlay" id="addBlockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Block Trainer Schedule</h2>
                <button class="modal-close" id="closeModal">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addBlockForm">
                <div class="form-group">
                    <label for="trainer">Trainer *</label>
                    <select id="trainer" name="trainer_id" required>
                        <option value="">Select Trainer</option>
                        <?php foreach ($trainers as $trainer): ?>
                            <option value="<?php echo $trainer['id']; ?>">
                                <?php echo htmlspecialchars($trainer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Date *</label>
                    <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="session">Session *</label>
                    <select id="session" name="session_time" required>
                        <option value="All Day">All Day (7 AM - 10 PM)</option>
                        <option value="Morning">Morning (7-11 AM)</option>
                        <option value="Afternoon">Afternoon (1-5 PM)</option>
                        <option value="Evening">Evening (6-10 PM)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reason">Reason (optional)</label>
                    <textarea id="reason" name="reason" rows="3"
                        placeholder="e.g., Vacation, Meeting, Sick Leave"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelModal">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-ban"></i> Block Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/trainer-schedules.js"></script>
</body>

</html>
