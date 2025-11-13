<?php
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

    if ($action === 'update_status') {
        $booking_id = intval($_POST['booking_id'] ?? 0);
        $new_status = $_POST['new_status'] ?? '';

        if ($booking_id && in_array($new_status, ['confirmed', 'completed', 'cancelled'])) {
            // Get booking details before update (for logging)
            if ($new_status === 'cancelled') {
                $info_query = "SELECT ur.id, u.username, t.name as trainer_name, ur.class_type,
                               ur.booking_date as date, ur.session_time
                               FROM user_reservations ur
                               JOIN users u ON ur.user_id = u.id
                               JOIN trainers t ON ur.trainer_id = t.id
                               WHERE ur.id = ?";
                $info_stmt = $conn->prepare($info_query);
                $info_stmt->bind_param('i', $booking_id);
                $info_stmt->execute();
                $booking_info = $info_stmt->get_result()->fetch_assoc();
            }

            $stmt = $conn->prepare("UPDATE user_reservations SET booking_status = ? WHERE id = ?");
            $stmt->bind_param('si', $new_status, $booking_id);
            $success = $stmt->execute();

            // Log cancellation
            if ($success && $new_status === 'cancelled' && isset($booking_info)) {
                $session_hours = $booking_info['session_time'] === 'Morning' ? '7-11 AM' :
                    ($booking_info['session_time'] === 'Afternoon' ? '1-5 PM' : '6-10 PM');
                $log_msg = "Reservation #$booking_id cancelled - Client: {$booking_info['username']}, Trainer: {$booking_info['trainer_name']}, Class: {$booking_info['class_type']}, Date: {$booking_info['date']} at {$booking_info['session_time']} ($session_hours)";
                ActivityLogger::log('reservation_cancelled', $booking_info['username'], $booking_id, $log_msg);
            }

            echo json_encode(['success' => $success]);
            exit;
        }
    }

    if ($action === 'bulk_update') {
        $ids = $_POST['ids'] ?? [];
        $new_status = $_POST['status'] ?? '';

        // Ensure $ids is an array
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        // Filter and sanitize IDs
        $ids = array_filter(array_map('intval', $ids));

        if (!empty($ids) && in_array($new_status, ['confirmed', 'completed', 'cancelled'])) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = 's' . str_repeat('i', count($ids));

            $stmt = $conn->prepare("UPDATE user_reservations SET booking_status = ? WHERE id IN ($placeholders)");
            $params = array_merge([$new_status], $ids);
            $stmt->bind_param($types, ...$params);
            $success = $stmt->execute();

            // Log bulk actions
            if ($success) {
                $count = count($ids);
                $ids_str = implode(', ', $ids);
                $action_name = $new_status === 'cancelled' ? 'reservation_bulk_cancelled' :
                              ($new_status === 'completed' ? 'reservation_bulk_completed' : 'reservation_bulk_confirmed');
                ActivityLogger::log($action_name, null, null, "Bulk update: $count reservation(s) marked as $new_status (IDs: $ids_str)");
            }

            echo json_encode(['success' => $success]);
            exit;
        }
    }

    echo json_encode(['success' => false]);
    exit;
}

// Get filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$trainer_filter = $_GET['trainer'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query - Updated for V2 schema (no reservations table)
$query = "
    SELECT ur.id, ur.user_id, ur.class_type, ur.booking_date as date,
           ur.session_time, ur.booking_status as status, ur.booked_at,
           u.username, u.email, u.avatar,
           t.id as trainer_id, t.name as trainer_name, t.specialization
    FROM user_reservations ur
    JOIN users u ON ur.user_id = u.id
    JOIN trainers t ON ur.trainer_id = t.id
    WHERE 1=1
";

$params = [];
$types = '';

if ($search) {
    $query .= " AND (u.username LIKE ? OR u.email LIKE ? OR t.name LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= 'sss';
}

if ($status_filter !== 'all') {
    $query .= " AND ur.booking_status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($trainer_filter !== 'all') {
    $query .= " AND t.id = ?";
    $params[] = intval($trainer_filter);
    $types .= 'i';
}

if ($date_from) {
    $query .= " AND ur.booking_date >= ?";
    $params[] = $date_from;
    $types .= 's';
}

if ($date_to) {
    $query .= " AND ur.booking_date <= ?";
    $params[] = $date_to;
    $types .= 's';
}

$query .= " ORDER BY ur.booking_date DESC, ur.session_time DESC";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get stats
$stats_query = "
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN booking_status = 'confirmed' THEN 1 ELSE 0 END) as upcoming,
        SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM user_reservations
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
    <title>Reservations Management - Fit & Brawl Admin</title>
    <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/reservations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header Section - Match products.php exactly -->
        <header class="page-header">
            <div>
                <h1>Reservations Management</h1>
                <p class="subtitle">View and manage all training session bookings</p>
            </div>
        </header>

        <!-- Stats Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['upcoming']; ?></h3>
                    <p>Upcoming</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['completed']; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['cancelled']; ?></h3>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="view-toggle">
            <button class="view-btn active" data-view="table">
                <i class="fa-solid fa-table"></i> Table View
            </button>
            <button class="view-btn" data-view="calendar">
                <i class="fa-solid fa-calendar"></i> Calendar View
            </button>
        </div>

        <!-- Toolbar - Match products.php exactly -->
        <div class="toolbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search by name, email, or trainer..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <select id="statusFilter" class="filter-dropdown">
                <option value="all">All Statuses</option>
                <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed
                </option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed
                </option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled
                </option>
            </select>
            <select id="trainerFilter" class="filter-dropdown">
                <option value="all">All Trainers</option>
                <?php foreach ($trainers as $trainer): ?>
                    <option value="<?php echo $trainer['id']; ?>" <?php echo $trainer_filter == $trainer['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($trainer['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="date" id="dateFrom" class="filter-dropdown" value="<?php echo $date_from; ?>">
            <input type="date" id="dateTo" class="filter-dropdown" value="<?php echo $date_to; ?>">
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActionsBar">
            <span id="selectedCount">0 selected</span>
            <button class="btn-complete">
                <i class="fa-solid fa-check"></i> Mark Complete
            </button>
            <button class="btn-cancel">
                <i class="fa-solid fa-xmark"></i> Cancel Selected
            </button>
        </div>

        <!-- Table View -->
        <div class="table-view active">
            <div class="table-container">
                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Client</th>
                            <th>Trainer</th>
                            <th>Class Type</th>
                            <th>Date & Time</th>
                            <th width="120">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="6" class="no-results">
                                    <i class="fa-solid fa-calendar-xmark"
                                        style="font-size: 48px; color: #ccc; margin-bottom: 12px;"></i>
                                    <p>No reservations found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <tr class="booking-row" data-id="<?php echo $booking['id']; ?>">
                                    <td>
                                        <input type="checkbox" class="booking-checkbox" value="<?php echo $booking['id']; ?>">
                                    </td>
                                    <td>
                                        <div class="client-info">
                                            <?php
                                            // Fix avatar path - use environment-aware paths
                                            $default_avatar = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='44' height='44' viewBox='0 0 24 24' fill='%23ddd'%3E%3Cpath d='M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z'/%3E%3C/svg%3E";
                                            $avatar_path = !empty($booking['avatar']) ? UPLOADS_PATH . '/avatars/' . htmlspecialchars($booking['avatar']) : $default_avatar;
                                            ?>
                                            <img src="<?php echo $avatar_path; ?>" alt="Avatar" class="client-avatar" onerror="this.src='<?php echo $default_avatar; ?>'">
                                            <div class="client-details">
                                                <h4><?php echo htmlspecialchars($booking['username']); ?></h4>
                                                <p><?php echo htmlspecialchars($booking['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['trainer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['class_type']); ?></td>
                                    <td>
                                        <div class="time-info">
                                            <?php
                                            // Display date
                                            if (!empty($booking['date']) && $booking['date'] != '0000-00-00') {
                                                echo date('M d, Y', strtotime($booking['date']));
                                            } else {
                                                echo 'No date set';
                                            }
                                            ?><br>
                                            <?php
                                            // Display session time
                                            if (!empty($booking['session_time'])) {
                                                $session_hours = [
                                                    'Morning' => '7:00 AM - 11:00 AM',
                                                    'Afternoon' => '1:00 PM - 5:00 PM',
                                                    'Evening' => '6:00 PM - 10:00 PM'
                                                ];
                                                echo '<strong>' . htmlspecialchars($booking['session_time']) . '</strong><br>';
                                                echo '<span style="font-size: 0.85em; color: #999;">' .
                                                    ($session_hours[$booking['session_time']] ?? 'Time not set') . '</span>';
                                            } else {
                                                echo 'No time set';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>"
                                            data-status="<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Calendar View -->
        <div class="calendar-container" id="calendarView">
            <div class="calendar-header">
                <button class="calendar-nav-btn" id="prevMonth"><i class="fa-solid fa-chevron-left"></i></button>
                <h3 id="currentMonth"></h3>
                <button class="calendar-nav-btn" id="nextMonth"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <div class="calendar">
                <div class="calendar-grid" id="calendarGrid"></div>
            </div>
        </div>
    </main>

    <!-- Day Bookings Modal -->
    <div class="day-modal-overlay" id="dayModalOverlay">
        <div class="day-modal" id="dayModal">
            <div class="day-modal-header">
                <h3 id="modalDate">Bookings for <span></span></h3>
                <button class="modal-close-btn" id="closeDayModal">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <div class="day-modal-body" id="dayBookingsList">
                <!-- Bookings will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        window.bookingsData = <?php echo json_encode($bookings); ?>;
    </script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/reservations.js"></script>
</body>

</html>
