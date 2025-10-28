<?php
require_once '../../includes/db_connect.php';

// Get receipt ID from URL
$receipt_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($receipt_id)) {
    header('Location: index.php');
    exit;
}

// Fetch receipt details
$stmt = $conn->prepare("SELECT * FROM non_member_bookings WHERE receipt_id = ?");
$stmt->bind_param("s", $receipt_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: index.php');
    exit;
}

// Format date for display
$service_date = new DateTime($booking['service_date']);
$booking_date = new DateTime($booking['booking_date']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt - FitXBrawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        body {
            background: var(--color-bg-dark);
            padding: var(--spacing-8) var(--spacing-4);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .receipt-container {
            max-width: 800px;
            width: 100%;
            background: linear-gradient(135deg, rgba(45, 103, 104, 0.95), rgba(23, 48, 49, 0.95));
            border: 3px solid var(--color-accent);
            border-radius: var(--radius-xl);
            padding: var(--spacing-8);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .receipt-header {
            text-align: center;
            border-bottom: 3px dashed var(--color-accent);
            padding-bottom: var(--spacing-6);
            margin-bottom: var(--spacing-6);
        }

        .receipt-logo {
            width: 80px;
            height: auto;
            margin-bottom: var(--spacing-3);
        }

        .receipt-header h1 {
            font-family: var(--font-family-display);
            font-size: var(--font-size-4xl);
            color: var(--color-accent);
            margin: 0 0 var(--spacing-2) 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .receipt-subtitle {
            color: var(--color-white);
            font-size: var(--font-size-lg);
            margin: 0;
        }

        .receipt-id {
            background: rgba(213, 186, 43, 0.2);
            color: var(--color-accent);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-xl);
            padding: var(--spacing-3);
            border-radius: var(--radius-md);
            margin: var(--spacing-4) 0;
            text-align: center;
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
        }

        .receipt-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-6);
        }

        .receipt-section {
            background: rgba(255, 255, 255, 0.05);
            padding: var(--spacing-5);
            border-radius: var(--radius-lg);
        }

        .receipt-section h2 {
            font-family: var(--font-family-display);
            font-size: var(--font-size-xl);
            color: var(--color-accent);
            margin: 0 0 var(--spacing-4) 0;
            text-transform: uppercase;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-2) 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
        }

        .info-value {
            color: var(--color-white);
            font-size: var(--font-size-base);
            font-weight: var(--font-weight-bold);
            text-align: right;
        }

        .service-highlight {
            background: rgba(213, 186, 43, 0.15);
            padding: var(--spacing-4);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--color-accent);
            margin: var(--spacing-4) 0;
        }

        .service-name {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-accent);
            margin: 0 0 var(--spacing-2) 0;
        }

        .service-price {
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-black);
            color: var(--color-white);
            margin: 0;
        }

        .qr-section {
            text-align: center;
            padding: var(--spacing-6);
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-lg);
            margin: var(--spacing-6) 0;
        }

        #qrcode {
            display: inline-block;
            padding: var(--spacing-4);
            background: white;
            border-radius: var(--radius-md);
            margin: var(--spacing-4) 0;
        }

        .qr-instruction {
            color: var(--color-accent);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-lg);
            margin: var(--spacing-3) 0 0 0;
        }

        .receipt-footer {
            text-align: center;
            padding-top: var(--spacing-6);
            border-top: 3px dashed var(--color-accent);
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-sm);
        }

        .action-buttons {
            display: flex;
            gap: var(--spacing-4);
            justify-content: center;
            margin-top: var(--spacing-6);
        }

        .btn {
            padding: var(--spacing-3) var(--spacing-8);
            border-radius: var(--radius-lg);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-base);
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            border: none;
        }

        .btn-primary {
            background: var(--color-accent);
            color: var(--color-fog);
        }

        .btn-primary:hover {
            background: #ffe066;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--color-white);
            border: 2px solid var(--color-white);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .status-badge {
            display: inline-block;
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-full);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-bold);
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .receipt-container {
                border: 2px solid #000;
                box-shadow: none;
            }

            .action-buttons {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .receipt-body {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container" id="receiptContainer">
        <div class="receipt-header">
            <img src="../../images/fnb-logo-yellow.svg" alt="FitXBrawl Logo" class="receipt-logo">
            <h1>Booking Receipt</h1>
            <p class="receipt-subtitle">Fit X Brawl Gym</p>
        </div>

        <div class="receipt-id">
            Receipt ID: <?php echo htmlspecialchars($receipt_id); ?>
        </div>

        <div class="service-highlight">
            <p class="service-name"><?php echo htmlspecialchars($booking['service_name']); ?></p>
            <p class="service-price"><?php echo number_format($booking['price'], 2); ?> PHP</p>
        </div>

        <div class="receipt-body">
            <div class="receipt-section">
                <h2><i class="fas fa-user"></i> Customer Details</h2>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['customer_email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['customer_phone']); ?></span>
                </div>
            </div>

            <div class="receipt-section">
                <h2><i class="fas fa-calendar-check"></i> Booking Details</h2>
                <div class="info-row">
                    <span class="info-label">Service Date:</span>
                    <span class="info-value"><?php echo $service_date->format('F j, Y'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Booked On:</span>
                    <span class="info-value"><?php echo $booking_date->format('M j, Y g:i A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-pending">Pending</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="qr-section">
            <div id="qrcode"></div>
            <p class="qr-instruction">
                <i class="fas fa-qrcode"></i> Show this QR code at the gym entrance
            </p>
        </div>

        <div class="receipt-footer">
            <p><strong>Important:</strong> Please present this receipt at the gym entrance on your scheduled date.</p>
            <p>1832 Oroquieta Rd, Santa Cruz, Manila, 1008 Metro Manila</p>
            <p>fitxbrawl@gmail.com | Sun–Fri: 9AM-10PM | Saturday: 10AM-7PM</p>
        </div>

        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <button onclick="downloadReceipt()" class="btn btn-primary">
                <i class="fas fa-download"></i> Download
            </button>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script>
        // Generate QR Code
        const qrData = JSON.stringify({
            id: '<?php echo $receipt_id; ?>',
            name: '<?php echo addslashes($booking['customer_name']); ?>',
            service: '<?php echo addslashes($booking['service_name']); ?>',
            date: '<?php echo $booking['service_date']; ?>',
            price: <?php echo $booking['price']; ?>
        });

        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Download receipt as image
        function downloadReceipt() {
            window.print();
        }
    </script>
</body>
</html>
