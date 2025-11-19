<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/csp_nonce.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';

// Generate CSP nonces for this request
CSPNonce::generate();

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Redirect admin and trainer to their respective dashboards
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/admin.php');
        exit;
    } elseif ($_SESSION['role'] === 'trainer') {
        header('Location: trainer/schedule.php');
        exit;
    }
}

$isLoggedIn = true;
$userName = $_SESSION['username'] ?? '';
$user_id = $_SESSION['user_id'];

// Don't include membership_check.php - we do our own check below
// require_once '../../includes/membership_check.php';

// Determine avatar source
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'account-icon.svg' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

// Fetch user's active membership
$activeMembership = null;
$membershipClassTypes = [];
$gracePeriodDays = 3;

if ($user_id) {
    $membership_query = "SELECT um.*, m.plan_name, m.class_type
                        FROM user_memberships um
                        JOIN memberships m ON um.plan_id = m.id
                        WHERE um.user_id = ?
                        AND um.membership_status = 'active'
                        AND DATE_ADD(um.end_date, INTERVAL ? DAY) >= CURDATE()
                        ORDER BY um.end_date DESC
                        LIMIT 1";
    $stmt = $conn->prepare($membership_query);
    $stmt->bind_param("si", $user_id, $gracePeriodDays);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $activeMembership = $row;
        // Parse class types from membership
        if (!empty($row['class_type'])) {
            $classTypes = preg_split('/\s*(?:,|and)\s*/i', $row['class_type']);
            $membershipClassTypes = array_filter(array_map('trim', $classTypes));
        }
    }
    $stmt->close();
}

$pageTitle = "Scheduling - Fit and Brawl";
$currentPage = "reservations";
$additionalCSS = [
    '../css/components/alert.css?v=' . time(),
    '../css/pages/reservations.css?v=4.0.' . time(),
    '../css/components/time-selection-v2.css?v=' . time(),
    'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css'
];
$additionalJS = [
    'https://cdn.jsdelivr.net/npm/flatpickr',
    '../js/dsa/dsa-utils.js?v=' . time(), // DSA Core Library
    '../js/time-selection-modern-v2.js?v=' . time(),
    '../js/resheduling-fixed.js?v=' . time() . rand(1000, 9999),  // ← LOAD THIS FIRST
    '../js/reservations-new.js?v=' . time() . mt_rand(),  // ← LOAD THIS SECOND - RENAMED TO BYPASS CACHE
    '../js/dsa/reservations-dsa-integration.js?v=' . time() // DSA Integration Layer
];

// Generate CSRF token for this page
$pageCsrfToken = CSRFProtection::generateToken();

require_once __DIR__ . '/../../includes/header.php';
?>

<script <?= CSPNonce::getScriptNonceAttr() ?>>
    window.CSRF_TOKEN = <?= json_encode($pageCsrfToken); ?>;
</script>

<!--Main Content-->
<main class="reservations-page">
    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>
    <div id="rateLimitPortal" class="rate-limit-portal" aria-live="polite"></div>

    <?php if ($activeMembership): ?>
        <?php
        // Check if membership is expiring soon (within 7 days including grace period)
        $endDate = new DateTime($activeMembership['end_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Reset to midnight for accurate day counting
        $endDate->setTime(0, 0, 0);

        $gracePeriodDays = 3;
        $endDateWithGrace = clone $endDate;
        $endDateWithGrace->modify("+{$gracePeriodDays} days");

        // Days until actual expiration (can be negative if expired)
        $daysUntilExpiration = $today->diff($endDate)->days;
        if ($today > $endDate) {
            $daysUntilExpiration = -$daysUntilExpiration;
        }

        // Days until grace period ends (can be negative)
        $daysUntilGraceEnd = $today->diff($endDateWithGrace)->days;
        if ($today > $endDateWithGrace) {
            $daysUntilGraceEnd = -$daysUntilGraceEnd;
        }

        $showExpirationWarning = $daysUntilGraceEnd <= 7 && $daysUntilGraceEnd > 0;
        ?>

        <!-- Page Header -->
        <div class="page-header-section">
            <div class="page-header-content">
                <div class="page-header-text">
                    <h1 class="page-title">Book Your Training Session</h1>
                    <p class="page-subtitle">Reserve your spot with our expert trainers</p>
                </div>
                <div
                    class="membership-status-bar <?= $showExpirationWarning ? ($daysUntilExpiration < 0 ? 'critical' : 'warning') : '' ?>">
                    <div class="status-badge <?= $daysUntilExpiration < 0 ? 'expired' : '' ?>">
                        <?php if ($daysUntilExpiration < 0): ?>
                            <i class="fas fa-times-circle"></i>
                        <?php elseif ($showExpirationWarning): ?>
                            <i class="fas fa-exclamation-triangle"></i>
                        <?php else: ?>
                            <i class="fas fa-check-circle"></i>
                        <?php endif; ?>

                        <div class="status-content">
                            <?php if ($daysUntilExpiration < 0): ?>
                                <!-- Expired - Grace Period -->
                                <span class="status-title">Membership Expired</span>
                                <span class="status-details">
                                    Expired on <strong><?= date('M d, Y', strtotime($activeMembership['end_date'])) ?></strong>
                                    (<?= abs($daysUntilExpiration) ?> day<?= abs($daysUntilExpiration) != 1 ? 's' : '' ?> ago) •
                                    Book until <strong><?= $endDateWithGrace->format('M d, Y') ?></strong>
                                    (<?= $daysUntilGraceEnd ?> day<?= $daysUntilGraceEnd != 1 ? 's' : '' ?> left)
                                </span>
                            <?php elseif ($showExpirationWarning): ?>
                                <!-- Expiring Soon -->
                                <span class="status-title">Membership Expiring Soon</span>
                                <span class="status-details">
                                    <?php if ($daysUntilExpiration == 0): ?>
                                        Expires <strong>Today</strong>
                                        (<?= date('M d, Y', strtotime($activeMembership['end_date'])) ?>) •
                                    <?php else: ?>
                                        Expires on <strong><?= date('M d, Y', strtotime($activeMembership['end_date'])) ?></strong>
                                        (<?= $daysUntilExpiration ?> day<?= $daysUntilExpiration != 1 ? 's' : '' ?> left) •
                                    <?php endif; ?>
                                    Book until <strong><?= $endDateWithGrace->format('M d, Y') ?></strong> •
                                    Visit gym to renew or wait for expiration date to renew online.
                                </span>
                            <?php else: ?>
                                <!-- Active - No Warning -->
                                <span class="status-title">
                                    Membership Active until <?= date('M d, Y', strtotime($activeMembership['end_date'])) ?>
                                    (<?= htmlspecialchars($activeMembership['plan_name']) ?> Plan)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($daysUntilExpiration < 0): ?>
                        <!-- Renew Button for Expired Memberships in Grace Period -->
                        <a href="membership.php" class="renew-btn">
                            <i class="fas fa-sync-alt"></i>
                            <span>Renew Membership</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="reservations-container">

            <!-- Stats Cards -->
            <div class="stats-section" id="statsSection">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Weekly Hours Used</div>
                        <div class="stat-value">
                            <span id="weeklyHoursUsed">-</span>
                            <span class="counter-max" id="weeklyHoursMax">/48h</span>
                        </div>
                        <div class="stat-subtext" id="weeklyProgressText">Loading...</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Next Booked Class:</div>
                        <div class="stat-value" id="upcomingClass">-</div>
                        <div class="stat-subtext" id="upcomingDate">No booked sessions</div>
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
            <div class="booking-wizard-section">
                <div class="booking-wizard">
                    <!-- Step 1: Select Date -->
                    <div class="wizard-step active" id="step1" data-step="1">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <div class="step-info">
                                <div class="step-text">
                                    <h2 class="step-title">Select Date</h2>
                                    <p class="step-subtitle">Choose a date for your training session</p>
                                </div>
                                <div class="wizard-navigation">
                                    <button class="btn-wizard btn-back" id="btnBack" style="display: none;">
                                        <i class="fas fa-arrow-left"></i>
                                        Back
                                    </button>
                                    <button class="btn-wizard btn-next" id="btnNext" disabled>
                                        Next
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="step-content">
                            <!-- Monthly Schedule (Full Size Calendar from V1) -->
                            <div class="monthly-schedule">
                                <div class="schedule-header">
                                    <h2> </h2>
                                    <div class="month-navigation-wrapper">
                                        <button class="month-nav-btn" id="prevMonth">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <div class="month-nav" id="monthNavBtn">
                                            <span id="calendarTitle">NOVEMBER</span>
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
                                        <button class="month-nav-btn" id="nextMonth">
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
                                        <div class="calendar-days" id="calendarDays">
                                            <!-- Calendar will be populated by JavaScript -->
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Select Class Type -->
                    <div class="wizard-step" id="step2" data-step="2">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <div class="step-info">
                                <div class="step-text">
                                    <h2 class="step-title">Select Class Type</h2>
                                    <p class="step-subtitle">Choose your training discipline</p>
                                </div>
                                <div class="wizard-navigation">
                                    <button class="btn-wizard btn-back" id="btnBack" style="display: none;">
                                        <i class="fas fa-arrow-left"></i>
                                        Back
                                    </button>
                                    <button class="btn-wizard btn-next" id="btnNext" disabled>
                                        Next
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="step-content">
                            <div class="class-types">
                                <?php if (!empty($membershipClassTypes)): ?>
                                    <?php foreach ($membershipClassTypes as $classType): ?>
                                        <?php
                                        $icons = [
                                            'Boxing' => 'fa-hand-fist',
                                            'MMA' => 'fa-shield-halved',
                                            'Muay Thai' => 'fa-hand-back-fist',
                                            'Gym' => 'fa-dumbbell'
                                        ];
                                        $icon = $icons[$classType] ?? 'fa-dumbbell';
                                        ?>
                                        <div class="class-card" data-class="<?= htmlspecialchars($classType) ?>">
                                            <div class="class-icon">
                                                <i class="fas <?= $icon ?>"></i>
                                            </div>
                                            <h3 class="class-name"><?= htmlspecialchars($classType) ?></h3>
                                            <p class="class-description">
                                                <?php
                                                $descriptions = [
                                                    'Boxing' => 'Improve technique, footwork, and conditioning',
                                                    'MMA' => 'Mixed martial arts training and sparring',
                                                    'Muay Thai' => 'Master the art of eight limbs',
                                                    'Gym' => 'Strength training and fitness conditioning'
                                                ];
                                                echo $descriptions[$classType] ?? 'Professional training session';
                                                ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="no-classes">No class types available in your membership</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Select Trainer -->
                    <div class="wizard-step" id="step3" data-step="3">
                        <div class="step-header">
                            <div class="step-number">3</div>
                            <div class="step-info">
                                <div class="step-text">
                                    <h2 class="step-title">Select Trainer</h2>
                                    <p class="step-subtitle">Choose your preferred trainer</p>
                                </div>
                                <div class="wizard-navigation">
                                    <button class="btn-wizard btn-back" id="btnBack" style="display: none;">
                                        <i class="fas fa-arrow-left"></i>
                                        Back
                                    </button>
                                    <button class="btn-wizard btn-next" id="btnNext" disabled>
                                        Next
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="step-content">
                            <div class="trainers-grid" id="trainersGrid">
                                <!-- Trainers populated by JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Select Time -->
                    <div class="wizard-step" id="step4" data-step="4">
                        <div class="step-header">
                            <div class="step-number">4</div>
                            <div class="step-info">
                                <div class="step-text">
                                    <h2 class="step-title">Select Time</h2>
                                    <p class="step-subtitle">Pick your preferred start time and duration</p>
                                </div>
                                <div class="wizard-navigation">
                                    <button class="btn-wizard btn-back" id="btnBack" style="display: none;">
                                        <i class="fas fa-arrow-left"></i>
                                        Back
                                    </button>
                                    <button class="btn-wizard btn-next" id="btnNext" disabled>
                                        Next
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="step-content">
                            <!-- Trainer Availability Banner -->
                            <div class="availability-banner" id="availabilityBanner">
                                <div class="banner-loading">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>Loading trainer availability...</span>
                                </div>
                            </div>

                            <!-- Time Selection Layout (Two Columns) -->
                            <div class="time-selection-layout" id="timeSelectionLayout" style="display: none;">
                                <!-- Left Column: Time Slot Selectors -->
                                <div class="time-pickers-column">
                                    <h3 class="section-title">
                                        <i class="fas fa-clock"></i>
                                        Select Your Training Time
                                    </h3>

                                    <!-- Start Time Selection -->
                                    <div class="time-slot-selector">
                                        <label class="time-picker-label">
                                            <i class="fas fa-play-circle"></i>
                                            Start Time
                                        </label>
                                        <select class="time-select-dropdown" id="startTimeSelect">
                                            <option value="">Select start time</option>
                                            <!-- Populated by JavaScript -->
                                        </select>
                                        <small class="time-picker-hint">Select when you want to begin</small>
                                    </div>

                                    <!-- End Time Selection -->
                                    <div class="time-slot-selector" id="endTimeSelector">
                                        <label class="time-picker-label">
                                            <i class="fas fa-stop-circle"></i>
                                            End Time
                                        </label>
                                        <select class="time-select-dropdown" id="endTimeSelect" disabled>
                                            <option value="">Select start time first</option>
                                            <!-- Populated by JavaScript -->
                                        </select>
                                        <small class="time-picker-hint">Select when you want to finish</small>
                                    </div>

                                    <!-- Duration Display -->
                                    <div class="duration-display" id="durationDisplay" style="display: none;">
                                        <div class="duration-icon">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                        <div class="duration-info">
                                            <span class="duration-label">Total Duration</span>
                                            <span class="duration-value" id="durationValue">0 minutes</span>
                                        </div>
                                    </div>

                                    <!-- Weekly Usage Info -->
                                    <div class="weekly-usage-info" id="weeklyUsageInfo" style="display: none;">
                                        <i class="fas fa-chart-line"></i>
                                        <span id="weeklyUsageText"></span>
                                    </div>
                                </div>

                                <!-- Right Column: Availability Sidebar -->
                                <div class="availability-sidebar">
                                    <h3 class="sidebar-title">
                                        <i class="fas fa-calendar-alt"></i>
                                        Trainer Availability
                                    </h3>
                                    <div class="availability-timeline" id="availabilityTimeline">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                </div>
                            </div>

                            <!-- Selected Time Summary -->
                            <div class="time-summary" id="timeSummary" style="display: none;">
                                <div class="summary-card">
                                    <div class="summary-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="summary-details">
                                        <div class="summary-label">Selected Time</div>
                                        <div class="summary-time" id="step4SummaryTime">-</div>
                                        <div class="summary-duration" id="step4SummaryDuration">-</div>
                                    </div>
                                    <button class="btn-change" id="btnChangeTime">
                                        <i class="fas fa-edit"></i>
                                        Change
                                    </button>
                                </div>
                                <div class="summary-info">
                                    <div class="info-row">
                                        <i class="fas fa-calendar-week"></i>
                                        <span>Weekly usage: <strong id="weeklyUsageDisplay">-</strong> / 48 hours</span>
                                    </div>
                                    <div class="info-row">
                                        <i class="fas fa-shield-alt"></i>
                                        <span>10-minute buffer added automatically</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Confirmation -->
                    <div class="wizard-step" id="step5" data-step="5">
                        <div class="step-header">
                            <div class="step-number">5</div>
                            <div class="step-info">
                                <div class="step-text">
                                    <h2 class="step-title">Confirm Booking</h2>
                                    <p class="step-subtitle">Review your session details</p>
                                </div>
                                <div class="wizard-navigation">
                                    <button class="btn-wizard btn-back" id="btnBack" style="display: none;">
                                        <i class="fas fa-arrow-left"></i>
                                        Back
                                    </button>
                                    <button class="btn-wizard btn-next" id="btnNext" disabled>
                                        Next
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="step-content">
                            <div class="booking-summary">
                                <div class="summary-card">
                                    <div class="summary-row">
                                        <span class="summary-label">
                                            <i class="fas fa-calendar"></i> Date
                                        </span>
                                        <span class="summary-value" id="summaryDate">-</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">
                                            <i class="fas fa-clock"></i> Time
                                        </span>
                                        <span class="summary-value" id="summaryTime">-</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">
                                            <i class="fas fa-hourglass-half"></i> Duration
                                        </span>
                                        <span class="summary-value" id="summaryDuration">-</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">
                                            <i class="fas fa-dumbbell"></i> Class Type
                                        </span>
                                        <span class="summary-value" id="summaryClass">-</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="summary-label">
                                            <i class="fas fa-user"></i> Trainer
                                        </span>
                                        <span class="summary-value" id="summaryTrainer">-</span>
                                    </div>
                                </div>
                                <button class="btn-confirm-booking" id="btnConfirmBooking">
                                    <i class="fas fa-check-circle"></i>
                                    Confirm Booking
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Bookings Section -->
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
                                <?php foreach ($membershipClassTypes as $classType): ?>
                                    <option value="<?php echo htmlspecialchars($classType); ?>">
                                        <?php echo htmlspecialchars($classType); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                            <div class="bookings-tabs">
                                <button class="tab-btn active" data-tab="upcoming">
                                    Booked
                                    <span class="tab-count" id="upcomingCount">0</span>
                                </button>
                                <button class="tab-btn" data-tab="past">
                                    Past
                                    <span class="tab-count" id="pastCount">0</span>
                                </button>
                                <button class="tab-btn" data-tab="cancelled">
                                    Cancelled
                                    <span class="tab-count" id="cancelledCount">0</span>
                                </button>
                                <button class="tab-btn tab-blocked" data-tab="blocked" style="display: none;">
                                    Unavailable
                                    <span class="tab-count" id="blockedCount">0</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="bookings-content">
                        <div class="bookings-list active" id="upcomingBookings">
                            <p class="loading-text">Loading bookings...</p>
                        </div>
                        <div class="bookings-list" id="pastBookings">
                            <p class="loading-text">Loading bookings...</p>
                        </div>
                        <div class="bookings-list" id="cancelledBookings">
                            <p class="loading-text">Loading bookings...</p>
                        </div>
                        <div class="bookings-list tab-blocked" id="blockedBookings">
                            <p class="loading-text">Loading blocked schedules...</p>
                        </div>
                    </div>
            </div>
        <?php else: ?>
            <!-- No Membership CTA -->
            <div class="no-membership-cta">
                <div class="cta-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h2>Get Started with a Membership</h2>
                <p>To book training sessions, you need an active membership plan.</p>
                <a href="membership.php" class="btn-cta">
                    View Membership Plans
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

</main>
<!-- Reschedule Booking Modal -->
<div id="rescheduleModal" class="reschedule-modal" style="display: none;">
    <div class="reschedule-modal-content">

        <!-- Header -->
        <div class="modal-header">
            <h2>Reschedule Booking</h2>
            <button class="close-modal" onclick="closeRescheduleModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body">
<!-- Current Booking (Always Visible) -->
            <div class="original-booking-info">
                <h3>Current Booking</h3>

                <div class="booking-detail">
                    <span class="label">Date & Time:</span>
                    <span class="value" id="originalDateTime">-</span>
                </div>

                <div class="booking-detail">
                    <span class="label">Trainer:</span>
                    <span class="value" id="originalTrainer">-</span>
                </div>

                <div class="booking-detail">
                    <span class="label">Class Type:</span>
                    <span class="value" id="originalClass">-</span>
                </div>

                <div class="booking-detail">
                    <span class="label">Duration:</span>
                    <span class="value" id="originalDuration">-</span>
                </div>
            </div>
            <!-- Reschedule Form (Hidden after time selection) -->
            <form id="rescheduleForm" class="reschedule-form">
  <input type="hidden" id="rescheduleBookingId" name="booking_id">
    <input type="hidden" id="rescheduleSelectedDate" name="new_date">
    <input type="hidden" id="rescheduleSelectedTime" name="new_time">
                <!-- New Date -->
                <div class="form-group">
                    <label for="rescheduleDate">
                        <i class="fas fa-calendar"></i> Select New Date
                    </label>
                    <input type="date" id="rescheduleDate"  onclick="this.showPicker()" required>
                </div>

                <!-- Class Type -->
                <div class="form-group">
                    <label for="rescheduleClass">
                        <i class="fas fa-dumbbell"></i> Class Type
                    </label>
                    <select id="rescheduleClass" required>
                        <option value="">Choose a class...</option>
                    </select>
                </div>

                <!-- Trainer Selection Grid -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-user"></i> Select Trainer
                    </label>
                    <input type="hidden" id="rescheduleTrainerInput">
                    <div class="trainers-grid" id="rescheduleTrainersGrid">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>

                <!-- Time Selection -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-hourglass-start"></i>
                        Select Time Slot
                    </label>

                    <!-- Availability Banner -->
                    <div id="rescheduleAvailabilityBanner" class="availability-banner" style="display: none;">
                        <div class="banner-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>Loading trainer availability...</span>
                        </div>
                    </div>

                    <!-- Time Selection Layout (Two Column) -->
                    <div id="rescheduleTimeSelectionLayout" class="time-selection-layout" style="display: none;">

                        <!-- Left Column: Time Pickers -->
                        <div class="time-pickers-column">
                            <h3 class="section-title">
                                <i class="fas fa-clock"></i>
                                Select Your Training Time
                            </h3>

                            <!-- Start Time -->
                            <div class="time-slot-selector">
                                <label class="time-picker-label">
                                    <i class="fas fa-play-circle"></i>
                                    Start Time
                                </label>
                                <select class="time-select-dropdown" id="rescheduleStartTimeSelect">
                                    <option value="">Select start time</option>
                                </select>
                                <small class="time-picker-hint">Select when you want to begin</small>
                            </div>

                            <!-- End Time -->
                            <div class="time-slot-selector">
                                <label class="time-picker-label">
                                    <i class="fas fa-stop-circle"></i>
                                    End Time
                                </label>
                                <select class="time-select-dropdown" id="rescheduleEndTimeSelect" disabled>
                                    <option value="">Select start time first</option>
                                </select>
                                <small class="time-picker-hint">Select when you want to finish</small>
                            </div>

                            <!-- Duration Display -->
                            <div class="duration-display" id="rescheduleDurationDisplay" style="display: none;">
                                <div class="duration-icon">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <div class="duration-info">
                                    <span class="duration-label">Total Duration</span>
                                    <span class="duration-value" id="rescheduleDurationValue">0 minutes</span>
                                </div>
                            </div>

                            <!-- Weekly Usage Info -->
                            <div class="weekly-usage-info" id="rescheduleWeeklyUsageInfo" style="display: none;">
                                <i class="fas fa-chart-line"></i>
                                <span id="rescheduleWeeklyUsageText">Loading...</span>
                            </div>
                        </div>

                        <!-- Right Column: Availability Sidebar -->
                        <div class="availability-sidebar">
                            <h3 class="sidebar-title">
                                <i class="fas fa-calendar-alt"></i>
                                Trainer Availability
                            </h3>
                            <div class="availability-timeline" id="rescheduleAvailabilityTimeline">
                                <!-- Populated by JavaScript -->
                            </div>
                        </div>
                    </div>

                    <!-- Reschedule Reason (ALWAYS VISIBLE) -->
                    <div class="form-group reason-section">
                        <label for="rescheduleReason">
                            <i class="fas fa-comment"></i> Reason for Rescheduling (Optional)
                        </label>
                        <textarea id="rescheduleReason" rows="3" placeholder="Tell us why you're rescheduling..."></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-actions">
                        <button type="button" class="btn-cancel" onclick="closeRescheduleModal()">
                             Cancel
                        </button>
                        <button type="button" class="btn-next" id="rescheduleNextBtn" style="display: none;" onclick="proceedToReview()">
                            <i class="fas fa-arrow-right"></i> Next
                        </button>
                    </div>
                </div>
            </form>

            <!-- Time Summary with Current vs New Booking Comparison (Shown after time selection) -->
            <div class="reschedule-time-summary" id="rescheduleTimeSummary" style="display: none;">
                <div class="reschedule-summary-comparison">
                    <!-- Current Booking (Left) -->
                    <div class="reschedule-summary-column current-booking">
                        <div class="reschedule-summary-header">
                            <i class="fas fa-calendar-check"></i>
                            <span>Current Booking</span>
                        </div>
                        <div class="reschedule-booking-card">
                            <div class="reschedule-booking-row">
                                <span class="reschedule-booking-label">Date & Time:</span>
                                <span class="reschedule-booking-value" id="currentBookingDateTime">-</span>
                            </div>
                            <div class="reschedule-booking-row">
                                <span class="reschedule-booking-label">Trainer:</span>
                                <span class="reschedule-booking-value" id="currentBookingTrainer">-</span>
                            </div>
                            <div class="reschedule-booking-row">
                                <span class="reschedule-booking-label">Class Type:</span>
                                <span class="reschedule-booking-value" id="currentBookingClass">-</span>
                            </div>
                            <div class="reschedule-booking-row">
                                <span class="reschedule-booking-label">Duration:</span>
                                <span class="reschedule-booking-value" id="currentBookingDuration">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Arrow Divider -->
                    <div class="reschedule-summary-divider">
                        <i class="fas fa-arrow-right"></i>
                    </div>

                    <!-- New Selected Booking (Right) -->
                    <div class="reschedule-summary-column new-booking">
                        <div class="reschedule-summary-header">
                            <i class="fas fa-calendar-plus"></i>
                            <span>New Booking</span>
                        </div>
                        <div class="reschedule-booking-card">
                            <div class="reschedule-booking-row">
                                <span class="reschedule-booking-label">Date & Time:</span>
                                <span class="reschedule-booking-value" id="newBookingDateTime">-</span>
                            </div>
                            <div class="reschedule-booking-row">
                                <span class="reschedule-booking-label">Trainer:</span>
                                <span class="reschedule-booking-value" id="newBookingTrainer">-</span>
                            </div>
                            <div class="reschedule-booking-row">
                                <span class="reschedule-booking-label">Class Type:</span>
                                <span class="reschedule-booking-value" id="newBookingClass">-</span>
                            </div>
                            <div class="reschedule-booking-row">
                                <span class="reschedule-booking-label">Duration:</span>
                                <span class="reschedule-booking-value" id="newBookingDuration">-</span>
                            </div>
                        </div>
                    </div>
                </div>
                    <!-- Reschedule Reason (ALWAYS VISIBLE) -->
                    <div class="form-group reason-section">
                        <label for="rescheduleReason">
                            <i class="fas fa-comment"></i> Reason for Rescheduling (Optional)
                        </label>
                        <textarea id="rescheduleReason" rows="3" placeholder="Tell us why you're rescheduling..."></textarea>
                    </div>
                    <br>

                <!-- Summary Info -->
                <div class="reschedule-summary-info">
                    <div class="reschedule-info-row">
                        <i class="fas fa-calendar-week"></i>
                        <span>Weekly usage: <strong id="rescheduleWeeklyUsageDisplay">-</strong></span>
                    </div>
                </div>

                <!-- Summary Buttons (Cancel button always visible) -->
                <div class="reschedule-summary-actions">
                    <button type="button" class="btn-cancel" onclick="closeRescheduleModal()">
                         Cancel
                    </button>
                    <button type="button" class="reschedule-btn-change" id="rescheduleChangeTime">
                        <i class="fas fa-edit"></i>
                        Change Selection
                    </button>
                    <button type="submit" class="btn-confirm" form="rescheduleForm">
                        <i class="fas fa-check"></i> Confirm Reschedule
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
<script <?= CSPNonce::getScriptNonceAttr() ?>>
    // Pass membership expiration data to JavaScript
    <?php if ($activeMembership): ?>
        window.membershipEndDate = '<?= $activeMembership['end_date'] ?>';
        window.membershipGracePeriodDays = 3;
        window.membershipPlanName = '<?= htmlspecialchars($activeMembership['plan_name']) ?>';

        // Calculate max booking date (end_date + grace period)
        const endDate = new Date('<?= $activeMembership['end_date'] ?>');
        const maxBookingDate = new Date(endDate);
        maxBookingDate.setDate(maxBookingDate.getDate() + 3);
        window.maxBookingDate = maxBookingDate.toISOString().split('T')[0];
    <?php else: ?>
        window.membershipEndDate = null;
        window.maxBookingDate = null;
    <?php endif; ?>
</script>

<?php require_once '../../includes/footer.php'; ?>
</body>

</html>
