<?php
/**
 * Automated Log Rotation Script
 * Can be run via cron, Task Scheduler, or manually
 *
 * Usage: php rotate_logs.php
 */

// Configuration
define('LOG_DIR', __DIR__ . '/../logs');
define('MAX_LOG_SIZE', 10 * 1024 * 1024); // 10MB
define('KEEP_DAYS', 30);
define('MAX_ROTATIONS', 10);
define('COMPRESS_LOGS', false); // Set to true if gzip is available

// Ensure we're running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

echo "=========================================\n";
echo "Log Rotation Script\n";
echo "=========================================\n";
echo "Log Directory: " . LOG_DIR . "\n";
echo "Max Log Size: " . (MAX_LOG_SIZE / 1024 / 1024) . "MB\n";
echo "Keep Days: " . KEEP_DAYS . "\n";
echo "=========================================\n\n";

// Create logs directory if it doesn't exist
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0775, true);
}

/**
 * Rotate a log file
 */
function rotateLog($logFile) {
    if (!file_exists($logFile)) {
        echo "[SKIP] " . basename($logFile) . " (file not found)\n";
        return false;
    }

    $fileSize = filesize($logFile);
    $sizeMB = round($fileSize / 1024 / 1024, 2);
    $sizeKB = round($fileSize / 1024, 2);

    if ($fileSize < MAX_LOG_SIZE) {
        echo "[OK] " . basename($logFile) . " ({$sizeKB}KB - no rotation needed)\n";
        return false;
    }

    echo "[ROTATE] " . basename($logFile) . " ({$sizeMB}MB)\n";

    $logDir = dirname($logFile);
    $logBasename = basename($logFile);

    // Rotate existing numbered logs
    for ($i = MAX_ROTATIONS - 1; $i >= 1; $i--) {
        $oldFile = "$logDir/$logBasename.$i";
        $newFile = "$logDir/$logBasename." . ($i + 1);

        if (file_exists("$oldFile.gz")) {
            rename("$oldFile.gz", "$newFile.gz");
        } elseif (file_exists($oldFile)) {
            rename($oldFile, $newFile);
        }
    }

    // Delete oldest log if we've hit max rotations
    $oldestLog = "$logDir/$logBasename." . (MAX_ROTATIONS + 1);
    if (file_exists("$oldestLog.gz")) {
        unlink("$oldestLog.gz");
    } elseif (file_exists($oldestLog)) {
        unlink($oldestLog);
    }

    // Rotate current log to .1
    $rotatedFile = "$logDir/$logBasename.1";
    rename($logFile, $rotatedFile);

    // Create new empty log file
    touch($logFile);
    chmod($logFile, 0664);

    // Compress rotated log if enabled
    if (COMPRESS_LOGS && function_exists('gzopen')) {
        $gzFile = "$rotatedFile.gz";
        $fp = fopen($rotatedFile, 'rb');
        $gz = gzopen($gzFile, 'wb9');

        while (!feof($fp)) {
            gzwrite($gz, fread($fp, 1024 * 512));
        }

        fclose($fp);
        gzclose($gz);
        unlink($rotatedFile);

        echo "[COMPRESSED] " . basename($gzFile) . "\n";
    }

    echo "[SUCCESS] " . basename($logFile) . " rotated\n";
    return true;
}

/**
 * Clean old log files
 */
function cleanOldLogs() {
    echo "\nCleaning logs older than " . KEEP_DAYS . " days...\n";

    $deletedCount = 0;
    $cutoffTime = time() - (KEEP_DAYS * 24 * 60 * 60);

    $files = glob(LOG_DIR . '/*.log.*');
    if ($files === false) {
        $files = [];
    }

    foreach ($files as $file) {
        if (filemtime($file) < $cutoffTime) {
            unlink($file);
            echo "[DELETED] " . basename($file) . "\n";
            $deletedCount++;
        }
    }

    if ($deletedCount === 0) {
        echo "[OK] No old logs to delete\n";
    } else {
        echo "[SUCCESS] Deleted $deletedCount old log files\n";
    }
}

/**
 * Get directory size
 */
function getDirectorySize($dir) {
    $size = 0;
    $files = glob($dir . '/*');

    foreach ($files as $file) {
        if (is_file($file)) {
            $size += filesize($file);
        }
    }

    if ($size < 1024) {
        return $size . 'B';
    } elseif ($size < 1024 * 1024) {
        return round($size / 1024, 2) . 'KB';
    } else {
        return round($size / 1024 / 1024, 2) . 'MB';
    }
}

// Main execution
try {
    echo "Rotating log files...\n\n";

    // Rotate each log file
    $logFiles = [
        LOG_DIR . '/php_errors.log',
        LOG_DIR . '/application.log',
        LOG_DIR . '/security.log',
        LOG_DIR . '/activity.log',
        LOG_DIR . '/database.log',
        LOG_DIR . '/email.log'
    ];

    foreach ($logFiles as $logFile) {
        rotateLog($logFile);
    }

    // Clean old logs
    cleanOldLogs();

    echo "\n=========================================\n";
    echo "Log rotation completed successfully!\n";
    echo "=========================================\n";
    echo "Total log directory size: " . getDirectorySize(LOG_DIR) . "\n";

    exit(0);

} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
