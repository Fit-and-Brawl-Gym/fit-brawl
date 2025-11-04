<?php
/**
 * Script to fix all relative includes to use __DIR__ for deployment
 * Run this script once to update all files
 */

$filesToFix = [
    // Main pages
    'public/php/logout.php',
    'public/php/forgot-password.php',
    'public/php/change-password.php',
    'public/php/verification.php',
    'public/php/verify-email.php',
    'public/php/resend-otp.php',
    'public/php/membership-status.php',
    'public/php/feedback-form.php',
    'public/php/transaction_nonmember.php',
    'public/php/transaction_service.php',
    'public/php/auth.php',
    'public/php/check_session.php',
    'public/php/extend_session.php',

    // API endpoints
    'public/php/api/book_session.php',
    'public/php/api/cancel_booking.php',
    'public/php/api/check_username.php',
    'public/php/api/contact_api.php',
    'public/php/api/feedback_vote.php',
    'public/php/api/generate_nonmember_receipt.php',
    'public/php/api/get_available_dates.php',
    'public/php/api/get_reservations.php',
    'public/php/api/get_trainers.php',
    'public/php/api/get_user_bookings.php',
    'public/php/api/get_user_membership.php',
    'public/php/api/process_service_booking.php',
    'public/php/api/process_subscription.php',

    // Admin pages
    'public/php/admin/admin.php',
    'public/php/admin/activity-log.php',
    'public/php/admin/contacts.php',
    'public/php/admin/equipment.php',
    'public/php/admin/feedback.php',
    'public/php/admin/products.php',
    'public/php/admin/reservations.php',
    'public/php/admin/subscriptions.php',
    'public/php/admin/trainers.php',
    'public/php/admin/trainer_add.php',
    'public/php/admin/trainer_edit.php',
    'public/php/admin/trainer_view.php',
    'public/php/admin/users.php',

    // Trainer pages
    'public/php/trainer/index.php',
    'public/php/trainer/profile.php',
    'public/php/trainer/schedule.php',
    'public/php/trainer/feedback.php',
];

function fixIncludes($filePath) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        return false;
    }

    $content = file_get_contents($filePath);
    $originalContent = $content;

    // Replace relative includes with __DIR__ based paths
    $patterns = [
        // Single quote patterns
        "/require_once\s+['\"]\.\.\/\.\.\/includes\/([^'\"]+)['\"]/",
        "/require\s+['\"]\.\.\/\.\.\/includes\/([^'\"]+)['\"]/",
        "/include\s+['\"]\.\.\/\.\.\/includes\/([^'\"]+)['\"]/",
        "/include_once\s+['\"]\.\.\/\.\.\/includes\/([^'\"]+)['\"]/",

        // Double quote patterns
        '/require_once\s+["\']\.\.\/\.\.\/includes\/([^"\']+)["\']/',
        '/require\s+["\']\.\.\/\.\.\/includes\/([^"\']+)["\']/',
        '/include\s+["\']\.\.\/\.\.\/includes\/([^"\']+)["\']/',
        '/include_once\s+["\']\.\.\/\.\.\/includes\/([^"\']+)["\']/',
    ];

    $replacements = [
        "require_once __DIR__ . '/../../includes/$1'",
        "require __DIR__ . '/../../includes/$1'",
        "include __DIR__ . '/../../includes/$1'",
        "include_once __DIR__ . '/../../includes/$1'",
    ];

    foreach ($patterns as $pattern) {
        $content = preg_replace($pattern, "require_once __DIR__ . '/../../includes/$1'", $content);
    }

    // Also fix footer includes
    $content = preg_replace("/require_once\s+['\"]\.\.\/\.\.\/includes\/footer\.php['\"]/", "require_once __DIR__ . '/../../includes/footer.php'", $content);
    $content = preg_replace('/require_once\s+["\']\.\.\/\.\.\/includes\/footer\.php["\']/', "require_once __DIR__ . '/../../includes/footer.php'", $content);

    // Fix vendor includes
    $content = preg_replace("/require\s+['\"]\.\.\/\.\.\/vendor\/([^'\"]+)['\"]/", "require __DIR__ . '/../../vendor/$1'", $content);
    $content = preg_replace('/require\s+["\']\.\.\/\.\.\/vendor\/([^"\']+)["\']/', "require __DIR__ . '/../../vendor/$1'", $content);

    if ($content !== $originalContent) {
        file_put_contents($filePath, $content);
        echo "Fixed: $filePath\n";
        return true;
    }

    return false;
}

echo "Fixing includes in all files...\n\n";

$fixedCount = 0;
foreach ($filesToFix as $file) {
    if (fixIncludes($file)) {
        $fixedCount++;
    }
}

echo "\nFixed $fixedCount files.\n";
echo "Done!\n";

