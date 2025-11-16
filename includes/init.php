<?php
/**
 * init.php
 * Global initialization file for Fit & Brawl Gym Website
 * --------------------------------------------------------
 * This file handles:
 *  - Configuration constants (PUBLIC_PATH, BASE_PATH)
 *  - Environment loading
 *  - Database connection
 *  - Session handling
 *  - Logging helper include
 * --------------------------------------------------------
 */

// Use __DIR__ to make includes absolute (never breaks)
require_once __DIR__ . '/config.php';           // Load configuration constants FIRST
require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/log_action.php';
require_once __DIR__ . '/security_headers.php';

// Safe session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optional: Security guard to prevent direct access if needed
// if (!isset($_SESSION['role'])) {
//     header("Location: ../login.php");
//     exit();
// }
?>
