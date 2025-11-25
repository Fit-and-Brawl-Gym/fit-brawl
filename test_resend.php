<?php
/**
 * Quick Resend API Test
 * Run: php test_resend.php your_api_key your_email@gmail.com
 */

if ($argc < 3) {
    die("Usage: php test_resend.php YOUR_RESEND_API_KEY YOUR_EMAIL@gmail.com\n");
}

$apiKey = $argv[1];
$toEmail = $argv[2];

echo "Testing Resend API...\n";
echo "To: $toEmail\n\n";

$data = [
    'from' => 'Fit & Brawl Gym <onboarding@resend.dev>',
    'to' => [$toEmail],
    'subject' => 'Test Email - Fit & Brawl',
    'html' => '<h2>Test Email</h2><p>If you received this, Resend is working!</p>',
];

$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false, // Skip SSL verification for local testing
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

if ($error) {
    echo "Curl Error: $error\n";
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo "\n✅ SUCCESS! Check your email inbox.\n";
} else {
    echo "\n❌ FAILED! Check the response above.\n";
}
