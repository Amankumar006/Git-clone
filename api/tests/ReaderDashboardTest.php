<?php
/**
 * Reader Dashboard Test
 * Tests the reader dashboard functionality
 */

require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Bookmark.php';
require_once __DIR__ . '/../models/Follow.php';

class ReaderDashboardTest {
    private $controller;
    private $testUserId;
    
    public function __construct() {
        $this->controller = new DashboardController();
    }
    
    /**
     * Run all reader dashboard tests
     */
    public function runTests() {
        echo "=== Reader Dashboard Tests ===\n";
        
        try {
            $this->testReaderStats();
            $this->testBookmarksEndpoint();
            $this->testFollowingFeedEndpoint();
            $this->testReadingHistoryEndpoint();
            
            echo "✅ All reader dashboard tests passed!\n";
        } catch (Exception $e) {
            echo "❌ Test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test reader statistics endpoint
     */
    private function testReaderStats() {
        echo "Testing reader stats endpoint...\n";
        
        // Mock authentication by setting a test user
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test_token';
        
        // Test that the method exists and can be called
        if (!method_exists($this->controller, 'readerStats')) {
            throw new Exception("readerStats method not found in DashboardController");
        }
        
        echo "✓ Reader stats method exists\n";
    }
    
    /**
     * Test bookmarks endpoint
     */
    private function testBookmarksEndpoint() {
        echo "Testing bookmarks endpoint...\n";
        
        if (!method_exists($this->controller, 'getBookmarks')) {
            throw new Exception("getBookmarks method not found in DashboardController");
        }
        
        echo "✓ Bookmarks method exists\n";
    }
    
    /**
     * Test following feed endpoint
     */
    private function testFollowingFeedEndpoint() {
        echo "Testing following feed endpoint...\n";
        
        if (!method_exists($this->controller, 'getFollowingFeed')) {
            throw new Exception("getFollowingFeed method not found in DashboardController");
        }
        
        echo "✓ Following feed method exists\n";
    }
    
    /**
     * Test reading history endpoint
     */
    private function testReadingHistoryEndpoint() {
        echo "Testing reading history endpoint...\n";
        
        if (!method_exists($this->controller, 'getReadingHistory')) {
            throw new Exception("getReadingHistory method not found in DashboardController");
        }
        
        echo "✓ Reading history method exists\n";
    }
    
    /**
     * Test model methods exist
     */
    public function testModelMethods() {
        echo "=== Testing Model Methods ===\n";
        
        // Test Article model methods
        $articleModel = new Article();
        $requiredMethods = [
            'getArticleCountsByStatus',
            'getTotalViewsByAuthor',
            'getTopArticlesByAuthor',
            'getUserArticlesForDashboard'
        ];
        
        foreach ($requiredMethods as $method) {
            if (!method_exists($articleModel, $method)) {
                throw new Exception("Article model missing method: $method");
            }
        }
        echo "✓ Article model methods exist\n";
        
        // Test Bookmark model methods
        $bookmarkModel = new Bookmark();
        if (!method_exists($bookmarkModel, 'getUserBookmarks')) {
            throw new Exception("Bookmark model missing getUserBookmarks method");
        }
        echo "✓ Bookmark model methods exist\n";
        
        // Test Follow model methods
        $followModel = new Follow();
        if (!method_exists($followModel, 'getFollowingFeed')) {
            throw new Exception("Follow model missing getFollowingFeed method");
        }
        echo "✓ Follow model methods exist\n";
        
        echo "✅ All model methods exist!\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ReaderDashboardTest();
    $test->runTests();
    $test->testModelMethods();
}