<?php
http_response_code(500);
require_once __DIR__ . '/../../../includes/config.php';
require_once __DIR__ . '/../../../includes/csp_nonce.php';

// Generate CSP nonces
CSPNonce::generate();

// Log the error (without exposing to user)
if (isset($_SERVER['REQUEST_URI'])) {
    error_log("500 Error on page: " . $_SERVER['REQUEST_URI']);
}

$pageTitle = "Server Error - Fit and Brawl";
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
            <div class="error-icon error-icon--danger">
                <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="60" cy="60" r="55" stroke="currentColor" stroke-width="4" opacity="0.2"/>
                    <path d="M60 35V65" stroke="currentColor" stroke-width="6" stroke-linecap="round"/>
                    <circle cx="60" cy="80" r="4" fill="currentColor"/>
                </svg>
            </div>

            <h1 class="error-code">500</h1>
            <h2 class="error-title">Internal Server Error</h2>
            <p class="error-message">
                Something went wrong on our end. We're working to fix the issue.
            </p>

            <div class="error-actions">
                <a href="<?= PUBLIC_PATH ?>/php/index.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path d="M10 3L3 10L10 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3 10H17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Go to Homepage
                </a>
                <button onclick="location.reload()" class="btn btn-secondary">
                    Try Again
                </button>
            </div>

            <div class="error-help">
                <p>If the problem persists, <a href="<?= PUBLIC_PATH ?>/php/contact.php">contact our support team</a></p>
            </div>
        </div>
    </div>
</body>
</html>
