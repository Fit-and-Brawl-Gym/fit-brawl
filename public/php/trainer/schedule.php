<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/session_manager.php';
require_once __DIR__ . '/../../../includes/config.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in and is a trainer
if (!SessionManager::isLoggedIn() || !isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer') {
    header('Location: ../login.php');
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['email']);
$userName = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Determine avatar source for logged-in users
$avatarSrc = '../../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../../images/account-icon.svg";
}

// Get trainer information
$trainer_id = null;
$trainer_name = '';
$trainer_specialization = '';


if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Get trainer details from trainers table based on user email/name
    $trainer_query = "SELECT id, name, specialization FROM trainers WHERE email = ? AND status = 'Active' LIMIT 1";
    $stmt = $conn->prepare($trainer_query);
    if ($stmt) {
        $trainer_email = $_SESSION['email'] ?? '';
        $stmt->bind_param("s", $trainer_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $trainer_id = $row['id'];
            $trainer_name = $row['name'];
            $trainer_specialization = $row['specialization'];
        } else {
            // Debug helper if no match found
            error_log("Trainer not found for email: " . $trainer_email);
        }

        $stmt->close();
          // Fetch bookings by status
    function getTrainerBookings($conn, $trainer_id, $status) {
        $bookings = [];
        $query = "SELECT ur.*, u.username AS member_name, u.email AS member_email
                  FROM user_reservations ur
                  JOIN users u ON ur.user_id = u.id
                  WHERE ur.trainer_id=? AND ur.booking_status=? ";
        if ($status === 'confirmed') $query .= "AND ur.booking_date >= CURDATE() ";
        $query .= "ORDER BY ur.booking_date ASC, FIELD(ur.session_time,'Morning','Afternoon','Evening')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("is", $trainer_id, $status);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $bookings[] = $row;
        $stmt->close();
        return $bookings;
    }

    $upcoming_bookings = getTrainerBookings($conn, $trainer_id, 'confirmed');
    $past_bookings = getTrainerBookings($conn, $trainer_id, 'completed');
    $cancelled_bookings = getTrainerBookings($conn, $trainer_id, 'cancelled');
    }
    
$upcoming_bookings = [];
    // Fetch upcoming bookings (next 5 confirmed bookings starting from today)
    if ($trainer_id) {
        $upcoming_query = "
            SELECT ur.*, u.username AS member_name, u.email AS member_email
            FROM user_reservations ur
            JOIN users u ON ur.user_id = u.id
            WHERE ur.trainer_id = ?
            AND ur.booking_status = 'confirmed'
            AND ur.booking_date >= CURDATE()
            ORDER BY ur.booking_date ASC,
                    FIELD(ur.session_time, 'Morning', 'Afternoon', 'Evening')
        ";
        $sessionRanges = [
    'Morning'   => '7-11 AM',
    'Afternoon' => '1-5 PM',
    'Evening'   => '6-10 PM'
];
        $stmt = $conn->prepare($upcoming_query);
        if ($stmt) {
            $stmt->bind_param("i", $trainer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $upcoming_bookings[] = $row;
            }
            $stmt->close();
        }
    }
    
}
// Fetch distinct class types for this trainer
$membershipClassTypes = [];

if ($trainer_id) {
    $class_query = "
        SELECT DISTINCT class_type
        FROM user_reservations
        WHERE trainer_id = ?
        ORDER BY class_type ASC
    ";
    $stmt = $conn->prepare($class_query);
    if ($stmt) {
        $stmt->bind_param("i", $trainer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (!empty($row['class_type'])) {
                $membershipClassTypes[] = $row['class_type'];
            }
        }
        $stmt->close();
    }
}

// Set variables for header
$pageTitle = "Schedule - Fit and Brawl Trainer";
$currentPage = "schedule";
$additionalCSS = [PUBLIC_PATH . "/css/pages/trainer/schedule.css?v=" . time()];


// Include header
require_once '../../../includes/trainer_header.php';
?>
    <!--Main Content-->
    <main class="schedule-page">
    <div class="schedule-container">
        <div class="page-header">
            <h1 class="page-title">TRAINING SCHEDULE</h1>
            <?php if ($trainer_name): ?>
            <div class="trainer-info">
                <i class="fas fa-user-check"></i>
                <span>Trainer: <strong><?= htmlspecialchars($trainer_name) ?></strong> | Specialization: <strong><?= htmlspecialchars($trainer_specialization) ?></strong></span>
            </div>
            <?php endif; ?>
        </div>

       <div class="my-bookings-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-calendar-alt"></i>
                    My Bookings
                </h2>
                <div class="bookings-controls">
                    <div class="bookings-filter">
                        <label for="classFilter" class="filter-label">
                            <i class="fas fa-filter"></i>
                            Filter by Class:
                        </label>
                        <select id="classFilter" class="class-filter-dropdown">
                            <option value="all">All Classes</option>
                            <?php if (!empty($membershipClassTypes)): ?>
                                <?php foreach ($membershipClassTypes as $classType): ?>
                                    <option value="<?= htmlspecialchars($classType) ?>">
                                        <?= htmlspecialchars($classType) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
            <div class="bookings-tabs">
                <button class="tab-btn active" data-tab="upcoming">
                    Upcoming <span class="tab-count"><?= count($upcoming_bookings) ?></span>
                </button>
                <button class="tab-btn" data-tab="past">
                    Past <span class="tab-count"><?= count($past_bookings) ?></span>
                </button>
                <button class="tab-btn" data-tab="cancelled">
                    Cancelled <span class="tab-count"><?= count($cancelled_bookings) ?></span>
                </button>
            </div>
        </div>
    </div>
    <hr>
    <div class="bookings-content">
        <!-- Upcoming Bookings -->
        
       <div class="bookings-list upcoming active" id="upcomingBookings">
        <h3 class="booking-section-title">Upcoming Bookings</h3>
            <?php if (!empty($upcoming_bookings)): ?>
                <?php foreach ($upcoming_bookings as $b): ?>
                    <div class="booking-card">
                        
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-user"></i> Client:</span>
                            <span class="booking-value"><?= htmlspecialchars($b['member_name']) ?></span>
                        </div>
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-calendar"></i> Date:</span>
                            <span class="booking-value"><?= date('M j, Y', strtotime($b['booking_date'])) ?></span>
                        </div>
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-clock"></i> Session:</span>
                            <span class="booking-value">
                                <?= htmlspecialchars($b['session_time'] . ' (' . ($sessionRanges[$b['session_time']] ?? 'N/A') . ')') ?>
                            </span>
                        </div>
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-dumbbell"></i> Class:</span>
                            <span class="booking-value"><?= htmlspecialchars($b['class_type'] ?? 'General Training') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-bookings"><i class="fas fa-calendar-times"></i> No upcoming bookings.</p>
            <?php endif; ?>
        </div>

        <!-- Past Bookings -->
        <div class="bookings-list past" id="pastBookings">
            <h3 class="booking-section-title">Past Bookings</h3>
            <?php if (!empty($past_bookings)): ?>
                <?php foreach ($past_bookings as $b): ?>
                    <div class="booking-card">
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-user"></i> Client:</span>
                            <span class="booking-value"><?= htmlspecialchars($b['member_name']) ?></span>
                        </div>
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-calendar"></i> Date:</span>
                            <span class="booking-value"><?= date('M j, Y', strtotime($b['booking_date'])) ?></span>
                        </div>
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-clock"></i> Session:</span>
                            <span class="booking-value">
                                <?= htmlspecialchars($b['session_time'] . ' (' . ($sessionRanges[$b['session_time']] ?? 'N/A') . ')') ?>
                            </span>
                        </div>
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-dumbbell"></i> Class:</span>
                            <span class="booking-value"><?= htmlspecialchars($b['class_type'] ?? 'General Training') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-bookings">No past bookings.</p>
            <?php endif; ?>
        </div>

        <!-- Cancelled Bookings -->
        <div class="bookings-list cancelled" id="cancelledBookings">
            <h3 class="booking-section-title">Cancelled Bookings</h3>
            <?php if (!empty($cancelled_bookings)): ?>
                <?php foreach ($cancelled_bookings as $b): ?>
                    <div class="booking-card">
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-user"></i> Client:</span>
                            <span class="booking-value"><?= htmlspecialchars($b['member_name']) ?></span>
                        </div>
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-calendar"></i> Date:</span>
                            <span class="booking-value"><?= date('M j, Y', strtotime($b['booking_date'])) ?></span>
                        </div>
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-clock"></i> Session:</span>
                            <span class="booking-value">
                                <?= htmlspecialchars($b['session_time'] . ' (' . ($sessionRanges[$b['session_time']] ?? 'N/A') . ')') ?>
                            </span>
                        </div> 
                        <div class="booking-row">
                            <span class="booking-label"><i class="fas fa-dumbbell"></i> Class:</span>
                            <span class="booking-value"><?= htmlspecialchars($b['class_type'] ?? 'General Training') ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-bookings">No cancelled bookings.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
    </div>
</main>

    <script>
        const trainerId = <?= json_encode($trainer_id) ?>;
    </script>
    <script src="<?= PUBLIC_PATH ?>/js/trainer/schedule.js"></script>
<script>
const tabBtns = document.querySelectorAll('.tab-btn');
const lists = document.querySelectorAll('.bookings-list');

tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        tabBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const tab = btn.getAttribute('data-tab');
        lists.forEach(l => l.classList.remove('active'));
        document.getElementById(tab + 'Bookings').classList.add('active');
    });
});
</script>
