<?php

/**
 * Test Bulk Operations
 * Tests the bulk operations functionality to debug the 500 error
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';

// Mock authentication for testing
class MockAuthMiddleware {
    public function authenticate() {
        // Return a mock user for testing
        return [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com'
        ];
    }
}

// Override the auth middleware
class TestDashboardController extends DashboardController {
    public function __construct() {
        parent::__construct();
        $this->authMiddleware = new MockAuthMiddleware();
    }
    
    // Make protected methods public for testing
    public function testBulkOperations($data) {
        // Simulate POST data
        $_POST = json_encode($data);
        return $this->bulkOperations();
    }
    
    public function getJsonInput() {
        return json_decode($_POST, true);
    }
}

echo "Testing Bulk Operations...\n\n";

try {
    $controller = new TestDashboardController();
    
    // First, let's check if we have any articles to work with
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, title, status, author_id FROM articles WHERE author_id = 1 LIMIT 3");
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    if (empty($articles)) {
        echo "❌ No articles found for user ID 1. Creating a test article...\n";
        
        // Create a test article
        $stmt = $db->prepare("INSERT INTO articles (author_id, title, content, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([1, 'Test Article for Bulk Operations', '{"blocks":[{"type":"paragraph","content":[{"type":"text","text":"This is a test article for bulk operations."}]}]}', 'draft']);
        
        $articleId = $db->lastInsertId();
        echo "✅ Created test article with ID: $articleId\n\n";
        
        $articles = [['id' => $articleId, 'title' => 'Test Article for Bulk Operations', 'status' => 'draft', 'author_id' => 1]];
    }
    
    echo "Available articles:\n";
    foreach ($articles as $article) {
        echo "- ID: {$article['id']}, Title: {$article['title']}, Status: {$article['status']}\n";
    }
    echo "\n";
    
    // Test 1: Archive operation
    echo "Test 1: Archive Operation\n";
    $testData = [
        'article_ids' => [$articles[0]['id']],
        'operation' => 'archive'
    ];
    
    ob_start();
    $controller->testBulkOperations($testData);
    $output = ob_get_clean();
    
    if (!empty($output)) {
        $result = json_decode($output, true);
        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ Archive operation successful\n";
            echo "Result: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "❌ Archive operation failed\n";
            echo "Response: " . $output . "\n\n";
        }
    } else {
        echo "❌ Archive operation returned no output\n\n";
    }
    
    // Test 2: Publish operation
    echo "Test 2: Publish Operation\n";
    $testData = [
        'article_ids' => [$articles[0]['id']],
        'operation' => 'publish'
    ];
    
    ob_start();
    $controller->testBulkOperations($testData);
    $output = ob_get_clean();
    
    if (!empty($output)) {
        $result = json_decode($output, true);
        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ Publish operation successful\n";
            echo "Result: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "❌ Publish operation failed\n";
            echo "Response: " . $output . "\n\n";
        }
    } else {
        echo "❌ Publish operation returned no output\n\n";
    }
    
    // Test 3: Invalid operation
    echo "Test 3: Invalid Operation (should fail)\n";
    $testData = [
        'article_ids' => [$articles[0]['id']],
        'operation' => 'invalid_operation'
    ];
    
    ob_start();
    $controller->testBulkOperations($testData);
    $output = ob_get_clean();
    
    if (!empty($output)) {
        $result = json_decode($output, true);
        if ($result && isset($result['success']) && !$result['success']) {
            echo "✅ Invalid operation correctly rejected\n";
            echo "Error: " . $result['message'] . "\n\n";
        } else {
            echo "❌ Invalid operation should have been rejected\n";
            echo "Response: " . $output . "\n\n";
        }
    } else {
        echo "❌ Invalid operation returned no output\n\n";
    }
    
    // Test 4: Missing article IDs
    echo "Test 4: Missing Article IDs (should fail)\n";
    $testData = [
        'operation' => 'archive'
    ];
    
    ob_start();
    $controller->testBulkOperations($testData);
    $output = ob_get_clean();
    
    if (!empty($output)) {
        $result = json_decode($output, true);
        if ($result && isset($result['success']) && !$result['success']) {
            echo "✅ Missing article IDs correctly rejected\n";
            echo "Error: " . $result['message'] . "\n\n";
        } else {
            echo "❌ Missing article IDs should have been rejected\n";
            echo "Response: " . $output . "\n\n";
        }
    } else {
        echo "❌ Missing article IDs test returned no output\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing bulk operations: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";