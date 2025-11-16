<?php
// Check if this is an API request

require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/config.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    include __DIR__ . '/../../includes/db_connect.php';

    try {
        $sql = "SELECT id, name, category as cat, stock, status, image_path as image FROM products";
        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception($conn->error);
        }

        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Normalize status values
            $status = strtolower($row['status']);
            $row['status'] = $status;

            // Ensure image path is valid
            if (empty($row['image'])) {
                $row['image'] = '../../uploads/products' . strtolower(str_replace(' ', '-', $row['name'])) . '.jpg';
            }

            $products[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $products]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

require_once __DIR__ . '/../../includes/db_connect.php';

// Check membership status for header
require_once __DIR__ . '/../../includes/membership_check.php';
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
// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

// Set variables for header
$pageTitle = "Products - Fit and Brawl";
$currentPage = "products";
$additionalCSS = [PUBLIC_PATH . "/css/pages/products.css"];

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>
<!--Main-->
<main class="container">

    <!-- Products Wrapper - Contains Sidebar and Grid -->
    <div class="products-wrapper">
        <!-- Left Sidebar - Filters -->
        <aside class="filter-sidebar">
            <h3>Filters</h3>

            <!-- Search Section -->
            <div class="filter-section search-section">
                <input type="search" id="q" placeholder="Search products..." aria-label="Search products">
            </div>

            <!-- Status Filter -->
            <div class="filter-section">
                <label for="statusFilter">Status</label>
                <select id="statusFilter">
                    <option value="all">All Products</option>
                    <option value="in">In Stock</option>
                    <option value="low">Low on Stock</option>
                    <option value="out">Out of Stock</option>
                </select>
            </div>

            <!-- Categories -->
            <div class="filter-section">
                <label>Categories</label>
                <div class="categories-list">
                    <div class="category-chip" data-cat="supplements">
                        <img src="../../images/supplements-icon.svg" alt="Supplements">
                        <p>Supplements</p>
                    </div>
                    <div class="category-chip" data-cat="hydration">
                        <img src="../../images/hydration-icon.svg" alt="Hydration & Drinks">
                        <p>Hydration and Drinks</p>
                    </div>
                    <div class="category-chip" data-cat="snacks">
                        <img src="../../images/snacks-icon.svg" alt="Snacks">
                        <p>Snacks</p>
                    </div>
                    <div class="category-chip" data-cat="boxing gloves">
                        <img src="../../images/boxing-icon.svg" alt="Boxing and Muay Thai Gloves">
                        <p>Boxing and Muay Thai</p>
                    </div>
                    <div class="category-chip" data-cat="accessories">
                        <img src="../../images/strength-icon.svg" alt="Accessories">
                        <p>Accessories</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="products-content">
            <div class="products-content-header">
                <h1>Products</h1>
             </div>
            <div class="products-panel">
                <!-- Grid -->
                <div id="grid" class="grid"></div>
            </div>
        </div>
    </div>
</main>

<!-- Back to Top Button -->
<button class="back-to-top" aria-label="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

<script src="<?= PUBLIC_PATH ?>/js/products.js?=v1"></script>