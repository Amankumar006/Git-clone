<?php

/**
 * Debug Dashboard Issues
 * Simple script to test dashboard functionality and identify issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "=== Dashboard Debug Script ===\n\n";

try {
    // Test database connection
    echo "1. Testing database connection...\n";
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connected successfully\n\n";
    
    // Check required tables
    echo "2. Checking required tables...\n";
    $requiredTables = ['articles', 'users', 'article_views', 'article_reads', 'tags', 'article_tags'];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }
    echo "\n";
    
    // Check if we have test data
    echo "3. Checking test data...\n";
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $userCount = $stmt->fetch()['count'];
    echo "Users: $userCount\n";
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM articles");
    $stmt->execute();
    $articleCount = $stmt->fetch()['count'];
    echo "Articles: $articleCount\n";
    
    if ($userCount == 0) {
        echo "❌ No users found - dashboard won't work without users\n";
    }
    
    if ($articleCount == 0) {
        echo "❌ No articles found - dashboard will show empty data\n";
    }
    echo "\n";
    
    // Test Article model methods
    echo "4. Testing Article model methods...\n";
    require_once __DIR__ . '/models/Article.php';
    
    $articleModel = new Article();
    
    // Test if methods exist
    $requiredMethods = ['deleteArticle', 'publish', 'unpublish', 'archive'];
    foreach ($requiredMethods as $method) {
        if (method_exists($articleModel, $method)) {
            echo "✅ Method '$method' exists\n";
        } else {
            echo "❌ Method '$method' missing\n";
        }
    }
    echo "\n";
    
    // Test dashboard controller
    echo "5. Testing Dashboard Controller...\n";
    require_once __DIR__ . '/controllers/DashboardController.php';
    
    if (class_exists('DashboardController')) {
        echo "✅ DashboardController class exists\n";
        
        $controller = new DashboardController();
        $requiredControllerMethods = ['writerStats', 'readerStats', 'bulkOperations', 'userArticles'];
        
        foreach ($requiredControllerMethods as $method) {
            if (method_exists($controller, $method)) {
                echo "✅ Controller method '$method' exists\n";
            } else {
                echo "❌ Controller method '$method' missing\n";
            }
        }
    } else {
        echo "❌ DashboardController class not found\n";
    }
    echo "\n";
    
    // Test authentication
    echo "6. Testing Authentication...\n";
    require_once __DIR__ . '/middleware/AuthMiddleware.php';
    
    if (class_exists('AuthMiddleware')) {
        echo "✅ AuthMiddleware class exists\n";
    } else {
        echo "❌ AuthMiddleware class not found\n";
    }
    echo "\n";
    
    // Test JSON input handling
    echo "7. Testing JSON input handling...\n";
    $testJson = '{"article_ids": [1, 2], "operation": "archive"}';
    $decoded = json_decode($testJson, true);
    
    if ($decoded && isset($decoded['article_ids']) && isset($decoded['operation'])) {
        echo "✅ JSON input handling works\n";
    } else {
        echo "❌ JSON input handling failed\n";
    }
    echo "\n";
    
    echo "=== Debug Complete ===\n";
    echo "If all checks pass, the dashboard should work.\n";
    echo "If you see missing tables, run: php setup_analytics_tables.php\n";
    echo "If you see missing methods, the Article model needs to be updated.\n";
    
} catch (Exception $e) {
    echo "❌ Error during debug: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}