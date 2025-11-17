<?php
/**
 * Centralized password policy helper to enforce consistent complexity rules.
 */
class PasswordPolicy
{
    public const MIN_LENGTH = 12;

    /**
     * Validate the supplied password.
     *
     * @param string $password
     * @return string[] list of error messages
     */
    public static function validate(string $password): array
    {
        $errors = [];

        if (mb_strlen($password) < self::MIN_LENGTH) {
            $errors[] = "Password must be at least " . self::MIN_LENGTH . " characters long";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }

        if (!preg_match('/[!@#$%^&*?_+=\-]/', $password)) {
            $errors[] = "Password must contain at least one special character (!@#$%^&*?_+=-)";
        }

        if (preg_match('/\s/', $password)) {
            $errors[] = "Password cannot contain spaces";
        }

        return $errors;
    }

    /**
     * Convenience helper that returns true when password meets policy.
     */
    public static function meetsRequirements(string $password): bool
    {
        return empty(self::validate($password));
    }
}
