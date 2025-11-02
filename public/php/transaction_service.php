<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/session_manager.php';

// Initialize session manager (handles session_start internally)
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
            'Training with Coach Rieze',
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
            'Training with Coach Thei',
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
            'Training with Coach Carlo',
            'Striking (Boxing/Muay Thai)',
            'Wrestling techniques',
            'Brazilian Jiu-Jitsu (BJJ)',
            'Well-rounded combat experience'
        ]
    ]
];

$selectedService = $services[$service];
$originalPrice = $selectedService['non_member_price'];

// Calculate final price based on type
if ($type === 'member' || $isMember) {
    // Members get member price
    $price = $selectedService['member_price'];
} else {
    // Non-members get non-member price
    $price = $originalPrice;
}

// Map service to class type for trainer availability
$serviceToClassMap = [
    'daypass-gym' => 'gym',
    'daypass-gym-student' => 'gym',
    'training-boxing' => 'boxing',
    'training-muaythai' => 'muay-thai',
    'training-mma' => 'mma'
];
$classType = isset($serviceToClassMap[$service]) ? $serviceToClassMap[$service] : 'gym';

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

$pageTitle = "Day Pass Transaction (Member) - Fit and Brawl";
$currentPage = "transaction_daypass_member";
$additionalCSS = [
    '../css/pages/transaction.css',
    'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css'
];
$additionalJS = [
    'https://cdn.jsdelivr.net/npm/flatpickr',
    '../js/transaction-service.js'
];

// Include header
require_once '../../includes/header.php';
?>

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

                            <div class="form-group">
                                <label for="serviceDate">
                                    <i class="far fa-calendar-alt"></i> Select Service Date
                                </label>
                                <input type="text" id="serviceDate" name="service_date" placeholder="Choose a date" required readonly style="cursor: pointer; background: rgba(255, 255, 255, 0.1); border: 2px solid rgba(213, 186, 43, 0.3); border-radius: var(--radius-md); color: var(--color-white);">
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="transaction-right">
                            <!-- Plan Card with Headband -->
                            <div class="plan-card-transaction">
                                <div class="plan-header-headband">
                                    <h2 class="plan-name"><?php echo $selectedService['name']; ?></h2>
                                    <div class="plan-price">
                                        <span class="price-amount"><?php echo $price; ?> PHP</span>
                                    </div>
                                </div>

                                <div class="plan-body">
                                    <p class="next-payment">Single Day Access</p>

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

<script>
    // Initialize Flatpickr date picker for service date after DOM and scripts load
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof flatpickr !== 'undefined') {
            const classType = '<?php echo $classType; ?>';

            // Fetch available dates from trainer schedules
            fetch(`api/get_available_dates.php?class=${classType}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Extract all dates
                        const allDates = data.available_dates.map(date => new Date(date));

                        // Calculate date range
                        const today = new Date();
                        const maxDate = new Date();
                        maxDate.setDate(today.getDate() + 30);

                        // Invert available dates to get disabled dates
                        const disabledDates = [];
                        const availableDatesSet = new Set(allDates.map(d => d.toDateString()));

                        for (let d = new Date(today); d <= maxDate; d.setDate(d.getDate() + 1)) {
                            if (!availableDatesSet.has(d.toDateString())) {
                                disabledDates.push(new Date(d));
                            }
                        }

                        // Initialize Flatpickr with disabled dates
                        flatpickr("#serviceDate", {
                            minDate: "today",
                            maxDate: new Date().fp_incr(30),
                            dateFormat: "F j, Y",
                            disable: disabledDates,
                            disableMobile: false,
                            onChange: function(selectedDates, dateStr, instance) {
                                console.log("Selected service date:", dateStr);
                            }
                        });
                    } else {
                        console.error('Failed to fetch available dates:', data.message);
                        // Fallback: initialize without disable filter
                        flatpickr("#serviceDate", {
                            minDate: "today",
                            maxDate: new Date().fp_incr(30),
                            dateFormat: "F j, Y",
                            disableMobile: false,
                            onChange: function(selectedDates, dateStr, instance) {
                                console.log("Selected service date:", dateStr);
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching available dates:', error);
                    // Fallback: initialize without disable filter
                    flatpickr("#serviceDate", {
                        minDate: "today",
                        maxDate: new Date().fp_incr(30),
                        dateFormat: "F j, Y",
                        disableMobile: false,
                        onChange: function(selectedDates, dateStr, instance) {
                            console.log("Selected service date:", dateStr);
                        }
                    });
                });
        } else {
            console.error('Flatpickr not loaded');
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>
