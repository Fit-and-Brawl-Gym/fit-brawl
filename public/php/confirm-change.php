<?php
/**
 * Confirm sensitive data change
 * Public page where users confirm email/phone changes
 */

session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/enhanced_audit_logger.php';
require_once __DIR__ . '/../../includes/sensitive_change_service.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';

EnhancedAuditLogger::init($conn);
SensitiveChangeService::init($conn);

$token = $_GET['token'] ?? null;
$result = null;

if (!$token) {
    $error = 'No confirmation token provided';
} else {
    // Process confirmation
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!CSRFProtection::validateToken($csrfToken)) {
            $error = 'Security token validation failed. Please try again.';
        } else {
            $result = SensitiveChangeService::confirmSensitiveChange($token);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Account Change - FitXBrawl</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #1a1d2e 0%, #2d3250 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }

        h1 {
            color: #1a1d2e;
            font-size: 24px;
            margin-bottom: 10px;
        }

        p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .btn {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .back-link {
            margin-top: 20px;
            display: block;
            color: #6366f1;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🔒</div>
        
        <?php if (isset($error)): ?>
            <h1>Invalid Link</h1>
            <div class="error"><?= htmlspecialchars($error) ?></div>
            <p>This confirmation link is invalid or has expired.</p>
            <a href="/fit-brawl" class="back-link">← Back to Home</a>
        
        <?php elseif ($result): ?>
            <?php if ($result['success']): ?>
                <h1>Change Confirmed! ✓</h1>
                <div class="success"><?= htmlspecialchars($result['message']) ?></div>
                <p>Your account has been updated successfully. You can now close this window.</p>
                <a href="/fit-brawl" class="back-link">← Back to Home</a>
            <?php else: ?>
                <h1>Confirmation Failed</h1>
                <div class="error"><?= htmlspecialchars($result['message']) ?></div>
                <p>Please contact support if you continue to experience issues.</p>
                <a href="/fit-brawl" class="back-link">← Back to Home</a>
            <?php endif; ?>
        
        <?php else: ?>
            <h1>Confirm Account Change</h1>
            <p>An administrator initiated a change to your account. Click the button below to confirm this change.</p>
            <form method="POST" action="">
                <?= CSRFProtection::getTokenField(); ?>
                <button type="submit" class="btn">Confirm Change</button>
            </form>
            <a href="/fit-brawl" class="back-link">← Cancel</a>
        <?php endif; ?>
    </div>
</body>
</html>
