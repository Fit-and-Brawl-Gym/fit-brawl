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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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

        /* Force consistent width on mobile for capture */
        @media (max-width: 768px) {
            body {
                padding: var(--spacing-4);
            }

            .receipt-container {
                min-width: 350px;
                max-width: 800px;
                width: 100%;
            }
        }

        .receipt-header {
            text-align: center;
            border-bottom: 3px dashed var(--color-accent);
            padding-bottom: var(--spacing-6);
            margin-bottom: var(--spacing-6);
        }

        .header-logo {
            width: 150px;
            height: auto;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
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
            border: 4px solid var(--color-accent);
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
            flex-wrap: wrap;
        }

        .btn {
            padding: var(--spacing-3) var(--spacing-6);
            border-radius: var(--radius-lg);
            font-weight: var(--font-weight-bold);
            font-size: var(--font-size-base);
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-2);
            border: none;
            flex: 1;
            min-width: 150px;
            max-width: 250px;
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
            padding: var(--spacing-2) var(--spacing-4);
            border-radius: var(--radius-lg);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-bold);
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                margin: 0;
                padding: 0;
                background: white;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }

            #printableImage {
                max-width: 190mm;
                max-height: 277mm;
                width: auto;
                height: auto;
                display: block;
                margin: 0 auto;
            }

            .receipt-container,
            .action-buttons {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .receipt-body {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .btn {
                flex: 1 1 100%;
                max-width: 100%;
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

        <div class="action-buttons">
            <button onclick="printReceipt()" class="btn btn-primary">
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

    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
        // Generate QR Code
        const qrData = JSON.stringify({
            id: '<?php echo $receipt_id; ?>',
            name: '<?php echo addslashes($booking['customer_name']); ?>',
            service: '<?php echo addslashes($booking['service_name']); ?>',
            date: '<?php echo $booking['service_date']; ?>',
            price: <?php echo $booking['price']; ?>,
            type: 'non_member'
        });

        const qr = qrcode(0, 'M');
        qr.addData(qrData);
        qr.make();

        // Store the QR code HTML for later use
        const qrCodeHTML = qr.createImgTag(6, 8);
        document.getElementById("qrcode").innerHTML = qrCodeHTML;

        // Helper function to capture receipt as image
        async function captureReceiptAsImage() {
            const container = document.getElementById('receiptContainer');
            const buttons = document.querySelector('.action-buttons');
            const qrcodeElement = document.getElementById('qrcode');

            // Store original QR code content
            const originalQRContent = qrcodeElement.innerHTML;

            // Temporarily hide buttons
            buttons.style.display = 'none';

            // Ensure QR code is present
            if (!qrcodeElement.innerHTML || qrcodeElement.innerHTML.trim() === '') {
                qrcodeElement.innerHTML = qrCodeHTML;
            }

            // Wait for QR code and all images to be fully rendered
            await new Promise(resolve => setTimeout(resolve, 500));

            // Capture the receipt
            const canvas = await html2canvas(container, {
                scale: 2,
                backgroundColor: '#2d6768',
                logging: false,
                useCORS: true,
                allowTaint: true,
                imageTimeout: 0,
                onclone: function(clonedDoc) {
                    // Ensure QR code is in the cloned document
                    const clonedQR = clonedDoc.getElementById('qrcode');
                    if (clonedQR && (!clonedQR.innerHTML || clonedQR.innerHTML.trim() === '')) {
                        clonedQR.innerHTML = qrCodeHTML;
                    }
                }
            });

            // Restore buttons and QR code
            buttons.style.display = 'flex';
            qrcodeElement.innerHTML = originalQRContent;

            return canvas;
        }

        // Print receipt function
        async function printReceipt() {
            try {
                const canvas = await captureReceiptAsImage();
                const imgData = canvas.toDataURL('image/png');

                // Create a new window for printing
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Print Receipt</title>
                        <style>
                            @page {
                                size: A4;
                                margin: 0;
                            }
                            body {
                                margin: 0;
                                padding: 0;
                                display: flex;
                                justify-content: center;
                                align-items: center;
                                min-height: 100vh;
                                background: white;
                            }
                            img {
                                max-width: 190mm;
                                max-height: 277mm;
                                width: auto;
                                height: auto;
                                display: block;
                            }
                        </style>
                    </head>
                    <body>
                        <img src="${imgData}" id="printableImage" onload="window.print(); window.onafterprint = function(){ window.close(); }">
                    </body>
                    </html>
                `);
                printWindow.document.close();
            } catch (error) {
                console.error('Error printing receipt:', error);
                alert('Failed to print receipt. Please try again.');
            }
        }

        // Download receipt as image
        async function downloadReceipt() {
            try {
                const canvas = await captureReceiptAsImage();

                // Convert canvas to blob and download
                canvas.toBlob(blob => {
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'receipt_<?php echo $receipt_id; ?>.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                });
            } catch (error) {
                console.error('Error downloading receipt:', error);
                alert('Failed to download receipt. Please try again.');
            }
        }
    </script>
</body>
</html>
