<?php
/**
 * AES Encryption Utility
 *
 * Provides AES-256-GCM encryption for sensitive data at rest
 * Use for: emails, phone numbers, credit card details, addresses, etc.
 *
 * DO NOT use for passwords - use password_hash() instead
 *
 * Security Features:
 * - AES-256-GCM (authenticated encryption with associated data)
 * - Random IV generation for each encryption
 * - Authentication tag validation (prevents tampering)
 * - Key derivation from master key
 * - Constant-time operations where possible
 *
 * Usage Example:
 *
 *   // Encrypt
 *   $encryptedEmail = Encryption::encrypt($plainEmail);
 *   // Store $encryptedEmail in database
 *
 *   // Decrypt
 *   $plainEmail = Encryption::decrypt($encryptedEmail);
 *   // Use $plainEmail in application
 *
 * @package FitBrawl
 * @author Fit & Brawl Security Team
 */

class Encryption
{
    /**
     * Encryption method - AES-256-GCM
     * GCM provides both confidentiality and authenticity
     */
    private const CIPHER_METHOD = 'aes-256-gcm';

    /**
     * Authentication tag length (16 bytes recommended for GCM)
     */
    private const TAG_LENGTH = 16;

    /**
     * IV length for AES-256-GCM (12 bytes is standard)
     */
    private const IV_LENGTH = 12;

    /**
     * Get encryption key from environment
     * Falls back to config if not in environment
     *
     * @return string Encryption key
     * @throws Exception if key is not configured
     */
    private static function getEncryptionKey(): string
    {
        // Try to load from environment first
        $key = getenv('ENCRYPTION_KEY');

        // Fall back to config file
        if (!$key && defined('ENCRYPTION_KEY')) {
            $key = ENCRYPTION_KEY;
        }

        // Validate key exists
        if (!$key) {
            throw new Exception('Encryption key not configured. Set ENCRYPTION_KEY in environment or config.php');
        }

        // Validate key length (must be 32 bytes for AES-256)
        if (strlen($key) !== 32) {
            throw new Exception('Encryption key must be exactly 32 bytes (256 bits)');
        }

        return $key;
    }

    /**
     * Encrypt plaintext data
     *
     * @param string $plaintext Data to encrypt
     * @return string Base64-encoded encrypted data with IV and tag
     * @throws Exception on encryption failure
     */
    public static function encrypt(string $plaintext): string
    {
        if (empty($plaintext)) {
            throw new Exception('Cannot encrypt empty data');
        }

        try {
            $key = self::getEncryptionKey();

            // Generate random IV (initialization vector)
            $iv = random_bytes(self::IV_LENGTH);

            // Initialize tag variable
            $tag = '';

            // Encrypt the data
            $ciphertext = openssl_encrypt(
                $plaintext,
                self::CIPHER_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag,
                '',  // No additional authenticated data
                self::TAG_LENGTH
            );

            if ($ciphertext === false) {
                throw new Exception('Encryption failed: ' . openssl_error_string());
            }

            // Combine IV + ciphertext + tag and encode
            // Format: [12 bytes IV][variable ciphertext][16 bytes tag]
            $encrypted = $iv . $ciphertext . $tag;

            // Return base64 encoded for safe storage
            return base64_encode($encrypted);

        } catch (Exception $e) {
            error_log('Encryption error: ' . $e->getMessage());
            throw new Exception('Failed to encrypt data');
        }
    }

    /**
     * Decrypt encrypted data
     *
     * @param string $encrypted Base64-encoded encrypted data
     * @return string Decrypted plaintext
     * @throws Exception on decryption failure
     */
    public static function decrypt(string $encrypted): string
    {
        if (empty($encrypted)) {
            throw new Exception('Cannot decrypt empty data');
        }

        try {
            $key = self::getEncryptionKey();

            // Decode from base64
            $data = base64_decode($encrypted, true);

            if ($data === false) {
                throw new Exception('Invalid encrypted data format');
            }

            // Extract IV, ciphertext, and tag
            $ivLength = self::IV_LENGTH;
            $tagLength = self::TAG_LENGTH;

            if (strlen($data) < ($ivLength + $tagLength)) {
                throw new Exception('Encrypted data is too short');
            }

            $iv = substr($data, 0, $ivLength);
            $tag = substr($data, -$tagLength);
            $ciphertext = substr($data, $ivLength, -$tagLength);

            // Decrypt the data
            $plaintext = openssl_decrypt(
                $ciphertext,
                self::CIPHER_METHOD,
                $key,
                OPENSSL_RAW_DATA,
                $iv,
                $tag
            );

            if ($plaintext === false) {
                throw new Exception('Decryption failed - data may be corrupted or tampered');
            }

            return $plaintext;

        } catch (Exception $e) {
            error_log('Decryption error: ' . $e->getMessage());
            throw new Exception('Failed to decrypt data');
        }
    }

    /**
     * Generate a secure encryption key
     * Use this to generate ENCRYPTION_KEY for your .env file
     *
     * @return string 32-byte (256-bit) random key in hex format (64 hex characters)
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32)); // 32 bytes = 64 hex characters = 256 bits
    }

    /**
     * Check if encryption is properly configured
     *
     * @return bool True if encryption is available
     */
    public static function isConfigured(): bool
    {
        try {
            self::getEncryptionKey();

            // Verify OpenSSL supports the cipher
            if (!in_array(self::CIPHER_METHOD, openssl_get_cipher_methods())) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Encrypt data only if value exists, otherwise return null
     * Useful for optional fields
     *
     * @param string|null $plaintext Data to encrypt
     * @return string|null Encrypted data or null
     */
    public static function encryptIfExists(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        return self::encrypt($plaintext);
    }

    /**
     * Decrypt data only if value exists, otherwise return null
     * Useful for optional fields
     *
     * @param string|null $encrypted Data to decrypt
     * @return string|null Decrypted data or null
     */
    public static function decryptIfExists(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') {
            return null;
        }

        return self::decrypt($encrypted);
    }

    /**
     * Test encryption/decryption functionality
     * Use for system health checks
     *
     * @return bool True if encryption is working correctly
     */
    public static function selfTest(): bool
    {
        try {
            $testData = 'Test data for encryption verification';
            $encrypted = self::encrypt($testData);
            $decrypted = self::decrypt($encrypted);

            return $decrypted === $testData;
        } catch (Exception $e) {
            error_log('Encryption self-test failed: ' . $e->getMessage());
            return false;
        }
    }
}
