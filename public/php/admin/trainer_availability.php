<?php
// Start output buffering to prevent stray output
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once('../../../includes/init.php');
require_once('../../../includes/activity_logger.php');
require_once('../../../includes/csrf_protection.php');
// Load environment variables (if needed for SMTP)
include_once __DIR__ . '/../../../includes/env_loader.php';
loadEnv(__DIR__ . '/../../.env');
require_once('../../../includes/mail_config.php');
require_once __DIR__ . '/../../../includes/email_template.php';
require_once __DIR__ . '/../../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Hide errors from breaking JSON responses for AJAX (still log them)
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

// Init logger
ActivityLogger::init($conn);

// =====================
// AJAX POST Handler
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? null;

    function jsonResponse($success, $message = '', $extra = []) {
        ob_clean();
        echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
        exit;
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->set_charset("utf8mb4");

    // -----------------
    // Add Block
    // -----------------
    if ($action === 'add_block') {
        $trainer_id   = intval($_POST['trainer_id'] ?? 0);
        $date         = $_POST['date'] ?? '';
        $session_from = $_POST['session_from'] ?? '';
        $session_to   = $_POST['session_to'] ?? '';
        $reason       = trim($_POST['reason'] ?? '');

        if (!$trainer_id || !$date || !$session_from || !$session_to) {
            jsonResponse(false, 'Trainer, date, start, and end times are required');
        }

        $block_start_time = $date . ' ' . date('H:i:s', strtotime($session_from));
        $block_end_time   = $date . ' ' . date('H:i:s', strtotime($session_to));
        $blocked_by       = (string) $_SESSION['user_id']; // Cast to string for DB

        try {
            // --- Check duplicate block ---
            $check_stmt = $conn->prepare("
                SELECT id FROM trainer_availability_blocks
                WHERE trainer_id = ? AND date = ? 
                  AND block_start_time = ? AND block_end_time = ? 
                  AND block_status = 'blocked'
            ");
            $check_stmt->bind_param('isss', $trainer_id, $date, $block_start_time, $block_end_time);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                jsonResponse(false, 'Block already exists for this trainer and time range');
            }

            // --- Insert new block ---
            $stmt = $conn->prepare("
                INSERT INTO trainer_availability_blocks
                (trainer_id, date, block_start_time, block_end_time, reason, blocked_by, block_status)
                VALUES (?, ?, ?, ?, ?, ?, 'blocked')
            ");
            $stmt->bind_param('isssss', $trainer_id, $date, $block_start_time, $block_end_time, $reason, $blocked_by);
            $stmt->execute();

            // --- Get trainer name ---
            $trainer_name_stmt = $conn->prepare("SELECT name FROM trainers WHERE id = ?");
            $trainer_name_stmt->bind_param('i', $trainer_id);
            $trainer_name_stmt->execute();
            $trainer_row = $trainer_name_stmt->get_result()->fetch_assoc();
            $trainer_name = $trainer_row['name'] ?? 'Unknown';

            // --- Log activity ---
            $log_msg = "Marked $trainer_name unavailable on $date from $session_from to $session_to" . ($reason ? " - Reason: $reason" : "");
            ActivityLogger::log('schedule_marked_unavailable', $trainer_name, $trainer_id, $log_msg);

            // --- Find overlapping reservations ---
            // The system uses users.id (varchar) as user_id in user_reservations.
            // Use LEFT JOIN users to obtain email/username.
            $res_stmt = $conn->prepare("
                SELECT ur.id, ur.user_id, ur.class_type, ur.start_time, ur.end_time,
                       u.email AS user_email, u.username AS user_name
                FROM user_reservations ur
                LEFT JOIN users u ON ur.user_id = u.id
                WHERE ur.trainer_id = ?
                  AND ur.booking_status = 'confirmed'
                  AND ur.start_time < ? AND ur.end_time > ?
            ");
            $res_stmt->bind_param('iss', $trainer_id, $block_end_time, $block_start_time);
            $res_stmt->execute();
            $reservations = $res_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Detect whether booking_status enum supports 'blocked'
            $colInfo = $conn->query("SHOW COLUMNS FROM user_reservations LIKE 'booking_status'")->fetch_assoc();
            $bookingStatusType = $colInfo['Type'] ?? '';
            $supportsTrainerUnavailable = (strpos($bookingStatusType, 'blocked') !== false);

            // Or, optionally, check for reservation_state column (fallback)
            $hasReservationState = false;
            $c = $conn->query("SHOW COLUMNS FROM user_reservations LIKE 'reservation_state'");
            if ($c && $c->num_rows > 0) $hasReservationState = true;

            // If neither supported, we will still send emails but report that a DB migration is required.
            $dbMigrationNeeded = (!$supportsTrainerUnavailable && !$hasReservationState);

            // Initialize booking conflict notifier
            require_once __DIR__ . '/../../../includes/booking_conflict_notifier.php';
            BookingConflictNotifier::init($conn);

            foreach ($reservations as $res) {
                $memberEmail = $res['user_email'] ?? null;
                $memberName  = $res['user_name'] ?? ($res['user_id'] ?? 'Member');

                // Build a readable time range
                $time_range = date('g:i A', strtotime($res['start_time'])) . ' - ' . date('g:i A', strtotime($res['end_time']));

                // --- Send reschedule/cancel email (best-effort) ---
                if (!empty($memberEmail)) {
                    try {
                        sendMemberBookingRescheduleOption(
                            $memberEmail,
                            $memberName,
                            $trainer_name,
                            date('Y-m-d', strtotime($res['start_time'])),
                            $time_range,
                            $res['class_type'] ?? '',
                            $reason,
                            $trainer_id
                        );
                    } catch (\Exception $e) {
                        error_log("Email failed for {$memberEmail}: " . $e->getMessage());
                    }
                } else {
                    error_log("Skipping email for reservation id {$res['id']} - email not found for user_id={$res['user_id']}");
                }

                // --- Mark reservation as "blocked" and create notification ---
                if ($supportsTrainerUnavailable) {
                    $upd = $conn->prepare("
                        UPDATE user_reservations
                        SET booking_status = 'blocked', unavailable_marked_at = NOW(), cancelled_at = NULL, updated_at = NOW()
                        WHERE id = ?
                    ");
                } elseif ($hasReservationState) {
                    // Use reservation_state as fallback.
                    $upd = $conn->prepare("
                        UPDATE user_reservations
                        SET reservation_state = 'blocked', unavailable_marked_at = NOW(), updated_at = NOW()
                        WHERE id = ?
                    ");
                } else {
                    // No supported column to mark as unavailable. Log and continue.
                    error_log("DB does not support marking reservations as blocked. Please run migration to add 'blocked' to booking_status or add reservation_state column.");
                    $upd = null;
                }

                if ($upd) {
                    $upd->bind_param('i', $res['id']);
                    $upd->execute();

                    // Create in-app notification for user
                    $booking_details = [
                        'trainer_name' => $trainer_name,
                        'booking_date' => date('Y-m-d', strtotime($res['start_time'])),
                        'start_time' => $res['start_time'],
                        'end_time' => $res['end_time'],
                        'class_type' => $res['class_type'] ?? 'training'
                    ];
                    
                    BookingConflictNotifier::notifyBlockedBooking(
                        $res['id'],
                        $res['user_id'],
                        $booking_details,
                        $reason,
                        $_SESSION['user_id'] ?? null
                    );
                }
            }

            // --- Return success (include a hint if DB migration is still needed) ---
            if ($dbMigrationNeeded) {
                jsonResponse(true, 'Trainer marked unavailable and affected members notified. NOTE: Database migration required to store "unavailable" state for reservations (see admin message).');
            } else {
                jsonResponse(true, 'Trainer marked unavailable and affected reservations moved to Unavailable.');
            }

        } catch (\Exception $e) {
            // Log the real error for debugging
            error_log("Add block error: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
            // Return a helpful message (remove detailed message in production if leaking info is a concern)
            jsonResponse(false, 'An error occurred while marking block: ' . $e->getMessage());
        }
    }

    // -----------------
    // Delete Block
    // -----------------
if ($action === 'delete_block') {
    $block_id = intval($_POST['block_id'] ?? 0);
    if (!$block_id) jsonResponse(false, 'Invalid block ID');

    // Get block info
    $stmt = $conn->prepare("SELECT trainer_id, date, block_start_time, block_end_time FROM trainer_availability_blocks WHERE id = ?");
    $stmt->bind_param('i', $block_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $block = $result->fetch_assoc();

    // Delete the block
    $stmt = $conn->prepare("DELETE FROM trainer_availability_blocks WHERE id = ?");
    $stmt->bind_param('i', $block_id);
    $success = $stmt->execute();

    if ($success && $block) {
        // Update all affected bookings back to confirmed based on start_time and end_time
        $stmt = $conn->prepare("
            UPDATE user_reservations
            SET booking_status = 'confirmed'
            WHERE trainer_id = ?
              AND booking_date = ?
              AND start_time >= ?
              AND end_time <= ?
              AND booking_status = 'blocked'
        ");
        $stmt->bind_param(
            'isss',
            $block['trainer_id'],
            $block['date'],
            $block['block_start_time'],
            $block['block_end_time']
        );
        $stmt->execute();

        if ($stmt->error) {
            jsonResponse(false, "Failed to restore bookings: " . $stmt->error);
        }
    }

    jsonResponse($success);
}
    // -----------------
    // Bulk Delete
    // -----------------
    if ($action === 'bulk_delete') {
        $ids = $_POST['ids'] ?? [];
        if (empty($ids)) jsonResponse(false, 'No entries selected');

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $conn->prepare("DELETE FROM trainer_availability_blocks WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
        $success = $stmt->execute();
        jsonResponse($success);
    }

    jsonResponse(false, 'Unknown action');
}

// -------------------------
// Page Filters & Data
// -------------------------
$trainer_filter = $_GET['trainer'] ?? 'all';
$session_filter = $_GET['session'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d', strtotime('+30 days'));

// Fetch trainer blocks
$query = "
    SELECT 
        tab.id, tab.trainer_id, tab.date, tab.session_time, tab.reason,
        tab.block_status, tab.created_at,
        tab.block_start_time, tab.block_end_time,
        t.name as trainer_name, t.specialization,
        u.username as marked_by_name
    FROM trainer_availability_blocks tab
    JOIN trainers t ON tab.trainer_id = t.id
    LEFT JOIN users u ON tab.blocked_by = u.id
    WHERE tab.block_status = 'blocked'
";
$params = []; $types = '';

if ($trainer_filter !== 'all') { $query .= " AND tab.trainer_id = ?"; $params[] = intval($trainer_filter); $types .= 'i'; }
if ($session_filter !== 'all') { $query .= " AND tab.session_time = ?"; $params[] = $session_filter; $types .= 's'; }
if ($date_from) { $query .= " AND tab.date >= ?"; $params[] = $date_from; $types .= 's'; }
if ($date_to) { $query .= " AND tab.date <= ?"; $params[] = $date_to; $types .= 's'; }

$query .= " ORDER BY tab.date ASC, t.name ASC, tab.session_time ASC";

$stmt = $conn->prepare($query);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$blocks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Stats
$stats = $conn->query("
    SELECT
        COUNT(*) as total_blocks,
        COUNT(DISTINCT trainer_id) as blocked_trainers,
        SUM(CASE WHEN date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_blocks,
        SUM(CASE WHEN date < CURDATE() THEN 1 ELSE 0 END) as past_blocks
    FROM trainer_availability_blocks
    WHERE block_status = 'blocked'
")->fetch_assoc();

// Trainers for filters
$trainers = $conn->query("SELECT id, name FROM trainers WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
    

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Schedules - Fit & Brawl Admin</title>
    <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/trainer_availability.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <header class="page-header">
            <div>
                <h1>Trainer Schedule Management</h1>
                <p class="subtitle">Mark trainer unavailability for vacations, meetings, and other events</p>
            </div>
            <button class="btn-primary" id="btnAddBlock">
                <i class="fa-solid fa-calendar-xmark"></i> Mark Unavailable
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
                    <p>Total Unavailabilities</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['blocked_trainers']; ?></h3>
                    <p>Trainers with Time Off</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['upcoming_blocks']; ?></h3>
                    <p>Time Offs</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon gray">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['past_blocks']; ?></h3>
                    <p>Past Time Off</p>
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
                        <th>Marked By</th>
                        <th width="100">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($blocks)): ?>
                        <tr>
                            <td colspan="7" class="no-results">
                                <i class="fa-solid fa-calendar-check"
                                    style="font-size: 48px; color: #ccc; margin-bottom: 12px;"></i>
                                <p>No unavailable schedules found</p>
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
                                        <strong>
                                            <?php echo htmlspecialchars($block['session_time']); ?>
                                        </strong>

                                        <?php if (!empty($block['block_start_time']) && !empty($block['block_end_time'])): ?>
                                            <span class="session-hours">
                                                <?php 
                                                    echo date('g:i A', strtotime($block['block_start_time'])) 
                                                        . ' - ' . 
                                                        date('g:i A', strtotime($block['block_end_time']));
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <?php echo $block['reason'] ? htmlspecialchars($block['reason']) : '<em style="color: #999;">No reason provided</em>'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($block['marked_by_name'] ?? 'Unknown'); ?></td>
                                <td>
                                    <button class="btn-icon btn-delete-single" data-id="<?php echo $block['id']; ?>"
                                        title="Remove time off">
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
                <h2>Mark Trainer Unavailable</h2>
                <button class="modal-close" id="closeModal">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            <form id="addBlockForm" method="POST">
                <?= CSRFProtection::getTokenField(); ?>
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
                    <label>Trainer Available Time *</label>
                    <div>
                        <div  class="form-group">
                            <label for="session_from">Start Time</label>
                            <select id="sessionFrom" name="session_from" required>
                                <option value="">From</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="session_from">End Time</label>
                            <select id="sessionTo" name="session_to" required>
                                <option value="">To</option>
                            </select>
                        </div>
                        
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Reason (optional)</label>
                    <textarea id="reason" name="reason" rows="3"
                        placeholder="e.g., Vacation, Meeting, Sick Leave"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-secondary" id="cancelModal">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-ban"></i> Mark Unavailable
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
    <script src="<?= PUBLIC_PATH ?>/php/admin/js/trainer_availability.js?v=1"></script>
    
</body>

</html>