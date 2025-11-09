<?php
require_once __DIR__ . '/config/config.php';

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if articles table exists and get its structure
    $stmt = $db->prepare("DESCRIBE articles");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Articles table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
    // Check if slug column exists
    $hasSlug = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'slug') {
            $hasSlug = true;
            break;
        }
    }
    
    echo "\nSlug column exists: " . ($hasSlug ? 'YES' : 'NO') . "\n";
    
    if (!$hasSlug) {
        echo "\nAdding slug column...\n";
        $db->exec("ALTER TABLE articles ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title");
        $db->exec("CREATE INDEX idx_articles_slug ON articles(slug)");
        echo "Slug column added successfully!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>