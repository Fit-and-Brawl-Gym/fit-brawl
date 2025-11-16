<?php
http_response_code(404);
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/csp_nonce.php';

// Generate CSP nonces
CSPNonce::generate();

$pageTitle = "Page Not Found - Fit and Brawl";
$currentPage = "error";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/css/pages/error.css?v=<?= time() ?>">
    <link rel="icon" type="image/png" href="<?= PUBLIC_PATH ?>/../images/favicon-members.png">
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="60" cy="60" r="55" stroke="currentColor" stroke-width="4" opacity="0.2"/>
                    <path d="M40 40L80 80M80 40L40 80" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                </svg>
            </div>

            <h1 class="error-code">404</h1>
            <h2 class="error-title">Page Not Found</h2>
            <p class="error-message">
                The page you're looking for doesn't exist or has been moved.
            </p>

            <div class="error-actions">
                <a href="<?= PUBLIC_PATH ?>/../index.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 3L3 10L10 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3 10H17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Go to Homepage
                </a>
                <button onclick="history.back()" class="btn btn-secondary">
                    Go Back
                </button>
            </div>

            <div class="error-help">
                <p>Need help? <a href="<?= PUBLIC_PATH ?>/php/contact.php">Contact Support</a></p>
            </div>
        </div>
    </div>
</body>
</html>
