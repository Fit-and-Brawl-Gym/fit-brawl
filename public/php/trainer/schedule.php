<?php
session_start();
require_once '../../../includes/db_connect.php';
require_once '../../../includes/session_manager.php';

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
    $avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../../images/account-icon.png";
}

// Get trainer information
$trainer_id = null;
$trainer_name = '';
$trainer_specialization = '';
$upcoming_bookings = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Get trainer details from trainers table based on user email/name
    $trainer_query = "SELECT id, name, specialization FROM trainers WHERE name = ? OR LOWER(name) = LOWER(?)";
    $stmt = $conn->prepare($trainer_query);
    if ($stmt) {
        $username = $_SESSION['name'] ?? '';
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $trainer_id = $row['id'];
            $trainer_name = $row['name'];
            $trainer_specialization = $row['specialization'];
        }
        $stmt->close();
    }

    // Fetch upcoming bookings (next 5 bookings starting from today)
    if ($trainer_id) {
        $upcoming_query = "SELECT r.*, u.name as member_name, u.email as member_email,
                          ct.class_name as class_type
                          FROM reservations r
                          JOIN users u ON r.user_id = u.id
                          LEFT JOIN class_types ct ON r.class_type_id = ct.id
                          WHERE r.trainer_id = ?
                          AND r.reservation_date >= CURDATE()
                          AND r.booking_status = 'confirmed'
                          ORDER BY r.reservation_date ASC, r.start_time ASC
                          LIMIT 5";
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

// Set variables for header
$pageTitle = "Schedule - Fit and Brawl Trainer";
$currentPage = "schedule";
$additionalCSS = ["../../css/pages/trainer/schedule.css"];

// Include header
require_once '../../../includes/trainer_header.php';
?>

    <!--Main Content-->
    <main class="schedule-page">
        <div class="schedule-container">
            <!-- Page Title Section -->
            <div class="page-header">
                <div class="header-content">
                    <h1 class="page-title">TRAINING SCHEDULE</h1>
                </div>
                <?php if ($trainer_name): ?>
                <div class="trainer-info">
                    <div class="info-badge">
                        <i class="fas fa-user-check"></i>
                        <span>Trainer: <strong><?= htmlspecialchars($trainer_name) ?></strong> | Specialization: <strong><?= htmlspecialchars($trainer_specialization) ?></strong></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Section -->
            <div class="upcoming-section">
                <h2><i class="fas fa-calendar-check"></i> Upcoming Schedule</h2>
                <div class="upcoming-cards">
                    <?php if (!empty($upcoming_bookings)): ?>
                        <?php foreach ($upcoming_bookings as $booking): ?>
                            <div class="upcoming-card">
                                <div class="card-header">
                                    <span class="card-date">
                                        <i class="fas fa-calendar"></i>
                                        <?= date('M j, Y', strtotime($booking['reservation_date'])) ?>
                                    </span>
                                    <span class="card-time">
                                        <i class="fas fa-clock"></i>
                                        <?= date('g:i A', strtotime($booking['start_time'])) ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="card-info">
                                        <i class="fas fa-user"></i>
                                        <span><strong>Client:</strong> <?= htmlspecialchars($booking['member_name']) ?></span>
                                    </div>
                                    <div class="card-info">
                                        <i class="fas fa-dumbbell"></i>
                                        <span><strong>Class:</strong> <?= htmlspecialchars($booking['class_type'] ?? 'General Training') ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-upcoming">
                            <i class="fas fa-calendar-times"></i>
                            <p>No upcoming bookings scheduled</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Calendar Section -->
            <div class="calendar-section">
                <div class="monthly-schedule">
                    <div class="schedule-header">
                        <h2>Monthly Schedule</h2>
                        <div class="month-nav" id="monthNavBtn">
                            <span id="currentMonthDisplay">OCTOBER</span>
                            <i class="fas fa-chevron-down"></i>
                            <div class="month-dropdown" id="monthDropdown">
                                <div class="month-option" data-month="0">JANUARY</div>
                                <div class="month-option" data-month="1">FEBRUARY</div>
                                <div class="month-option" data-month="2">MARCH</div>
                                <div class="month-option" data-month="3">APRIL</div>
                                <div class="month-option" data-month="4">MAY</div>
                                <div class="month-option" data-month="5">JUNE</div>
                                <div class="month-option" data-month="6">JULY</div>
                                <div class="month-option" data-month="7">AUGUST</div>
                                <div class="month-option" data-month="8">SEPTEMBER</div>
                                <div class="month-option" data-month="9">OCTOBER</div>
                                <div class="month-option" data-month="10">NOVEMBER</div>
                                <div class="month-option" data-month="11">DECEMBER</div>
                            </div>
                        </div>
                    </div>

                    <div class="schedule-calendar">
                        <div class="calendar-weekdays">
                            <div class="weekday">SUN</div>
                            <div class="weekday">MON</div>
                            <div class="weekday">TUE</div>
                            <div class="weekday">WED</div>
                            <div class="weekday">THU</div>
                            <div class="weekday">FRI</div>
                            <div class="weekday">SAT</div>
                        </div>
                        <div class="calendar-days" id="scheduleCalendar">
                            <!-- Calendar will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for viewing bookings -->
        <div class="modal" id="bookingsModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Bookings for <span id="modalDate"></span></h2>
                    <button class="close-modal" id="closeModal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="session-section">
                        <h3><i class="fas fa-sun"></i> Morning Sessions (6:00 AM - 12:00 PM)</h3>
                        <div id="morningBookings" class="bookings-list">
                            <p class="no-bookings">No bookings</p>
                        </div>
                    </div>

                    <div class="session-section">
                        <h3><i class="fas fa-cloud-sun"></i> Afternoon Sessions (12:00 PM - 6:00 PM)</h3>
                        <div id="afternoonBookings" class="bookings-list">
                            <p class="no-bookings">No bookings</p>
                        </div>
                    </div>

                    <div class="session-section">
                        <h3><i class="fas fa-moon"></i> Evening Sessions (6:00 PM - 10:00 PM)</h3>
                        <div id="eveningBookings" class="bookings-list">
                            <p class="no-bookings">No bookings</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const trainerId = <?= json_encode($trainer_id) ?>;
    </script>
    <script src="../../js/trainer/schedule.js"></script>

<?php require_once '../../../includes/trainer_footer.php'; ?>
