<!DOCTYPE html>
<html>
<head>
    <title>Environment Configuration Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .config-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .config-item {
            padding: 10px;
            margin: 5px 0;
            background: #f9f9f9;
            border-left: 4px solid #4CAF50;
        }
        .config-item.warning {
            border-left-color: #ff9800;
        }
        .config-item.error {
            border-left-color: #f44336;
        }
        h1 {
            color: #333;
        }
        h2 {
            color: #666;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        code {
            background: #e8e8e8;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.ok {
            background: #4CAF50;
            color: white;
        }
        .status.dev {
            background: #2196F3;
            color: white;
        }
        .status.prod {
            background: #FF5722;
            color: white;
        }
    </style>
</head>
<body>
    <h1>🔧 Fit & Brawl Environment Configuration Test</h1>
    
    <?php
    // Load config
    require_once __DIR__ . '/../includes/config.php';
    
    $serverName = $_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? 'unknown';
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    ?>
    
    <div class="config-box">
        <h2>🌍 Environment Detection</h2>
        <div class="config-item">
            <strong>Environment:</strong> 
            <code><?= ENVIRONMENT ?></code>
            <span class="status <?= ENVIRONMENT === 'development' ? 'dev' : 'prod' ?>">
                <?= strtoupper(ENVIRONMENT) ?>
            </span>
        </div>
        <div class="config-item">
            <strong>Server Name:</strong> <code><?= htmlspecialchars($serverName) ?></code>
        </div>
        <div class="config-item">
            <strong>Document Root:</strong> <code><?= htmlspecialchars($documentRoot) ?></code>
        </div>
        <div class="config-item">
            <strong>Request URI:</strong> <code><?= htmlspecialchars($requestUri) ?></code>
        </div>
    </div>
    
    <div class="config-box">
        <h2>📁 Path Configuration</h2>
        <div class="config-item <?= BASE_PATH === '/' ? '' : 'warning' ?>">
            <strong>BASE_PATH:</strong> <code><?= htmlspecialchars(BASE_PATH) ?></code>
            <br><small>Expected: <code>/</code> (production) or <code>/fit-brawl/</code> (localhost)</small>
        </div>
        <div class="config-item">
            <strong>PUBLIC_PATH:</strong> <code><?= htmlspecialchars(PUBLIC_PATH) ?></code>
            <br><small>Expected: <code></code> (empty - production) or <code>/fit-brawl/public</code> (localhost)</small>
        </div>
        <div class="config-item">
            <strong>IMAGES_PATH:</strong> <code><?= htmlspecialchars(IMAGES_PATH) ?></code>
        </div>
        <div class="config-item">
            <strong>UPLOADS_PATH:</strong> <code><?= htmlspecialchars(UPLOADS_PATH) ?></code>
        </div>
    </div>
    
    <div class="config-box">
        <h2>🧪 CSS Path Tests</h2>
        <?php
        $cssTests = [
            'Admin Dashboard CSS' => PUBLIC_PATH . '/php/admin/css/admin.css',
            'Global CSS' => PUBLIC_PATH . '/css/global.css',
            'Trainer Header CSS' => PUBLIC_PATH . '/css/components/header.css',
            'Favicon (Admin)' => '/images/favicon-admin.png',
        ];
        
        foreach ($cssTests as $name => $path) {
            $fullPath = 'http://' . $serverName . $path;
            echo '<div class="config-item">';
            echo '<strong>' . htmlspecialchars($name) . ':</strong><br>';
            echo '<code>' . htmlspecialchars($path) . '</code><br>';
            echo '<small>Full URL: <a href="' . htmlspecialchars($fullPath) . '" target="_blank">' . htmlspecialchars($fullPath) . '</a></small>';
            echo '</div>';
        }
        ?>
    </div>
    
    <div class="config-box">
        <h2>✅ Expected Behavior</h2>
        
        <?php if (ENVIRONMENT === 'development'): ?>
            <div class="config-item">
                <strong>Development Mode (Localhost/XAMPP)</strong>
                <ul>
                    <li>BASE_PATH should be: <code>/fit-brawl/</code></li>
                    <li>PUBLIC_PATH should be: <code>/fit-brawl/public</code></li>
                    <li>Access site at: <code>http://localhost/fit-brawl/</code></li>
                    <li>CSS loads from: <code>http://localhost/fit-brawl/public/css/...</code></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="config-item">
                <strong>Production Mode (Live Server)</strong>
                <ul>
                    <li>BASE_PATH should be: <code>/</code></li>
                    <li>PUBLIC_PATH should be: <code></code> (empty)</li>
                    <li>Access site at: <code>http://<?= htmlspecialchars($serverName) ?>/</code></li>
                    <li>CSS loads from: <code>http://<?= htmlspecialchars($serverName) ?>/css/...</code></li>
                    <li>DocumentRoot is: <code>/public/</code> (in Docker)</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="config-box">
        <h2>🔍 Database Configuration</h2>
        <div class="config-item">
            <strong>DB_HOST:</strong> <code><?= htmlspecialchars(getenv('DB_HOST') ?: 'Not set') ?></code>
        </div>
        <div class="config-item">
            <strong>DB_NAME:</strong> <code><?= htmlspecialchars(getenv('DB_NAME') ?: 'Not set') ?></code>
        </div>
        <div class="config-item <?= getenv('DB_PASS') ? '' : 'warning' ?>">
            <strong>DB_PASS:</strong> <code><?= getenv('DB_PASS') ? '****** (set)' : 'Not set' ?></code>
        </div>
    </div>
    
    <div class="config-box">
        <h2>📝 Notes</h2>
        <div class="config-item">
            <ul>
                <li>Environment auto-detects based on SERVER_NAME (localhost = development)</li>
                <li>You can override by setting <code>APP_ENV=production</code> in <code>.env</code> file</li>
                <li>All CSS/JS paths should use <code>PUBLIC_PATH</code> constant</li>
                <li>Images and uploads use <code>IMAGES_PATH</code> and <code>UPLOADS_PATH</code></li>
            </ul>
        </div>
    </div>
    
</body>
</html>
