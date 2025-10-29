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

// Set avatar source
$avatarSrc = '../../../images/account-icon.svg';
if (isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../../images/account-icon.png";
}

// Get trainer information and upcoming bookings
$trainer_id = null;
$trainer_name = '';
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
$pageTitle = "Home - Fit and Brawl Trainer";
$currentPage = "home";
$additionalCSS = ["../../css/pages/loggedin-homepage.css", "../../css/pages/trainer/home.css"];

// Include header
require_once '../../../includes/trainer_header.php';
?>

    <!--Main-->
    <main>
        <section class="homepage-hero">
            <div class="hero-content">
                <div class="hero-underline top-line"></div>
                <h1>
                    BUILD A <span class="yellow">BODY</span> THAT<span class="apostrophe">&#39;</span>S<br>
                    BUILT FOR <span class="yellow">BATTLE</span>
                </h1>
                <p class="hero-sub"><span class="sub-underline">Ready to train champions?</span></p>
                <a href="schedule.php" class="hero-btn">View Schedule</a>
            </div>
        </section>

        <!-- Upcoming Section -->
        <section class="upcoming-section">
            <div class="container">
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
        </section>
    </main>

<?php require_once '../../../includes/trainer_footer.php'; ?>
