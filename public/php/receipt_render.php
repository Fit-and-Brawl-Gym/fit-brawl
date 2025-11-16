<?php
// Server-side rendering proxy for receipts using headless Chrome (Puppeteer)
// Usage: receipt_render.php?type=member|nonmember&id=...&format=pdf|png

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

// Prefer calling internal renderer service via HTTP if configured
$rendererUrl = getenv('RENDERER_URL');
if ($rendererUrl) {
    $payload = json_encode([
        'url' => $receiptUrl,
        'format' => $format,
        'selector' => '.receipt-wrapper',
        'timeout' => intval(getenv('RENDERER_TIMEOUT_MS') ?: 20000)
    ]);

    $ch = curl_init(rtrim($rendererUrl, '/') . '/render');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30
    ]);
    $resp = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno === 0 && $http >= 200 && $http < 300) {
        // Proxy binary from renderer
        $filename = 'receipt_' . preg_replace('/[^A-Za-z0-9_-]/', '', $id) . ($format === 'pdf' ? '.pdf' : '.png');
        header('Content-Type: ' . ($format === 'pdf' ? 'application/pdf' : 'image/png'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo $resp;
        exit;
    }
    // Fall back to local rendering below on failure
}

// Local rendering fallback using Node (works when Node is in the same container)
// SECURITY: Command injection prevention - all inputs are validated and escaped
$projectRoot = realpath(__DIR__ . '/../../');
$wrapper = $projectRoot . DIRECTORY_SEPARATOR . 'server-renderer' . DIRECTORY_SEPARATOR . 'render-wrapper.js';
$node = 'node'; // Hardcoded, not from user input

// Validate wrapper file exists and is within project root (path traversal prevention)
if (!file_exists($wrapper) || strpos(realpath($wrapper), $projectRoot) !== 0) {
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

// Build command with proper escaping to prevent command injection
// SECURITY: All user inputs ($receiptUrl, $format) are validated above and escaped here
// $node is hardcoded, $wrapper is validated file path, $outFile is from tempnam()
$cmd = escapeshellcmd($node) . ' ' . escapeshellarg($wrapper) .
    ' --url=' . escapeshellarg($receiptUrl) .
    ' --format=' . escapeshellarg($format) .
    ' --output=' . escapeshellarg($outFile);

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

unlink($outFile);
@unlink($tmpFile);
