<?php
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/session_manager.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Check if user is logged in
if (!SessionManager::isLoggedIn()) {
  header('Location: login.php');
  exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$hasActiveMembership = false;
$userName = '';

// 1. Fetch User Details & Check Membership
if ($user_id) {
  // Fetch Username for Auto-fill
  $userStmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
  if ($userStmt) {
    $userStmt->bind_param('i', $user_id);
    $userStmt->execute();
    $userStmt->bind_result($dbUsername);
    if ($userStmt->fetch()) {
      $userName = $dbUsername;
    }
    $userStmt->close();
  }

  // Check for active membership
  $gracePeriodDays = 3;
  $today = date('Y-m-d');

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
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
      $endDate = $row['end_date'];
      $expiryWithGrace = date(
        'Y-m-d',
        strtotime($endDate . " +$gracePeriodDays days"),
      );

      if ($expiryWithGrace >= $today) {
        $hasActiveMembership = true;
      }
    }
    $stmt->close();
  }
}

// If user has active membership, redirect to membership page with message
if ($hasActiveMembership) {
  $_SESSION['plan_error'] =
    'You already have an active membership. To change or upgrade your plan, please visit the gym in person.';
  header('Location: membership.php');
  exit();
}

// 2. Define Country List (Cleaner than hardcoding HTML)
$countries = [
  'Afghanistan',
  'Albania',
  'Algeria',
  'Andorra',
  'Angola',
  'Argentina',
  'Armenia',
  'Australia',
  'Austria',
  'Azerbaijan',
  'Bahamas',
  'Bahrain',
  'Bangladesh',
  'Barbados',
  'Belarus',
  'Belgium',
  'Belize',
  'Benin',
  'Bhutan',
  'Bolivia',
  'Bosnia and Herzegovina',
  'Botswana',
  'Brazil',
  'Brunei',
  'Bulgaria',
  'Burkina Faso',
  'Burundi',
  'Cambodia',
  'Cameroon',
  'Canada',
  'Chile',
  'China',
  'Colombia',
  'Costa Rica',
  'Croatia',
  'Cuba',
  'Cyprus',
  'Czech Republic',
  'Denmark',
  'Dominican Republic',
  'Ecuador',
  'Egypt',
  'El Salvador',
  'Estonia',
  'Ethiopia',
  'Fiji',
  'Finland',
  'France',
  'Georgia',
  'Germany',
  'Ghana',
  'Greece',
  'Guatemala',
  'Haiti',
  'Honduras',
  'Hong Kong SAR',
  'Hungary',
  'Iceland',
  'India',
  'Indonesia',
  'Iran',
  'Iraq',
  'Ireland',
  'Israel',
  'Italy',
  'Jamaica',
  'Japan',
  'Jordan',
  'Kazakhstan',
  'Kenya',
  'Kuwait',
  'Laos',
  'Latvia',
  'Lebanon',
  'Lithuania',
  'Luxembourg',
  'Malaysia',
  'Malta',
  'Mexico',
  'Moldova',
  'Monaco',
  'Mongolia',
  'Morocco',
  'Mozambique',
  'Myanmar',
  'Namibia',
  'Nepal',
  'Netherlands',
  'New Zealand',
  'Nicaragua',
  'Nigeria',
  'North Macedonia',
  'Norway',
  'Oman',
  'Pakistan',
  'Panama',
  'Paraguay',
  'Peru',
  'Philippines',
  'Poland',
  'Portugal',
  'Qatar',
  'Romania',
  'Russia',
  'Rwanda',
  'San Marino',
  'Saudi Arabia',
  'Senegal',
  'Serbia',
  'Singapore',
  'Slovakia',
  'Slovenia',
  'South Africa',
  'South Korea',
  'Spain',
  'Sri Lanka',
  'Sudan',
  'Sweden',
  'Switzerland',
  'Syria',
  'Taiwan',
  'Tajikistan',
  'Tanzania',
  'Thailand',
  'Togo',
  'Trinidad and Tobago',
  'Tunisia',
  'Turkey',
  'Turkmenistan',
  'Uganda',
  'Ukraine',
  'United Arab Emirates',
  'United Kingdom',
  'United States',
  'Uruguay',
  'Uzbekistan',
  'Venezuela',
  'Vietnam',
  'Yemen',
  'Zambia',
  'Zimbabwe',
];

// Get plan details from URL parameters or session
$plan = isset($_GET['plan']) ? $_GET['plan'] : 'gladiator';
$billing = isset($_GET['billing']) ? $_GET['billing'] : 'monthly';

// Plan configurations
$plans = [
  'brawler' => [
    'name' => 'BRAWLER PLAN',
    'monthly' => 11500,
    'quarterly' => 32775,
    'has_discount' => true,
    'discount_percent' => 5,
    'benefits' => [
      'Muay Thai Training with Professional Coaches',
      'MMA Area Access',
      'Free Orientation and Fitness Assessment',
      'Shower Access',
      'Locker Access',
    ],
  ],
  'gladiator' => [
    'name' => 'GLADIATOR PLAN',
    'monthly' => 14500,
    'quarterly' => 36540,
    'has_discount' => true,
    'discount_percent' => 16,
    'benefits' => [
      'Boxing Training with Professional Coaches',
      'MMA Training with Professional Coaches',
      'Boxing and MMA Area Access',
      'Gym Equipment Access',
      'Jakuzi Access',
      'Shower Access',
      'Locker Access',
    ],
  ],
  'champion' => [
    'name' => 'CHAMPION PLAN',
    'monthly' => 7000,
    'quarterly' => 19950,
    'has_discount' => true,
    'discount_percent' => 5,
    'benefits' => [
      'Boxing Training with Professional Coaches',
      'MMA Area Access',
      'Free Orientation and Fitness Assessment',
      'Shower Access',
      'Locker Access',
    ],
  ],
  'clash' => [
    'name' => 'CLASH PLAN',
    'monthly' => 13500,
    'quarterly' => 38475,
    'has_discount' => true,
    'discount_percent' => 5,
    'benefits' => [
      'MMA Training with Professional Coaches',
      'MMA Area Access',
      'Free Orientation and Fitness Assessment',
      'Shower Access',
      'Locker Access',
    ],
  ],
  'resolution-regular' => [
    'name' => 'RESOLUTION PLAN',
    'monthly' => 2200,
    'quarterly' => 6270,
    'has_discount' => true,
    'discount_percent' => 5,
    'benefits' => [
      'Gym Equipment Access with Face Recognition',
      'Shower Access',
      'Locker Access',
    ],
  ],
];

$selectedPlan = $plans[$plan];
$price =
  $billing === 'quarterly'
    ? $selectedPlan['quarterly']
    : $selectedPlan['monthly'];
$monthlyPrice = $selectedPlan['monthly'];
$quarterlyPrice = $selectedPlan['quarterly'];

// Calculate next payment date
$nextPayment =
  $billing === 'quarterly'
    ? date('F d, Y', strtotime('+3 months'))
    : date('F d, Y', strtotime('+1 month'));

// Helper function to format plan name with styled parentheses
function formatPlanName($planName)
{
  if (preg_match('/(.*?)\s*\((.*?)\)/', $planName, $matches)) {
    $baseName = trim($matches[1]);
    $variant = trim($matches[2]);
    return $baseName .
      ' <span class="plan-variant-parens">(</span>' .
      $variant .
      '<span class="plan-variant-parens">)</span>';
  }
  return $planName;
}

// Determine avatar source for logged-in users
$avatarSrc = '../../images/account-icon.svg';
if (isset($_SESSION['email']) && isset($_SESSION['avatar'])) {
  $hasCustomAvatar =
    $_SESSION['avatar'] !== 'default-avatar.png' && !empty($_SESSION['avatar']);
  $avatarSrc = $hasCustomAvatar
    ? '../../uploads/avatars/' . htmlspecialchars($_SESSION['avatar'])
    : '../../images/account-icon.svg';
}

$pageTitle = 'Transaction - Fit and Brawl';
$currentPage = 'transaction';
$additionalCSS = ['../css/pages/transaction.css?v=' . time()];
$additionalJS = ['../js/transaction.js'];
?>
<script>
    const monthlyPrice = <?php echo json_encode($monthlyPrice); ?>;
    const quarterlyPrice = <?php echo json_encode($quarterlyPrice); ?>;
</script>
<?php // Include header
require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="transaction-page">
    <div class="transaction-container">
        <h1 class="transaction-title">COMPLETE YOUR SUBSCRIPTION</h1>

        <div class="transaction-box">
            <form id="subscriptionForm" class="subscription-form">
                <div class="transaction-content">
                    <div class="transaction-left">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" 
                                   value="<?php echo htmlspecialchars(
                                     $userName,
                                   ); ?>" 
                                   placeholder="Juan Dela Cruz" required>
                        </div>

                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" required>
                                <?php foreach ($countries as $countryName): ?>
                                    <option value="<?php echo htmlspecialchars(
                                      $countryName,
                                    ); ?>" 
                                        <?php echo $countryName ===
                                        'Philippines'
                                          ? 'selected'
                                          : ''; ?>>
                                        <?php echo htmlspecialchars(
                                          $countryName,
                                        ); ?>
                                    </option>
                                <?php endforeach; ?>
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

                    <div class="transaction-right">
                        <div class="billing-toggle">
                            <label class="billing-option">
                                <input type="radio" name="billing" value="monthly" <?php echo $billing ===
                                'monthly'
                                  ? 'checked'
                                  : ''; ?> data-billing="monthly">
                                <div class="billing-label">
                                    <div class="radio-custom"></div>
                                    <div class="billing-info">
                                        <div class="billing-title">Pay monthly</div>
                                        <div class="billing-price"><?php echo number_format(
                                          $monthlyPrice,
                                        ); ?>PHP/month
                                        </div>
                                    </div>
                                </div>
                            </label>

                            <label class="billing-option">
                                <input type="radio" name="billing" value="quarterly" <?php echo $billing ===
                                'quarterly'
                                  ? 'checked'
                                  : ''; ?> data-billing="quarterly">
                                <?php if ($selectedPlan['has_discount']): ?>
                                    <span class="discount-badge"><?php echo $selectedPlan[
                                      'discount_percent'
                                    ]; ?>%
                                        OFF</span>
                                <?php endif; ?>
                                <div class="billing-label">
                                    <div class="radio-custom"></div>
                                    <div class="billing-info">
                                        <div class="billing-title">Pay quarterly</div>
                                        <div class="billing-price">
                                            <?php if (
                                              $billing === 'quarterly' &&
                                              $selectedPlan['has_discount']
                                            ): ?>
                                                <span
                                                    class="original-price"><?php echo number_format(
                                                      $monthlyPrice * 3,
                                                    ); ?>PHP</span>
                                            <?php endif; ?>
                                            <?php echo number_format(
                                              $quarterlyPrice,
                                            ); ?>PHP/quarter
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <div class="plan-card-transaction">
                            <div class="plan-header-headband">
                                <h2 class="plan-name"><?php echo formatPlanName(
                                  $selectedPlan['name'],
                                ); ?></h2>
                                <div class="plan-price">
                                    <span class="price-amount"><?php echo $price; ?></span>
                                    <span
                                        class="price-period">/<?php echo $billing ===
                                        'quarterly'
                                          ? 'QUARTER'
                                          : 'MONTH'; ?></span>
                                </div>
                            </div>

                            <div class="plan-body">
                                <p class="next-payment">Next payment on <strong><?php echo $nextPayment; ?></strong></p>

                                <ul class="benefits-list">
                                    <?php foreach (
                                      $selectedPlan['benefits']
                                      as $benefit
                                    ): ?>
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

<script src="<?= PUBLIC_PATH ?>/js/transaction.js?=v2"></script>
</body>

</html>