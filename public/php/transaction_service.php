<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php'; 

// Initialize session manager
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get service details from URL parameters
$service = isset($_GET['service']) ? $_GET['service'] : 'daypass-gym';

// Determine user status
$isLoggedIn = isset($_SESSION['email']);
// TODO: Check if user has active membership subscription from database
// For now, we'll assume logged in users without explicit member flag are non-members
$isMember = false; // This should be fetched from database based on active subscription

// Get type from URL if provided (from table click), otherwise determine automatically
$type = isset($_GET['type']) ? $_GET['type'] : ($isMember ? 'member' : 'non-member');

// Service configurations
$services = [
    'daypass-gym' => [
        'name' => 'Day Pass: Gym Access',
        'member_price' => 90,
        'non_member_price' => 150,
        'discount' => 30,
        'benefits' => [
            'Full-day access to all gym facilities and equipment',
            'Weight room access',
            'Cardio machines access',
            'Functional training areas',
            'Perfect for one-off workout or travelers'
        ]
    ],
    'daypass-gym-student' => [
        'name' => 'Day Pass: Student Gym Access',
        'member_price' => 70,
        'non_member_price' => 120,
        'discount' => 30,
        'benefits' => [
            'Full-day access to all gym facilities',
            'Weight room access',
            'Cardio machines access',
            'Must present valid student ID upon entry'
        ]
    ],
    'training-boxing' => [
        'name' => 'Training: Boxing',
        'member_price' => 350,
        'non_member_price' => 380,
        'discount' => 30,
        'benefits' => [
            'Full-day access to boxing area',
            'Focused on footwork and defense',
            'Power punching technique',
            'Pad work included',
            'Personalized fight strategies'
        ]
    ],
    'training-muaythai' => [
        'name' => 'Training: Muay Thai',
        'member_price' => 400,
        'non_member_price' => 530,
        'discount' => 30,
        'benefits' => [
            'Full-day access to MMA area',
            'Clinch work training',
            'Teeps technique',
            'Powerful low kicks training',
            'Traditional techniques mastery'
        ]
    ],
    'training-mma' => [
        'name' => 'Training: MMA',
        'member_price' => 500,
        'non_member_price' => 630,
        'discount' => 30,
        'benefits' => [
            '75-minute comprehensive session',
            'Striking (Boxing/Muay Thai)',
            'Wrestling techniques',
            'Brazilian Jiu-Jitsu (BJJ)',
            'Well-rounded combat experience'
        ]
    ]
];

$selectedService = $services[$service];
$originalPrice = $selectedService['non_member_price'];
$discountAmount = $selectedService['discount'];

// Calculate final price based on type
if ($type === 'member' || $isMember) {
    // Members get member price (no discount shown)
    $price = $selectedService['member_price'];
    $showDiscount = false;
    $showSignupPromo = false;
} else {
    // Non-members
    if (!$isLoggedIn) {
        // Not logged in: show full non-member price with signup promo
        $price = $originalPrice;
        $showDiscount = false;
        $showSignupPromo = true;
    } else {
        // Logged in but not a member: get signup discount!
        $price = $originalPrice - $discountAmount;
        $showDiscount = true;
        $showSignupPromo = false;
    }
}

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/profile-icon.svg";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - FitXBrawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/transaction.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/header-dropdown.js"></script>
<<<<<<< Updated upstream
    <script src="../js/hamburger-menu.js"></script>
=======
>>>>>>> Stashed changes
    <?php if(SessionManager::isLoggedIn()): ?>
    <link rel="stylesheet" href="../css/components/session-warning.css">
    <script src="../js/session-timeout.js"></script>
    <?php endif; ?>
</head>
<body>
    <!--Header-->
    <header>
        <div class="wrapper">
            <button class="hamburger-menu" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
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
                    <li><a href="membership.php" class="active">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </nav>
            <?php if(isset($_SESSION['email'])): ?>
                <div class="account-dropdown">
                    <img src="<?= $avatarSrc ?>"
                         alt="Account" class="account-icon">
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

    <!--Main-->
    <main class="transaction-page">
        <div class="transaction-container">
            <h1 class="transaction-title">COMPLETE YOUR PAYMENT</h1>

            <div class="transaction-box">
                <form id="subscriptionForm" class="subscription-form">
                    <div class="transaction-content">
                        <!-- Left Column -->
                        <div class="transaction-left">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" id="name" name="name" placeholder="Excel Bondoc" required>
                            </div>

                            <div class="form-group">
                                <label for="country">Country</label>
                                <select id="country" name="country" required>
                                    <option value="Philippines" selected>Philippines</option>
                                    <!-- Add other countries as needed -->
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="address">Permanent Address</label>
                                <input type="text" id="address" name="address" placeholder="123 Mabini Street, Barangay Maligaya, Quezon City" required>
                            </div>

                            <div class="payment-qr-section">
                                <div class="qr-code-container">
                                    <img src="../../images/qr-code.png" alt="InstaPay QR Code" class="qr-code">
                                </div>
                                <p class="qr-instruction">KINDLY SCAN TO PROCEED WITH YOUR PAYMENT</p>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="transaction-right">
                            <?php if (!$isLoggedIn && $type === 'non-member'): ?>
                            <!-- Not logged in: Encourage signup -->
                            <div class="login-notice-banner">
                                <span class="notice-icon">ℹ️</span>
                                <span class="notice-text">Sign up now to save <?php echo $discountAmount; ?> PHP! <a href="signup.php" class="login-link">Sign Up</a></span>
                            </div>
                            <?php elseif ($isLoggedIn && !$isMember && $type === 'non-member'): ?>
                            <!-- Logged in: Show they got the signup discount -->
                            <div class="member-status-banner">
                                <span class="member-icon">🎉</span>
                                <span class="member-text">Signup Discount Applied! Saved <?php echo $discountAmount; ?> PHP</span>
                            </div>
                            <?php elseif ($isMember || $type === 'member'): ?>
                            <!-- Member Status Banner -->
                            <div class="member-status-banner">
                                <span class="member-icon">✓</span>
                                <span class="member-text">Member Pricing Applied</span>
                            </div>
                            <?php endif; ?>

                            <!-- Plan Card with Headband -->
                            <div class="plan-card-transaction">
                                <div class="plan-header-headband">
                                    <h2 class="plan-name"><?php echo $selectedService['name']; ?></h2>
                                    <div class="plan-price">
                                        <?php if ($showDiscount): ?>
                                            <span class="price-original"><?php echo $originalPrice; ?> PHP</span>
                                        <?php endif; ?>
                                        <span class="price-amount"><?php echo $price; ?> PHP</span>
                                    </div>
                                </div>

                                <?php if ($showSignupPromo): ?>
                                <div class="signup-discount-banner">
                                    <span class="discount-icon">🎉</span>
                                    <span class="discount-text">Sign up to save <?php echo $discountAmount; ?> PHP!</span>
                                </div>
                                <?php endif; ?>

                                <div class="plan-body">
                                    <p class="next-payment">Valid for today only</p>

                                    <ul class="benefits-list">
                                        <?php foreach ($selectedService['benefits'] as $benefit): ?>
                                            <li>
                                                <svg class="checkmark" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                    <path d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z"
                                                          stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                </svg>
                                                <?php echo $benefit; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="transaction-actions">
                                <button type="button" class="confirm-payment-btn" id="confirmPaymentBtn">
                                    CONFIRM PAYMENT
                                </button>
                                <button type="button" class="cancel-btn" onclick="window.location.href='membership.php'">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="terms-notice">
                        By confirming your payment, you agree to our terms and conditions. This is a single-day pass and is non-refundable.
                    </p>
                </form>
            </div>
        </div>
    </main>

    <!-- Receipt Upload Modal -->
    <div class="modal-overlay" id="receiptModalOverlay"></div>
    <div class="receipt-modal" id="receiptModal">
        <div class="modal-header">
            <h2>Submit Payment Receipt</h2>
            <button class="modal-close-btn" id="closeReceiptModal">&times;</button>
        </div>
        <div class="modal-body">
            <p class="modal-instruction">Please upload a screenshot or photo of your payment receipt to complete your purchase.</p>

            <div class="file-upload-area" id="fileUploadArea">
                <svg class="upload-icon" width="48" height="48" viewBox="0 0 24 24" fill="none">
                    <path d="M7 18C4.23858 18 2 15.7614 2 13C2 10.2386 4.23858 8 7 8C7 5.23858 9.23858 3 12 3C14.7614 3 17 5.23858 17 8C19.7614 8 22 10.2386 22 13C22 15.7614 19.7614 18 17 18M12 13V21M12 13L9 16M12 13L15 16"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <p class="upload-text">Click to upload or drag and drop</p>
                <p class="upload-subtext">PNG, JPG, PDF up to 10MB</p>
                <input type="file" id="receiptFile" name="receipt" accept="image/*,.pdf" hidden>
            </div>

            <div class="file-preview" id="filePreview" style="display: none;">
                <img id="previewImage" src="" alt="Receipt preview">
                <p id="fileName"></p>
                <button type="button" class="remove-file-btn" id="removeFile">Remove</button>
            </div>

            <div class="modal-actions">
                <button type="button" class="submit-receipt-btn" id="submitReceiptBtn" disabled>
                    SUBMIT RECEIPT
                </button>
                <button type="button" class="modal-cancel-btn" id="cancelReceiptBtn">
                    Cancel
                </button>
            </div>
        </div>
    </div>

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

    <script src="../js/transaction.js"></script>
</body>
</html>
