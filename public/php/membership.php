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

// Check if user is logged in
$isLoggedIn = isset($_SESSION['email']);

if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    include __DIR__ . '/../../includes/db_connect.php';

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
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
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
                <!-- All plans in order -->
                <div class="plan-card" data-plan="brawler" data-category="member">
                    <h3 class="plan-name">BRAWLER</h3>
                    <p class="plan-subtitle">MEMBERSHIP IN MUAY THAI</p>
                    <div class="plan-price">1500 PHP <span>/MONTH</span></div>
                    <ul class="plan-features">
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
                        <div class="price-main">3500 PHP <span>/MONTH</span></div>
                    </div>
                    <ul class="plan-features">
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
                    <div class="plan-price">1500 PHP <span>/MONTH</span></div>
                    <ul class="plan-features">
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
                    <div class="plan-price">1500 PHP <span>/MONTH</span></div>
                    <ul class="plan-features">
                        <li>MMA Training with Professional Coaches</li>
                        <li>MMA Area Access</li>
                        <li>Free Orientation and Fitness Assessment</li>
                        <li>Shower Access</li>
                        <li>Locker Access</li>
                    </ul>
                    <button class="select-btn">SELECT PLAN</button>
                </div>

                <div class="plan-card" data-plan="resolution-regular" data-category="non-member">
                    <h3 class="plan-name">RESOLUTION</h3>
                    <p class="plan-subtitle">MEMBERSHIP IN GYM</p>
                    <div class="plan-price">1000 PHP <span>/MONTH</span></div>
                    <ul class="plan-features">
                        <li>Gym Equipment Access with Face Recognition</li>
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

    <!-- Pricing Tables -->
    <section class="pricing-section">
        <div class="pricing-header" id="pricingHeader">
            <h2 class="pricing-title">MEMBER ADDITIONAL SERVICES</h2>
        </div>

        <!-- Members Services -->
        <div class="services-grid-container active" id="memberTable">
            <div class="services-grid">
                <div class="service-card" data-price="90 PHP" data-service="Day Pass: Gym Access"
                    data-benefits="Full-day access to all gym facilities and equipment, including the weight room, cardio machines, and functional training areas. Perfect for a one-off workout or for travelers.">
                    <div class="service-price">90 PHP</div>
                    <div class="service-name">Day Pass: Gym Access</div>
                    <div class="service-benefits">Full-day access to all gym facilities and equipment, including the
                        weight room, cardio machines, and functional training areas. Perfect for a one-off workout or
                        for travelers.</div>
                    <button class="service-select-btn">SELECT SERVICE</button>
                </div>

                <div class="service-card" data-price="70 PHP" data-service="Day Pass: Student Access"
                    data-benefits="Discounted full-day access to all gym facilities (weight room, cardio, etc.). Must present a valid student ID upon entry.">
                    <div class="service-price">70 PHP</div>
                    <div class="service-name">Day Pass: Student Access</div>
                    <div class="service-benefits">Discounted full-day access to all gym facilities (weight room, cardio,
                        etc.). Must present a valid student ID upon entry.</div>
                    <button class="service-select-btn">SELECT SERVICE</button>
                </div>

                <div class="service-card" data-price="350 PHP" data-service="Training: Boxing"
                    data-benefits="Full-day access to boxing area. Focused on footwork, defense, and power punching technique. Ideal for rapid skill improvement, pad work, and personalized fight strategies.">
                    <div class="service-price">350 PHP</div>
                    <div class="service-name">Training: Boxing</div>
                    <div class="service-benefits">Full-day access to boxing area. Focused on footwork, defense, and
                        power punching technique. Ideal for rapid skill improvement, pad work, and personalized fight
                        strategies.</div>
                    <button class="service-select-btn">SELECT SERVICE</button>
                </div>

                <div class="service-card" data-price="400 PHP" data-service="Training: Muay Thai"
                    data-benefits="Full-day access to mma area. Includes in-depth training on clinch work, teeps, and powerful low kicks. Perfect for mastering traditional techniques and conditioning.">
                    <div class="service-price">400 PHP</div>
                    <div class="service-name">Training: Muay Thai</div>
                    <div class="service-benefits">Full-day access to mma area. Includes in-depth training on clinch
                        work, teeps, and powerful low kicks. Perfect for mastering traditional techniques and
                        conditioning.</div>
                    <button class="service-select-btn">SELECT SERVICE</button>
                </div>

                <div class="service-card" data-price="500 PHP" data-service="Training: MMA"
                    data-benefits="A 75-minute comprehensive session that integrates striking (boxing/Muay Thai), wrestling, and Brazilian Jiu-Jitsu (BJJ) for a well-rounded combat experience. Ideal for competitive fighters or those wanting an intense, varied workout.">
                    <div class="service-price">500 PHP</div>
                    <div class="service-name">Training: MMA</div>
                    <div class="service-benefits">A 75-minute comprehensive session that integrates striking
                        (boxing/Muay Thai), wrestling, and Brazilian Jiu-Jitsu (BJJ) for a well-rounded combat
                        experience. Ideal for competitive fighters or those wanting an intense, varied workout.</div>
                    <button class="service-select-btn">SELECT SERVICE</button>
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
</main>

<script>
    // Pass login status to JavaScript
    window.userLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
</script>
<script src="<?= PUBLIC_PATH ?>/js/membership.js?=v1"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>