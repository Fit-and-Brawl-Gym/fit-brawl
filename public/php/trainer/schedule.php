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
}

// Set variables for header
$pageTitle = "Schedule - FitXBrawl Trainer";
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
                    <h1 class="page-title">CLASS SCHEDULE</h1>
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
