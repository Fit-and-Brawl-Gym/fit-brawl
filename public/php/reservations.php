<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager
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
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.png";
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
$additionalCSS = ['../css/pages/reservations.css?=v2'];
$additionalJS = ['../js/reservations.js?=v1'];

require_once '../../includes/header.php';
?>

    <!--Main Content-->
    <main class="reservations-page">
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
                        <span>Membership <strong>Active</strong> until <?= date('M d, Y', strtotime($activeMembership['end_date'])) ?> (<?= htmlspecialchars($activeMembership['plan_name']) ?> Plan)</span>
                    </div>
                    <a href="membership.php" class="upgrade-btn">Upgrade Plan</a>
                </div>
                <?php elseif ($isLoggedIn): ?>
                <div class="login-prompt">
                    <p>You don't have an active membership. <a href="membership.php">Subscribe now</a> to book training sessions</p>
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
                                    <button
                                        class="filter-btn<?= !$hasMultipleClasses ? ' active' : '' ?>"
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
                                        <option value="<?= strtolower(str_replace(' ', '-', htmlspecialchars($trainer['name']))) ?>">
                                            <?= htmlspecialchars($trainer['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option disabled>No trainers assigned</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <h3 class="section-title">Day/Date Selector:</h3>
                        <div class="date-selector">
                            <div class="date-header">
                                <button class="date-nav-btn" id="prevMonth">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <div class="current-month">
                                    <span id="monthDisplay">09</span>
                                    <span id="monthName">SEP</span>
                                    <span id="yearDisplay">2025</span>
                                </div>
                                <button class="date-nav-btn" id="nextMonth">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                            <div class="calendar-grid" id="calendarGrid">
                                <!-- Calendar days will be generated by JavaScript -->
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

                        <!-- Schedule Details Popup -->
                        <div class="schedule-details" id="scheduleDetails" style="display: none;">
                            <button class="close-details" id="closeDetails">&times;</button>
                            <h3>Class: <span id="detailClass">Boxing</span></h3>
                            <p><strong>Trainer:</strong> <span id="detailTrainer">Coach Thei</span></p>
                            <p><strong>Date & Time:</strong> <span id="detailDateTime">Mon, Sept 15, 2025; 5:00 PM To 7:00 PM</span></p>
                            <p><strong>Remaining Slots:</strong> <span id="detailSlots" class="slots-count">5</span> / <span id="detailMaxSlots">10</span></p>
                            <button class="schedule-training-btn" id="scheduleTrainingBtn" data-reservation-id="">
                                <i class="fas fa-calendar-check"></i> Schedule Training
                            </button>
                        </div>
                    </div>

                    <!-- Booked Sessions -->
                    <div class="booked-sessions">
                        <div class="booked-header">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>Booked Sessions</h3>
                        </div>

                        <div class="sessions-list" id="sessionsList">
                            <p style="color: var(--color-text-muted); text-align: center; padding: var(--spacing-4);">Loading...</p>
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
