<?php
/**
 * Centralized Input Validation Helper
 * Provides consistent validation and sanitization for all user inputs
 */
class InputValidator {
    /**
     * Sanitize string input
     *
     * @param string $input The input to sanitize
     * @param int $maxLength Maximum allowed length (0 = no limit)
     * @return string Sanitized string
     */
    public static function sanitizeString($input, $maxLength = 0) {
        if (!is_string($input)) {
            return '';
        }

        // Trim whitespace
        $input = trim($input);

        // Remove null bytes
        $input = str_replace("\0", '', $input);

        // Apply length limit
        if ($maxLength > 0 && mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength);
        }

        return $input;
    }

    /**
     * Validate and sanitize email address
     *
     * @param string $email Email to validate
     * @return string|false Validated email or false if invalid
     */
    public static function validateEmail($email) {
        $email = self::sanitizeString($email, 255);

        if (empty($email)) {
            return false;
        }

        $email = filter_var($email, FILTER_SANITIZE_EMAIL);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return $email;
    }

    /**
     * Validate and sanitize integer
     *
     * @param mixed $input Input to validate
     * @param int|null $min Minimum value (null = no minimum)
     * @param int|null $max Maximum value (null = no maximum)
     * @return int|false Validated integer or false if invalid
     */
    public static function validateInt($input, $min = null, $max = null) {
        // Convert to integer
        $int = filter_var($input, FILTER_VALIDATE_INT);

        if ($int === false) {
            return false;
        }

        // Check bounds
        if ($min !== null && $int < $min) {
            return false;
        }

        if ($max !== null && $int > $max) {
            return false;
        }

        return $int;
    }

    /**
     * Validate and sanitize float
     *
     * @param mixed $input Input to validate
     * @param float|null $min Minimum value
     * @param float|null $max Maximum value
     * @return float|false Validated float or false if invalid
     */
    public static function validateFloat($input, $min = null, $max = null) {
        $float = filter_var($input, FILTER_VALIDATE_FLOAT);

        if ($float === false) {
            return false;
        }

        if ($min !== null && $float < $min) {
            return false;
        }

        if ($max !== null && $float > $max) {
            return false;
        }

        return $float;
    }

    /**
     * Validate against whitelist
     *
     * @param string $input Input to validate
     * @param array $allowedValues Whitelist of allowed values
     * @param bool $caseSensitive Whether comparison is case-sensitive
     * @return string|false Validated value or false if not in whitelist
     */
    public static function validateWhitelist($input, array $allowedValues, $caseSensitive = true) {
        $input = self::sanitizeString($input);

        if (!$caseSensitive) {
            $input = strtolower($input);
            $allowedValues = array_map('strtolower', $allowedValues);
        }

        if (!in_array($input, $allowedValues, true)) {
            return false;
        }

        // Return original case from whitelist
        if (!$caseSensitive) {
            $key = array_search($input, $allowedValues);
            return $allowedValues[$key];
        }

        return $input;
    }

    /**
     * Validate URL
     *
     * @param string $url URL to validate
     * @param bool $requireScheme Whether scheme (http/https) is required
     * @return string|false Validated URL or false if invalid
     */
    public static function validateUrl($url, $requireScheme = false) {
        $url = self::sanitizeString($url);

        if (empty($url)) {
            return false;
        }

        // Sanitize URL
        $url = filter_var($url, FILTER_SANITIZE_URL);

        // Validate URL
        $flags = $requireScheme ? FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED : 0;
        if (!filter_var($url, FILTER_VALIDATE_URL, $flags)) {
            return false;
        }

        return $url;
    }

    /**
     * Validate phone number (basic validation)
     *
     * @param string $phone Phone number to validate
     * @return string|false Validated phone or false if invalid
     */
    public static function validatePhone($phone) {
        $phone = self::sanitizeString($phone);

        // Remove common formatting characters
        $phone = preg_replace('/[\s\-\(\)\+]/', '', $phone);

        // Validate: 7-15 digits
        if (!preg_match('/^\d{7,15}$/', $phone)) {
            return false;
        }

        return $phone;
    }

    /**
     * Validate date string
     *
     * @param string $date Date string to validate
     * @param string $format Expected format (default: Y-m-d)
     * @return DateTime|false Validated DateTime object or false if invalid
     */
    public static function validateDate($date, $format = 'Y-m-d') {
        $date = self::sanitizeString($date);

        if (empty($date)) {
            return false;
        }

        $dt = DateTime::createFromFormat($format, $date);

        if (!$dt || $dt->format($format) !== $date) {
            return false;
        }

        return $dt;
    }

    /**
     * Validate date-time string
     *
     * @param string $datetime DateTime string to validate
     * @param string $format Expected format (default: Y-m-d H:i:s)
     * @return DateTime|false Validated DateTime object or false if invalid
     */
    public static function validateDateTime($datetime, $format = 'Y-m-d H:i:s') {
        return self::validateDate($datetime, $format);
    }

    /**
     * Sanitize for HTML output (XSS prevention)
     *
     * @param string $input Input to sanitize
     * @return string HTML-safe string
     */
    public static function sanitizeHtml($input) {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize for URL usage
     *
     * @param string $input Input to sanitize
     * @return string URL-safe string
     */
    public static function sanitizeUrl($input) {
        return urlencode($input);
    }

    /**
     * Sanitize for JavaScript string usage
     *
     * @param string $input Input to sanitize
     * @return string JavaScript-safe string
     */
    public static function sanitizeJs($input) {
        // Use json_encode for safe JavaScript string escaping
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Validate password strength
     *
     * @param string $password Password to validate
     * @param int $minLength Minimum length (default: 8)
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePassword($password, $minLength = 8) {
        $errors = [];

        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Get sanitized value from $_POST with default
     *
     * @param string $key POST key
     * @param mixed $default Default value if key doesn't exist
     * @param int $maxLength Maximum length for strings (0 = no limit)
     * @return mixed Sanitized value
     */
    public static function post($key, $default = null, $maxLength = 0) {
        if (!isset($_POST[$key])) {
            return $default;
        }

        if (is_string($_POST[$key])) {
            return self::sanitizeString($_POST[$key], $maxLength);
        }

        return $_POST[$key];
    }

    /**
     * Get sanitized value from $_GET with default
     *
     * @param string $key GET key
     * @param mixed $default Default value if key doesn't exist
     * @param int $maxLength Maximum length for strings (0 = no limit)
     * @return mixed Sanitized value
     */
    public static function get($key, $default = null, $maxLength = 0) {
        if (!isset($_GET[$key])) {
            return $default;
        }

        if (is_string($_GET[$key])) {
            return self::sanitizeString($_GET[$key], $maxLength);
        }

        return $_GET[$key];
    }

    /**
     * Validate and sanitize array of inputs
     *
     * @param array $data Input data array
     * @param array $rules Validation rules ['field' => ['type' => 'string|email|int|float|whitelist', ...]]
     * @return array ['valid' => bool, 'data' => array, 'errors' => array]
     */
    public static function validateArray(array $data, array $rules) {
        $validated = [];
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $type = $rule['type'] ?? 'string';
            $required = $rule['required'] ?? false;

            // Check required
            if ($required && (is_null($value) || $value === '')) {
                $errors[$field] = "Field '{$field}' is required";
                continue;
            }

            // Skip validation if not required and empty
            if (!$required && (is_null($value) || $value === '')) {
                $validated[$field] = $rule['default'] ?? null;
                continue;
            }

            // Validate based on type
            $validatedValue = null;

            switch ($type) {
                case 'string':
                    $maxLength = $rule['max_length'] ?? 0;
                    $validatedValue = self::sanitizeString($value, $maxLength);
                    break;

                case 'email':
                    $validatedValue = self::validateEmail($value);
                    if ($validatedValue === false) {
                        $errors[$field] = "Invalid email format";
                    }
                    break;

                case 'int':
                    $min = $rule['min'] ?? null;
                    $max = $rule['max'] ?? null;
                    $validatedValue = self::validateInt($value, $min, $max);
                    if ($validatedValue === false) {
                        $errors[$field] = "Invalid integer value";
                    }
                    break;

                case 'float':
                    $min = $rule['min'] ?? null;
                    $max = $rule['max'] ?? null;
                    $validatedValue = self::validateFloat($value, $min, $max);
                    if ($validatedValue === false) {
                        $errors[$field] = "Invalid number value";
                    }
                    break;

                case 'whitelist':
                    $allowed = $rule['allowed'] ?? [];
                    $caseSensitive = $rule['case_sensitive'] ?? true;
                    $validatedValue = self::validateWhitelist($value, $allowed, $caseSensitive);
                    if ($validatedValue === false) {
                        $errors[$field] = "Invalid value. Must be one of: " . implode(', ', $allowed);
                    }
                    break;

                case 'date':
                    $format = $rule['format'] ?? 'Y-m-d';
                    $validatedValue = self::validateDate($value, $format);
                    if ($validatedValue === false) {
                        $errors[$field] = "Invalid date format";
                    }
                    break;

                default:
                    $validatedValue = self::sanitizeString($value);
            }

            if ($validatedValue !== false && $validatedValue !== null) {
                $validated[$field] = $validatedValue;
            }
        }

        return [
            'valid' => empty($errors),
            'data' => $validated,
            'errors' => $errors
        ];
    }
}
