<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "Testing database connection...\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASS: " . (DB_PASS ? '[SET]' : '[EMPTY]') . "\n\n";

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful!\n\n";
    
    // Check if users table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $userTableExists = $stmt->fetch();
    
    if ($userTableExists) {
        echo "✅ Users table exists\n";
        
        // Check users table structure
        $stmt = $db->prepare("DESCRIBE users");
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "Users table columns:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
        
        // Check if there are any users
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users");
        $stmt->execute();
        $userCount = $stmt->fetch();
        echo "\nTotal users: {$userCount['count']}\n";
        
    } else {
        echo "❌ Users table does not exist\n";
    }
    
    // Check if articles table exists
    $stmt = $db->prepare("SHOW TABLES LIKE 'articles'");
    $stmt->execute();
    $articleTableExists = $stmt->fetch();
    
    if ($articleTableExists) {
        echo "✅ Articles table exists\n";
        
        // Check if slug column exists
        $stmt = $db->prepare("SHOW COLUMNS FROM articles LIKE 'slug'");
        $stmt->execute();
        $slugExists = $stmt->fetch();
        
        echo "Slug column exists: " . ($slugExists ? 'YES' : 'NO') . "\n";
        
    } else {
        echo "❌ Articles table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}
?>