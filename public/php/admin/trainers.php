<?php
session_start();
require_once '../../../includes/db_connect.php';

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get admin info if needed (optional, based on your system)
$admin_username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
$admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // Toggle trainer status
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
        $trainer_id = intval($_POST['trainer_id']);

        // Get current status
        $status_query = "SELECT status FROM trainers WHERE id = ?";
        $stmt = $conn->prepare($status_query);
        $stmt->bind_param("i", $trainer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $trainer = $result->fetch_assoc();

        if ($trainer) {
            // Determine new status (cycle through: Active -> Inactive -> On Leave -> Active)
            $current = $trainer['status'];
            $new_status = $current === 'Active' ? 'Inactive' : ($current === 'Inactive' ? 'On Leave' : 'Active');

            $update_query = "UPDATE trainers SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_status, $trainer_id);
            $stmt->execute();

            // Log activity
            $log_query = "INSERT INTO trainer_activity_log (trainer_id, admin_id, action, details) VALUES (?, ?, 'Status Changed', ?)";
            $details = "Status changed from $current to $new_status";
            $stmt = $conn->prepare($log_query);
            $stmt->bind_param("iis", $trainer_id, $admin_id, $details);
            $stmt->execute();

            echo json_encode(['success' => true, 'new_status' => $new_status]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Trainer not found']);
        }
        exit;
    }

    // Soft delete trainer
    if (isset($_POST['action']) && $_POST['action'] === 'delete_trainer') {
        $trainer_id = intval($_POST['trainer_id']);

        $delete_query = "UPDATE trainers SET deleted_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $trainer_id);
        $stmt->execute();

        // Log activity
        $log_query = "INSERT INTO trainer_activity_log (trainer_id, admin_id, action, details) VALUES (?, ?, 'Deleted', 'Trainer soft-deleted')";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("ii", $trainer_id, $admin_id);
        $stmt->execute();

        echo json_encode(['success' => true]);
        exit;
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialization_filter = isset($_GET['specialization']) ? $_GET['specialization'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Build query - With soft delete check
$query = "SELECT t.*, 
          (SELECT COUNT(DISTINCT ur.user_id) 
           FROM user_reservations ur 
           JOIN reservations r ON ur.reservation_id = r.id 
           WHERE r.trainer_id = t.id 
           AND ur.booking_status = 'confirmed' 
           AND ur.date = CURDATE()) as clients_today,
          (SELECT COUNT(*) 
           FROM user_reservations ur 
           JOIN reservations r ON ur.reservation_id = r.id 
           WHERE r.trainer_id = t.id 
           AND ur.booking_status = 'confirmed' 
           AND ur.date >= CURDATE()) as upcoming_bookings
          FROM trainers t 
          WHERE t.deleted_at IS NULL";

$params = [];
$types = '';

// Add search filter
if (!empty($search)) {
    $query .= " AND (t.name LIKE ? OR t.email LIKE ? OR t.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

// Add specialization filter
if ($specialization_filter !== 'all') {
    $query .= " AND t.specialization = ?";
    $params[] = $specialization_filter;
    $types .= 's';
}

// Add status filter
if ($status_filter !== 'all') {
    $query .= " AND t.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

// Add sorting
$allowed_sorts = ['name', 'email', 'specialization', 'status', 'created_at'];
if (!in_array($sort_by, $allowed_sorts)) {
    $sort_by = 'name';
}
$sort_order = strtoupper($sort_order) === 'DESC' ? 'DESC' : 'ASC';
$query .= " ORDER BY t.$sort_by $sort_order";

// Prepare and execute
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

// Bind parameters if any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$trainers_result = $stmt->get_result();

// Get statistics - With soft delete check and error handling
$stats_query = "SELECT 
                COUNT(*) as total_trainers,
                SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_trainers,
                SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive_trainers,
                SUM(CASE WHEN status = 'On Leave' THEN 1 ELSE 0 END) as on_leave_trainers
                FROM trainers WHERE deleted_at IS NULL";
$stats_result = $conn->query($stats_query);

if (!$stats_result) {
    die("Stats query failed: " . $conn->error);
}

$stats = $stats_result->fetch_assoc();

// Set defaults if no data
if (!$stats) {
    $stats = [
        'total_trainers' => 0,
        'active_trainers' => 0,
        'inactive_trainers' => 0,
        'on_leave_trainers' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainers Management - Admin Panel</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/trainers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="page-header">
            <div>
                <h1>Trainers Management</h1>
                <p class="subtitle">Manage your gym trainers and their schedules</p>
            </div>
            <a href="trainer_add.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add New Trainer
            </a>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['total_trainers'] ?></h3>
                    <p>Total Trainers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['active_trainers'] ?></h3>
                    <p>Active</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['inactive_trainers'] ?></h3>
                    <p>Inactive</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-umbrella-beach"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $stats['on_leave_trainers'] ?></h3>
                    <p>On Leave</p>
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search trainers by name, email, or phone..."
                    value="<?= htmlspecialchars($search) ?>">
            </div>

            <select id="specializationFilter" class="filter-select">
                <option value="all" <?= $specialization_filter === 'all' ? 'selected' : '' ?>>All Specializations</option>
                <option value="Gym" <?= $specialization_filter === 'Gym' ? 'selected' : '' ?>>Gym</option>
                <option value="MMA" <?= $specialization_filter === 'MMA' ? 'selected' : '' ?>>MMA</option>
                <option value="Boxing" <?= $specialization_filter === 'Boxing' ? 'selected' : '' ?>>Boxing</option>
                <option value="Muay Thai" <?= $specialization_filter === 'Muay Thai' ? 'selected' : '' ?>>Muay Thai
                </option>
            </select>

            <select id="statusFilter" class="filter-select">
                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Status</option>
                <option value="Active" <?= $status_filter === 'Active' ? 'selected' : '' ?>>Active</option>
                <option value="Inactive" <?= $status_filter === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                <option value="On Leave" <?= $status_filter === 'On Leave' ? 'selected' : '' ?>>On Leave</option>
            </select>

            <div class="view-toggle">
                <button class="view-btn active" data-view="table">
                    <i class="fas fa-table"></i>
                </button>
                <button class="view-btn" data-view="cards">
                    <i class="fas fa-th"></i>
                </button>
            </div>
        </div>

        <!-- Table View -->
        <div class="trainers-table-view active" id="tableView">
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <a
                                    href="?sort=name&order=<?= $sort_by === 'name' && $sort_order === 'ASC' ? 'DESC' : 'ASC' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $specialization_filter !== 'all' ? '&specialization=' . $specialization_filter : '' ?><?= $status_filter !== 'all' ? '&status=' . $status_filter : '' ?>">
                                    Name <?= $sort_by === 'name' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?>
                                </a>
                            </th>
                            <th>Contact</th>
                            <th>
                                <a
                                    href="?sort=specialization&order=<?= $sort_by === 'specialization' && $sort_order === 'ASC' ? 'DESC' : 'ASC' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $specialization_filter !== 'all' ? '&specialization=' . $specialization_filter : '' ?><?= $status_filter !== 'all' ? '&status=' . $status_filter : '' ?>">
                                    Specialization
                                    <?= $sort_by === 'specialization' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?>
                                </a>
                            </th>
                            <th>Clients Today</th>
                            <th>Upcoming</th>
                            <th>
                                <a
                                    href="?sort=status&order=<?= $sort_by === 'status' && $sort_order === 'ASC' ? 'DESC' : 'ASC' ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= $specialization_filter !== 'all' ? '&specialization=' . $specialization_filter : '' ?><?= $status_filter !== 'all' ? '&status=' . $status_filter : '' ?>">
                                    Status <?= $sort_by === 'status' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?>
                                </a>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($trainers_result->num_rows > 0): ?>
                            <?php while ($trainer = $trainers_result->fetch_assoc()): ?>
                                <tr data-trainer-id="<?= $trainer['id'] ?>">
                                    <td>
                                        <div class="trainer-info">
                                            <img src="<?= !empty($trainer['photo']) ? '../../../uploads/trainers/' . htmlspecialchars($trainer['photo']) : '../../../images/default-trainer.png' ?>"
                                                alt="<?= htmlspecialchars($trainer['name']) ?>" class="trainer-avatar">
                                            <span><?= htmlspecialchars($trainer['name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($trainer['email']) ?>
                                            </div>
                                            <div><i class="fas fa-phone"></i> <?= htmlspecialchars($trainer['phone']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="specialization-badge <?= strtolower(str_replace(' ', '-', $trainer['specialization'])) ?>">
                                            <?= htmlspecialchars($trainer['specialization']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= $trainer['clients_today'] ?></strong> / 3 sessions
                                    </td>
                                    <td>
                                        <strong><?= $trainer['upcoming_bookings'] ?></strong> bookings
                                    </td>
                                    <td>
                                        <span
                                            class="status-badge status-<?= strtolower(str_replace(' ', '-', $trainer['status'])) ?>"
                                            onclick="toggleStatus(<?= $trainer['id'] ?>)" style="cursor: pointer;"
                                            title="Click to change status">
                                            <?= htmlspecialchars($trainer['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="trainer_view.php?id=<?= $trainer['id'] ?>" class="btn-action btn-view"
                                                title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="trainer_edit.php?id=<?= $trainer['id'] ?>" class="btn-action btn-edit"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button
                                                onclick="deleteTrainer(<?= $trainer['id'] ?>, '<?= htmlspecialchars($trainer['name']) ?>')"
                                                class="btn-action btn-delete" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                    <i class="fas fa-inbox"
                                        style="font-size: 48px; margin-bottom: 12px; display: block;"></i>
                                    No trainers found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cards View -->
        <div class="trainers-cards-view" id="cardsView">
            <div class="cards-grid">
                <?php
                $trainers_result->data_seek(0); // Reset result pointer
                if ($trainers_result->num_rows > 0):
                    ?>
                    <?php while ($trainer = $trainers_result->fetch_assoc()): ?>
                        <div class="trainer-card" data-trainer-id="<?= $trainer['id'] ?>">
                            <div class="card-header">
                                <img src="<?= !empty($trainer['photo']) ? '../../../uploads/trainers/' . htmlspecialchars($trainer['photo']) : '../../../images/default-trainer.png' ?>"
                                    alt="<?= htmlspecialchars($trainer['name']) ?>" class="card-avatar">
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $trainer['status'])) ?>"
                                    onclick="toggleStatus(<?= $trainer['id'] ?>)" style="cursor: pointer;"
                                    title="Click to change status">
                                    <?= htmlspecialchars($trainer['status']) ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h3><?= htmlspecialchars($trainer['name']) ?></h3>
                                <span
                                    class="specialization-badge <?= strtolower(str_replace(' ', '-', $trainer['specialization'])) ?>">
                                    <?= htmlspecialchars($trainer['specialization']) ?>
                                </span>
                                <div class="card-info">
                                    <div class="info-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?= htmlspecialchars($trainer['email']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?= htmlspecialchars($trainer['phone']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-users"></i>
                                        <span><?= $trainer['clients_today'] ?> clients today</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span><?= $trainer['upcoming_bookings'] ?> upcoming</span>
                                    </div>
                                </div>
                                <?php if (!empty($trainer['bio'])): ?>
                                    <p class="card-bio">
                                        <?= htmlspecialchars(substr($trainer['bio'], 0, 100)) ?>
                                        <?= strlen($trainer['bio']) > 100 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <a href="trainer_view.php?id=<?= $trainer['id'] ?>" class="btn-secondary btn-small">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="trainer_edit.php?id=<?= $trainer['id'] ?>" class="btn-primary btn-small">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button
                                    onclick="deleteTrainer(<?= $trainer['id'] ?>, '<?= htmlspecialchars($trainer['name']) ?>')"
                                    class="btn-danger btn-small">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px; color: #999;">
                        <i class="fas fa-inbox" style="font-size: 64px; margin-bottom: 16px; display: block;"></i>
                        <p style="font-size: 18px;">No trainers found</p>

                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!--            Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-overlay">
            <div class="modal-content">
                <h3>Delete Trainer</h3>
                <p>Are you sure you want to delete <strong id="trainerNameToDelete"></strong>? This action can be undone
                    from the activity log.</p>
                <div class="modal-actions">
                    <button class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button class="btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="js/trainers.js"></script>
</body>

</html>