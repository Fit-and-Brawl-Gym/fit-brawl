<?php
// Fallback page for receipt download when Node.js renderer is unavailable
session_start();

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$id   = isset($_GET['id']) ? trim($_GET['id']) : '';

// Determine redirect based on type
$receiptPage = ($type === 'member') ? 'receipt_service.php' : 'receipt_nonmember.php';
$redirectUrl = $receiptPage . '?id=' . urlencode($id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Receipt - Fit & Brawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="shortcut icon" href="../../images/fnb-icon.png" type="image/x-icon">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-bg-dark);
            padding: var(--spacing-4);
        }
        .fallback-container {
            background: var(--color-card-bg);
            border: 2px solid var(--color-accent);
            border-radius: var(--radius-xl);
            padding: var(--spacing-6);
            max-width: 600px;
            text-align: center;
            box-shadow: var(--shadow-xl);
        }
        h1 {
            color: var(--color-accent);
            margin-bottom: var(--spacing-4);
            font-family: var(--font-family-display);
        }
        p {
            color: var(--color-text-light);
            margin-bottom: var(--spacing-3);
            line-height: 1.6;
        }
        .instruction-box {
            background: rgba(213, 186, 43, 0.1);
            border: 1px solid var(--color-accent);
            border-radius: var(--radius-lg);
            padding: var(--spacing-4);
            margin: var(--spacing-4) 0;
        }
        .instruction-box h2 {
            color: var(--color-accent);
            margin-bottom: var(--spacing-2);
            font-size: var(--font-size-lg);
        }
        .instruction-box ol {
            color: var(--color-text-light);
            text-align: left;
            margin: var(--spacing-3) auto;
            max-width: 400px;
        }
        .instruction-box li {
            margin-bottom: var(--spacing-2);
        }
        .kbd {
            background: var(--color-primary);
            border: 1px solid var(--color-accent);
            border-radius: var(--radius-sm);
            padding: 0.25rem 0.5rem;
            font-family: monospace;
            color: var(--color-accent);
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: var(--spacing-3) var(--spacing-6);
            background: var(--color-accent);
            color: var(--color-fog);
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-weight: bold;
            margin-top: var(--spacing-4);
            transition: var(--transition-fast);
        }
        .btn:hover {
            background: #ffe066;
        }
        .info-note {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
            padding: var(--spacing-3);
            margin-top: var(--spacing-4);
            text-align: left;
        }
        .info-note strong {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="fallback-container">
        <h1><i class="fas fa-download"></i> Receipt Download</h1>

        <p>Automatic PDF/PNG download is not available on this server.</p>
        <p>Please use your browser's built-in print feature to save your receipt:</p>

        <div class="instruction-box">
            <h2><i class="fas fa-print"></i> How to Download:</h2>
            <ol>
                <li>Click the button below to view your receipt</li>
                <li>Press <span class="kbd">Ctrl + P</span> (Windows) or <span class="kbd">Cmd + P</span> (Mac)</li>
                <li>In the print dialog, select <strong>"Save as PDF"</strong></li>
                <li>Choose your location and click Save</li>
            </ol>
        </div>

        <a href="<?php echo htmlspecialchars($redirectUrl); ?>" class="btn">
            <i class="fas fa-eye"></i> View Receipt
        </a>

        <div class="info-note">
            <strong><i class="fas fa-info-circle"></i> Note:</strong>
            Your receipt includes a QR code that will be visible when printed or saved as PDF.
        </div>
    </div>
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
</body>
</html>

