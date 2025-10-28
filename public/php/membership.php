<?php
// Check if this is an API request
session_start();

require_once '../../includes/session_manager.php';

// Initialize session manager
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
    include '../../includes/db_connect.php';

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
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.png";
}

// Set variables for header
$pageTitle = "Fit and Brawl - Membership";
$currentPage = "membership";
$additionalCSS = ["../css/pages/membership.css"];
$additionalJS = [];

// Include header
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main class="membership-main">
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
                            <li>Muay Thai Training</li>
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
                            <li>Boxing and MMA Training</li>
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
                            <li>Boxing Training</li>
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
                            <li>MMA Training</li>
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
                        <div class="plan-price">
                            <div class="price-student">700 PHP <span>/MONTH</span><br><span class="student-label">For Students</span></div>
                            <div class="price-regular">1000 PHP <span>/MONTH</span><br><span class="regular-label">For Regular</span></div>
                        </div>
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
                <div class="pricing-toggle">
                    <button class="toggle-btn active" data-table="member">FOR MEMBERS</button>
                    <button class="toggle-btn" data-table="non-member">FOR NON<span class="toggle-hyphen">-</span>MEMBERS</button>
                </div>
                <?php if (!$isLoggedIn): ?>
                <div class="signup-notice" id="signupNotice" data-logged-in="false">
                    <span class="signup-notice-text">Sign up now and save 30 PHP <br> on all services!</span>
                </div>
                <?php else: ?>
                <div class="signup-notice" id="signupNotice" data-logged-in="true" style="opacity: 0; pointer-events: none;">
                    <span class="signup-notice-icon">✓</span>
                    <span class="signup-notice-text">You're getting member pricing!</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Members Table -->
            <div class="pricing-table-container active" id="memberTable">
                <div class="pricing-table-scroll">
                    <table class="pricing-table">
                        <thead>
                            <tr>
                                <th>PRICE</th>
                                <th>SERVICE</th>
                                <th>BENEFITS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="price-cell">90 PHP</td>
                                <td>Day Pass: Gym Access</td>
                                <td>Full-day access to all gym facilities and equipment, including the weight room, cardio machines, and functional training areas. Perfect for a one-off workout or for travelers.</td>
                            </tr>
                            <tr>
                                <td class="price-cell">70 PHP</td>
                                <td>Day Pass: Student Access</td>
                                <td>Discounted full-day access to all gym facilities (weight room, cardio, etc.). Must present a valid student ID upon entry.</td>
                            </tr>
                            <tr>
                                <td class="price-cell">350 PHP</td>
                                <td>Training: Boxing</td>
                                <td>Full-day access to boxing area. Focused on footwork, defense, and power punching technique. Ideal for rapid skill improvement, pad work, and personalized fight strategies.</td>
                            </tr>
                            <tr>
                                <td class="price-cell">400 PHP</td>
                                <td>Training: Muay Thai</td>
                                <td>Full-day access to mma area. Includes in-depth training on clinch work, teeps, and powerful low kicks. Perfect for mastering traditional techniques and conditioning.</td>
                            </tr>
                            <tr>
                                <td class="price-cell">500 PHP</td>
                                <td>Training: MMA</td>
                                <td>A 75-minute comprehensive session that integrates striking (boxing/Muay Thai), wrestling, and Brazilian Jiu-Jitsu (BJJ) for a well-rounded combat experience. Ideal for competitive fighters or those wanting an intense, varied workout.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Non-Members Table -->
            <div class="pricing-table-container" id="nonMemberTable">
                <div class="pricing-table-scroll">
                    <table class="pricing-table">
                        <thead>
                            <tr>
                                <th>PRICE</th>
                                <th>SERVICE</th>
                                <th>BENEFITS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="price-cell">150 PHP</td>
                                <td>Day Pass: Gym Access</td>
                                <td>Full-day access to all gym facilities and equipment, including the weight room, cardio machines, and functional training areas. Perfect for a one-off workout or for travelers.</td>
                            </tr>
                            <tr>
                                <td class="price-cell">120 PHP</td>
                                <td>Day Pass: Student Access</td>
                                <td>Discounted full-day access to all gym facilities (weight room, cardio, etc.). Must present a valid student ID upon entry.</td>
                            </tr>
                            <tr>
                                <td class="price-cell">380 PHP</td>
                                <td>Training: Boxing</td>
                                <td>Full-day access to boxing area. Focused on footwork, defense, and power punching technique. Ideal for rapid skill improvement, pad work, and personalized fight strategies.</td>
                            </tr>
                            <tr>
                                <td class="price-cell">530 PHP</td>
                                <td>Training: Muay Thai</td>
                                <td>Full-day access to mma area. Includes in-depth training on clinch work, teeps, and powerful low kicks. Perfect for mastering traditional techniques and conditioning.</td>
                            </tr>
                            <tr>
                                <td class="price-cell">630 PHP</td>
                                <td>Training: MMA</td>
                                <td>A 75-minute comprehensive session that integrates striking (boxing/Muay Thai), wrestling, and Brazilian Jiu-Jitsu (BJJ) for a well-rounded combat experience. Ideal for competitive fighters or those wanting an intense, varied workout.</td>
                            </tr>
                        </tbody>
                    </table>
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
    <script src="../js/membership.js?=v1"></script>

<?php require_once '../../includes/footer.php'; ?>
