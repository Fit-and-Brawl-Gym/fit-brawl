<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['email']);
$userName = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Check membership status for header
require_once '../../includes/membership_check.php';

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}


// Fetch user's active membership
$activeMembership = null;

if ($isLoggedIn && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $membership_query = "SELECT um.*, m.plan_name
                        FROM user_memberships um
                        JOIN memberships m ON um.plan_id = m.id
                        WHERE um.user_id = ? AND um.membership_status = 'active' AND um.end_date >= CURDATE()
                        ORDER BY um.end_date DESC
                        LIMIT 1";
    $stmt = $conn->prepare($membership_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $activeMembership = $row;
    }
}

$membershipDetails = null;
$membershipTrainers = [];
if ($activeMembership) {
    $plan_name = $activeMembership['plan_name'];

    // Fetch class type
    $details_query = "SELECT id, class_type FROM memberships WHERE plan_name = ?";
    $stmt = $conn->prepare($details_query);
    $stmt->bind_param("s", $plan_name);
    $stmt->execute();
    $details_result = $stmt->get_result();

    if ($membershipDetails = $details_result->fetch_assoc()) {
        $membership_id = $membershipDetails['id'];

        // Fetch all trainers assigned to this membership
        $trainer_query = "
            SELECT t.name, t.specialization
            FROM membership_trainers mt
            JOIN trainers t ON mt.trainer_id = t.id
            WHERE mt.membership_id = ?";
        $stmt_trainers = $conn->prepare($trainer_query);
        $stmt_trainers->bind_param("i", $membership_id);
        $stmt_trainers->execute();
        $trainers_result = $stmt_trainers->get_result();

        while ($trainer = $trainers_result->fetch_assoc()) {
            $membershipTrainers[] = $trainer;
        }
    }
}

$pageTitle = "Scheduling - Fit and Brawl";
$currentPage = "membership";
$additionalCSS = ['../css/pages/reservations.css?v=' . time()];
$additionalJS = ['../js/reservations.js?v=' . time()];

require_once '../../includes/header.php';
?>

<!--Main Content-->
<main class="reservations-page">
    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <!-- Booking Confirmation Modal -->
    <div id="bookingConfirmModal" class="confirm-modal">
        <div class="confirm-content booking-confirm">
            <div class="confirm-header">
                <i class="fas fa-calendar-check"></i>
                <h3>Book Training Session</h3>
            </div>
            <div id="bookingConfirmMessage" class="confirm-message"></div>
            <div class="confirm-buttons">
                <button class="confirm-btn confirm-yes booking-yes" id="bookingConfirmYes">
                    <i class="fas fa-check-circle"></i> Yes, Book This Session
                </button>
                <button class="confirm-btn confirm-no" id="bookingConfirmNo">
                    <i class="fas fa-times-circle"></i> No, Go Back
                </button>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-content">
            <div class="confirm-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3 id="confirmTitle">Confirm Action</h3>
            </div>
            <p id="confirmMessage" class="confirm-message"></p>
            <div class="confirm-buttons">
                <button class="confirm-btn confirm-yes" id="confirmYes">
                    <i class="fas fa-check"></i> Yes, Continue
                </button>
                <button class="confirm-btn confirm-no" id="confirmNo">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <div class="reservations-container">
        <!-- Page Title Section -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">SCHEDULING</h1>
            </div>
            <?php if ($isLoggedIn && $activeMembership): ?>
                <div class="membership-status">
                    <div class="status-badge">
                        <i class="fas fa-check-circle"></i>
                        <span>Membership <strong>Active</strong> until
                            <?= date('M d, Y', strtotime($activeMembership['end_date'])) ?>
                            (<?= htmlspecialchars($activeMembership['plan_name']) ?> Plan)</span>
                    </div>
                    <a href="membership.php" class="upgrade-btn">Upgrade Plan</a>
                </div>
            <?php elseif ($isLoggedIn): ?>
                <div class="login-prompt">
                    <p>You don't have an active membership. <a href="membership.php">Subscribe now</a> to book training
                        sessions</p>
                </div>
            <?php else: ?>
                <div class="login-prompt">
                    <p>Please <a href="login.php">login</a> to book training sessions</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Stats Cards -->
        <div class="stats-section" id="statsSection">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-fire"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Sessions Attended</div>
                    <div class="stat-value" id="sessionsAttended">0</div>
                    <div class="stat-subtext">This Month</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Upcoming Class:</div>
                    <div class="stat-value" id="upcomingClass">-</div>
                    <div class="stat-subtext" id="upcomingDate">No upcoming sessions</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Trainer:</div>
                    <div class="stat-value" id="upcomingTrainer">-</div>
                    <div class="stat-subtext" id="trainerSubtext">-</div>
                </div>
            </div>
        </div>

        <!-- Main Scheduling Section -->
        <div class="scheduling-section">
            <!-- Left Column: Filters and Calendar -->
            <div class="scheduling-left">
                <div class="filter-section">
                    <h3 class="section-title">Class Type:</h3>
                    <div class="class-filters">
                        <?php if ($activeMembership && $membershipDetails): ?>
                            <?php

                            $classTypes = preg_split('/\s*(?:,|and|,)\s*/i', $membershipDetails['class_type']);
                            $classTypes = array_filter(array_map('trim', $classTypes));
                            $hasMultipleClasses = count($classTypes) > 1;
                            ?>

                            <?php foreach ($classTypes as $type): ?>
                                <?php $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($type))); ?>
                                <button class="filter-btn<?= !$hasMultipleClasses ? ' active' : '' ?>"
                                    data-class="<?= htmlspecialchars($slug) ?>">
                                    <?= htmlspecialchars($type) ?>
                                </button>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <button class="filter-btn active" data-class="all">All</button>
                            <p style="color: var(--color-text-muted); font-size: 0.9rem;">No active membership</p>
                        <?php endif; ?>
                    </div>

                    <h3 class="section-title">Trainer:</h3>
                    <div class="coach-select-wrapper">
                        <select id="coachSelect" class="coach-select">
                            <?php if (!empty($membershipTrainers)): ?>
                                <?php foreach ($membershipTrainers as $trainer): ?>
                                    <option
                                        value="<?= strtolower(str_replace(' ', '-', htmlspecialchars($trainer['name']))) ?>">
                                        <?= htmlspecialchars($trainer['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option disabled>No trainers assigned</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <h3 class="section-title">Time of Day:</h3>
                    <div class="time-select-wrapper">
                        <select id="sessionSelect" class="time-select">
                            <option value="all" selected>All Sessions</option>
                            <option value="morning">Morning (6 AM - 12 PM)</option>
                            <option value="afternoon">Afternoon (12 PM - 6 PM)</option>
                            <option value="evening">Evening (6 PM - 10 PM)</option>
                        </select>
                    </div>

                    <h3 class="section-title">Quick Filters:</h3>
                    <div class="quick-filters">
                        <label class="filter-checkbox">
                            <input type="checkbox" id="availableOnly" />
                            <span class="checkbox-label">
                                <i class="fas fa-check-circle"></i> Available Slots Only
                            </span>
                        </label>
                        <label class="filter-checkbox">
                            <input type="checkbox" id="upcomingOnly" checked />
                            <span class="checkbox-label">
                                <i class="fas fa-calendar-day"></i> Upcoming Only
                            </span>
                        </label>
                    </div>

                    <h3 class="section-title">Session Guide:</h3>
                    <div class="calendar-legend">
                        <div class="legend-item">
                            <div class="legend-badge slot-high">3 sessions</div>
                            <span>All Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-badge slot-medium">2/3 open</div>
                            <span>Some Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-badge slot-low">1/3 open</div>
                            <span>Few Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-badge slot-full">FULL</div>
                            <span>All Booked</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Monthly Schedule and Booked Sessions -->
            <div class="scheduling-right">
                <!-- Monthly Schedule -->
                <div class="monthly-schedule">
                    <div class="schedule-header">
                        <h2>Monthly Schedule</h2>
                        <div class="month-navigation-wrapper">
                            <button class="month-nav-btn" id="prevMonthBtn">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="month-nav" id="monthNavBtn">
                                <span id="currentMonthDisplay">SEPTEMBER</span>
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
                            <button class="month-nav-btn" id="nextMonthBtn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="schedule-calendar-wrapper">
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

                    <!-- Mobile List View -->
                    <div class="mobile-schedule-list" id="mobileScheduleList">
                        <!-- Sessions list will be populated by JavaScript -->
                    </div>

                    <!-- Schedule Details Popup -->
                    <div class="schedule-details" id="scheduleDetails" style="display: none;">
                        <div class="schedule-details-content">
                            <div class="modal-header">
                                <button class="close-details" id="closeDetails">&times;</button>
                            </div>
                            <div class="modal-body">
                                <h3 class="details-title">Available Sessions</h3>
                                <p class="details-date" id="detailDate">Monday, November 4, 2025</p>
                                <div class="sessions-list-modal" id="sessionsListModal">
                                    <!-- Sessions will be populated here dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booked Sessions -->
                <div class="booked-sessions">
                    <div class="booked-header">
                        <i class="fas fa-calendar-alt"></i>
                        <h3>Booked Sessions</h3>
                        <div class="booked-month-selector">
                            <button class="booked-month-nav" id="prevBookedMonth">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="booked-month-display" id="bookedMonthDisplay">
                                <span id="bookedMonthText">All Upcoming</span>
                                <i class="fas fa-chevron-down"></i>
                                <div class="booked-month-dropdown" id="bookedMonthDropdown">
                                    <div class="booked-month-option" data-month="all">All Upcoming</div>
                                    <div class="booked-month-option" data-month="1">January</div>
                                    <div class="booked-month-option" data-month="2">February</div>
                                    <div class="booked-month-option" data-month="3">March</div>
                                    <div class="booked-month-option" data-month="4">April</div>
                                    <div class="booked-month-option" data-month="5">May</div>
                                    <div class="booked-month-option" data-month="6">June</div>
                                    <div class="booked-month-option" data-month="7">July</div>
                                    <div class="booked-month-option" data-month="8">August</div>
                                    <div class="booked-month-option" data-month="9">September</div>
                                    <div class="booked-month-option" data-month="10">October</div>
                                    <div class="booked-month-option" data-month="11">November</div>
                                    <div class="booked-month-option" data-month="12">December</div>
                                </div>
                            </div>
                            <button class="booked-month-nav" id="nextBookedMonth">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="sessions-list" id="sessionsList">
                        <p style="color: var(--color-text-muted); text-align: center; padding: var(--spacing-4);">
                            Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!--Footer-->
<?php require_once '../../includes/footer.php'; ?>
</body>

</html>