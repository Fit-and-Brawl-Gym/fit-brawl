<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/config.php';

// Initialize session manager (handles session_start internally)
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

if (!isset($_SESSION['email']) && isset($_SESSION['remember_password'])) {
    $token = $_SESSION['remember_password'];

    $result = $conn->query("SELECT * FROM remember_password");
    while ($row = $result->fetch_assoc()) {
        if (password_verify($token, $row['token_hash'])) {
            $stmtUser = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmtUser->bind_param("s", $row['user_id']);
            $stmtUser->execute();
            $user = $stmtUser->get_result()->fetch_assoc();

            if ($user) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['avatar'] = $user['avatar'];
            }
            break;
        }
    }
}

// Redirect non-logged-in users
if (!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit;
}

$hasActiveMembership = false;
$hasAnyRequest = false;
$gracePeriodDays = 3;
$activeMembership = null;
$weeklyBookings = 0;
$upcomingBookings = [];
$favoriteTrainer = null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');

    // Check user_memberships table and get full membership details
    if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT um.*, m.plan_name, m.class_type
            FROM user_memberships um
            JOIN memberships m ON um.plan_id = m.id
            WHERE um.user_id = ?
            ORDER BY um.date_submitted DESC
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $requestStatus = $row['request_status'] ?? null;
                $membershipStatus = $row['membership_status'] ?? null;
                $endDate = $row['end_date'] ?? null;

                $hasAnyRequest = true;

                if ($requestStatus === 'approved' && $endDate) {
                    $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

                    if ($expiryWithGrace >= $today) {
                        $hasActiveMembership = true;
                        $hasAnyRequest = false;
                        $activeMembership = $row;
                    }
                }
            }

            $stmt->close();
        }
    }

    // If no membership found in user_memberships, check subscriptions table
    if (!$hasActiveMembership && $conn->query("SHOW TABLES LIKE 'subscriptions'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT s.*, m.plan_name, m.class_type
            FROM subscriptions s
            LEFT JOIN memberships m ON s.plan_id = m.id
            WHERE s.user_id = ? AND s.status IN ('Approved','approved')
            ORDER BY s.date_submitted DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $status = strtolower($row['status']);
                $endDate = $row['end_date'] ?? null;
                $hasAnyRequest = true;

                if ($status === 'approved' && $endDate) {
                    $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

                    if ($expiryWithGrace >= $today) {
                        $hasActiveMembership = true;
                        $hasAnyRequest = false;
                        // Create activeMembership array with subscription data
                        $activeMembership = [
                            'plan_name' => $row['plan_name'] ?? 'Subscription',
                            'class_type' => $row['class_type'] ?? 'All Classes',
                            'end_date' => $endDate,
                            'request_status' => 'approved',
                            'membership_status' => 'active'
                        ];
                    }
                }
            }

            $stmt->close();
        }
    }

    // Get weekly bookings count (current week: Monday to Sunday)
    // Calculate the start of the week (Monday)
    $currentDayOfWeek = date('N'); // 1 (Monday) to 7 (Sunday)
    $daysSinceMonday = $currentDayOfWeek - 1;
    $weekStart = date('Y-m-d', strtotime("-{$daysSinceMonday} days"));
    $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM user_reservations
        WHERE user_id = ?
        AND booking_date BETWEEN ? AND ?
        AND booking_status IN ('confirmed', 'completed')
    ");
    if ($stmt) {
        $stmt->bind_param("sss", $user_id, $weekStart, $weekEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $weeklyBookings = $row['count'];
        }
        $stmt->close();
    }

    // Get upcoming bookings (next 3)
    $stmt = $conn->prepare("
        SELECT ur.*, t.name as trainer_name, t.photo as trainer_photo
        FROM user_reservations ur
        LEFT JOIN trainers t ON ur.trainer_id = t.id
        WHERE ur.user_id = ?
        AND ur.booking_date >= CURDATE()
        AND ur.booking_status = 'confirmed'
        ORDER BY ur.booking_date ASC,
                 FIELD(ur.session_time, 'Morning', 'Afternoon', 'Evening')
        LIMIT 3
    ");
    if ($stmt) {
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $upcomingBookings[] = $row;
        }
        $stmt->close();
    }

    // Get favorite trainer (most booked)
    $stmt = $conn->prepare("
        SELECT t.name, t.photo, COUNT(*) as booking_count
        FROM user_reservations ur
        JOIN trainers t ON ur.trainer_id = t.id
        WHERE ur.user_id = ?
        AND ur.booking_status = 'confirmed'
        GROUP BY ur.trainer_id
        ORDER BY booking_count DESC
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $favoriteTrainer = $row;
        }
        $stmt->close();
    }
}


if ($hasActiveMembership) {
    $membershipLink = 'reservations.php';
} elseif ($hasAnyRequest) {
    $membershipLink = 'membership-status.php';
} else {
    $membershipLink = 'membership.php';
}
// Set avatar source
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

// Set variables for header
$pageTitle = "Homepage - Fit and Brawl";
$currentPage = "home";
$additionalCSS = [PUBLIC_PATH . "/css/pages/loggedin-homepage.css?v=" . time()];

// Include header
require_once __DIR__ . '/../../includes/header.php';

// Time-based greeting
$hour = date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
$userName = $_SESSION['name'] ?? 'Member';

// Calculate days remaining in membership
$daysRemaining = 0;
if ($activeMembership && isset($activeMembership['end_date'])) {
    $endDate = new DateTime($activeMembership['end_date']);
    $currentDate = new DateTime();
    $interval = $currentDate->diff($endDate);
    $daysRemaining = $interval->days;
}

// Session time mapping
$sessionHours = [
    'Morning' => '7:00 AM - 11:00 AM',
    'Afternoon' => '1:00 PM - 5:00 PM',
    'Evening' => '6:00 PM - 10:00 PM'
];
?>

<!--Main-->
<main class="dashboard-main">
    <!-- Welcome Header -->
    <section class="welcome-section">
        <div class="welcome-content">
            <div class="welcome-text">
                <h1 class="greeting"><?= htmlspecialchars($greeting) ?>, <span
                        class="user-name"><?= htmlspecialchars($userName) ?></span></h1>
                <p class="welcome-subtitle">Ready to push your limits today?</p>
            </div>
            <?php if ($hasActiveMembership): ?>
                <div class="quick-stats-bar">
                    <div class="stat-pill">
                        <i class="fas fa-calendar-week"></i>
                        <span><?= $weeklyBookings ?>/12 sessions</span>
                    </div>
                    <div class="stat-pill">
                        <i class="fas fa-clock"></i>
                        <span><?= $daysRemaining ?> days left</span>
                    </div>
                    <?php if ($favoriteTrainer): ?>
                        <div class="stat-pill">
                            <i class="fas fa-star"></i>
                            <span><?= htmlspecialchars($favoriteTrainer['name']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="dashboard-container">
        <!-- Main Action Card - Book Session -->
        <div class="action-card primary-action">
            <div class="card-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="card-content">
                <h2>Book a Session</h2>
                <p>Schedule your next training session</p>
            </div>
            <a href="<?= htmlspecialchars($membershipLink) ?>" class="card-btn">
                Book Now <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Booked Sessions Card -->
            <div class="dashboard-card upcoming-card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Booked Sessions</h3>
                    <?php if (!empty($upcomingBookings)): ?>
                        <a href="reservations.php" class="view-all">View All</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($upcomingBookings)): ?>
                        <?php foreach ($upcomingBookings as $booking): ?>
                            <div class="booking-preview">
                                <div class="booking-date-badge">
                                    <div class="badge-day"><?= date('d', strtotime($booking['booking_date'])) ?></div>
                                    <div class="badge-month"><?= date('M', strtotime($booking['booking_date'])) ?></div>
                                </div>
                                <div class="booking-info">
                                    <h4><?= htmlspecialchars($booking['class_type']) ?></h4>
                                    <p class="booking-details">
                                        <span><i class="fas fa-clock"></i>
                                            <?php 
                                            // Check if time-based booking
                                            if (!empty($booking['start_time']) && !empty($booking['end_time'])) {
                                                $startTime = new DateTime($booking['start_time']);
                                                $endTime = new DateTime($booking['end_time']);
                                                echo $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A');
                                            } else {
                                                // Legacy session-based
                                                echo htmlspecialchars($booking['session_time']);
                                            }
                                            ?>
                                        </span>
                                        <span><i class="fas fa-user"></i>
                                            <?= htmlspecialchars($booking['trainer_name']) ?></span>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No booked sessions</p>
                            <a href="<?= htmlspecialchars($membershipLink) ?>" class="empty-cta">Book your first session</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Weekly Progress Card -->
            <div class="dashboard-card progress-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> Weekly Progress</h3>
                </div>
                <div class="card-body">
                    <div class="progress-stats">
                        <div class="progress-number">
                            <?php 
                            // Get user's membership weekly limit
                            $limit_query = "SELECT m.weekly_hours_limit
                                           FROM user_memberships um
                                           JOIN memberships m ON um.plan_id = m.id
                                           WHERE um.user_id = ?
                                           AND um.membership_status = 'active'
                                           AND DATE_ADD(um.end_date, INTERVAL 3 DAY) >= CURDATE()
                                           ORDER BY um.end_date DESC
                                           LIMIT 1";
                            $limit_stmt = $conn->prepare($limit_query);
                            $limit_stmt->bind_param('s', $user_id);
                            $limit_stmt->execute();
                            $limit_result = $limit_stmt->get_result();
                            $limit_row = $limit_result->fetch_assoc();
                            $limit_stmt->close();
                            $weeklyHourLimit = $limit_row ? (int)$limit_row['weekly_hours_limit'] : 48;
                            
                            // Calculate hours used from weeklyBookings duration
                            $weekStart = new DateTime();
                            $weekStart->modify('Sunday this week')->setTime(0, 0, 0);
                            $weekEnd = clone $weekStart;
                            $weekEnd->modify('+6 days')->setTime(23, 59, 59);
                            
                            $hours_query = "SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
                                           FROM user_reservations
                                           WHERE user_id = ?
                                           AND start_time >= ?
                                           AND start_time <= ?
                                           AND booking_status IN ('confirmed', 'completed')
                                           AND start_time IS NOT NULL
                                           AND end_time IS NOT NULL";
                            $hours_stmt = $conn->prepare($hours_query);
                            $weekStartStr = $weekStart->format('Y-m-d H:i:s');
                            $weekEndStr = $weekEnd->format('Y-m-d H:i:s');
                            $hours_stmt->bind_param('sss', $user_id, $weekStartStr, $weekEndStr);
                            $hours_stmt->execute();
                            $hours_result = $hours_stmt->get_result();
                            $hours_row = $hours_result->fetch_assoc();
                            $hours_stmt->close();
                            
                            $totalMinutes = (int)($hours_row['total_minutes'] ?? 0);
                            $hoursUsed = floor($totalMinutes / 60);
                            $minutesUsed = $totalMinutes % 60;
                            $displayUsed = $minutesUsed > 0 ? "{$hoursUsed}h {$minutesUsed}m" : "{$hoursUsed}h";
                            ?>
                            <span class="big-number"><?= $displayUsed ?></span>
                            <span class="small-text">/ <?= $weeklyHourLimit ?>h this week</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar">
                                <div class="progress-fill"
                                    style="width: <?= min(100, ($totalMinutes / ($weeklyHourLimit * 60)) * 100) ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php if ($weeklyBookings > 0): ?>
                        <div class="motivation-message">
                            <?php if ($weeklyBookings >= 10): ?>
                                <i class="fas fa-fire"></i> <span>You're on fire! Keep it up!</span>
                            <?php elseif ($weeklyBookings >= 6): ?>
                                <i class="fas fa-bolt"></i> <span>Great progress this week!</span>
                            <?php else: ?>
                                <i class="fas fa-dumbbell"></i> <span>Let's keep pushing!</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Membership Status Card -->
            <div class="dashboard-card membership-card">
                <div class="card-header">
                    <h3><i class="fas fa-id-card"></i> Membership Status</h3>
                </div>
                <div class="card-body">
                    <?php if ($hasActiveMembership && $activeMembership): ?>
                        <div class="membership-info">
                            <div class="membership-badge active">
                                <i class="fas fa-check-circle"></i> Active
                            </div>
                            <h4 class="plan-name"><?= htmlspecialchars($activeMembership['plan_name']) ?> Plan</h4>
                            <div class="membership-details">
                                <div class="detail-row">
                                    <span class="detail-label">Classes:</span>
                                    <span
                                        class="detail-value"><?= htmlspecialchars($activeMembership['class_type']) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Expires:</span>
                                    <span
                                        class="detail-value"><?= date('M d, Y', strtotime($activeMembership['end_date'])) ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Days Left:</span>
                                    <span class="detail-value highlight"><?= $daysRemaining ?> days</span>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($hasAnyRequest): ?>
                        <div class="membership-pending">
                            <i class="fas fa-hourglass-half"></i>
                            <p>Your membership request is pending approval</p>
                            <a href="membership-status.php" class="status-link">Check Status</a>
                        </div>
                    <?php else: ?>
                        <div class="no-membership">
                            <i class="fas fa-ticket-alt"></i>
                            <p>You don't have an active membership</p>
                            <a href="membership.php" class="get-membership-btn">Get Membership</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>
