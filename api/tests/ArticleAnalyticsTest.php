<?php

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../controllers/ArticleController.php';
require_once __DIR__ . '/../config/database.php';

class ArticleAnalyticsTest {
    private $article;
    private $controller;
    private $testArticleId;
    private $testUserId;

    public function __construct() {
        $this->article = new Article();
        $this->controller = new ArticleController();
        $this->setupTestData();
    }

    private function setupTestData() {
        // Create test user
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute(['testuser', 'test@example.com', password_hash('password', PASSWORD_DEFAULT)]);
        $this->testUserId = $db->lastInsertId();

        // Create test article
        $articleData = [
            'title' => 'Test Analytics Article',
            'content' => json_encode([['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Test content']]]]),
            'author_id' => $this->testUserId,
            'status' => 'published'
        ];
        $article = $this->article->create($articleData);
        $this->testArticleId = $article['id'];
    }

    public function runAllTests() {
        echo "Running Article Analytics Tests...\n";
        
        $this->testViewTracking();
        $this->testReadTracking();
        $this->testAnalyticsRecording();
        $this->testBasicAnalytics();
        $this->testViewTrends();
        $this->testReadingStats();
        $this->testReferrerStats();
        $this->testPopularArticles();
        
        echo "All Article Analytics Tests Completed!\n";
    }

    public function testViewTracking() {
        echo "Testing view tracking...\n";
        
        // Test basic view increment
        $initialViewCount = $this->article->findById($this->testArticleId)['view_count'];
        
        $result = $this->article->trackView(
            $this->testArticleId,
            $this->testUserId,
            'Test User Agent',
            'https://example.com',
            '127.0.0.1'
        );
        
        assert($result === true, "View tracking should succeed");
        
        $updatedViewCount = $this->article->findById($this->testArticleId)['view_count'];
        assert($updatedViewCount === $initialViewCount + 1, "View count should increment");
        
        // Test anonymous view tracking
        $result = $this->article->trackView(
            $this->testArticleId,
            null,
            'Anonymous User Agent',
            '',
            '192.168.1.1'
        );
        
        assert($result === true, "Anonymous view tracking should succeed");
        
        echo "✓ View tracking tests passed\n";
    }

    public function testReadTracking() {
        echo "Testing read tracking...\n";
        
        // Test read tracking
        $result = $this->article->trackRead(
            $this->testArticleId,
            $this->testUserId,
            120000, // 2 minutes
            75.5    // 75.5% scroll depth
        );
        
        assert($result === true, "Read tracking should succeed");
        
        // Test duplicate read tracking (should update existing record)
        $result = $this->article->trackRead(
            $this->testArticleId,
            $this->testUserId,
            180000, // 3 minutes (should update)
            85.0    // 85% scroll depth (should update)
        );
        
        assert($result === true, "Duplicate read tracking should succeed");
        
        echo "✓ Read tracking tests passed\n";
    }

    public function testAnalyticsRecording() {
        echo "Testing analytics recording...\n";
        
        $result = $this->article->recordAnalytics(
            $this->testArticleId,
            $this->testUserId,
            150000, // 2.5 minutes
            80.0,   // 80% scroll depth
            true,   // is_read
            '127.0.0.1'
        );
        
        assert($result === true, "Analytics recording should succeed");
        
        // Test anonymous analytics
        $result = $this->article->recordAnalytics(
            $this->testArticleId,
            null,
            90000,  // 1.5 minutes
            60.0,   // 60% scroll depth
            false,  // not read
            '192.168.1.1'
        );
        
        assert($result === true, "Anonymous analytics recording should succeed");
        
        echo "✓ Analytics recording tests passed\n";
    }

    public function testBasicAnalytics() {
        echo "Testing basic analytics retrieval...\n";
        
        $analytics = $this->article->getArticleAnalytics($this->testArticleId);
        
        assert(isset($analytics['basic_stats']), "Basic stats should be present");
        assert(isset($analytics['view_trends']), "View trends should be present");
        assert(isset($analytics['reading_stats']), "Reading stats should be present");
        assert(isset($analytics['referrer_stats']), "Referrer stats should be present");
        
        $basicStats = $analytics['basic_stats'];
        assert($basicStats['total_views'] > 0, "Should have view data");
        assert($basicStats['total_reads'] > 0, "Should have read data");
        assert($basicStats['unique_viewers'] > 0, "Should have unique viewer data");
        
        echo "✓ Basic analytics tests passed\n";
    }

    public function testViewTrends() {
        echo "Testing view trends...\n";
        
        $trends = $this->article->getViewTrends($this->testArticleId, 7);
        
        assert(is_array($trends), "View trends should be an array");
        
        if (!empty($trends)) {
            $trend = $trends[0];
            assert(isset($trend['date']), "Trend should have date");
            assert(isset($trend['views']), "Trend should have views count");
            assert(isset($trend['unique_views']), "Trend should have unique views count");
        }
        
        echo "✓ View trends tests passed\n";
    }

    public function testReadingStats() {
        echo "Testing reading statistics...\n";
        
        $stats = $this->article->getReadingStats($this->testArticleId);
        
        assert(isset($stats['avg_time_spent']), "Should have average time spent");
        assert(isset($stats['max_time_spent']), "Should have max time spent");
        assert(isset($stats['avg_scroll_depth']), "Should have average scroll depth");
        assert(isset($stats['deep_reads']), "Should have deep reads count");
        assert(isset($stats['total_reads']), "Should have total reads count");
        
        assert($stats['total_reads'] > 0, "Should have read data");
        
        echo "✓ Reading statistics tests passed\n";
    }

    public function testReferrerStats() {
        echo "Testing referrer statistics...\n";
        
        $stats = $this->article->getReferrerStats($this->testArticleId);
        
        assert(is_array($stats), "Referrer stats should be an array");
        
        if (!empty($stats)) {
            $stat = $stats[0];
            assert(isset($stat['source']), "Referrer stat should have source");
            assert(isset($stat['views']), "Referrer stat should have views count");
        }
        
        echo "✓ Referrer statistics tests passed\n";
    }

    public function testPopularArticles() {
        echo "Testing popular articles...\n";
        
        // Test different timeframes
        $timeframes = ['day', 'week', 'month', 'all'];
        
        foreach ($timeframes as $timeframe) {
            $articles = $this->article->getPopularArticles(5, $timeframe);
            
            assert(is_array($articles), "Popular articles should be an array for timeframe: $timeframe");
            
            if (!empty($articles)) {
                $article = $articles[0];
                assert(isset($article['id']), "Article should have ID");
                assert(isset($article['title']), "Article should have title");
                assert(isset($article['popularity_score']), "Article should have popularity score");
                assert(isset($article['recent_views']), "Article should have recent views");
                assert(isset($article['recent_reads']), "Article should have recent reads");
            }
        }
        
        echo "✓ Popular articles tests passed\n";
    }

    public function testAnalyticsController() {
        echo "Testing analytics controller endpoints...\n";
        
        // Mock request data
        $_POST = json_encode([
            'article_id' => $this->testArticleId,
            'time_spent' => 120000,
            'scroll_depth' => 75.0,
            'is_read' => true
        ]);
        
        // Test track view endpoint
        ob_start();
        $this->controller->trackView();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        assert($response['success'] === true, "Track view should succeed");
        
        // Test track read endpoint
        ob_start();
        $this->controller->trackRead();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        assert($response['success'] === true, "Track read should succeed");
        
        // Test analytics endpoint
        ob_start();
        $this->controller->analytics();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        assert($response['success'] === true, "Analytics recording should succeed");
        
        echo "✓ Analytics controller tests passed\n";
    }

    public function cleanup() {
        echo "Cleaning up test data...\n";
        
        $db = Database::getInstance()->getConnection();
        
        // Clean up analytics data
        $stmt = $db->prepare("DELETE FROM article_analytics WHERE article_id = ?");
        $stmt->execute([$this->testArticleId]);
        
        $stmt = $db->prepare("DELETE FROM article_reads WHERE article_id = ?");
        $stmt->execute([$this->testArticleId]);
        
        $stmt = $db->prepare("DELETE FROM article_views WHERE article_id = ?");
        $stmt->execute([$this->testArticleId]);
        
        // Clean up article
        $stmt = $db->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([$this->testArticleId]);
        
        // Clean up user
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$this->testUserId]);
        
        echo "✓ Cleanup completed\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $test = new ArticleAnalyticsTest();
        $test->runAllTests();
        $test->cleanup();
        echo "\n🎉 All tests passed successfully!\n";
    } catch (Exception $e) {
        echo "\n❌ Test failed: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
        exit(1);
    }
}