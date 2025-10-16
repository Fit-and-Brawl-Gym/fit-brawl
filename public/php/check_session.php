<?php
require_once '../../includes/session_manager.php';

// Prevent any HTML error output
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    SessionManager::initialize();

    if (!SessionManager::isLoggedIn()) {
        echo json_encode([
            'remainingTime' => 0,
            'status' => 'not_logged_in'
        ]);
        exit;
    }

    $remainingTime = SessionManager::getRemainingTime();
    echo json_encode([
        'remainingTime' => (int)$remainingTime,
        'serverTime' => time(),
        'shouldWarn' => $remainingTime <= SessionManager::WARNING_TIME,
        'status' => 'active'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error occurred',
        'status' => 'error'
    ]);
}