<?php
/**
 * Encryption Test Script
 *
 * Tests encryption/decryption functionality
 * Run after setting up encryption key
 *
 * Usage: php test_encryption.php
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/encryption.php';

echo "\n";
echo "========================================\n";
echo "  Encryption System Test\n";
echo "========================================\n\n";

// Test 1: Configuration check
echo "Test 1: Configuration Check\n";
echo "───────────────────────────\n";

if (Encryption::isConfigured()) {
    echo "✅ Encryption is properly configured\n\n";
} else {
    echo "❌ Encryption is NOT configured\n";
    echo "   Run: php generate_encryption_key.php\n\n";
    exit(1);
}

// Test 2: Self-test
echo "Test 2: Self-Test (Encrypt/Decrypt)\n";
echo "────────────────────────────────────\n";

if (Encryption::selfTest()) {
    echo "✅ Encryption/Decryption working correctly\n\n";
} else {
    echo "❌ Encryption test failed\n";
    echo "   Check error logs for details\n\n";
    exit(1);
}

// Test 3: Various data types
echo "Test 3: Data Type Testing\n";
echo "─────────────────────────\n";

$testCases = [
    'Email' => 'user@example.com',
    'Phone' => '+1-234-567-8900',
    'Address' => '123 Main St, City, State 12345',
    'Special chars' => 'Test!@#$%^&*()',
    'Unicode' => '你好世界 🎉',
    'Long text' => str_repeat('A', 1000),
    'Empty after null' => '',
];

$passed = 0;
$failed = 0;

foreach ($testCases as $name => $testData) {
    if ($testData === '') {
        // Test encryptIfExists for empty strings
        $encrypted = Encryption::encryptIfExists($testData);
        if ($encrypted === null) {
            echo "✅ $name: Empty string handled correctly\n";
            $passed++;
        } else {
            echo "❌ $name: Empty string should return null\n";
            $failed++;
        }
        continue;
    }

    try {
        $encrypted = Encryption::encrypt($testData);
        $decrypted = Encryption::decrypt($encrypted);

        if ($testData === $decrypted) {
            echo "✅ $name: PASS\n";
            $passed++;
        } else {
            echo "❌ $name: FAIL (data mismatch)\n";
            $failed++;
        }
    } catch (Exception $e) {
        echo "❌ $name: ERROR - " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n";

// Test 4: Performance test
echo "Test 4: Performance Test\n";
echo "────────────────────────\n";

$iterations = 100;
$testEmail = 'performance@test.com';

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $encrypted = Encryption::encrypt($testEmail);
}
$encryptTime = microtime(true) - $start;

$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $decrypted = Encryption::decrypt($encrypted);
}
$decryptTime = microtime(true) - $start;

$avgEncrypt = ($encryptTime / $iterations) * 1000; // ms
$avgDecrypt = ($decryptTime / $iterations) * 1000; // ms

echo "Iterations: $iterations\n";
echo "Average encrypt time: " . number_format($avgEncrypt, 3) . " ms\n";
echo "Average decrypt time: " . number_format($avgDecrypt, 3) . " ms\n";

if ($avgEncrypt < 1.0 && $avgDecrypt < 1.0) {
    echo "✅ Performance is acceptable\n";
} else {
    echo "⚠️  Performance may need optimization\n";
}

echo "\n";

// Summary
echo "========================================\n";
echo "  Test Summary\n";
echo "========================================\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed === 0) {
    echo "\n✅ All tests passed! Encryption is ready to use.\n\n";
    exit(0);
} else {
    echo "\n❌ Some tests failed. Please check configuration.\n\n";
    exit(1);
}
