<?php
require_once __DIR__ . '/../../includes/config.php';

echo "<h2>Timezone Test</h2>";
echo "<p><strong>Current timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Current time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>Unix timestamp:</strong> " . time() . "</p>";
echo "<p><strong>PHP timezone from ini:</strong> " . ini_get('date.timezone') . "</p>";
