<?php
class SessionManager {
    const IDLE_TIMEOUT = 900;    // 15 minutes 
    const WARNING_TIME = 120;     // 2 minutes 
    const ABSOLUTE_TIMEOUT = 36000; // 10 hours

    public static function initialize() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (self::isLoggedIn()) {
            // Check for absolute timeout first
            if (self::isAbsoluteTimeoutReached()) {
                self::logout('Maximum session duration reached');
                return;
            }
            // Then check for idle timeout
            if (self::isIdleTimeoutReached()) {
                self::logout('Session expired due to inactivity');
                return;
            }
        }
    }

    public static function startSession($email) {
        $_SESSION['email'] = $email;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['session_expires'] = time() + self::IDLE_TIMEOUT;
    }

    public static function getRemainingTime() {
        if (!self::isLoggedIn()) return 0;

        // Get both idle and absolute remaining times
        $idleRemaining = isset($_SESSION['session_expires']) ? 
            $_SESSION['session_expires'] - time() : 0;
        
        $absoluteRemaining = isset($_SESSION['login_time']) ? 
            ($_SESSION['login_time'] + self::ABSOLUTE_TIMEOUT) - time() : 0;

        // Return the smaller of the two (whichever will expire first)
        return max(0, min($idleRemaining, $absoluteRemaining));
    }

    private static function isAbsoluteTimeoutReached() {
        return !isset($_SESSION['login_time']) || 
            (time() - $_SESSION['login_time']) >= self::ABSOLUTE_TIMEOUT;
    }

    private static function isIdleTimeoutReached() {
        return !isset($_SESSION['last_activity']) || 
            (time() - $_SESSION['last_activity']) >= self::IDLE_TIMEOUT;
    }

    public static function updateActivity() {
        if (self::isLoggedIn() && !self::isAbsoluteTimeoutReached()) {
            $_SESSION['last_activity'] = time();
            $_SESSION['session_expires'] = time() + self::IDLE_TIMEOUT;
            return self::getRemainingTime();
        }
        return 0;
    }

    public static function isLoggedIn() {
        return isset($_SESSION['email']) && !empty($_SESSION['email']);
    }

    public static function logout($message = '') {
        if (!empty($message)) {
            setcookie('session_message', $message, time() + 30, '/');
        }
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    }
}