<?php
require_once '../../includes/session_manager.php';

header('Content-Type: application/json');

try {
    SessionManager::initialize();
    
    if (SessionManager::isLoggedIn()) {
        $newRemainingTime = SessionManager::updateActivity();
        
        echo json_encode([
            'success' => true,
            'remainingTime' => $newRemainingTime,
            'expiresAt' => $_SESSION['session_expires'],
            'debug' => [
                'currentTime' => time(),
                'lastActivity' => $_SESSION['last_activity'],
                'remainingTime' => $newRemainingTime
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}