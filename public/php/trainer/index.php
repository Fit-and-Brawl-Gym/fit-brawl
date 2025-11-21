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

// Set avatar source
$avatarSrc = '../../../images/account-icon.svg';
if (isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../../images/account-icon.svg";
}

// Get trainer information and upcoming bookings
$trainer_id = null;
$trainer_name = '';
$upcoming_bookings = [];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Get trainer details from trainers table based on user email
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
        } else {
            // Debug helper if no match found
            error_log("Trainer not found for email: " . $trainer_email);
        }
        $stmt->close();
    }

    // Fetch upcoming bookings (next 5 bookings starting from today)
    if ($trainer_id) {
        $upcoming_query = "SELECT ur.*, u.username as member_name, u.email as member_email
                          FROM user_reservations ur
                          JOIN users u ON ur.user_id = u.id
                          WHERE ur.trainer_id = ?
                          AND ur.booking_date >= CURDATE()
                          AND ur.booking_status = 'confirmed'
                          ORDER BY ur.booking_date ASC,
                                   FIELD(ur.session_time, 'Morning', 'Afternoon', 'Evening')
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

// Define session time ranges
$sessionRanges = [
    'Morning'   => '7-11 AM',
    'Afternoon' => '1-5 PM',
    'Evening'   => '6-10 PM'
];

// Set variables for header
$pageTitle = "Home - Fit and Brawl Trainer";
$currentPage = "home";
$additionalCSS = [PUBLIC_PATH . "/css/pages/loggedin-homepage.css", PUBLIC_PATH . "/css/pages/trainer/home.css"];

// Include header
?>

    <!--Main-->
    <main>
        <?php require_once '../../../includes/trainer_header.php';?>
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
                                        <?= date('M j, Y', strtotime($booking['booking_date'])) ?>
                                    </span>
                                    <span class="card-time">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars($booking['session_time'] . ' (' . ($sessionRanges[$booking['session_time']] ?? 'N/A') . ')') ?>
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