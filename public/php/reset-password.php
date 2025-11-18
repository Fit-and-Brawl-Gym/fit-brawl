<?php
/**
 * Reset password
 * Public page where users set their own password after admin triggers reset
 */

session_start();
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db_connect.php';
require_once __DIR__ . '/../../includes/enhanced_audit_logger.php';
require_once __DIR__ . '/../../includes/password_reset_service.php';
require_once __DIR__ . '/../../includes/password_policy.php';
require_once __DIR__ . '/../../includes/csrf_protection.php';

EnhancedAuditLogger::init($conn);
PasswordResetService::init($conn);

$token = $_GET['token'] ?? null;
$result = null;
$tokenValid = false;

if (!$token) {
    $error = 'No reset token provided';
} else {
    // Verify token first
    $verification = PasswordResetService::verifyResetToken($token);
    $tokenValid = $verification['success'];
    
    if (!$tokenValid) {
        $error = $verification['message'];
    }
}

// Handle password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    // Validate CSRF token
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!CSRFProtection::validateToken($csrfToken)) {
        $error = 'Security token validation failed. Please try again.';
    } else {
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword)) {
        $error = 'Password is required';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Validate password using PasswordPolicy
        $passwordErrors = PasswordPolicy::validate($newPassword);
        if (!empty($passwordErrors)) {
            $error = implode('<br>', $passwordErrors);
        } else {
            $result = PasswordResetService::completePasswordReset($token, $newPassword);
        }
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - FitXBrawl</title>
    <link rel="icon" type="image/png" href="../../images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --color-bg-dark: #002f3f;
            --color-teal-dark: rgba(0, 47, 63, 0.95);
            --color-accent: #ffce00;
            --color-white: #ffffff;
            --color-fog: #1e1e21;
            --color-primary: #002f3f;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-5: 1.25rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            --radius-md: 8px;
            --radius-lg: 12px;
            --radius-xl: 16px;
            --radius-2xl: 24px;
            --font-size-sm: 14px;
            --font-size-base: 16px;
            --font-size-lg: 18px;
            --font-size-xl: 20px;
            --font-size-2xl: 24px;
            --font-weight-medium: 500;
            --font-weight-semibold: 600;
            --font-weight-bold: 700;
            --transition-fast: all 0.2s ease;
            --transition-base: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: var(--color-bg-dark);
            background-image: url('../../images/logged_in-bg.webp');
            background-repeat: no-repeat;
            background-position: center right;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right,
                rgba(0, 47, 63, 0.95) 0%,
                rgba(0, 47, 63, 0.85) 50%,
                rgba(0, 47, 63, 0.7) 100%);
            z-index: 0;
            pointer-events: none;
        }

        .container {
            background: linear-gradient(135deg, rgba(255, 206, 0, 0.08) 0%, rgba(0, 47, 63, 0.7) 100%);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 206, 0, 0.2);
            border-radius: var(--radius-2xl);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3), 0 0 60px rgba(255, 206, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: var(--spacing-8);
            position: relative;
            z-index: 1;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--color-accent) 0%, #ffd700 100%);
            border-radius: 50%;
            margin: 0 auto var(--spacing-6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: var(--color-fog);
            box-shadow: 0 8px 20px rgba(255, 206, 0, 0.4);
        }

        .logo.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
        }

        .logo.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        h1 {
            color: var(--color-white);
            font-size: 28px;
            margin-bottom: 12px;
            text-align: center;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .yellow {
            color: var(--color-accent);
            font-weight: 900;
        }

        .title-underline {
            width: 60px;
            height: 3px;
            background: var(--color-accent);
            margin: 0 auto var(--spacing-6);
        }

        p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
            margin-bottom: var(--spacing-6);
            text-align: center;
            font-size: 15px;
        }

        .form-group {
            margin-bottom: var(--spacing-6);
            position: relative;
        }

        label {
            display: block;
            color: var(--color-white);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .password-input-group {
            position: relative;
            margin-bottom: var(--spacing-6);
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 16px;
            z-index: 2;
        }

        .eye-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 18px;
            cursor: pointer;
            transition: var(--transition-fast);
            z-index: 2;
        }

        .eye-toggle:hover {
            color: var(--color-accent);
        }

        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 14px 44px 14px 44px;
            border: 2px solid rgba(255, 206, 0, 0.2);
            border-radius: var(--radius-lg);
            font-size: 15px;
            transition: var(--transition-fast);
            font-family: inherit;
            background: rgba(255, 255, 255, 0.05);
            color: var(--color-white);
            backdrop-filter: blur(10px);
        }

        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: var(--color-accent);
            box-shadow: 0 0 0 4px rgba(255, 206, 0, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }

        input[type="password"]::placeholder,
        input[type="text"]::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        /* Password Requirements Modal - Positioned to the left side */
        .password-requirements-modal {
            position: absolute;
            left: -300px;
            top: 50%;
            transform: translateY(-50%);
            background: linear-gradient(135deg, #002f3f 0%, #1a4552 100%);
            border: 2px solid var(--color-accent);
            border-radius: var(--radius-xl);
            padding: var(--spacing-5);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(213, 186, 43, 0.1);
            min-width: 280px;
            max-width: 280px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
            z-index: 100;
        }

        .password-requirements-modal.show {
            opacity: 1;
            visibility: visible;
            animation: slideInLeft 0.3s ease;
        }

        @keyframes slideInLeft {
            from {
                transform: translateY(-50%) translateX(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(-50%) translateX(0);
                opacity: 1;
            }
        }

        .password-requirements-header {
            margin-bottom: var(--spacing-4);
            padding-bottom: var(--spacing-3);
            border-bottom: 2px solid var(--color-accent);
            text-align: center;
        }

        .password-requirements-header h4 {
            font-size: var(--font-size-xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-accent);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .password-requirements-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-2);
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            font-size: var(--font-size-sm);
            background-color: rgba(255, 255, 255, 0.05);
            color: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .requirement-item.met {
            background-color: rgba(213, 186, 43, 0.15);
            color: var(--color-accent);
            border-color: var(--color-accent);
        }

        .requirement-icon {
            width: 20px;
            height: 20px;
            min-width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 11px;
            background-color: rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .requirement-item.met .requirement-icon {
            background-color: var(--color-accent);
            color: var(--color-primary);
            border-color: var(--color-accent);
            box-shadow: 0 0 8px rgba(213, 186, 43, 0.4);
        }

        .requirement-text {
            font-size: var(--font-size-sm);
            line-height: 1.4;
        }

        /* Password Strength Indicator */
        .strength-indicator {
            margin-top: 12px;
            display: none;
        }

        .strength-indicator.show {
            display: block;
        }

        .strength-bar {
            height: 6px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-bar-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .strength-bar-fill.weak {
            width: 33%;
            background-color: #dc3545;
        }

        .strength-bar-fill.medium {
            width: 66%;
            background-color: #ffc107;
        }

        .strength-bar-fill.strong {
            width: 100%;
            background-color: #28a745;
        }

        .strength-text {
            font-size: 12px;
            font-weight: 600;
        }

        .strength-text.weak {
            color: #dc3545;
        }

        .strength-text.medium {
            color: #ffc107;
        }

        .strength-text.strong {
            color: #28a745;
        }

        /* Password Match Message */
        .password-match-message {
            font-size: 13px;
            font-weight: 600;
            padding: 10px 12px;
            border-radius: var(--radius-md);
            margin-top: -12px;
            margin-bottom: var(--spacing-4);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .password-match-message.show {
            opacity: 1;
            visibility: visible;
        }

        .password-match-message.match {
            background-color: rgba(16, 185, 129, 0.15);
            color: #6ee7b7;
            border: 1px solid #10b981;
        }

        .password-match-message.no-match {
            background-color: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid #ef4444;
        }


        .success-message {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.1) 100%);
            border: 2px solid #10b981;
            color: #6ee7b7;
            padding: 18px;
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-6);
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .success-message i {
            font-size: 20px;
        }

        .error-message {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%);
            border: 2px solid #ef4444;
            color: #fca5a5;
            padding: 18px;
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-6);
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 20px;
        }

        .btn {
            background: linear-gradient(135deg, var(--color-accent) 0%, #ffd700 100%);
            color: var(--color-fog);
            border: none;
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 700;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: var(--transition-base);
            width: 100%;
            box-shadow: 0 4px 12px rgba(255, 206, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255, 206, 0, 0.5);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .back-link {
            margin-top: var(--spacing-6);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--color-accent);
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: var(--transition-fast);
        }

        .back-link:hover {
            color: #ffd700;
            gap: 12px;
        }

        .security-note {
            background: rgba(255, 206, 0, 0.05);
            border-left: 4px solid var(--color-accent);
            padding: 16px;
            margin-top: var(--spacing-6);
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            border-radius: 8px;
            display: flex;
            gap: 12px;
        }

        .security-note i {
            color: var(--color-accent);
            font-size: 18px;
            margin-top: 2px;
        }

        .security-note strong {
            color: var(--color-white);
        }

        @media (max-width: 640px) {
            .container {
                padding: 32px 24px;
            }

            h1 {
                font-size: 24px;
            }

            .password-requirements-modal {
                position: fixed;
                left: 50%;
                bottom: auto;
                top: 50%;
                transform: translate(-50%, -50%);
                max-width: calc(100vw - 40px);
                min-width: 280px;
            }

            .password-requirements-modal.show {
                animation: fadeIn 0.3s ease;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translate(-50%, -50%) scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: translate(-50%, -50%) scale(1);
                }
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo <?= isset($error) && !$tokenValid ? 'error' : ($result && $result['success'] ? 'success' : '') ?>">
            <?= isset($error) && !$tokenValid ? '<i class="fas fa-lock"></i>' : ($result && $result['success'] ? '<i class="fas fa-check"></i>' : '<i class="fas fa-key"></i>') ?>
        </div>
        
        <?php if (isset($error) && !$tokenValid): ?>
            <h1><span class="yellow">Invalid</span> Link</h1>
            <div class="title-underline"></div>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
            <p>This password reset link is invalid or has expired.</p>
            <?php
            require_once __DIR__ . '/../../includes/config.php';
            $homeUrl = (ENVIRONMENT === 'production') ? '/php/index.php' : PUBLIC_PATH . '/php/index.php';
            ?>
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        
        <?php elseif ($result && $result['success']): ?>
            <h1>Password <span class="yellow">Reset Complete!</span></h1>
            <div class="title-underline"></div>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span><?= htmlspecialchars($result['message']) ?></span>
            </div>
            <p>Your password has been successfully updated. You can now log in with your new password.</p>
            <?php
            $loginUrl = (ENVIRONMENT === 'production') ? '/php/login.php' : PUBLIC_PATH . '/php/login.php';
            ?>
            <a href="<?= htmlspecialchars($loginUrl) ?>" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                Go to Login
            </a>
        
        <?php else: ?>
            <h1>Set <span class="yellow">New Password</span></h1>
            <div class="title-underline"></div>
            <p>Choose a strong password for your account. <br>Your administrator will never see this password.</p>
            
            <?php if (isset($error) && $tokenValid): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="resetForm">
                <?= CSRFProtection::getTokenField(); ?>
                <div class="password-input-group">
                    <label for="passwordInput">New Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="passwordInput" name="password" 
                               autocomplete="new-password" autocapitalize="off" 
                               autocorrect="off" spellcheck="false" required>
                        <i class="fas fa-eye eye-toggle" id="togglePassword"></i>
                    </div>

                    <!-- Password Requirements Modal -->
                    <div class="password-requirements-modal" id="passwordRequirementsModal">
                        <div class="password-requirements-header">
                            <h4>Password Requirements</h4>
                        </div>
                        <div class="password-requirements-list">
                            <div class="requirement-item" id="req-length">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">At least 12 characters</span>
                            </div>
                            <div class="requirement-item" id="req-uppercase">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">One uppercase letter</span>
                            </div>
                            <div class="requirement-item" id="req-lowercase">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">One lowercase letter</span>
                            </div>
                            <div class="requirement-item" id="req-number">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">One number</span>
                            </div>
                            <div class="requirement-item" id="req-special">
                                <span class="requirement-icon">✗</span>
                                <span class="requirement-text">One special character (!@#$%^&*?)</span>
                            </div>
                        </div>
                        <div class="strength-indicator" id="strengthIndicator">
                            <div class="strength-bar">
                                <div class="strength-bar-fill" id="strengthBarFill"></div>
                            </div>
                            <span class="strength-text" id="strengthText">Strength: Weak</span>
                        </div>
                    </div>
                </div>

                <div class="password-input-group">
                    <label for="confirmPasswordInput">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirmPasswordInput" name="confirm_password" 
                               autocomplete="new-password" autocapitalize="off" 
                               autocorrect="off" spellcheck="false" required>
                        <i class="fas fa-eye eye-toggle" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <!-- Password Match Message -->
                <div class="password-match-message" id="passwordMatchMessage"></div>

                <button type="submit" class="btn">
                    <i class="fas fa-check"></i>
                    Reset Password
                </button>
            </form>

            <div class="security-note">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <strong>Security Note:</strong> Your password is encrypted and stored securely. No one, including administrators, can see your password.
                </div>
            </div>

            <?php
            $homeUrl = (ENVIRONMENT === 'production') ? '/php/index.php' : PUBLIC_PATH . '/php/index.php';
            ?>
            <a href="<?= htmlspecialchars($homeUrl) ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        <?php endif; ?>
    </div>

    <script>
        // Real-time password validation from sign-up.php
        (function() {
            const passwordInput = document.getElementById('passwordInput');
            const confirmPasswordInput = document.getElementById('confirmPasswordInput');
            const passwordRequirementsModal = document.getElementById('passwordRequirementsModal');
            const strengthIndicator = document.getElementById('strengthIndicator');
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordMatchMessage = document.getElementById('passwordMatchMessage');

            // Password validation patterns (12 chars minimum like sign-up.php)
            const patterns = {
                length: /.{12,}/,
                uppercase: /[A-Z]/,
                lowercase: /[a-z]/,
                number: /[0-9]/,
                special: /[!@#$%^&*?]/
            };

            // Requirement elements
            const requirements = {
                length: document.getElementById('req-length'),
                uppercase: document.getElementById('req-uppercase'),
                lowercase: document.getElementById('req-lowercase'),
                number: document.getElementById('req-number'),
                special: document.getElementById('req-special')
            };

            function checkPasswordRequirements(password) {
                // If password is empty, reset all requirements to unmet
                if (!password || password.length === 0) {
                    Object.keys(requirements).forEach(key => {
                        if (requirements[key]) {
                            requirements[key].classList.remove('met');
                            const icon = requirements[key].querySelector('.requirement-icon');
                            if (icon) icon.textContent = '✗';
                        }
                    });
                    return {
                        length: false,
                        uppercase: false,
                        lowercase: false,
                        number: false,
                        special: false
                    };
                }

                const checks = {
                    length: patterns.length.test(password),
                    uppercase: patterns.uppercase.test(password),
                    lowercase: patterns.lowercase.test(password),
                    number: patterns.number.test(password),
                    special: patterns.special.test(password)
                };

                // Update requirement display
                Object.keys(checks).forEach(key => {
                    if (requirements[key]) {
                        if (checks[key]) {
                            requirements[key].classList.add('met');
                            const icon = requirements[key].querySelector('.requirement-icon');
                            if (icon) icon.textContent = '✓';
                        } else {
                            requirements[key].classList.remove('met');
                            const icon = requirements[key].querySelector('.requirement-icon');
                            if (icon) icon.textContent = '✗';
                        }
                    }
                });

                return checks;
            }

            function getPasswordStrength(password) {
                const checks = checkPasswordRequirements(password);
                const metCount = Object.values(checks).filter(Boolean).length;

                if (metCount <= 2) return 'weak';
                if (metCount <= 3) return 'medium';
                return 'strong';
            }

            function updateStrengthIndicator(password) {
                if (!strengthIndicator) return;

                const strength = getPasswordStrength(password);
                const strengthBarFill = document.getElementById('strengthBarFill');
                const strengthText = document.getElementById('strengthText');

                if (strengthBarFill) strengthBarFill.className = `strength-bar-fill ${strength}`;
                if (strengthText) {
                    strengthText.className = `strength-text ${strength}`;
                    strengthText.textContent = `Strength: ${strength.charAt(0).toUpperCase() + strength.slice(1)}`;
                }
            }

            function checkPasswordMatch() {
                if (!confirmPasswordInput || !passwordMatchMessage) return;

                // If confirm password is empty, hide message
                if (confirmPasswordInput.value === '') {
                    passwordMatchMessage.classList.remove('show', 'match', 'no-match');
                    return;
                }

                // Show the message
                passwordMatchMessage.classList.add('show');

                if (passwordInput && passwordInput.value === confirmPasswordInput.value) {
                    passwordMatchMessage.classList.remove('no-match');
                    passwordMatchMessage.classList.add('match');
                    passwordMatchMessage.textContent = 'Passwords match';
                } else {
                    passwordMatchMessage.classList.remove('match');
                    passwordMatchMessage.classList.add('no-match');
                    passwordMatchMessage.textContent = 'Passwords do not match';
                }
            }

            // Event listeners
            if (passwordInput && passwordRequirementsModal) {
                passwordInput.addEventListener('input', (e) => {
                    // Show modal only when user types (has content)
                    if (e.target.value.length > 0) {
                        passwordRequirementsModal.classList.add('show');
                        if (strengthIndicator) strengthIndicator.classList.add('show');
                    } else {
                        passwordRequirementsModal.classList.remove('show');
                        if (strengthIndicator) strengthIndicator.classList.remove('show');
                    }

                    // Always check requirements
                    checkPasswordRequirements(e.target.value);

                    if (e.target.value.length > 0 && strengthIndicator) {
                        updateStrengthIndicator(e.target.value);
                    }

                    // Check if passwords match (if confirm password has been filled)
                    if (confirmPasswordInput && confirmPasswordInput.value.length > 0) {
                        checkPasswordMatch();
                    }
                });

                passwordInput.addEventListener('blur', () => {
                    // Hide modal when leaving the field
                    setTimeout(() => {
                        if (document.activeElement !== confirmPasswordInput) {
                            passwordRequirementsModal.classList.remove('show');
                        }
                    }, 150);
                });
            }

            if (confirmPasswordInput && passwordRequirementsModal) {
                confirmPasswordInput.addEventListener('input', (e) => {
                    checkPasswordMatch();
                });

                confirmPasswordInput.addEventListener('focus', () => {
                    // Show modal if password field has content
                    if (passwordInput && passwordInput.value.length > 0) {
                        passwordRequirementsModal.classList.add('show');
                    }
                });

                confirmPasswordInput.addEventListener('blur', () => {
                    // Hide modal when leaving confirm password field
                    setTimeout(() => {
                        if (document.activeElement !== passwordInput) {
                            passwordRequirementsModal.classList.remove('show');
                        }
                    }, 150);
                });
            }

            // Toggle password visibility - iOS compatible
            if (togglePassword && passwordInput) {
                togglePassword.addEventListener('click', () => {
                    const isPassword = passwordInput.type === 'password';
                    const currentValue = passwordInput.value;
                    const cursorPosition = passwordInput.selectionStart;

                    passwordInput.type = isPassword ? 'text' : 'password';
                    passwordInput.value = currentValue;
                    passwordInput.setSelectionRange(cursorPosition, cursorPosition);

                    togglePassword.classList.toggle('fa-eye');
                    togglePassword.classList.toggle('fa-eye-slash');
                    passwordInput.focus();
                });
            }

            if (toggleConfirmPassword && confirmPasswordInput) {
                toggleConfirmPassword.addEventListener('click', () => {
                    const isPassword = confirmPasswordInput.type === 'password';
                    const currentValue = confirmPasswordInput.value;
                    const cursorPosition = confirmPasswordInput.selectionStart;

                    confirmPasswordInput.type = isPassword ? 'text' : 'password';
                    confirmPasswordInput.value = currentValue;
                    confirmPasswordInput.setSelectionRange(cursorPosition, cursorPosition);

                    toggleConfirmPassword.classList.toggle('fa-eye');
                    toggleConfirmPassword.classList.toggle('fa-eye-slash');
                    confirmPasswordInput.focus();
                });
            }

            // Form validation before submit
            document.getElementById('resetForm')?.addEventListener('submit', function(e) {
                const password = passwordInput.value;
                const confirm = confirmPasswordInput.value;

                // Check all requirements
                const checks = checkPasswordRequirements(password);
                const allMet = Object.values(checks).every(Boolean);

                if (!allMet) {
                    e.preventDefault();
                    alert('Please ensure all password requirements are met');
                    return;
                }

                // Check if passwords match
                if (password !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match');
                    return;
                }
            });
        })();
    </script>
</body>
</html>
