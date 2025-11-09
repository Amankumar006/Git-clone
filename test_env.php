<?php
require_once __DIR__ . '/api/config/config.php';

echo "Environment Test:\n";
echo "APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'NOT DEFINED') . "\n";
echo "APP_DEBUG: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'true' : 'false') : 'NOT DEFINED') . "\n";
echo "_ENV['APP_ENV']: " . ($_ENV['APP_ENV'] ?? 'NOT SET') . "\n";
?>