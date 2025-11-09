<?php

/**
 * Database initialization script
 * This script ensures that all required database modifications are applied
 */

require_once __DIR__ . '/models/Article.php';

try {
    $article = new Article();
    
    // Ensure slug column exists
    if ($article->ensureSlugColumn()) {
        echo "Database initialization completed successfully.\n";
    } else {
        echo "Warning: Could not ensure slug column exists.\n";
    }
    
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}