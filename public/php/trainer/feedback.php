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
// Handle API requests
if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');

    $current_user_id = $_SESSION['user_id'] ?? null;
    $plan_filter = $_GET['plan'] ?? 'all';
    $sort_by = $_GET['sort'] ?? 'recent';
    $search = $_GET['search'] ?? '';

    // Check optional columns
    $hasVisible = $conn->query("SHOW COLUMNS FROM feedback LIKE 'is_visible'")->num_rows > 0;
    $hasHelpful = $conn->query("SHOW COLUMNS FROM feedback LIKE 'helpful_count'")->num_rows > 0;

    // Build SQL
    $sql = "SELECT f.id, f.user_id, f.username, f.message, f.avatar, f.date";

    $sql .= $hasVisible ? ", f.is_visible" : ", 1 as is_visible";
    $sql .= $hasHelpful ? ", f.helpful_count, f.not_helpful_count" : ", 0 as helpful_count, 0 as not_helpful_count";
    $sql .= $current_user_id ? ", fv.vote_type as user_vote" : ", NULL as user_vote";
    $sql .= ", COALESCE(m.plan_name,'No Plan') as plan_name FROM feedback f";

    if ($current_user_id) {
        $sql .= " LEFT JOIN feedback_votes fv ON f.id = fv.feedback_id AND fv.user_id = ?";
    }

    $sql .= " LEFT JOIN users u ON f.user_id = u.id
              LEFT JOIN user_memberships um ON u.id = um.user_id AND um.membership_status='active'
              LEFT JOIN memberships m ON um.plan_id = m.id";

    $sql .= $hasVisible ? " WHERE f.is_visible = 1" : " WHERE 1=1";

    if ($plan_filter !== 'all') {
        $sql .= " AND m.plan_name = ?";
    }
    if ($search !== '') {
        $sql .= " AND (f.message LIKE ? OR f.username LIKE ?)";
    }

    $sql .= $sort_by === 'relevant' && $hasHelpful ? " ORDER BY f.helpful_count DESC, f.date DESC" : " ORDER BY f.date DESC";

    $stmt = $conn->prepare($sql);

    // Bind parameters dynamically
    $params = [];
    $types = '';

    if ($current_user_id) { $params[] = $current_user_id; $types .= 'i'; }
    if ($plan_filter !== 'all') { $params[] = $plan_filter; $types .= 's'; }
    if ($search !== '') { $searchParam = "%$search%"; $params[] = $searchParam; $params[] = $searchParam; $types .= 'ss'; }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $feedbacks = [];
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
    echo json_encode($feedbacks);
    exit;
}


// Determine avatar source for logged-in users
$avatarSrc = '../../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../../images/account-icon.svg";
}

// Set variables for header
$pageTitle = "Feedback - Fit and Brawl Trainer";
$currentPage = "feedback";
$additionalCSS = [PUBLIC_PATH . "/css/pages/feedback.css?=v2"];

// Include header
require_once '../../../includes/trainer_header.php';
?>
    <!--Main-->
    <main>
        <!-- Page Header -->
        <div class="feedback-header">
            <h1 class="feedback-title">Member Feedback</h1>
            <p class="feedback-subtitle">See what our community has to say about their Fit X Brawl experience</p>
            <div class="header-underline"></div>
        </div>

        <!-- Filters Section -->
    <div class="filters-container">
        <div class="filters-wrapper">
            <!-- Search Bar -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search feedback by keywords..." />
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <!-- Plan Filter -->
                <div class="filter-group">
                    <label for="planFilter">
                        <i class="fas fa-filter"></i>
                        <span>Plan:</span>
                    </label>
                    <select id="planFilter" class="filter-select">
                        <option value="all">All Plans</option>
                        <option value="Gladiator">Gladiator</option>
                        <option value="Brawler">Brawler</option>
                        <option value="Champion">Champion</option>
                        <option value="Clash">Clash</option>
                        <option value="Resolution Regular">Resolution Regular</option>
                        <option value="Resolution Student">Resolution Student</option>
                    </select>
                </div>

                <!-- Sort Filter -->
                <div class="filter-group">
                    <label for="sortFilter">
                        <i class="fas fa-sort"></i>
                        <span>Sort by:</span>
                    </label>
                    <select id="sortFilter" class="filter-select">
                        <option value="recent">Most Recent</option>
                        <option value="relevant">Most Helpful</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="feedback-container">
        <div class="feedback-section" id="feedback-section">
            <!-- Loading State -->
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading feedback...</p>
            </div>
        </div>
    </div>
     <div class="feedback-button fab-group">
        <button class="back-to-top" aria-label="Back to top">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>
    </main>

<script>
    const IMAGES_PATH = "<?= IMAGES_PATH ?>";
    const UPLOADS_PATH = "<?= UPLOADS_PATH ?>";
    const PUBLIC_PATH = "<?= PUBLIC_PATH ?>";
</script>
<script src="<?= PUBLIC_PATH ?>/js/feedback.js?v=<?= time() ?>"></script>

<?php require_once '../../../includes/trainer_footer.php'; ?>
