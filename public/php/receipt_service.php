<?php
session_start();
require_once '../../includes/db_connect.php';

// Get receipt ID from URL
$receipt_id = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($receipt_id)) {
    header('Location: index.php');
    exit;
}

// Fetch booking details
$stmt = $conn->prepare("
    SELECT
        msb.*,
        u.username,
        u.email as user_email
    FROM member_service_bookings msb
    LEFT JOIN users u ON msb.user_id = u.id
    WHERE msb.receipt_id = ?
");

$stmt->bind_param("s", $receipt_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$booking = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Format dates
$service_date_formatted = date('F d, Y', strtotime($booking['service_date']));
$booking_date_formatted = date('F d, Y \a\t g:i A', strtotime($booking['booking_date']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Receipt - <?php echo htmlspecialchars($receipt_id); ?></title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .receipt-container {
            background: white;
            max-width: 800px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .receipt-header {
            background: linear-gradient(135deg, #f4c430 0%, #d4a017 100%);
            padding: 2rem;
            text-align: center;
            color: #1a1a1a;
        }

        .receipt-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .receipt-header p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .receipt-body {
            padding: 2.5rem;
        }

        .receipt-id {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            border: 2px dashed #d4a017;
        }

        .receipt-id strong {
            font-size: 1.3rem;
            color: #d4a017;
            font-weight: 600;
        }

        .info-section {
            margin-bottom: 2rem;
        }

        .info-section h2 {
            font-size: 1.2rem;
            color: #1a1a1a;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f4c430;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #666;
        }

        .info-value {
            font-weight: 600;
            color: #1a1a1a;
            text-align: right;
        }

        .price-row {
            background: #fff8e1;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .price-row .info-value {
            color: #d4a017;
            font-size: 1.5rem;
        }

        .member-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .non-member-badge {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 0.35rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .qr-section {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 2rem 0;
        }

        .qr-section h3 {
            margin-bottom: 1rem;
            color: #1a1a1a;
        }

        #qrcode {
            display: inline-block;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .instructions {
            background: #e7f3ff;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #0066cc;
            margin: 2rem 0;
        }

        .instructions h3 {
            color: #0066cc;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }

        .instructions ul {
            margin-left: 1.5rem;
            color: #333;
        }

        .instructions li {
            margin-bottom: 0.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f4c430 0%, #d4a017 100%);
            color: #1a1a1a;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 196, 48, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .action-buttons {
                display: none;
            }
            .receipt-container {
                box-shadow: none;
            }
        }

        @media (max-width: 600px) {
            .receipt-body {
                padding: 1.5rem;
            }
            .action-buttons {
                flex-direction: column;
            }
            .info-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            .info-value {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>🥊 FIT X BRAWL GYM</h1>
            <p>Service Booking Receipt</p>
        </div>

        <div class="receipt-body">
            <div class="receipt-id">
                <strong>Receipt ID: <?php echo htmlspecialchars($receipt_id); ?></strong>
            </div>

            <div class="info-section">
                <h2>Customer Information</h2>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Username:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['username']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['user_email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Country:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['country']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['permanent_address']); ?></span>
                </div>
            </div>

            <div class="info-section">
                <h2>Service Details</h2>
                <div class="info-row">
                    <span class="info-label">Service:</span>
                    <span class="info-value"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Service Date:</span>
                    <span class="info-value"><?php echo $service_date_formatted; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Booking Date:</span>
                    <span class="info-value"><?php echo $booking_date_formatted; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member Status:</span>
                    <span class="info-value">
                        <?php if ($booking['is_member']): ?>
                            <span class="member-badge">Active Member</span>
                        <?php else: ?>
                            <span class="non-member-badge">Non-Member</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-row price-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value">₱<?php echo number_format($booking['price'], 2); ?></span>
                </div>
            </div>

            <div class="qr-section">
                <h3>Show this QR code at the gym entrance</h3>
                <div id="qrcode"></div>
            </div>

            <div class="instructions">
                <h3>📋 Important Instructions</h3>
                <ul>
                    <li>Present this receipt at the gym entrance on your service date</li>
                    <li>Arrive at least 15 minutes before your scheduled time</li>
                    <li>Bring a valid ID for verification</li>
                    <li>For student passes, bring your student ID</li>
                    <li>Receipt is valid only for the date specified above</li>
                    <li>No refunds after booking confirmation</li>
                </ul>
            </div>

            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">🖨️ Print Receipt</button>
                <a href="membership-status.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
        // Generate QR Code
        const qrData = {
            receipt_id: '<?php echo $receipt_id; ?>',
            service: '<?php echo addslashes($booking['service_name']); ?>',
            name: '<?php echo addslashes($booking['name']); ?>',
            date: '<?php echo $booking['service_date']; ?>',
            price: <?php echo $booking['price']; ?>,
            type: 'member_service'
        };

        const qr = qrcode(0, 'M');
        qr.addData(JSON.stringify(qrData));
        qr.make();

        document.getElementById('qrcode').innerHTML = qr.createImgTag(6, 8);
    </script>
</body>
</html>
