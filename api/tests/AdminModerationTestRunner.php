<?php

/**
 * Admin and Moderation Test Runner
 * 
 * This script runs comprehensive tests for the admin and moderation system
 * including content reporting, moderation actions, security monitoring,
 * audit logging, system health, and platform analytics.
 */

echo "Admin and Moderation System Test Runner\n";
echo "======================================\n\n";

// Check if we're running from command line
if (php_sapi_name() !== 'cli') {
    echo "This script should be run from the command line.\n";
    exit(1);
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start timing
$startTime = microtime(true);

try {
    // Include the test class
    require_once __DIR__ . '/AdminModerationTest.php';
    
    echo "Initializing test environment...\n";
    
    // Check database connection
    try {
        $db = Database::getInstance()->getConnection();
        echo "✓ Database connection established\n";
    } catch (Exception $e) {
        echo "✗ Database connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Check required tables exist
    $requiredTables = [
        'users', 'articles', 'comments', 'reports', 'moderation_actions',
        'content_flags', 'security_events', 'audit_log', 'system_health_metrics',
        'system_alerts', 'ip_blocks'
    ];
    
    echo "Checking required database tables...\n";
    foreach ($requiredTables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() === 0) {
            echo "⚠️  Warning: Table '{$table}' not found. Some tests may fail.\n";
        } else {
            echo "✓ Table '{$table}' exists\n";
        }
    }
    
    echo "\nStarting test execution...\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // Run the tests
    $tester = new AdminModerationTest();
    $tester->runAllTests();
    
} catch (Exception $e) {
    echo "\n✗ Test execution failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Calculate execution time
$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\nTest execution completed in {$executionTime} seconds.\n";

// Memory usage
$memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
echo "Peak memory usage: {$memoryUsage} MB\n";

echo "\nFor detailed logs, check the application error logs.\n";
echo "To run individual test components, modify the AdminModerationTest class.\n";