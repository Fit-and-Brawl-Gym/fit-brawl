<?php
session_start();


if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    include __DIR__ . '/../../includes/db_connect.php';

    try {
        $sql = "SELECT id, name, category, status, description, image_path FROM equipment";
        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception($conn->error);
        }

        $equipment = [];
        while ($row = $result->fetch_assoc()) {
            $imageBase = '/fit-brawl/uploads/equipment/';
            $placeholder = '/fit-brawl/images/placeholder-equipment.jpg';

            $row['image_path'] = !empty($row['image_path'])
                ? (strpos($row['image_path'], '/fit-brawl/') === false
                    ? $imageBase . basename($row['image_path'])
                    : $row['image_path'])
                : $placeholder;

            $equipment[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $equipment]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}


// === MAIN PAGE ===
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/membership_check.php';
require_once __DIR__ . '/../../includes/session_manager.php';

// Initialize session manager
SessionManager::initialize();

// Redirect if not logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Avatar for header
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar
        ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar'])
        : "../../images/account-icon.svg";
}
// Check active membership

$hasActiveMembership = false;
$hasAnyRequest = false;
$gracePeriodDays = 3;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');

    // Check user_memberships table
    if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT request_status, membership_status, end_date
            FROM user_memberships
            WHERE user_id = ?
            ORDER BY date_submitted DESC
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("i", $user_id);
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
                    }
                }
            }

            $stmt->close();
        }


    } elseif ($conn->query("SHOW TABLES LIKE 'subscriptions'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT status, end_date
            FROM subscriptions
            WHERE user_id = ? AND status IN ('Approved','approved')
            ORDER BY date_submitted DESC
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
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
                    }
                }
            }

            $stmt->close();
        }
    }
}


if ($hasActiveMembership) {
    $membershipLink = 'reservations.php';
} elseif ($hasAnyRequest) {
    $membershipLink = 'membership-status.php';
} else {
    $membershipLink = 'membership.php';
}

$pageTitle = "Equipment - Fit and Brawl";
$currentPage = "equipment";
$additionalCSS = ['../css/pages/equipment.css?=v1'];
$additionalJS = ['../js/equipment.js'];

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>
        <div class="bg"></div>
 <!-- HERO -->
    <section class="equipment-hero">
        <div style="width:100%;margin:0;padding:var(--spacing-4) var(--spacing-12);">
        <h1 class="title"><strong style="color:var(--color-accent)">PLAN</strong> YOUR WORKOUT</h1>
        <h1 class="title">WITH <strong style="color:var(--color-accent)">CONFIDENCE</strong></h1>
        <p class="subtitle"> Choose the <strong style="color:var(--color-accent)">EQUIPMENT</strong> best for you!</p>
        </div>
    </section>

    <!--Main-->
    <main>
        <!-- Equipment Heading - Full Width -->
        <div class="panel-header">
            <h2>Equipment Availability</h2>
        </div>

        <!-- Equipment Wrapper - Contains Sidebar and Grid -->
        <div class="equipment-wrapper">
            <!-- Left Sidebar - Filters -->
            <aside class="filter-sidebar">
                <h3>Filters</h3>

                <!-- Search Section -->
                <div class="filter-section search-section">
                    <label for="equipmentSearch">Search</label>
                    <input type="search" id="equipmentSearch" placeholder="Search Equipment...">
                </div>

                <!-- Status Filter -->
                <div class="filter-section">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter">
                        <option value="all">All Equipment</option>
                        <option value="available">Available</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <!-- Categories -->
                <div class="filter-section">
                    <label>Categories</label>
                    <div class="categories-list" id="category-filters">
                        <div class="category-chip" data-category="cardio">
                            <img src="../../images/cardio-icon.svg" alt="Cardio">
                            <p>Cardio</p>
                        </div>
                        <div class="category-chip" data-category="flexibility">
                            <img src="../../images/flexibility-icon.svg" alt="Flexibility">
                            <p>Flexibility</p>
                        </div>
                        <div class="category-chip" data-category="core">
                            <img src="../../images/core-icon.svg" alt="Core">
                            <p>Core</p>
                        </div>
                        <div class="category-chip" data-category="strength">
                            <img src="../../images/strength-icon.svg" alt="Strength Training">
                            <p>Strength Training</p>
                        </div>
                        <div class="category-chip" data-category="functional">
                            <img src="../../images/functional-icon.svg" alt="Functional Training">
                            <p>Functional Training</p>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="equipment-content">
                <!-- equipment list -->
                <div id="equipment-container">
                    <!-- JS will render equipment cards here -->
                </div>
            </div>
        </div>
    </main>

    <!-- Back to Top Button -->
    <button class="back-to-top" aria-label="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
