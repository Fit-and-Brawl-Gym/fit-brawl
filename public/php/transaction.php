<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/session_manager.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user's current active plan
$user_id = $_SESSION['user_id'] ?? null;
$currentPlan = null;
$currentPlanKey = null;
$hasActiveMembership = false;

if ($user_id) {
    $gracePeriodDays = 3;
    $today = date('Y-m-d');

    // Check for active membership
    $stmt = $conn->prepare("
        SELECT plan_name, end_date
        FROM user_memberships
        WHERE user_id = ?
          AND request_status = 'approved'
          AND membership_status = 'active'
        ORDER BY end_date DESC
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $endDate = $row['end_date'];
            $expiryWithGrace = date('Y-m-d', strtotime($endDate . " +$gracePeriodDays days"));

            if ($expiryWithGrace >= $today) {
                $hasActiveMembership = true;
                $currentPlan = $row['plan_name'];

                // Map plan name to plan key (database stores short names like "Gladiator", "Brawler")
                $planMapping = [
                    'Gladiator' => 'gladiator',
                    'Brawler' => 'brawler',
                    'Champion' => 'champion',
                    'Clash' => 'clash',
                    'Resolution Regular' => 'resolution-regular',
                    'Resolution Student' => 'resolution-student'
                ];

                $currentPlanKey = $planMapping[$currentPlan] ?? null;
            }
        }
        $stmt->close();
    }
}

// Get plan details from URL parameters or session
$plan = isset($_GET['plan']) ? $_GET['plan'] : 'gladiator';
$billing = isset($_GET['billing']) ? $_GET['billing'] : 'monthly';

// Check if user is trying to select the same plan they currently have
if ($hasActiveMembership && $currentPlanKey && $plan === $currentPlanKey) {
    // Redirect to membership page with error message
    $planDisplayName = $currentPlan . ' Plan';
    $_SESSION['plan_error'] = "You already have an active " . $planDisplayName . ". Please choose a different plan to upgrade.";
    header('Location: membership.php');
    exit;
}

// Plan configurations
$plans = [
    'brawler' => [
        'name' => 'BRAWLER PLAN',
        'monthly' => 11500,
        'quarterly' => 32775, // 3 months with 5% off: (11500 * 3) * 0.95
        'has_discount' => true,
        'discount_percent' => 5,
        'benefits' => [
            'Muay Thai Training with Professional Coaches',
            'MMA Area Access',
            'Free Orientation and Fitness Assessment',
            'Shower Access',
            'Locker Access'
        ]
    ],
    'gladiator' => [
        'name' => 'GLADIATOR PLAN',
        'monthly' => 14500,
        'quarterly' => 36540, // 3 months with 16% off: (14500 * 3) * 0.84
        'has_discount' => true,
        'discount_percent' => 16,
        'benefits' => [
            'Boxing Training with Professional Coaches',
            'MMA Training with Professional Coaches',
            'Boxing and MMA Area Access',
            'Gym Equipment Access',
            'Jakuzi Access',
            'Shower Access',
            'Locker Access'
        ]
    ],
    'champion' => [
        'name' => 'CHAMPION PLAN',
        'monthly' => 7000,
        'quarterly' => 19950, // 3 months with 5% off: (7000 * 3) * 0.95
        'has_discount' => true,
        'discount_percent' => 5,
        'benefits' => [
            'Boxing Training with Professional Coaches',
            'MMA Area Access',
            'Free Orientation and Fitness Assessment',
            'Shower Access',
            'Locker Access'
        ]
    ],
    'clash' => [
        'name' => 'CLASH PLAN',
        'monthly' => 13500,
        'quarterly' => 38475, // 3 months with 5% off: (13500 * 3) * 0.95
        'has_discount' => true,
        'discount_percent' => 5,
        'benefits' => [
            'MMA Training with Professional Coaches',
            'MMA Area Access',
            'Free Orientation and Fitness Assessment',
            'Shower Access',
            'Locker Access'
        ]
    ],
    'resolution-regular' => [
        'name' => 'RESOLUTION PLAN',
        'monthly' => 2200,
        'quarterly' => 6270, // 3 months with 5% off: (2200 * 3) * 0.95
        'has_discount' => true,
        'discount_percent' => 5,
        'benefits' => [
            'Gym Equipment Access with Face Recognition',
            'Shower Access',
            'Locker Access'
        ]
    ]
];

$selectedPlan = $plans[$plan];
$price = $billing === 'quarterly' ? $selectedPlan['quarterly'] : $selectedPlan['monthly'];
$monthlyPrice = $selectedPlan['monthly'];
$quarterlyPrice = $selectedPlan['quarterly'];

// Calculate next payment date
$nextPayment = $billing === 'quarterly' ? date('F d, Y', strtotime('+3 months')) : date('F d, Y', strtotime('+1 month'));

// Helper function to format plan name with styled parentheses
function formatPlanName($planName)
{
    // Check if the plan name contains parentheses
    if (preg_match('/(.*?)\s*\((.*?)\)/', $planName, $matches)) {
        $baseName = trim($matches[1]);
        $variant = trim($matches[2]);
        return $baseName . ' <span class="plan-variant-parens">(</span>' . $variant . '<span class="plan-variant-parens">)</span>';
    }
    return $planName;
}

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
    $hasCustomAvatar = $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
    $avatarSrc = $hasCustomAvatar ? "../../uploads/avatars/" . htmlspecialchars($_SESSION['avatar']) : "../../images/account-icon.svg";
}

$pageTitle = "Transaction - Fit and Brawl";
$currentPage = "transaction";
$additionalCSS = ['../css/pages/transaction.css?v=' . time()];
$additionalJS = ['../js/transaction.js'];
?>
<script>
    const monthlyPrice = <?php echo json_encode($monthlyPrice); ?>;
    const quarterlyPrice = <?php echo json_encode($quarterlyPrice); ?>;
</script>
<?php
// Include header
require_once __DIR__ . '/../../includes/header.php';
?>

<!--Main-->
<main class="transaction-page">
    <div class="transaction-container">
        <h1 class="transaction-title">COMPLETE YOUR SUBSCRIPTION</h1>

        <div class="transaction-box">
            <form id="subscriptionForm" class="subscription-form">
                <div class="transaction-content">
                    <!-- Left Column -->
                    <div class="transaction-left">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" placeholder="Juan Dela Cruz" required>
                        </div>

                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" required>
                                <option value="Afghanistan">Afghanistan</option>
                                <option value="Albania">Albania</option>
                                <option value="Algeria">Algeria</option>
                                <option value="Andorra">Andorra</option>
                                <option value="Angola">Angola</option>
                                <option value="Argentina">Argentina</option>
                                <option value="Armenia">Armenia</option>
                                <option value="Australia">Australia</option>
                                <option value="Austria">Austria</option>
                                <option value="Azerbaijan">Azerbaijan</option>
                                <option value="Bahamas">Bahamas</option>
                                <option value="Bahrain">Bahrain</option>
                                <option value="Bangladesh">Bangladesh</option>
                                <option value="Barbados">Barbados</option>
                                <option value="Belarus">Belarus</option>
                                <option value="Belgium">Belgium</option>
                                <option value="Belize">Belize</option>
                                <option value="Benin">Benin</option>
                                <option value="Bhutan">Bhutan</option>
                                <option value="Bolivia">Bolivia</option>
                                <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                                <option value="Botswana">Botswana</option>
                                <option value="Brazil">Brazil</option>
                                <option value="Brunei">Brunei</option>
                                <option value="Bulgaria">Bulgaria</option>
                                <option value="Burkina Faso">Burkina Faso</option>
                                <option value="Burundi">Burundi</option>
                                <option value="Cambodia">Cambodia</option>
                                <option value="Cameroon">Cameroon</option>
                                <option value="Canada">Canada</option>
                                <option value="Chile">Chile</option>
                                <option value="China">China</option>
                                <option value="Colombia">Colombia</option>
                                <option value="Costa Rica">Costa Rica</option>
                                <option value="Croatia">Croatia</option>
                                <option value="Cuba">Cuba</option>
                                <option value="Cyprus">Cyprus</option>
                                <option value="Czech Republic">Czech Republic</option>
                                <option value="Denmark">Denmark</option>
                                <option value="Dominican Republic">Dominican Republic</option>
                                <option value="Ecuador">Ecuador</option>
                                <option value="Egypt">Egypt</option>
                                <option value="El Salvador">El Salvador</option>
                                <option value="Estonia">Estonia</option>
                                <option value="Ethiopia">Ethiopia</option>
                                <option value="Fiji">Fiji</option>
                                <option value="Finland">Finland</option>
                                <option value="France">France</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Germany">Germany</option>
                                <option value="Ghana">Ghana</option>
                                <option value="Greece">Greece</option>
                                <option value="Guatemala">Guatemala</option>
                                <option value="Haiti">Haiti</option>
                                <option value="Honduras">Honduras</option>
                                <option value="Hong Kong SAR">Hong Kong SAR</option>
                                <option value="Hungary">Hungary</option>
                                <option value="Iceland">Iceland</option>
                                <option value="India">India</option>
                                <option value="Indonesia">Indonesia</option>
                                <option value="Iran">Iran</option>
                                <option value="Iraq">Iraq</option>
                                <option value="Ireland">Ireland</option>
                                <option value="Israel">Israel</option>
                                <option value="Italy">Italy</option>
                                <option value="Jamaica">Jamaica</option>
                                <option value="Japan">Japan</option>
                                <option value="Jordan">Jordan</option>
                                <option value="Kazakhstan">Kazakhstan</option>
                                <option value="Kenya">Kenya</option>
                                <option value="Kuwait">Kuwait</option>
                                <option value="Laos">Laos</option>
                                <option value="Latvia">Latvia</option>
                                <option value="Lebanon">Lebanon</option>
                                <option value="Lithuania">Lithuania</option>
                                <option value="Luxembourg">Luxembourg</option>
                                <option value="Malaysia">Malaysia</option>
                                <option value="Malta">Malta</option>
                                <option value="Mexico">Mexico</option>
                                <option value="Moldova">Moldova</option>
                                <option value="Monaco">Monaco</option>
                                <option value="Mongolia">Mongolia</option>
                                <option value="Morocco">Morocco</option>
                                <option value="Mozambique">Mozambique</option>
                                <option value="Myanmar">Myanmar</option>
                                <option value="Namibia">Namibia</option>
                                <option value="Nepal">Nepal</option>
                                <option value="Netherlands">Netherlands</option>
                                <option value="New Zealand">New Zealand</option>
                                <option value="Nicaragua">Nicaragua</option>
                                <option value="Nigeria">Nigeria</option>
                                <option value="North Macedonia">North Macedonia</option>
                                <option value="Norway">Norway</option>
                                <option value="Oman">Oman</option>
                                <option value="Pakistan">Pakistan</option>
                                <option value="Panama">Panama</option>
                                <option value="Paraguay">Paraguay</option>
                                <option value="Peru">Peru</option>
                                <option value="Philippines" selected>Philippines</option>
                                <option value="Poland">Poland</option>
                                <option value="Portugal">Portugal</option>
                                <option value="Qatar">Qatar</option>
                                <option value="Romania">Romania</option>
                                <option value="Russia">Russia</option>
                                <option value="Rwanda">Rwanda</option>
                                <option value="San Marino">San Marino</option>
                                <option value="Saudi Arabia">Saudi Arabia</option>
                                <option value="Senegal">Senegal</option>
                                <option value="Serbia">Serbia</option>
                                <option value="Singapore">Singapore</option>
                                <option value="Slovakia">Slovakia</option>
                                <option value="Slovenia">Slovenia</option>
                                <option value="South Africa">South Africa</option>
                                <option value="South Korea">South Korea</option>
                                <option value="Spain">Spain</option>
                                <option value="Sri Lanka">Sri Lanka</option>
                                <option value="Sudan">Sudan</option>
                                <option value="Sweden">Sweden</option>
                                <option value="Switzerland">Switzerland</option>
                                <option value="Syria">Syria</option>
                                <option value="Taiwan">Taiwan</option>
                                <option value="Tajikistan">Tajikistan</option>
                                <option value="Tanzania">Tanzania</option>
                                <option value="Thailand">Thailand</option>
                                <option value="Togo">Togo</option>
                                <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                                <option value="Tunisia">Tunisia</option>
                                <option value="Turkey">Turkey</option>
                                <option value="Turkmenistan">Turkmenistan</option>
                                <option value="Uganda">Uganda</option>
                                <option value="Ukraine">Ukraine</option>
                                <option value="United Arab Emirates">United Arab Emirates</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="United States">United States</option>
                                <option value="Uruguay">Uruguay</option>
                                <option value="Uzbekistan">Uzbekistan</option>
                                <option value="Venezuela">Venezuela</option>
                                <option value="Vietnam">Vietnam</option>
                                <option value="Yemen">Yemen</option>
                                <option value="Zambia">Zambia</option>
                                <option value="Zimbabwe">Zimbabwe</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="address">Permanent Address</label>
                            <input type="text" id="address" name="address"
                                placeholder="123 Rizal Street, Barangay San Isidro, Quezon City" required>
                        </div>

                        <div class="payment-qr-section">
                            <div class="qr-code-container">
                                <img src="../../images/qr-code.webp" alt="InstaPay QR Code" class="qr-code">
                            </div>
                            <p class="qr-instruction">KINDLY SCAN TO PROCEED WITH YOUR PAYMENT</p>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="transaction-right">
                        <!-- Billing Options -->
                        <div class="billing-toggle">
                            <label class="billing-option">
                                <input type="radio" name="billing" value="monthly" <?php echo $billing === 'monthly' ? 'checked' : ''; ?> data-billing="monthly">
                                <div class="billing-label">
                                    <div class="radio-custom"></div>
                                    <div class="billing-info">
                                        <div class="billing-title">Pay monthly</div>
                                        <div class="billing-price"><?php echo number_format($monthlyPrice); ?>PHP/month
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="billing-option">
                                <input type="radio" name="billing" value="quarterly" <?php echo $billing === 'quarterly' ? 'checked' : ''; ?> data-billing="quarterly">
                                <?php if ($selectedPlan['has_discount']): ?>
                                    <span class="discount-badge"><?php echo $selectedPlan['discount_percent']; ?>%
                                        OFF</span>
                                <?php endif; ?>
                                <div class="billing-label">
                                    <div class="radio-custom"></div>
                                    <div class="billing-info">
                                        <div class="billing-title">Pay quarterly</div>
                                        <div class="billing-price">
                                            <?php if ($billing === 'quarterly' && $selectedPlan['has_discount']): ?>
                                                <span
                                                    class="original-price"><?php echo number_format($monthlyPrice * 3); ?>PHP</span>
                                            <?php endif; ?>
                                            <?php echo number_format($quarterlyPrice); ?>PHP/quarter
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Plan Card with Headband -->
                        <div class="plan-card-transaction">
                            <div class="plan-header-headband">
                                <h2 class="plan-name"><?php echo formatPlanName($selectedPlan['name']); ?></h2>
                                <div class="plan-price">
                                    <span class="price-amount"><?php echo $price; ?></span>
                                    <span
                                        class="price-period">/<?php echo $billing === 'quarterly' ? 'QUARTER' : 'MONTH'; ?></span>
                                </div>
                            </div>

                            <div class="plan-body">
                                <p class="next-payment">Next payment on <strong><?php echo $nextPayment; ?></strong></p>

                                <ul class="benefits-list">
                                    <?php foreach ($selectedPlan['benefits'] as $benefit): ?>
                                        <li>
                                            <svg class="checkmark" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                                <path
                                                    d="M7 10L9 12L13 8M19 10C19 14.9706 14.9706 19 10 19C5.02944 19 1 14.9706 1 10C1 5.02944 5.02944 1 10 1C14.9706 1 19 5.02944 19 10Z"
                                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                            <?php echo $benefit; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Action Buttons - Moved here -->
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
                    By confirming your membership, you acknowledge that this is a one-time payment for the selected plan
                    duration. Your membership will remain active until the end of the paid period and will not
                    automatically renew. To continue enjoying our services after expiration, you will need to purchase a
                    new membership plan.
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
        <p class="modal-instruction">Please upload a screenshot or photo of your payment receipt to complete your
            subscription.</p>

        <div class="file-upload-area" id="fileUploadArea">
            <svg class="upload-icon" width="48" height="48" viewBox="0 0 24 24" fill="none">
                <path
                    d="M7 18C4.23858 18 2 15.7614 2 13C2 10.2386 4.23858 8 7 8C7 5.23858 9.23858 3 12 3C14.7614 3 17 5.23858 17 8C19.7614 8 22 10.2386 22 13C22 15.7614 19.7614 18 17 18M12 13V21M12 13L9 16M12 13L15 16"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script src="/public/js/transaction.js?=v2"></script>
</body>

</html>