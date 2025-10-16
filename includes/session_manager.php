<?php
class SessionManager {
    const IDLE_TIMEOUT = 180;    // 3 minutes for testing
    const WARNING_TIME = 60;     // 1 minute warning
    const ABSOLUTE_TIMEOUT = 300; // 5 minutes for testing

    public static function initialize() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function startSession($email) {
        $_SESSION['email'] = $email;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        $_SESSION['session_expires'] = time() + self::IDLE_TIMEOUT;
    }

    public static function updateActivity() {
        if (self::isLoggedIn()) {
            $_SESSION['last_activity'] = time();
            $_SESSION['session_expires'] = time() + self::IDLE_TIMEOUT;
            return self::getRemainingTime();
        }
        return 0;
    }

    public static function getRemainingTime() {
        if (!isset($_SESSION['session_expires'])) {
            return 0;
        }
        
        $remaining = $_SESSION['session_expires'] - time();
        return max(0, $remaining);
    }

    public static function isLoggedIn() {
        return isset($_SESSION['email']) && !empty($_SESSION['email']);
    }
}