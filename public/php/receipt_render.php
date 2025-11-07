<?php
// Server-side rendering proxy for receipts using headless Chrome (Puppeteer)
// Usage: receipt_render.php?type=member|nonmember&id=...&format=pdf|png

// Load environment variables
include_once __DIR__ . '/../../includes/env_loader.php';
loadEnv(__DIR__ . '/../../.env');

// Basic validation
$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : '';
$id   = isset($_GET['id']) ? trim($_GET['id']) : '';
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'pdf';
if (!in_array($type, ['member', 'nonmember'], true) || $id === '' || !in_array($format, ['pdf', 'png'], true)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Bad request';
    exit;
}

// Build absolute URL to the receipt page with ?render=1 to hide actions
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // e.g., /fit-brawl/public/php
$receiptPath = $type === 'member' ? 'receipt_service.php' : 'receipt_nonmember.php';
$receiptUrl = $scheme . '://' . $host . $basePath . '/' . $receiptPath . '?id=' . rawurlencode($id) . '&render=1';

// Graceful fallback: redirect to browser print instructions
// (Node.js renderer removed for local-only deployment)
header('Location: receipt_fallback.php?type=' . urlencode($type) . '&id=' . urlencode($id));
exit;
