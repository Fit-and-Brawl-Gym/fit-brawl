<?php
// No session_start or login check - accessible to non-members
require_once '../../includes/db_connect.php';

// Get service details from URL parameters
$service = isset($_GET['service']) ? $_GET['service'] : 'daypass-gym';

// Service configurations (non-member prices only)
$services = [
    'daypass-gym' => [
        'name' => 'Day Pass: Gym Access',
        'price' => 150,
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
        'price' => 120,
        'benefits' => [
            'Full-day access to all gym facilities',
            'Weight room access',
            'Cardio machines access',
            'Must present valid student ID upon entry'
        ]
    ],
    'training-boxing' => [
        'name' => 'Training: Boxing',
        'price' => 380,
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
        'price' => 530,
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
        'price' => 630,
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
$pageTitle = "Day Pass Transaction (Non-Member) - Fit and Brawl";
$currentPage = "transaction_daypass_non_member";
$selectedService = $services[$service];
$price = $selectedService['price'];

// Map service to class type for trainer availability
$serviceToClassMap = [
    'daypass-gym' => 'gym',
    'daypass-gym-student' => 'gym',
    'training-boxing' => 'boxing',
    'training-muaythai' => 'muay-thai',
    'training-mma' => 'mma'
];
$classType = isset($serviceToClassMap[$service]) ? $serviceToClassMap[$service] : 'gym';

$additionalCSS = [
    '../css/pages/transaction.css',
    'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css'
];
$additionalJS = [
    'https://cdn.jsdelivr.net/npm/flatpickr'
];
?>
<style>
    .date-picker-group {
        margin-bottom: var(--spacing-4);
    }

    .date-picker-group label {
        display: block;
        font-weight: var(--font-weight-bold);
        color: var(--color-white);
        margin-bottom: var(--spacing-2);
        font-size: var(--font-size-base);
    }

    .date-picker-group input {
        width: 100%;
        padding: var(--spacing-3);
        background: rgba(255, 255, 255, 0.1);
        border: 2px solid rgba(213, 186, 43, 0.3);
        border-radius: var(--radius-md);
        color: var(--color-white);
        font-size: var(--font-size-base);
        font-family: var(--font-family-primary);
        cursor: pointer;
    }

    .date-picker-group input:focus {
        outline: none;
        border-color: var(--color-accent);
    }
</style>
<?php
// Include header
require_once '../../includes/header.php';
?>

    <!--Main-->
    <main class="transaction-page">
        <div class="transaction-container">
            <h1 class="transaction-title">BOOK YOUR SERVICE</h1>
            <div class="transaction-box">
                <form id="nonMemberForm" class="subscription-form">
                    <input type="hidden" name="service" value="<?php echo $service; ?>">

                    <div class="transaction-content">
                        <!-- Left Column -->
                        <div class="transaction-left">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" placeholder="Juan Dela Cruz" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" placeholder="juan.delacruz@email.com" required>
                            </div>

                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" placeholder="+63 912 345 6789" required>
                            </div>

                            <div class="date-picker-group">
                                <label for="serviceDate">
                                    <i class="far fa-calendar-alt"></i> Select Service Date *
                                </label>
                                <input type="text" id="serviceDate" name="service_date" placeholder="Choose a date" required readonly>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="transaction-right">
                            <!-- Service Card -->
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
                                <button type="submit" class="confirm-payment-btn" id="generateReceiptBtn">
                                    GENERATE RECEIPT
                                </button>
                                <button type="button" class="cancel-btn" onclick="window.location.href='index.php'">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="terms-notice">
                        By generating your receipt, you agree to our terms and conditions. Please present this receipt at the gym entrance on your selected date.
                    </p>
                </form>
            </div>
        </div>
    </main>

<script>
    // Initialize Flatpickr date picker after DOM and scripts load
    document.addEventListener('DOMContentLoaded', function() {
        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
                
                // If user starts typing with 0 or 9, automatically add +63
                if (value.length > 0) {
                    if (value.startsWith('0')) {
                        // Remove leading 0 and add +63
                        value = '63' + value.substring(1);
                    } else if (value.startsWith('9')) {
                        // Add +63 before the 9
                        value = '63' + value;
                    }
                    
                    // Format: +63 XXX XXX XXXX
                    let formatted = '+63';
                    if (value.length > 2) {
                        // Remove the 63 prefix for formatting
                        const number = value.substring(2);
                        if (number.length > 0) {
                            formatted += ' ' + number.substring(0, 3);
                        }
                        if (number.length > 3) {
                            formatted += ' ' + number.substring(3, 6);
                        }
                        if (number.length > 6) {
                            formatted += ' ' + number.substring(6, 10);
                        }
                    }
                    
                    e.target.value = formatted;
                } else {
                    e.target.value = '';
                }
            });

            // Prevent user from deleting +63 prefix
            phoneInput.addEventListener('keydown', function(e) {
                const cursorPosition = e.target.selectionStart;
                const value = e.target.value;
                
                // If backspace or delete is pressed and cursor is at position 0-3 (within +63)
                if ((e.key === 'Backspace' || e.key === 'Delete') && cursorPosition <= 3) {
                    e.preventDefault();
                }
            });

            // Set initial placeholder
            phoneInput.placeholder = '+63 912 345 6789';
        }

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
                                console.log("Selected date:", dateStr);
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
                                console.log("Selected date:", dateStr);
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
                            console.log("Selected date:", dateStr);
                        }
                    });
                });
        } else {
            console.error('Flatpickr not loaded');
        }

        // Handle form submission
        document.getElementById('nonMemberForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Show loading state
            const btn = document.getElementById('generateReceiptBtn');
            const originalText = btn.textContent;
            btn.textContent = 'GENERATING...';
            btn.disabled = true;

            fetch('api/generate_nonmember_receipt.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to receipt page
                    window.location.href = 'receipt_nonmember.php?id=' + data.receipt_id;
                } else {
                    alert(data.message || 'An error occurred. Please try again.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                btn.textContent = originalText;
                btn.disabled = false;
            });
        });
    });
</script>

<?php require_once '../../includes/footer.php'; ?>
