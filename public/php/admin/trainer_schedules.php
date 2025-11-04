<?php
session_start();
require_once '../../../includes/db_connect.php';

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'] ?? null;

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    if (isset($_POST['action']) && $_POST['action'] === 'update_schedule') {
        $trainer_id = intval($_POST['trainer_id']);
        $day_offs = $_POST['day_offs'] ?? [];

        // Start transaction
        $conn->begin_transaction();

        try {
            // First, set all days as working days
            $update_all = "UPDATE trainer_day_offs SET is_day_off = FALSE WHERE trainer_id = ?";
            $stmt = $conn->prepare($update_all);
            $stmt->bind_param("i", $trainer_id);
            $stmt->execute();

            // Then mark selected days as day-offs
            if (!empty($day_offs)) {
                $placeholders = implode(',', array_fill(0, count($day_offs), '?'));
                $update_days = "UPDATE trainer_day_offs SET is_day_off = TRUE 
                               WHERE trainer_id = ? AND day_of_week IN ($placeholders)";
                $stmt = $conn->prepare($update_days);
                
                $types = str_repeat('s', count($day_offs));
                $params = array_merge([$trainer_id], $day_offs);
                $stmt->bind_param("i$types", ...$params);
                $stmt->execute();
            }

            // Log activity
            $day_off_list = !empty($day_offs) ? implode(', ', $day_offs) : 'None';
            $log_query = "INSERT INTO trainer_activity_log (trainer_id, admin_id, action, details) 
                         VALUES (?, ?, 'Schedule Updated', ?)";
            $details = "Day-offs updated: $day_off_list";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("iis", $trainer_id, $admin_id, $details);
            $stmt->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}

// Get all active trainers with their day-offs
$trainers_query = "
    SELECT t.id, t.name, t.email, t.specialization, t.photo, t.status,
           GROUP_CONCAT(CASE WHEN td.is_day_off = TRUE THEN td.day_of_week END ORDER BY 
               FIELD(td.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
           ) as day_offs,
           COUNT(CASE WHEN td.is_day_off = TRUE THEN 1 END) as day_off_count
    FROM trainers t
    LEFT JOIN trainer_day_offs td ON t.id = td.trainer_id
    WHERE t.deleted_at IS NULL
    GROUP BY t.id, t.name, t.email, t.specialization, t.photo, t.status
    ORDER BY t.name ASC
";

$trainers_result = $conn->query($trainers_query);

// Get statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT t.id) as total_trainers,
        COUNT(DISTINCT CASE WHEN t.status = 'Active' THEN t.id END) as active_trainers,
        AVG(day_off_count) as avg_day_offs
    FROM trainers t
    LEFT JOIN (
        SELECT trainer_id, COUNT(*) as day_off_count 
        FROM trainer_day_offs 
        WHERE is_day_off = TRUE 
        GROUP BY trainer_id
    ) dc ON t.id = dc.trainer_id
    WHERE t.deleted_at IS NULL
";
$stats = $conn->query($stats_query)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Schedules - Admin Panel</title>
    <link rel="icon" type="image/png" href="../../../images/favicon-admin.png">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/trainer_schedules.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>Trainer Schedules</h1>
                <p class="subtitle">Manage trainer weekly day-off schedules</p>
            </div>
            <div class="header-actions">
                <div class="view-toggle">
                    <button class="toggle-btn active" data-view="calendar" title="Calendar View">
                        <i class="fas fa-calendar-week"></i>
                    </button>
                    <button class="toggle-btn" data-view="cards" title="Card View">
                        <i class="fas fa-th-large"></i>
                    </button>
                    <button class="toggle-btn" data-view="table" title="Table View">
                        <i class="fas fa-table"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Full-Time Schedule:</strong> Trainers must have exactly 2 days off per week. 
                Select the days each trainer will be unavailable. The system will automatically prevent bookings on their day-offs.
            </div>
        </div>

        <!-- Search and Filter Controls -->
        <div class="controls-section">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchTrainer" placeholder="Search trainers by name...">
            </div>
            <div class="filter-group">
                <select id="filterStatus" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                <select id="filterSpecialization" class="filter-select">
                    <option value="">All Specializations</option>
                    <?php
                    $spec_query = "SELECT DISTINCT specialization FROM trainers WHERE deleted_at IS NULL ORDER BY specialization";
                    $spec_result = $conn->query($spec_query);
                    while ($spec = $spec_result->fetch_assoc()):
                    ?>
                        <option value="<?= htmlspecialchars($spec['specialization']) ?>">
                            <?= htmlspecialchars($spec['specialization']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select id="sortBy" class="filter-select">
                    <option value="name">Sort by Name</option>
                    <option value="days_off">Sort by Days Off</option>
                    <option value="status">Sort by Status</option>
                </select>
            </div>
        </div>

        <!-- Weekly Calendar View -->
        <div id="calendarView" class="view-container active">
            <?php
            // Calculate coverage per day
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $coverage = [];
            $trainers_result->data_seek(0); // Reset pointer
            
            foreach ($days as $day) {
                $coverage[$day] = ['available' => 0, 'off' => 0, 'trainers' => []];
            }
            
            while ($trainer = $trainers_result->fetch_assoc()) {
                $day_offs_query = "SELECT day_of_week, is_day_off FROM trainer_day_offs WHERE trainer_id = ?";
                $stmt = $conn->prepare($day_offs_query);
                $stmt->bind_param("i", $trainer['id']);
                $stmt->execute();
                $day_offs_result = $stmt->get_result();
                
                while ($row = $day_offs_result->fetch_assoc()) {
                    if ($row['is_day_off']) {
                        $coverage[$row['day_of_week']]['off']++;
                    } else {
                        $coverage[$row['day_of_week']]['available']++;
                        $coverage[$row['day_of_week']]['trainers'][] = $trainer['name'];
                    }
                }
            }
            
            $trainers_result->data_seek(0); // Reset again for other views
            ?>
            
            <div class="calendar-grid">
                <?php foreach ($days as $day): 
                    $available = $coverage[$day]['available'];
                    $total = $stats['active_trainers'];
                    $percentage = $total > 0 ? ($available / $total) * 100 : 0;
                    
                    // Determine warning level
                    $warning_class = '';
                    if ($percentage < 40) {
                        $warning_class = 'critical';
                    } elseif ($percentage < 60) {
                        $warning_class = 'warning';
                    }
                ?>
                    <div class="calendar-day <?= $warning_class ?>">
                        <div class="day-header">
                            <h3><?= $day ?></h3>
                            <span class="day-date"><?= substr($day, 0, 3) ?></span>
                        </div>
                        <div class="day-stats">
                            <div class="stats-row">
                                <span class="stat-available">
                                    <i class="fas fa-check-circle"></i> <?= $available ?> Available
                                </span>
                                <span class="stat-off">
                                    <i class="fas fa-times-circle"></i> <?= $coverage[$day]['off'] ?> Off
                                </span>
                            </div>
                        </div>
                        <?php if ($warning_class): ?>
                            <div class="coverage-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?= $warning_class === 'critical' ? 'Critical Coverage' : 'Low Coverage' ?>
                            </div>
                        <?php endif; ?>
                        <div class="trainers-list">
                            <?php foreach (array_slice($coverage[$day]['trainers'], 0, 3) as $trainer_name): ?>
                                <div class="trainer-chip"><?= htmlspecialchars($trainer_name) ?></div>
                            <?php endforeach; ?>
                            <?php if (count($coverage[$day]['trainers']) > 3): 
                                $remaining_trainers = array_slice($coverage[$day]['trainers'], 3);
                                $all_trainers = $coverage[$day]['trainers'];
                                $remaining_count = count($remaining_trainers);
                            ?>
                                <div class="trainer-chip more" 
                                     data-all-trainers="<?= htmlspecialchars(implode('|', $all_trainers)) ?>"
                                     data-day="<?= $day ?>"
                                     data-total="<?= count($all_trainers) ?>"
                                     onclick="showAllTrainers(this)">
                                    +<?= $remaining_count ?> more
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Card View -->
        <div id="cardsView" class="view-container schedules-container">
            <?php if ($trainers_result->num_rows > 0): ?>
                <?php while ($trainer = $trainers_result->fetch_assoc()): 
                    // Get detailed day-off information
                    $day_offs_query = "SELECT day_of_week, is_day_off FROM trainer_day_offs WHERE trainer_id = ?";
                    $stmt = $conn->prepare($day_offs_query);
                    $stmt->bind_param("i", $trainer['id']);
                    $stmt->execute();
                    $day_offs_result = $stmt->get_result();
                    $schedule = [];
                    while ($row = $day_offs_result->fetch_assoc()) {
                        $schedule[$row['day_of_week']] = $row['is_day_off'];
                    }
                    
                    // Check if exactly 2 days off
                    $is_compliant = $trainer['day_off_count'] == 2;
                ?>
                    <div class="trainer-schedule-card <?= !$is_compliant ? 'non-compliant' : '' ?></div>" 
                         data-trainer-name="<?= htmlspecialchars($trainer['name']) ?>"
                         data-status="<?= htmlspecialchars($trainer['status']) ?>"
                         data-specialization="<?= htmlspecialchars($trainer['specialization']) ?>"
                         data-days-off="<?= $trainer['day_off_count'] ?>">
                        <div class="trainer-info">
                            <div class="trainer-avatar">
                                <?php if (!empty($trainer['photo'])): ?>
                                    <img src="../../../uploads/trainers/<?= htmlspecialchars($trainer['photo']) ?>" 
                                         alt="<?= htmlspecialchars($trainer['name']) ?>">
                                <?php else: ?>
                                    <i class="fas fa-user-tie"></i>
                                <?php endif; ?>
                            </div>
                            <div class="trainer-details">
                                <h3><?= htmlspecialchars($trainer['name']) ?></h3>
                                <p class="specialization"><?= htmlspecialchars($trainer['specialization']) ?></p>
                                <span class="status-badge status-<?= strtolower($trainer['status']) ?>">
                                    <?= $trainer['status'] ?>
                                </span>
                            </div>
                            <div class="day-off-summary">
                                <div class="day-off-count <?= !$is_compliant ? 'warning' : 'success' ?>">
                                    <i class="fas fa-calendar-xmark"></i>
                                    <span><?= $trainer['day_off_count'] ?> day<?= $trainer['day_off_count'] != 1 ? 's' : '' ?> off</span>
                                </div>
                                <?php if (!$is_compliant): ?>
                                    <div class="compliance-badge">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?= $trainer['day_off_count'] < 2 ? 'Needs more days off' : 'Too many days off' ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form class="schedule-form" data-trainer-id="<?= $trainer['id'] ?>">
                            <div class="validation-counter">
                                <i class="fas fa-calendar-check"></i>
                                <span>Selected: <strong class="days-selected"><?= $trainer['day_off_count'] ?></strong> / 2 days off</span>
                            </div>
                            <div class="days-grid">
                                <?php 
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($days as $day): 
                                    $is_checked = isset($schedule[$day]) && $schedule[$day] == 1;
                                    $short_day = substr($day, 0, 3);
                                ?>
                                    <label class="day-checkbox <?= $is_checked ? 'checked' : '' ?>">
                                        <input type="checkbox" 
                                               name="day_offs[]" 
                                               value="<?= $day ?>" 
                                               <?= $is_checked ? 'checked' : '' ?>>
                                        <div class="day-label">
                                            <span class="day-full"><?= $day ?></span>
                                            <span class="day-short"><?= $short_day ?></span>
                                        </div>
                                        <div class="checkbox-indicator">
                                            <i class="fas fa-times"></i>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div class="schedule-actions">
                                <button type="submit" class="btn-save" disabled>
                                    <i class="fas fa-save"></i> Save Schedule
                                </button>
                                <button type="button" class="btn-reset">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-trainers">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Trainers Found</h3>
                    <p>Add trainers to manage their schedules</p>
                    <a href="trainer_add.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Add Trainer
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Table View -->
        <div id="tableView" class="view-container">
            <div class="table-wrapper">
                <table class="schedules-table">
                    <thead>
                        <tr>
                            <th>Trainer</th>
                            <th>Specialization</th>
                            <th>Status</th>
                            <th class="text-center">Mon</th>
                            <th class="text-center">Tue</th>
                            <th class="text-center">Wed</th>
                            <th class="text-center">Thu</th>
                            <th class="text-center">Fri</th>
                            <th class="text-center">Sat</th>
                            <th class="text-center">Sun</th>
                            <th class="text-center">Compliance</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $trainers_result->data_seek(0); // Reset pointer
                        while ($trainer = $trainers_result->fetch_assoc()): 
                            $day_offs_query = "SELECT day_of_week, is_day_off FROM trainer_day_offs WHERE trainer_id = ?";
                            $stmt = $conn->prepare($day_offs_query);
                            $stmt->bind_param("i", $trainer['id']);
                            $stmt->execute();
                            $day_offs_result = $stmt->get_result();
                            $schedule = [];
                            while ($row = $day_offs_result->fetch_assoc()) {
                                $schedule[$row['day_of_week']] = $row['is_day_off'];
                            }
                            $is_compliant = $trainer['day_off_count'] == 2;
                        ?>
                            <tr class="trainer-row <?= !$is_compliant ? 'non-compliant' : '' ?>"
                                data-trainer-name="<?= htmlspecialchars($trainer['name']) ?>"
                                data-status="<?= htmlspecialchars($trainer['status']) ?>"
                                data-specialization="<?= htmlspecialchars($trainer['specialization']) ?>"
                                data-days-off="<?= $trainer['day_off_count'] ?>">
                                <td class="trainer-cell">
                                    <div class="trainer-info-compact">
                                        <div class="trainer-avatar-small">
                                            <?php if (!empty($trainer['photo'])): ?>
                                                <img src="../../../uploads/trainers/<?= htmlspecialchars($trainer['photo']) ?>" 
                                                     alt="<?= htmlspecialchars($trainer['name']) ?>">
                                            <?php else: ?>
                                                <i class="fas fa-user-tie"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span class="trainer-name"><?= htmlspecialchars($trainer['name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($trainer['specialization']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($trainer['status']) ?>">
                                        <?= $trainer['status'] ?>
                                    </span>
                                </td>
                                <?php 
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                foreach ($days as $day): 
                                    $is_off = isset($schedule[$day]) && $schedule[$day] == 1;
                                ?>
                                    <td class="day-cell text-center">
                                        <span class="day-status <?= $is_off ? 'off' : 'working' ?>" 
                                              title="<?= $is_off ? 'Day Off' : 'Working' ?>">
                                            <i class="fas fa-<?= $is_off ? 'times-circle' : 'check-circle' ?>"></i>
                                        </span>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-center">
                                    <?php if ($is_compliant): ?>
                                        <span class="compliance-badge success">
                                            <i class="fas fa-check-circle"></i> Compliant
                                        </span>
                                    <?php else: ?>
                                        <span class="compliance-badge warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?= $trainer['day_off_count'] ?>/2
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn-icon btn-edit-schedule" 
                                            data-trainer-id="<?= $trainer['id'] ?>"
                                            data-trainer-name="<?= htmlspecialchars($trainer['name']) ?>"
                                            title="Edit Schedule">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Edit Schedule Modal -->
    <div class="modal" id="editScheduleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-calendar-alt"></i> Edit Schedule</h2>
                <button class="modal-close" onclick="closeEditModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="trainer-modal-info">
                    <h3 id="modalTrainerName"></h3>
                </div>
                <form id="modalScheduleForm">
                    <input type="hidden" id="modalTrainerId" name="trainer_id">
                    <div class="validation-counter">
                        <i class="fas fa-calendar-check"></i>
                        <span>Selected: <strong class="days-selected">0</strong> / 2 days off</span>
                    </div>
                    <div class="days-grid modal-days-grid">
                        <?php 
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day): 
                            $short_day = substr($day, 0, 3);
                        ?>
                            <label class="day-checkbox">
                                <input type="checkbox" name="day_offs[]" value="<?= $day ?>">
                                <div class="day-label">
                                    <span class="day-full"><?= $day ?></span>
                                    <span class="day-short"><?= $short_day ?></span>
                                </div>
                                <div class="checkbox-indicator">
                                    <i class="fas fa-times"></i>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn-save" disabled>
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Success Toast -->
    <div class="toast" id="successToast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Schedule updated successfully!</span>
    </div>

    <script src="js/trainer_schedules.js"></script>
</body>

</html>
