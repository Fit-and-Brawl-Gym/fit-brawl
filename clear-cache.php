<?php
// Clear OpCache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OpCache cleared successfully\n";
} else {
    echo "⚠️ OpCache not enabled\n";
}

// Clear APCu cache if enabled
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "✅ APCu cache cleared\n";
}

echo "\nNow try refreshing the reservations page with Ctrl+F5\n";
