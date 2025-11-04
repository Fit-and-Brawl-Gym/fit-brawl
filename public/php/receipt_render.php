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

// Check if we're running on Google Cloud and should use Cloud Run
$isGCP = isset($_SERVER['GAE_ENV']) || isset($_SERVER['GAE_VERSION']);
$cloudRunUrl = getenv('RECEIPT_RENDERER_URL');

// If on GCP and Cloud Run URL is configured, use Cloud Run service
if ($isGCP && $cloudRunUrl && filter_var($cloudRunUrl, FILTER_VALIDATE_URL)) {
    // Call Cloud Run service via HTTP
    $ch = curl_init($cloudRunUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'url' => $receiptUrl,
        'format' => $format
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/pdf, image/png'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200 && $result) {
        $filename = 'receipt_' . preg_replace('/[^A-Za-z0-9_-]/', '', $id) . ($format === 'pdf' ? '.pdf' : '.png');
        header('Content-Type: ' . ($format === 'pdf' ? 'application/pdf' : 'image/png'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($result));
        echo $result;
        exit;
    } else {
        http_response_code(500);
        header('Content-Type: text/plain');
        echo 'Cloud Run rendering failed: ' . $error;
        exit;
    }
}

// Otherwise, use local Node.js renderer
// Locate Node and the renderer script
$projectRoot = realpath(__DIR__ . '/../../');
$wrapper = $projectRoot . DIRECTORY_SEPARATOR . 'server-renderer' . DIRECTORY_SEPARATOR . 'render-wrapper.js';
$node = 'node'; // assumes node in PATH

if (!file_exists($wrapper)) {
    // Graceful fallback: redirect to browser print instructions
    header('Location: receipt_fallback.php?type=' . urlencode($type) . '&id=' . urlencode($id));
    exit;
}

// Create temp output file
$ext = $format === 'pdf' ? '.pdf' : '.png';
$tmpDir = sys_get_temp_dir();
$tmpFile = tempnam($tmpDir, 'fb-render-');
if ($tmpFile === false) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Failed to create temp file';
    exit;
}
$outFile = $tmpFile . $ext;

// Build command
$cmd = escapeshellcmd($node) . ' ' . escapeshellarg($wrapper) .
    ' --url=' . escapeshellarg($receiptUrl) .
    ' --format=' . escapeshellarg($format) .
    ' --output=' . escapeshellarg($outFile);

// Execute
$descriptorSpec = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = proc_open($cmd, $descriptorSpec, $pipes, $projectRoot);
if (!is_resource($proc)) {
    unlink($tmpFile);
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Failed to start renderer process';
    exit;
}

$output = stream_get_contents($pipes[1]);
$error = stream_get_contents($pipes[2]);
foreach ($pipes as $p) { fclose($p); }
$status = proc_close($proc);

if ($status !== 0 || !file_exists($outFile)) {
    if (file_exists($outFile)) { unlink($outFile); }
    @unlink($tmpFile);
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Render failed. ' . ($error ?: $output);
    exit;
}

// Stream file to client
$filename = 'receipt_' . preg_replace('/[^A-Za-z0-9_-]/', '', $id) . $ext;
header('Content-Type: ' . ($format === 'pdf' ? 'application/pdf' : 'image/png'));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($outFile));
readfile($outFile);

// Cleanup
unlink($outFile);
@unlink($tmpFile);
