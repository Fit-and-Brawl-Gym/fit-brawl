<?php
class SessionManager {
    const IDLE_TIMEOUT = 900;    // 15 minutes
    const WARNING_TIME = 120;     // 2 minutes
    const ABSOLUTE_TIMEOUT = 36000; // 10 hours

    public static function initialize() {
        if (session_status() === PHP_SESSION_NONE) {
            // Configure session settings before starting - critical for Firefox
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');

            // Set session cookie parameters for better security and Firefox compatibility
            session_set_cookie_params([
                'lifetime' => 0, // Session cookie (expires when browser closes)
                'path' => '/',
                'domain' => '', // Use default domain
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Auto-detect HTTPS
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();

            // Regenerate session ID if it's a new session (security best practice)
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
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
        // Store message before destroying session
        if (!empty($message)) {
            $messageToStore = $message;
        }

        // Get session name before destroying
        $sessionName = session_name();
        $sessionParams = session_get_cookie_params();

        // Unset all session variables
        $_SESSION = array();

        // Destroy the session file on server
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        // Delete the session cookie - CRITICAL for Firefox
        // Must set all parameters to match the original cookie
        if (isset($_COOKIE[$sessionName])) {
            setcookie(
                $sessionName,
                '',
                [
                    'expires' => time() - 3600,
                    'path' => $sessionParams['path'],
                    'domain' => $sessionParams['domain'],
                    'secure' => $sessionParams['secure'],
                    'httponly' => $sessionParams['httponly'],
                    'samesite' => $sessionParams['samesite'] ?? 'Lax'
                ]
            );
            // Also try the simple method for older PHP versions as fallback
            setcookie($sessionName, '', time() - 3600, '/');
        }

        // Clear any other application cookies
        $cookieSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $cookiesToClear = ['remember_password', 'email', 'session_message'];
        foreach ($cookiesToClear as $cookieName) {
            if (isset($_COOKIE[$cookieName])) {
                setcookie($cookieName, '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'secure' => $cookieSecure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
                // Fallback for older PHP versions
                setcookie($cookieName, '', time() - 3600, '/');
            }
        }

        // Set message cookie after clearing session
        if (!empty($messageToStore)) {
            setcookie('session_message', $messageToStore, [
                'expires' => time() + 30,
                'path' => '/',
                'secure' => $cookieSecure,
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        }

        // Prevent caching of the logout action - CRITICAL for Firefox
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past

        // Redirect with cache-busting timestamp
        $timestamp = time();
        header('Location: index.php?_t=' . $timestamp);
        exit;
    }
}
