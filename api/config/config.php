<?php
/**
 * Application Configuration
 */

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '"\'');
        }
    }
}

// Environment configuration
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN));

// Database configuration (loaded from environment or defaults)
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'medium_clone');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// JWT configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production');
define('JWT_EXPIRY', 3600 * 24); // 24 hours

// File upload configuration
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? '../uploads/');
define('MAX_UPLOAD_SIZE', $_ENV['MAX_UPLOAD_SIZE'] ?? 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Email configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');

// Application URLs
define('API_BASE_URL', $_ENV['API_BASE_URL'] ?? 'http://localhost/medium-clone/api');
define('FRONTEND_URL', $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000');

// Security settings
define('BCRYPT_COST', 12);
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour

// Pagination defaults
define('DEFAULT_PAGE_SIZE', 10);
define('MAX_PAGE_SIZE', 100);

// Rate limiting
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour

// Error reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('UTC');