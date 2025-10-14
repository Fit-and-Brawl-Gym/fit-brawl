<?php
session_start();
require_once '../../includes/db_connect.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['email']);
$userName = $isLoggedIn && isset($_SESSION['username']) ? $_SESSION['username'] : '';

// Check membership status for header
require_once '../../includes/membership_check.php';

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/profile-icon.svg";
}

// Fetch trainers for the dropdown
$trainers_query = "SELECT * FROM trainers ORDER BY name";
$trainers_result = $conn->query($trainers_query);
$trainers = [];
while ($row = $trainers_result->fetch_assoc()) {
    $trainers[] = $row;
}

// Fetch user's active membership
$activeMembership = null;
if ($isLoggedIn && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $membership_query = "SELECT um.*, m.plan_name
                        FROM user_memberships um
                        JOIN memberships m ON um.membership_id = m.id
                        WHERE um.user_id = ? AND um.status = 'active' AND um.end_date >= CURDATE()
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduling - FitXBrawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/reservations.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
</head>
<body>
    <!--Header-->
    <header>
        <div class="wrapper">
            <div class="title">
                <a href="index.php">
                    <img src="../../images/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo">
                </a>
                <a href="index.php">
                    <img src="../../images/header-title.svg" alt="FITXBRAWL" class="logo-title">
                </a>
            </div>
            <nav class="nav-bar">
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="<?= $membershipLink ?>" class="active">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </nav>

            <?php if($isLoggedIn): ?>
                <div class="account-dropdown">
                    <img src="<?= $avatarSrc ?>" alt="Account" class="account-icon">
                    <div class="dropdown-menu">
                        <a href="user_profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="account-link">
                    <img src="../../images/profile-icon.svg" alt="Account" class="account-icon">
                </a>
            <?php endif; ?>
        </div>
    </header>

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
                            <button class="filter-btn" data-class="muay-thai">Muay Thai</button>
                            <button class="filter-btn" data-class="boxing">Boxing</button>
                            <button class="filter-btn" data-class="mma">MMA</button>
                            <button class="filter-btn active" data-class="all">All</button>
                        </div>

                        <h3 class="section-title">Coach Name:</h3>
                        <div class="coach-select-wrapper">
                            <select id="coachSelect" class="coach-select">
                                <option value="all">All Coaches</option>
                                <option value="coach-carlo">Coach Carlo</option>
                                <option value="coach-rieze">Coach Rieze</option>
                                <option value="coach-thei">Coach Thei</option>
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
    <footer>
        <div class="container footer-flex">
            <div class="footer-logo-block">
                <img src="../../images/footer-title.png" alt="FITXBRAWL" class="footer-logo-title">
            </div>
            <div class="footer-menu-block">
                <div class="footer-menu-title">MENU</div>
                <ul class="footer-menu-list">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="membership.php">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </div>
            <div class="footer-contact-block">
                <div class="footer-contact-title">CONTACT</div>
                <div class="footer-contact-details">
                    1832 Oroquieta Rd, Santa Cruz, Manila,<br>
                    1008 Metro Manila<br><br>
                    Gmail: fitxbrawl@gmail.com
                </div>
            </div>
            <div class="footer-hours-block">
                <div class="footer-hours-title">OPENING HOURS</div>
                <div class="footer-hours-details">
                    Sun–Fri: 9AM to 10PM<br>
                    Saturday: 10AM to 7PM
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Fit X Brawl, All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/header-dropdown.js"></script>
    <script src="../js/reservations.js"></script>
</body>
</html>
