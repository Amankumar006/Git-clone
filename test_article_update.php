<?php
/**
 * Test article update functionality
 */

require_once __DIR__ . '/api/config/config.php';
require_once __DIR__ . '/api/models/Article.php';

echo "🧪 Testing Article Update\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $articleModel = new Article();
    
    // Test if article 42 exists
    echo "📋 Checking if article 42 exists...\n";
    $existingArticle = $articleModel->findById(42);
    
    if (!$existingArticle) {
        echo "❌ Article 42 not found\n";
        exit(1);
    }
    
    echo "✅ Article 42 found: " . $existingArticle['title'] . "\n";
    echo "📝 Current status: " . $existingArticle['status'] . "\n";
    echo "👤 Author ID: " . $existingArticle['author_id'] . "\n\n";
    
    // Test update
    echo "🔄 Testing update...\n";
    $updateData = [
        'title' => 'Test Update ' . date('H:i:s'),
        'content' => 'Updated content at ' . date('Y-m-d H:i:s'),
        'subtitle' => 'Test subtitle',
        'status' => 'draft'
    ];
    
    echo "📤 Update data: " . json_encode($updateData, JSON_PRETTY_PRINT) . "\n\n";
    
    $result = $articleModel->update(42, $updateData);
    
    if ($result) {
        echo "✅ Update successful!\n";
        echo "📊 Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Update failed!\n";
    }
    
} catch (Exception $e) {
    echo "💥 Exception: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "🔍 Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Test completed\n";
?>