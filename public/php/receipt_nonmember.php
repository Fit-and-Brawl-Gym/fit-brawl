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

$pageTitle = "Day Pass Receipt (Non-Member) - Fit and Brawl";
$currentPage = "receipt_non_member";
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
    <style>
        /* Font face declaration for receipt rendering */
        @font-face {
            font-family: 'zuume-rough-bold';
            src: url('../css/components/fonts/zuume-rough-bold.ttf') format('truetype');
        }
        /* CSS Variables for receipt - ensures colors work even if global.css fails */
        :root {
            --color-accent: #d5ba2b;
            --color-white: #ffffff;
            --color-bg-dark: #010d17;
            --color-fog: #0a2a36;
            --font-family-display: 'zuume-rough-bold', 'Poppins', sans-serif;
            --font-size-xs: 0.75rem;
            --font-size-sm: 0.875rem;
            --font-size-base: 1rem;
            --font-size-xl: 1.25rem;
            --font-weight-bold: 700;
            --font-weight-medium: 500;
            --font-weight-black: 900;
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-5: 1.25rem;
            --radius-md: 0.375rem;
            --radius-lg: 0.5rem;
            --radius-xl: 0.75rem;
        }
        <?php if (isset($_GET['render'])): ?>
        .action-buttons { display: none !important; }
        body {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .receipt-wrapper {
            margin: 0 !important;
            padding: 0 !important;
        }
        .receipt-container {
            margin: 0 !important;
            box-shadow: none !important;
        }
        <?php endif; ?>
        body {
            background: var(--color-bg-dark);
            padding: var(--spacing-4);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .receipt-wrapper {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            box-sizing: border-box;
            /* Force exact color rendering for all browsers */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        .receipt-container {
            /* Fixed size: half of A4 height (148.5mm) and reasonable width */
            width: 100%;
            background: linear-gradient(135deg, rgba(45, 103, 104, 0.95), rgba(23, 48, 49, 0.95));
            border: 3px solid var(--color-accent);
            border-radius: var(--radius-xl);
            padding: var(--spacing-5);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            margin-bottom: var(--spacing-4);
            box-sizing: border-box;
            /* Force exact color rendering for all browsers */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed var(--color-accent);
            padding-bottom: var(--spacing-3);
            margin-bottom: var(--spacing-3);
        }

        .header-logo {
            width: 100px;
            height: auto;
            margin: 0 auto;
        }

        .receipt-logo {
            width: 50px;
            height: auto;
            margin-bottom: var(--spacing-2);
        }

        .receipt-header h1 {
            font-family: var(--font-family-display);
            font-size: var(--font-size-xl);
            color: var(--color-accent);
            margin: 0 0 var(--spacing-1) 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .receipt-id {
            background: rgba(213, 186, 43, 0.2);
            color: var(--color-accent);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-base);
            padding: var(--spacing-2);
            border-radius: var(--radius-md);
            margin: var(--spacing-2) 0;
            text-align: center;
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
            border: 2px solid var(--color-accent);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        .receipt-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-3);
            margin-bottom: var(--spacing-3);
        }

        .receipt-section {
            background: rgba(255, 255, 255, 0.05);
            padding: var(--spacing-3);
            border-radius: var(--radius-lg);
        }

        .receipt-section h2 {
            font-family: var(--font-family-display);
            font-size: var(--font-size-base);
            color: var(--color-accent);
            margin: 0 0 var(--spacing-2) 0;
            text-transform: uppercase;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: var(--spacing-1) 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            gap: var(--spacing-2);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-medium);
            flex-shrink: 0;
        }

        .info-value {
            color: var(--color-white);
            font-size: var(--font-size-xs);
            font-weight: var(--font-weight-bold);
            text-align: right;
            word-break: break-word;
        }

        .service-highlight {
            background: rgba(213, 186, 43, 0.15);
            padding: var(--spacing-2);
            border-radius: var(--radius-md);
            margin: var(--spacing-2) 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        .service-name {
            font-size: var(--font-size-base);
            font-weight: var(--font-weight-bold);
            color: var(--color-accent);
            margin: 0 0 var(--spacing-1) 0;
        }

        .service-price {
            font-size: var(--font-size-xl);
            font-weight: var(--font-weight-black);
            color: var(--color-white);
            margin: 0;
        }

        .qr-section {
            text-align: center;
            padding: var(--spacing-3);
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-lg);
            margin: var(--spacing-3) 0;
        }

        #qrcode {
            display: inline-block;
            padding: var(--spacing-2);
            background: white;
            border-radius: var(--radius-md);
            margin: var(--spacing-2) 0;
            line-height: 0;
        }

        #qrcode img {
            display: block;
            width: 120px !important;
            height: 120px !important;
        }

        .qr-instruction {
            color: var(--color-accent);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-sm);
            margin: var(--spacing-2) 0 0 0;
        }

        .receipt-footer {
            text-align: center;
            padding-top: var(--spacing-3);
            border-top: 2px dashed var(--color-accent);
            color: rgba(255, 255, 255, 0.7);
            font-size: 10px;
            line-height: 1.4;
        }

        .receipt-footer p {
            margin: var(--spacing-1) 0;
        }

        .action-buttons {
            display: flex;
            gap: var(--spacing-3);
            justify-content: center;
            margin-top: var(--spacing-4);
            flex-wrap: wrap;
            width: 100%;
            box-sizing: border-box;
        }

        .btn {
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-lg);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-sm);
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-2);
            border: none;
            flex: 1;
            min-width: 120px;
            max-width: 200px;
        }

        .btn-primary {
            background: var(--color-accent);
            color: var(--color-fog);
        }

        .btn-primary:hover {
            background: #ffe066;
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
            padding: var(--spacing-1) var(--spacing-2);
            border-radius: var(--radius-lg);
            font-size: 10px;
            font-weight: var(--font-weight-bold);
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        /* (No download modal styles; direct download only) */

        @media print {
            @page { size: A4; margin: 15mm; }
            body * { visibility: hidden !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .receipt-wrapper, .receipt-wrapper * { visibility: visible !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .receipt-wrapper { position: absolute; left: 0; top: 0; right: 0; margin: 0 auto; width: 150mm; }
            .receipt-container { min-height: 148.5mm; box-shadow: none; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .print-modal, .print-modal * { display: none !important; }
            .action-buttons { display: none !important; }
        }

        @media (max-width: 768px) {
            .receipt-container {
                max-width: 100%;
                padding: var(--spacing-4);
            }

            .receipt-body {
                grid-template-columns: 1fr;
                gap: var(--spacing-2);
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                max-width: 100%;
            }

            .header-logo {
                width: 80px;
            }

            .receipt-logo {
                width: 40px;
            }

            #qrcode img {
                width: 100px !important;
                height: 100px !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-wrapper">
        <div class="receipt-container" id="receiptContainer">
        <div class="receipt-header">
            <img src="../../images/fnb-logo-yellow.svg" alt="FitXBrawl Logo" class="receipt-logo">
            <h1>Booking Receipt</h1>
            <img src="../../images/header-title.svg" alt="Fit and Brawl Gym Logo" class="header-logo">
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
        </div>

        <div class="action-buttons">
            <a class="btn btn-primary" href="receipt_render.php?type=nonmember&id=<?php echo urlencode($receipt_id); ?>&format=pdf">
                <i class="fas fa-file-pdf"></i> Save as PDF
            </a>
            <a class="btn btn-primary" href="receipt_render.php?type=nonmember&id=<?php echo urlencode($receipt_id); ?>&format=png">
                <i class="fas fa-file-image"></i> Save as PNG
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <!-- (No download modal; direct download is used) -->

    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
    let qrReadyPromise;
        // Generate QR Code on page load
        document.addEventListener('DOMContentLoaded', function() {
            qrReadyPromise = generateQRCode();
        });

        // Generate QR Code
        function generateQRCode() {
            const qrData = JSON.stringify({
                id: '<?php echo $receipt_id; ?>',
                name: '<?php echo addslashes($booking['customer_name']); ?>',
                service: '<?php echo addslashes($booking['service_name']); ?>',
                date: '<?php echo $booking['service_date']; ?>',
                price: <?php echo $booking['price']; ?>,
                type: 'non_member'
            });
            return new Promise((resolve) => {
                try {
                    const qr = qrcode(0, 'M');
                    qr.addData(qrData);
                    qr.make();
                    const qrImage = qr.createDataURL(4, 0);
                    const img = document.createElement('img');
                    img.alt = 'QR Code';
                    img.decoding = 'sync';
                    img.loading = 'eager';
                    img.src = qrImage;
                    img.style.width = '120px';
                    img.style.height = '120px';
                    img.style.display = 'block';
                    const qrcodeElement = document.getElementById('qrcode');
                    qrcodeElement.innerHTML = '';
                    img.addEventListener('load', () => resolve(true), { once: true });
                    img.addEventListener('error', () => resolve(false), { once: true });
                    qrcodeElement.appendChild(img);
                } catch (e) {
                    console.error('QR generation failed', e);
                    resolve(false);
                }
            });
        }

    </script>
</body>
</html>
