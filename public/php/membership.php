<?php
// Check if this is an API request
require_once __DIR__ . '/../../includes/session_manager.php';
require_once __DIR__ . '/../../includes/csp_nonce.php';
require_once __DIR__ . '/../../includes/config.php';

// Generate CSP nonces for this request
CSPNonce::generate();

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Allow non-logged-in users to view membership plans
// Only require login for purchase/join actions (see below)

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

// Check if user is logged in
$isLoggedIn = isset($_SESSION['email']);

// Check if user has active membership
require_once __DIR__ . '/../../includes/db_connect.php';
$hasActiveMembership = false;
$activeMembershipDetails = null;
$isInGracePeriod = false;
$gracePeriodDays = 3; // Same as reservations.php

if ($isLoggedIn && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $today = date('Y-m-d');

    // Check user_memberships table (including grace period)
    if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows) {
        $stmt = $conn->prepare("
            SELECT id, plan_name, end_date
            FROM user_memberships
            WHERE user_id = ?
            AND request_status = 'approved'
            AND membership_status = 'active'
            AND DATE_ADD(end_date, INTERVAL ? DAY) >= ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("sis", $user_id, $gracePeriodDays, $today);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $hasActiveMembership = true;
                $activeMembershipDetails = $result->fetch_assoc();
                // Check if in grace period
                $isInGracePeriod = strtotime($activeMembershipDetails['end_date']) < strtotime($today);
            }
            $stmt->close();
        }
    }
}

if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');

    $sql = "SELECT id, plan_name, price, duration FROM memberships";
    $result = $conn->query($sql);

    $memberships = [];
    while ($row = $result->fetch_assoc()) {
        $memberships[] = $row;
    }

    echo json_encode($memberships);
    exit;
}

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'account-icon.svg' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

// Set variables for header
$pageTitle = "Membership Plans - Fit and Brawl";
$currentPage = "membership";
$additionalCSS = [PUBLIC_PATH . "/css/pages/membership.css?v=" . time() . mt_rand()];
$additionalJS = ["../js/membership.js?v=" . time() . mt_rand()];

// Include header
require_once __DIR__ . '/../../includes/header.php';
?>

<!--Main-->
<main class="membership-main">
    <!-- Error/Success Notification -->
    <?php if (isset($_SESSION['plan_error'])): ?>
        <div class="notification error-notification">
            <span class="notification-icon">⚠️</span>
            <span class="notification-text"><?php echo htmlspecialchars($_SESSION['plan_error']); ?></span>
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
        </div>
        <?php unset($_SESSION['plan_error']); ?>
    <?php endif; ?>

    <?php if ($hasActiveMembership && !$isInGracePeriod): ?>
        <!-- Active Membership Notice - Consistent Design -->
        <section class="active-membership-section">
            <div class="active-membership-container">
                <!-- Icon and Badge -->
                <div class="membership-badge">
                    <i class="fas fa-trophy"></i>
                </div>

                <!-- Title -->
                <h1 class="membership-title">
                    ACTIVE <span class="highlight">MEMBERSHIP</span>
                </h1>

                <!-- Membership Details Card -->
                <div class="membership-details-card">
                    <div class="detail-row">
                        <div class="detail-label">Current Plan</div>
                        <div class="detail-value plan-name">
                            <?= htmlspecialchars($activeMembershipDetails['plan_name']) ?>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Valid Until</div>
                        <div class="detail-value">
                            <i class="far fa-calendar-alt"></i>
                            <?= date('F d, Y', strtotime($activeMembershipDetails['end_date'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <div class="info-icon">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="info-content">
                        <h3 class="info-title">Want to Change or Upgrade Your Plan?</h3>
                        <p class="info-text">
                            Please visit our gym in person to change or upgrade your membership plan.
                            Our staff will be happy to assist you!
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="membership-actions">
                    <a href="loggedin-index.php" class="membership-btn primary-btn">
                        <i class="fas fa-home"></i>
                        <span>Go to Dashboard</span>
                    </a>
                    <a href="reservations.php" class="membership-btn secondary-btn">
                        <i class="fas fa-calendar-check"></i>
                        <span>Book a Session</span>
                    </a>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- Show membership plans for: non-members OR members in grace period -->

        <!-- Hero Section -->
        <section class="membership-hero">
            <h1 class="hero-title">CHOOSE YOUR <span class="yellow">JOURNEY </span> <span class="exclamation">!</span></h1>
            <p class="hero-subtitle">Unlock Your Full Potential with Our Plans By Being a Member</p>
        </section>

        <!-- Plans Carousel -->
        <section class="plans-carousel">
            <button class="carousel-btn prev" id="prevBtn">
                <i class="fas fa-chevron-left"></i>
            </button>

            <div class="plans-container" id="plansContainer">
                <div class="plans-viewport">
                    <!-- All plans in order - Resolution first, Gladiator in center -->
                    <div class="plan-card" data-plan="resolution-regular" data-category="non-member">
                        <h3 class="plan-name">RESOLUTION</h3>
                        <p class="plan-subtitle">MEMBERSHIP IN GYM</p>
                        <div class="plan-price">2,200 PHP <span>/MONTH</span></div>
                        <div class="plan-savings">(Save 1,400 PHP)</div>
                        <ul class="plan-features">
                            <li><strong>24 Hours Training/Week</strong></li>
                            <li>Gym Equipment Access with Face Recognition</li>
                            <li>Shower Access</li>
                            <li>Locker Access</li>
                        </ul>
                        <button class="select-btn">SELECT PLAN</button>
                    </div>

                    <div class="plan-card" data-plan="brawler" data-category="member">
                        <h3 class="plan-name">BRAWLER</h3>
                        <p class="plan-subtitle">MEMBERSHIP IN MUAY THAI</p>
                        <div class="plan-price">11,500 PHP <span>/MONTH</span></div>
                        <div class="plan-savings">(Save 3,500 PHP)</div>
                        <ul class="plan-features">
                            <li><strong>36 Hours Training/Week</strong></li>
                            <li>Muay Thai Training with Professional Coaches</li>
                            <li>MMA Area Access</li>
                            <li>Free Orientation and Fitness Assessment</li>
                            <li>Shower Access</li>
                            <li>Locker Access</li>
                        </ul>
                        <button class="select-btn">SELECT PLAN</button>
                    </div>

                    <div class="plan-card gladiator-plan" data-plan="gladiator" data-category="member">
                        <div class="popular-badge">POPULAR CHOICE!!</div>
                        <h3 class="plan-name">GLADIATOR</h3>
                        <p class="plan-subtitle">MEMBERSHIP IN BOXING AND MMA</p>
                        <div class="plan-price featured-price">
                            <div class="price-main">14,500 PHP <span>/MONTH</span></div>
                        </div>
                        <ul class="plan-features">
                            <li><strong>48 Hours Training/Week</strong></li>
                            <li>Boxing Training with Professional Coaches</li>
                            <li>MMA Training with Professional Coaches</li>
                            <li>Boxing and MMA Area Access</li>
                            <li>Gym Equipment Access</li>
                            <li>Jakuzzi Access</li>
                            <li>Shower Access</li>
                            <li>Locker Access</li>
                        </ul>
                        <button class="select-btn gladiator-btn">SELECT PLAN</button>
                    </div>

                    <div class="plan-card" data-plan="champion" data-category="member">
                        <h3 class="plan-name">CHAMPION</h3>
                        <p class="plan-subtitle">MEMBERSHIP IN BOXING</p>
                        <div class="plan-price">7,000 PHP <span>/MONTH</span></div>
                        <div class="plan-savings">(Save 3,500 PHP)</div>
                        <ul class="plan-features">
                            <li><strong>36 Hours Training/Week</strong></li>
                            <li>Boxing Training with Professional Coaches</li>
                            <li>MMA Area Access</li>
                            <li>Free Orientation and Fitness Assessment</li>
                            <li>Shower Access</li>
                            <li>Locker Access</li>
                        </ul>
                        <button class="select-btn">SELECT PLAN</button>
                    </div>

                    <div class="plan-card" data-plan="clash" data-category="non-member">
                        <h3 class="plan-name">CLASH</h3>
                        <p class="plan-subtitle">MEMBERSHIP IN MMA</p>
                        <div class="plan-price">13,500 PHP <span>/MONTH</span></div>
                        <div class="plan-savings">(Save 4,500 PHP)</div>
                        <ul class="plan-features">
                            <li><strong>36 Hours Training/Week</strong></li>
                            <li>MMA Training with Professional Coaches</li>
                            <li>MMA Area Access</li>
                            <li>Free Orientation and Fitness Assessment</li>
                            <li>Shower Access</li>
                            <li>Locker Access</li>
                        </ul>
                        <button class="select-btn">SELECT PLAN</button>
                    </div>
                </div>
            </div>

            <button class="carousel-btn next" id="nextBtn">
                <i class="fas fa-chevron-right"></i>
            </button>
        </section>

        <!-- Plan Comparison Section -->
        <section class="comparison-section">
            <div class="comparison-header">
                <h2 class="comparison-title">NOT SURE WHICH PLAN?</h2>
                <p class="comparison-subtitle">Compare all membership plans side by side</p>
                <button class="comparison-toggle-btn" id="comparisonToggleBtn">
                    <i class="fas fa-table"></i>
                    <span class="toggle-text">Compare Plans</span>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </button>
            </div>

            <div class="comparison-table-container" id="comparisonTableContainer">
                <div class="comparison-table-wrapper">
                    <table class="comparison-table">
                        <thead>
                            <tr>
                                <th class="feature-column">Features</th>
                                <th class="plan-column">
                                    <div class="plan-header">
                                        <span class="plan-name-header">RESOLUTION</span>
                                        <span class="plan-subtitle-header">Gym Access</span>
                                        <span class="plan-price-header">2,200&nbsp;PHP<small>/month</small></span>
                                        <span class="plan-savings-header">(Save 1,400)</span>
                                    </div>
                                </th>
                                <th class="plan-column">
                                    <div class="plan-header">
                                        <span class="plan-name-header">BRAWLER</span>
                                        <span class="plan-subtitle-header">Muay Thai</span>
                                        <span class="plan-price-header">11,500&nbsp;PHP<small>/month</small></span>
                                        <span class="plan-savings-header">(Save 3,500)</span>
                                    </div>
                                </th>
                                <th class="plan-column featured-column">
                                    <div class="plan-header">
                                        <span class="popular-badge-table">⭐ POPULAR ⭐</span>
                                        <span class="plan-name-header">GLADIATOR</span>
                                        <span class="plan-subtitle-header">All-Access</span>
                                        <span class="plan-price-header">14,500&nbsp;PHP<small>/month</small></span>
                                    </div>
                                </th>
                                <th class="plan-column">
                                    <div class="plan-header">
                                        <span class="plan-name-header">CHAMPION</span>
                                        <span class="plan-subtitle-header">Boxing</span>
                                        <span class="plan-price-header">7,000&nbsp;PHP<small>/month</small></span>
                                        <span class="plan-savings-header">(Save 3,500)</span>
                                    </div>
                                </th>
                                <th class="plan-column">
                                    <div class="plan-header">
                                        <span class="plan-name-header">CLASH</span>
                                        <span class="plan-subtitle-header">MMA</span>
                                        <span class="plan-price-header">13,500&nbsp;PHP<small>/month</small></span>
                                        <span class="plan-savings-header">(Save 4,500)</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Weekly Training Hours -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-clock feature-icon"></i>
                                    Weekly Training Hours
                                </td>
                                <td class="feature-value"><strong>24 hrs</strong></td>
                                <td class="feature-value"><strong>36 hrs</strong></td>
                                <td class="feature-value"><strong>48 hrs</strong></td>
                                <td class="feature-value"><strong>36 hrs</strong></td>
                                <td class="feature-value"><strong>36 hrs</strong></td>
                            </tr>

                            <!-- Gym Access -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-dumbbell feature-icon"></i>
                                    Gym Equipment Access
                                </td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                            </tr>

                            <!-- Face Recognition -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-user-check feature-icon"></i>
                                    Face Recognition Access
                                </td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                            </tr>

                            <!-- Shower Access -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-shower feature-icon"></i>
                                    Shower Access
                                </td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                            </tr>

                            <!-- Locker Access -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-lock feature-icon"></i>
                                    Locker Access
                                </td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                            </tr>

                            <!-- MMA Area Access -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-ring feature-icon"></i>
                                    MMA Area Access
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                            </tr>

                            <!-- Boxing Training -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-hand-rock feature-icon"></i>
                                    Boxing Training
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                            </tr>

                            <!-- Muay Thai Training -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-fist-raised feature-icon"></i>
                                    Muay Thai Training
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                            </tr>

                            <!-- MMA Training -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-shield-alt feature-icon"></i>
                                    MMA Training
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                            </tr>

                            <!-- BJJ Training -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-hands feature-icon"></i>
                                    Brazilian Jiu-Jitsu
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                            </tr>

                            <!-- Wrestling Training -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-running feature-icon"></i>
                                    Wrestling Training
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                            </tr>

                            <!-- Professional Coaches -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-user-tie feature-icon"></i>
                                    Professional Coaches
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                            </tr>

                            <!-- Orientation & Assessment -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-clipboard-check feature-icon"></i>
                                    Orientation & Assessment
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                            </tr>

                            <!-- Nutrition Consultation -->
                            <tr>
                                <td class="feature-name">
                                    <i class="fas fa-apple-alt feature-icon"></i>
                                    Nutrition Consultation
                                </td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value included"><i class="fas fa-check"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                                <td class="feature-value not-included"><i class="fas fa-times"></i></td>
                            </tr>

                            <!-- Select Button Row -->
                            <tr class="cta-row">
                                <td class="feature-name"></td>
                                <td class="feature-value">
                                    <button class="comparison-select-btn" data-plan="resolution-regular"
                                        data-category="non-member">
                                        SELECT PLAN
                                    </button>
                                </td>
                                <td class="feature-value">
                                    <button class="comparison-select-btn" data-plan="brawler" data-category="member">
                                        SELECT PLAN
                                    </button>
                                </td>
                                <td class="feature-value">
                                    <button class="comparison-select-btn featured-btn" data-plan="gladiator"
                                        data-category="member">
                                        SELECT PLAN
                                    </button>
                                </td>
                                <td class="feature-value">
                                    <button class="comparison-select-btn" data-plan="champion" data-category="member">
                                        SELECT PLAN
                                    </button>
                                </td>
                                <td class="feature-value">
                                    <button class="comparison-select-btn" data-plan="clash" data-category="non-member">
                                        SELECT PLAN
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Pricing Tables -->
        <section class="pricing-section">
            <div class="pricing-header" id="pricingHeader">
                <h2 class="pricing-title">TRAIN FOR A DAY</h2>
                <p class="pricing-subtitle">Single Day Passes</p>
            </div>

            <!-- Members Services -->
            <div class="services-grid-container active" id="memberTable"
                data-has-membership="<?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'true' : 'false'; ?>">
                <?php if ($hasActiveMembership && !$isInGracePeriod): ?>
                    <div
                        style="background: rgba(255, 206, 0, 0.1); border: 2px solid rgba(255, 206, 0, 0.3); border-radius: 16px; padding: 24px; margin-bottom: 32px; text-align: center;">
                        <i class="fas fa-info-circle"
                            style="color: var(--color-accent); font-size: 32px; margin-bottom: 16px;"></i>
                        <h3
                            style="color: var(--color-accent); font-family: var(--font-family-display); font-size: 24px; margin-bottom: 12px; text-transform: uppercase;">
                            You Already Have an Active Membership</h3>
                        <p style="color: rgba(255, 255, 255, 0.9); font-size: 18px; line-height: 1.6;">Single day passes are
                            only available for non-members. As an active member, you already have access to the facilities
                            included in your membership plan.</p>
                    </div>
                <?php endif; ?>

                <div class="services-grid">
                    <div class="service-card <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'disabled' : ''; ?>"
                        data-price="350 PHP" data-service="Training: Boxing"
                        data-benefits="Full-day access to boxing area. Focused on footwork, defense, and power punching technique. Ideal for rapid skill improvement, pad work, and personalized fight strategies.">
                        <div class="service-price">350 PHP</div>
                        <div class="service-name">Training: Boxing</div>
                        <div class="service-benefits">Full-day access to boxing area. Focused on footwork, defense, and
                            power punching technique. Ideal for rapid skill improvement, pad work, and personalized fight
                            strategies.</div>
                        <button class="service-select-btn" <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'disabled' : ''; ?>>
                            <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'DISABLED' : 'SELECT SERVICE'; ?>
                        </button>
                    </div>

                    <div class="service-card <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'disabled' : ''; ?>"
                        data-price="400 PHP" data-service="Training: Muay Thai"
                        data-benefits="Full-day access to mma area. Includes in-depth training on clinch work, teeps, and powerful low kicks. Perfect for mastering traditional techniques and conditioning.">
                        <div class="service-price">400 PHP</div>
                        <div class="service-name">Training: Muay Thai</div>
                        <div class="service-benefits">Full-day access to mma area. Includes in-depth training on clinch
                            work, teeps, and powerful low kicks. Perfect for mastering traditional techniques and
                            conditioning.</div>
                        <button class="service-select-btn" <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'disabled' : ''; ?>>
                            <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'DISABLED' : 'SELECT SERVICE'; ?>
                        </button>
                    </div>

                    <div class="service-card <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'disabled' : ''; ?>"
                        data-price="500 PHP" data-service="Training: MMA"
                        data-benefits="A 75-minute comprehensive session that integrates striking (boxing/Muay Thai), wrestling, and Brazilian Jiu-Jitsu (BJJ) for a well-rounded combat experience. Ideal for competitive fighters or those wanting an intense, varied workout.">
                        <div class="service-price">500 PHP</div>
                        <div class="service-name">Training: MMA</div>
                        <div class="service-benefits">A 75-minute comprehensive session that integrates striking
                            (boxing/Muay Thai), wrestling, and Brazilian Jiu-Jitsu (BJJ) for a well-rounded combat
                            experience. Ideal for competitive fighters or those wanting an intense, varied workout.</div>
                        <button class="service-select-btn" <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'disabled' : ''; ?>>
                            <?php echo ($hasActiveMembership && !$isInGracePeriod) ? 'DISABLED' : 'SELECT SERVICE'; ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contact Button -->
            <div class="contact-cta">
                <p class="contact-text">Still unsure?</p>
                <a href="contact.php" class="contact-btn">CONTACT US NOW<span class="exclamation-mark">!</span></a>
            </div>
        </section>

        <!-- Service Selection Modal -->
        <div class="service-modal" id="serviceModal">
            <div class="modal-overlay" id="modalOverlay"></div>
            <div class="modal-content">
                <button class="modal-close" id="modalClose">
                    <i class="fas fa-times"></i>
                </button>

                <div class="modal-header">
                    <h2 class="modal-title" id="modalTitle">Select Service</h2>
                </div>

                <div class="modal-body">
                    <div class="service-info">
                        <div class="info-row">
                            <span class="info-label">Price:</span>
                            <span class="info-value price" id="modalPrice">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Service:</span>
                            <span class="info-value" id="modalService">-</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Benefits:</span>
                            <span class="info-value" id="modalBenefits">-</span>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button class="action-btn purchase-btn" id="purchaseBtn">
                            <i class="fas fa-shopping-cart"></i>
                            Proceed to Transaction
                        </button>
                        <button class="action-btn inquire-btn" id="inquireBtn">
                            <i class="fas fa-question-circle"></i>
                            Inquire
                        </button>
                        <button class="action-btn cancel-btn" id="cancelBtn">
                            <i class="fas fa-times-circle"></i>
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; // End of membership plans display (show for non-members or grace period) ?>
</main>

<script <?= CSPNonce::getScriptNonceAttr() ?>>
    // Pass login status to JavaScript
    window.userLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
</script>

<script src="<?= PUBLIC_PATH ?>/js/membership.js?=v1"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
