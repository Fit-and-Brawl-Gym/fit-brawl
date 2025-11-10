<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$isLoggedIn = true;
$userName = $_SESSION['username'] ?? '';
$user_id = $_SESSION['user_id'];

// Check membership status
require_once '../../includes/membership_check.php';

// Determine avatar source
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
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
    $stmt->bind_param("ii", $user_id, $gracePeriodDays);
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
$additionalCSS = ['../css/pages/reservations.css?v=2.0.' . time()];
$additionalJS = ['../js/reservations.js?v=' . time() . mt_rand()];

require_once __DIR__ . '/../../includes/header.php';
?>

<!--Main Content-->
<main class="reservations-page">
    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

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
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-label">Weekly Bookings</div>
                        <div class="stat-value">
                            <span id="weeklyBookingsCount">-</span>
                            <span class="counter-max">/12</span>
                        </div>
                        <div class="stat-subtext" id="weeklyProgressText">Loading...</div>
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

                    <!-- Step 2: Select Session Time -->
                    <div class="wizard-step" id="step2" data-step="2">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <div class="step-info">
                                <div class="step-text">
                                    <h2 class="step-title">Select Session Time</h2>
                                    <p class="step-subtitle">Choose your preferred time block</p>
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
                            <div class="session-blocks">
                                <div class="session-block" data-session="Morning">
                                    <div class="session-icon">
                                        <i class="fas fa-sun"></i>
                                    </div>
                                    <div class="session-info">
                                        <h3 class="session-name">Morning</h3>
                                        <p class="session-time">7:00 AM - 11:00 AM</p>
                                        <p class="session-note">Arrive anytime during this window</p>
                                    </div>
                                    <div class="session-action">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                                <div class="session-block" data-session="Afternoon">
                                    <div class="session-icon">
                                        <i class="fas fa-cloud-sun"></i>
                                    </div>
                                    <div class="session-info">
                                        <h3 class="session-name">Afternoon</h3>
                                        <p class="session-time">1:00 PM - 5:00 PM</p>
                                        <p class="session-note">Arrive anytime during this window</p>
                                    </div>
                                    <div class="session-action">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                                <div class="session-block" data-session="Evening">
                                    <div class="session-icon">
                                        <i class="fas fa-moon"></i>
                                    </div>
                                    <div class="session-info">
                                        <h3 class="session-name">Evening</h3>
                                        <p class="session-time">6:00 PM - 10:00 PM</p>
                                        <p class="session-note">Arrive anytime during this window</p>
                                    </div>
                                    <div class="session-action">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Select Class Type -->
                    <div class="wizard-step" id="step3" data-step="3">
                        <div class="step-header">
                            <div class="step-number">3</div>
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

                    <!-- Step 4: Select Trainer -->
                    <div class="wizard-step" id="step4" data-step="4">
                        <div class="step-header">
                            <div class="step-number">4</div>
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
                            <div class="facility-capacity-info" id="facilityCapacityInfo">
                                <i class="fas fa-info-circle"></i>
                                <span>Checking trainer availability...</span>
                            </div>
                            <div class="trainers-grid" id="trainersGrid">
                                <!-- Trainers populated by JS -->
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
                                            <i class="fas fa-clock"></i> Session
                                        </span>
                                        <span class="summary-value" id="summarySession">-</span>
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
                                Upcoming
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

<script>
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

<!-- Booking Details Modal (placed after footer for proper z-index stacking) -->
<div class="booking-modal" id="bookingModal">
    <div class="booking-modal-overlay" onclick="closeBookingModal()"></div>
    <div class="booking-modal-content">
        <button class="booking-modal-close" onclick="closeBookingModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="booking-modal-header">
            <h2 class="booking-modal-title">Booking Details</h2>
            <div class="booking-modal-status" id="modalStatus"></div>
        </div>

        <div class="booking-modal-body">
            <div class="booking-detail-section">
                <h3><i class="fas fa-calendar-alt"></i> Session Information</h3>
                <div class="booking-detail-grid">
                    <div class="booking-detail-item">
                        <span class="detail-label">Date</span>
                        <span class="detail-value" id="modalDate">-</span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="detail-label">Day</span>
                        <span class="detail-value" id="modalDay">-</span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="detail-label">Time Slot</span>
                        <span class="detail-value" id="modalTimeSlot">-</span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value" id="modalDuration">-</span>
                    </div>
                </div>
            </div>

            <div class="booking-detail-section">
                <h3><i class="fas fa-dumbbell"></i> Training Details</h3>
                <div class="booking-detail-grid">
                    <div class="booking-detail-item">
                        <span class="detail-label">Class Type</span>
                        <span class="detail-value" id="modalClassType">-</span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="detail-label">Trainer</span>
                        <span class="detail-value" id="modalTrainer">-</span>
                    </div>
                </div>
            </div>

            <div class="booking-detail-section">
                <h3><i class="fas fa-info-circle"></i> Booking Info</h3>
                <div class="booking-detail-grid">
                    <div class="booking-detail-item">
                        <span class="detail-label">Booking ID</span>
                        <span class="detail-value" id="modalBookingId">-</span>
                    </div>
                    <div class="booking-detail-item">
                        <span class="detail-label">Booked On</span>
                        <span class="detail-value" id="modalBookedOn">-</span>
                    </div>
                </div>
            </div>

            <div class="booking-modal-qr" id="modalQRSection" style="display: none;">
                <h3><i class="fas fa-qrcode"></i> Check-in QR Code</h3>
                <div class="qr-code-container" id="modalQRCode">
                    <!-- QR code will be generated here -->
                </div>
                <p class="qr-instruction">Show this QR code to your trainer for session confirmation</p>
            </div>
        </div>

        <div class="booking-modal-footer">
            <button class="btn-modal-action btn-share" onclick="shareBooking()">
                <i class="fas fa-share-alt"></i> Share
            </button>
            <button class="btn-modal-action btn-cancel-modal" id="btnCancelModal" onclick="cancelBookingFromModal()" style="display: none;">
                <i class="fas fa-times-circle"></i> Cancel Booking
            </button>
            <button class="btn-modal-action btn-close-modal" onclick="closeBookingModal()">
                <i class="fas fa-check"></i> Close
            </button>
        </div>
    </div>
</div>

</body>

</html>