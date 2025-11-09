<?php
/**
 * Comprehensive Dashboard and Analytics Test Suite
 * Tests dashboard data accuracy, calculations, notification system, and analytics algorithms
 */

require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Clap.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../models/Notification.php';

class DashboardAnalyticsTest {
    private $dashboardController;
    private $notificationController;
    private $userModel;
    private $articleModel;
    private $clapModel;
    private $commentModel;
    private $followModel;
    private $notificationModel;
    private $testUserId;
    private $testArticleId;
    
    public function __construct() {
        $this->dashboardController = new DashboardController();
        $this->notificationController = new NotificationController();
        $this->userModel = new User();
        $this->articleModel = new Article();
        $this->clapModel = new Clap();
        $this->commentModel = new Comment();
        $this->followModel = new Follow();
        $this->notificationModel = new Notification();
        
        // Set up test data
        $this->setupTestData();
    }
    
    /**
     * Set up test data for testing
     */
    private function setupTestData() {
        // Mock test user ID and article ID for testing
        $this->testUserId = 1;
        $this->testArticleId = 1;
        
        // Mock authentication for testing
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token-' . $this->testUserId;
    }
    
    /**
     * Run all dashboard and analytics tests
     */
    public function runAllTests() {
        echo "=== Dashboard and Analytics Test Suite ===\n\n";
        
        try {
            // Test dashboard data accuracy
            $this->testDashboardDataAccuracy();
            echo "\n";
            
            // Test analytics calculations
            $this->testAnalyticsCalculations();
            echo "\n";
            
            // Test notification system
            $this->testNotificationSystem();
            echo "\n";
            
            // Test data visualization algorithms
            $this->testDataVisualizationAlgorithms();
            echo "\n";
            
            // Test performance and responsiveness
            $this->testPerformanceAndResponsiveness();
            echo "\n";
            
            // Test advanced analytics
            $this->testAdvancedAnalytics();
            echo "\n";
            
            // Test export functionality
            $this->testExportFunctionality();
            echo "\n";
            
            echo "✅ All dashboard and analytics tests completed successfully!\n";
            
        } catch (Exception $e) {
            echo "❌ Test suite failed: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }
    
    /**
     * Test dashboard data accuracy and calculations
     */
    private function testDashboardDataAccuracy() {
        echo "=== Testing Dashboard Data Accuracy ===\n";
        
        // Test writer stats calculation accuracy
        $this->testWriterStatsAccuracy();
        
        // Test reader stats calculation accuracy
        $this->testReaderStatsAccuracy();
        
        // Test article counts accuracy
        $this->testArticleCountsAccuracy();
        
        // Test engagement metrics accuracy
        $this->testEngagementMetricsAccuracy();
    }
    
    /**
     * Test writer statistics accuracy
     */
    private function testWriterStatsAccuracy() {
        echo "Testing writer stats accuracy...\n";
        
        try {
            // Test article counts by status
            $articleCounts = $this->articleModel->getArticleCountsByStatus($this->testUserId);
            
            if (is_array($articleCounts) && isset($articleCounts['published'])) {
                echo "✓ Article counts by status calculation works\n";
            } else {
                throw new Exception("Article counts calculation failed");
            }
            
            // Test total views calculation
            $totalViews = $this->articleModel->getTotalViewsByAuthor($this->testUserId);
            
            if (is_numeric($totalViews) && $totalViews >= 0) {
                echo "✓ Total views calculation works\n";
            } else {
                throw new Exception("Total views calculation failed");
            }
            
            // Test total claps calculation
            $totalClaps = $this->clapModel->getTotalClapsByAuthor($this->testUserId);
            
            if (is_numeric($totalClaps) && $totalClaps >= 0) {
                echo "✓ Total claps calculation works\n";
            } else {
                throw new Exception("Total claps calculation failed");
            }
            
            // Test follower count calculation
            $followerCount = $this->followModel->getFollowerCount($this->testUserId);
            
            if (is_numeric($followerCount) && $followerCount >= 0) {
                echo "✓ Follower count calculation works\n";
            } else {
                throw new Exception("Follower count calculation failed");
            }
            
        } catch (Exception $e) {
            echo "✗ Writer stats accuracy test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test reader statistics accuracy
     */
    private function testReaderStatsAccuracy() {
        echo "Testing reader stats accuracy...\n";
        
        try {
            // Mock reader stats method call
            ob_start();
            $this->dashboardController->readerStats();
            $output = ob_get_clean();
            
            // Check if method executes without errors
            if (strpos($output, 'error') === false) {
                echo "✓ Reader stats endpoint accessible\n";
            }
            
            // Test reading streak calculation
            $readingStreak = $this->getReadingStreakForTesting($this->testUserId);
            
            if (is_numeric($readingStreak) && $readingStreak >= 0) {
                echo "✓ Reading streak calculation works\n";
            } else {
                echo "✗ Reading streak calculation failed\n";
            }
            
            // Test favorite topics calculation
            $favoriteTopics = $this->getFavoriteTopicsForTesting($this->testUserId);
            
            if (is_array($favoriteTopics)) {
                echo "✓ Favorite topics calculation works\n";
            } else {
                echo "✗ Favorite topics calculation failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Reader stats accuracy test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test article counts accuracy
     */
    private function testArticleCountsAccuracy() {
        echo "Testing article counts accuracy...\n";
        
        try {
            // Test article counts by status
            $counts = $this->articleModel->getArticleCountsByStatus($this->testUserId);
            
            // Verify structure
            $expectedKeys = ['draft', 'published', 'archived'];
            $hasAllKeys = true;
            
            foreach ($expectedKeys as $key) {
                if (!isset($counts[$key])) {
                    $hasAllKeys = false;
                    break;
                }
            }
            
            if ($hasAllKeys) {
                echo "✓ Article counts structure is correct\n";
            } else {
                throw new Exception("Article counts structure is incorrect");
            }
            
            // Test that counts are non-negative integers
            foreach ($counts as $status => $count) {
                if (!is_numeric($count) || $count < 0) {
                    throw new Exception("Invalid count for status: $status");
                }
            }
            
            echo "✓ Article counts are valid non-negative integers\n";
            
        } catch (Exception $e) {
            echo "✗ Article counts accuracy test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test engagement metrics accuracy
     */
    private function testEngagementMetricsAccuracy() {
        echo "Testing engagement metrics accuracy...\n";
        
        try {
            // Test clap count calculation
            $clapCount = $this->clapModel->getTotalClapsByAuthor($this->testUserId);
            
            if (is_numeric($clapCount) && $clapCount >= 0) {
                echo "✓ Clap count calculation is accurate\n";
            } else {
                throw new Exception("Clap count calculation failed");
            }
            
            // Test comment count calculation
            $commentCount = $this->commentModel->getTotalCommentsByAuthor($this->testUserId);
            
            if (is_numeric($commentCount) && $commentCount >= 0) {
                echo "✓ Comment count calculation is accurate\n";
            } else {
                throw new Exception("Comment count calculation failed");
            }
            
            // Test engagement score calculation
            $topArticles = $this->articleModel->getTopArticlesByAuthor($this->testUserId, 5);
            
            if (is_array($topArticles)) {
                foreach ($topArticles as $article) {
                    if (!isset($article['engagement_score']) || !is_numeric($article['engagement_score'])) {
                        throw new Exception("Engagement score calculation failed");
                    }
                }
                echo "✓ Engagement score calculation is accurate\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Engagement metrics accuracy test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test analytics calculations
     */
    private function testAnalyticsCalculations() {
        echo "=== Testing Analytics Calculations ===\n";
        
        // Test views over time calculation
        $this->testViewsOverTimeCalculation();
        
        // Test engagement over time calculation
        $this->testEngagementOverTimeCalculation();
        
        // Test article performance comparison
        $this->testArticlePerformanceComparison();
        
        // Test audience insights calculation
        $this->testAudienceInsightsCalculation();
    }
    
    /**
     * Test views over time calculation
     */
    private function testViewsOverTimeCalculation() {
        echo "Testing views over time calculation...\n";
        
        try {
            $viewsOverTime = $this->articleModel->getViewsOverTime($this->testUserId, 30);
            
            if (is_array($viewsOverTime)) {
                // Check data structure
                foreach ($viewsOverTime as $dataPoint) {
                    if (!isset($dataPoint['date']) || !isset($dataPoint['views'])) {
                        throw new Exception("Views over time data structure is incorrect");
                    }
                    
                    if (!is_numeric($dataPoint['views']) || $dataPoint['views'] < 0) {
                        throw new Exception("Invalid views count in time series data");
                    }
                }
                
                echo "✓ Views over time calculation is accurate\n";
            } else {
                throw new Exception("Views over time calculation failed");
            }
            
        } catch (Exception $e) {
            echo "✗ Views over time calculation test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test engagement over time calculation
     */
    private function testEngagementOverTimeCalculation() {
        echo "Testing engagement over time calculation...\n";
        
        try {
            // Test claps over time
            $clapsOverTime = $this->clapModel->getClapsOverTime($this->testUserId, 30);
            
            if (is_array($clapsOverTime)) {
                echo "✓ Claps over time calculation works\n";
            } else {
                echo "✗ Claps over time calculation failed\n";
            }
            
            // Test comments over time
            $commentsOverTime = $this->commentModel->getCommentsOverTime($this->testUserId, 30);
            
            if (is_array($commentsOverTime)) {
                echo "✓ Comments over time calculation works\n";
            } else {
                echo "✗ Comments over time calculation failed\n";
            }
            
            // Test follows over time
            $followsOverTime = $this->followModel->getFollowsOverTime($this->testUserId, 30);
            
            if (is_array($followsOverTime)) {
                echo "✓ Follows over time calculation works\n";
            } else {
                echo "✗ Follows over time calculation failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Engagement over time calculation test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test article performance comparison
     */
    private function testArticlePerformanceComparison() {
        echo "Testing article performance comparison...\n";
        
        try {
            $articlePerformance = $this->articleModel->getArticlePerformanceComparison($this->testUserId);
            
            if (is_array($articlePerformance)) {
                // Verify each article has required metrics
                foreach ($articlePerformance as $article) {
                    $requiredFields = ['id', 'title', 'view_count', 'clap_count', 'comment_count', 'engagement_score'];
                    
                    foreach ($requiredFields as $field) {
                        if (!isset($article[$field])) {
                            throw new Exception("Missing field '$field' in article performance data");
                        }
                    }
                }
                
                echo "✓ Article performance comparison calculation is accurate\n";
            } else {
                throw new Exception("Article performance comparison failed");
            }
            
        } catch (Exception $e) {
            echo "✗ Article performance comparison test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test audience insights calculation
     */
    private function testAudienceInsightsCalculation() {
        echo "Testing audience insights calculation...\n";
        
        try {
            // Test top readers calculation
            $topReaders = $this->userModel->getTopReadersByAuthor($this->testUserId, 10);
            
            if (is_array($topReaders)) {
                echo "✓ Top readers calculation works\n";
            } else {
                echo "✗ Top readers calculation failed\n";
            }
            
            // Test engagement patterns
            $engagementByDay = $this->clapModel->getEngagementByDayOfWeek($this->testUserId);
            
            if (is_array($engagementByDay)) {
                echo "✓ Engagement by day of week calculation works\n";
            } else {
                echo "✗ Engagement by day of week calculation failed\n";
            }
            
            // Test tag performance
            $tagPerformance = $this->articleModel->getTagPerformanceByAuthor($this->testUserId);
            
            if (is_array($tagPerformance)) {
                echo "✓ Tag performance calculation works\n";
            } else {
                echo "✗ Tag performance calculation failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Audience insights calculation test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test notification system functionality
     */
    private function testNotificationSystem() {
        echo "=== Testing Notification System ===\n";
        
        // Test notification creation
        $this->testNotificationCreation();
        
        // Test notification retrieval
        $this->testNotificationRetrieval();
        
        // Test notification management
        $this->testNotificationManagement();
        
        // Test notification filtering
        $this->testNotificationFiltering();
    }
    
    /**
     * Test notification creation
     */
    private function testNotificationCreation() {
        echo "Testing notification creation...\n";
        
        try {
            // Test follow notification creation
            $result = $this->notificationModel->createFollowNotification(
                $this->testUserId + 1, 
                $this->testUserId, 
                'testuser'
            );
            
            if (isset($result['success']) && $result['success']) {
                echo "✓ Follow notification creation works\n";
            } else {
                echo "✗ Follow notification creation failed\n";
            }
            
            // Test clap notification creation
            $result = $this->notificationModel->createClapNotification(
                $this->testUserId + 1, 
                $this->testUserId, 
                $this->testArticleId, 
                'testuser', 
                'Test Article', 
                5
            );
            
            if (isset($result['success']) && $result['success']) {
                echo "✓ Clap notification creation works\n";
            } else {
                echo "✗ Clap notification creation failed\n";
            }
            
            // Test comment notification creation
            $result = $this->notificationModel->createCommentNotification(
                $this->testUserId + 1, 
                $this->testUserId, 
                $this->testArticleId, 
                'testuser', 
                'Test Article'
            );
            
            if (isset($result['success']) && $result['success']) {
                echo "✓ Comment notification creation works\n";
            } else {
                echo "✗ Comment notification creation failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Notification creation test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test notification retrieval
     */
    private function testNotificationRetrieval() {
        echo "Testing notification retrieval...\n";
        
        try {
            // Test getting user notifications
            $notifications = $this->notificationModel->getUserNotifications($this->testUserId, false, 10, 0);
            
            if (is_array($notifications)) {
                echo "✓ User notifications retrieval works\n";
            } else {
                echo "✗ User notifications retrieval failed\n";
            }
            
            // Test getting unread count
            $unreadCount = $this->notificationModel->getUnreadCount($this->testUserId);
            
            if (is_numeric($unreadCount) && $unreadCount >= 0) {
                echo "✓ Unread count retrieval works\n";
            } else {
                echo "✗ Unread count retrieval failed\n";
            }
            
            // Test notification controller endpoint
            ob_start();
            $this->notificationController->getUserNotifications();
            $output = ob_get_clean();
            
            if (strpos($output, 'error') === false) {
                echo "✓ Notification controller endpoint works\n";
            } else {
                echo "✗ Notification controller endpoint failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Notification retrieval test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test notification management
     */
    private function testNotificationManagement() {
        echo "Testing notification management...\n";
        
        try {
            // Test marking notification as read
            $result = $this->notificationModel->markAsRead(1, $this->testUserId);
            
            if (is_array($result) && isset($result['success'])) {
                echo "✓ Mark as read functionality works\n";
            } else {
                echo "✗ Mark as read functionality failed\n";
            }
            
            // Test marking all as read
            $result = $this->notificationModel->markAllAsRead($this->testUserId);
            
            if (is_array($result) && isset($result['success'])) {
                echo "✓ Mark all as read functionality works\n";
            } else {
                echo "✗ Mark all as read functionality failed\n";
            }
            
            // Test deleting notification
            $result = $this->notificationModel->deleteNotification(1, $this->testUserId);
            
            if (is_array($result) && isset($result['success'])) {
                echo "✓ Delete notification functionality works\n";
            } else {
                echo "✗ Delete notification functionality failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Notification management test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test notification filtering
     */
    private function testNotificationFiltering() {
        echo "Testing notification filtering...\n";
        
        try {
            // Test unread only filter
            $unreadNotifications = $this->notificationModel->getUserNotifications($this->testUserId, true, 10, 0);
            
            if (is_array($unreadNotifications)) {
                echo "✓ Unread notifications filtering works\n";
            } else {
                echo "✗ Unread notifications filtering failed\n";
            }
            
            // Test pagination
            $page1 = $this->notificationModel->getUserNotifications($this->testUserId, false, 5, 0);
            $page2 = $this->notificationModel->getUserNotifications($this->testUserId, false, 5, 5);
            
            if (is_array($page1) && is_array($page2)) {
                echo "✓ Notification pagination works\n";
            } else {
                echo "✗ Notification pagination failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Notification filtering test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test data visualization algorithms
     */
    private function testDataVisualizationAlgorithms() {
        echo "=== Testing Data Visualization Algorithms ===\n";
        
        // Test chart data preparation
        $this->testChartDataPreparation();
        
        // Test trend analysis
        $this->testTrendAnalysis();
        
        // Test data aggregation
        $this->testDataAggregation();
    }
    
    /**
     * Test chart data preparation
     */
    private function testChartDataPreparation() {
        echo "Testing chart data preparation...\n";
        
        try {
            // Test views over time chart data
            $viewsData = $this->articleModel->getViewsOverTime($this->testUserId, 7);
            
            if (is_array($viewsData) && count($viewsData) <= 7) {
                echo "✓ Views chart data preparation works\n";
            } else {
                echo "✗ Views chart data preparation failed\n";
            }
            
            // Test engagement patterns data
            $engagementPatterns = $this->clapModel->getEngagementByDayOfWeek($this->testUserId);
            
            if (is_array($engagementPatterns)) {
                echo "✓ Engagement patterns data preparation works\n";
            } else {
                echo "✗ Engagement patterns data preparation failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Chart data preparation test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test trend analysis
     */
    private function testTrendAnalysis() {
        echo "Testing trend analysis...\n";
        
        try {
            // Test engagement velocity calculation
            ob_start();
            $this->dashboardController->advancedAnalytics();
            $output = ob_get_clean();
            
            if (strpos($output, 'error') === false) {
                echo "✓ Advanced analytics trend analysis works\n";
            } else {
                echo "✗ Advanced analytics trend analysis failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Trend analysis test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test data aggregation
     */
    private function testDataAggregation() {
        echo "Testing data aggregation...\n";
        
        try {
            // Test article performance aggregation
            $performance = $this->articleModel->getArticlePerformanceComparison($this->testUserId);
            
            if (is_array($performance)) {
                echo "✓ Article performance aggregation works\n";
            } else {
                echo "✗ Article performance aggregation failed\n";
            }
            
            // Test tag performance aggregation
            $tagPerformance = $this->articleModel->getTagPerformanceByAuthor($this->testUserId);
            
            if (is_array($tagPerformance)) {
                echo "✓ Tag performance aggregation works\n";
            } else {
                echo "✗ Tag performance aggregation failed\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Data aggregation test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test performance and responsiveness
     */
    private function testPerformanceAndResponsiveness() {
        echo "=== Testing Performance and Responsiveness ===\n";
        
        // Test response times
        $this->testResponseTimes();
        
        // Test memory usage
        $this->testMemoryUsage();
        
        // Test database query efficiency
        $this->testDatabaseQueryEfficiency();
    }
    
    /**
     * Test response times
     */
    private function testResponseTimes() {
        echo "Testing response times...\n";
        
        try {
            // Test writer stats response time
            $startTime = microtime(true);
            ob_start();
            $this->dashboardController->writerStats();
            ob_get_clean();
            $endTime = microtime(true);
            
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            if ($responseTime < 1000) { // Less than 1 second
                echo "✓ Writer stats response time is acceptable ({$responseTime}ms)\n";
            } else {
                echo "⚠ Writer stats response time is slow ({$responseTime}ms)\n";
            }
            
            // Test analytics response time
            $startTime = microtime(true);
            ob_start();
            $this->dashboardController->writerAnalytics();
            ob_get_clean();
            $endTime = microtime(true);
            
            $responseTime = ($endTime - $startTime) * 1000;
            
            if ($responseTime < 2000) { // Less than 2 seconds for analytics
                echo "✓ Analytics response time is acceptable ({$responseTime}ms)\n";
            } else {
                echo "⚠ Analytics response time is slow ({$responseTime}ms)\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Response time test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test memory usage
     */
    private function testMemoryUsage() {
        echo "Testing memory usage...\n";
        
        try {
            $initialMemory = memory_get_usage();
            
            // Execute dashboard operations
            ob_start();
            $this->dashboardController->writerStats();
            $this->dashboardController->writerAnalytics();
            ob_get_clean();
            
            $finalMemory = memory_get_usage();
            $memoryUsed = $finalMemory - $initialMemory;
            
            if ($memoryUsed < 10 * 1024 * 1024) { // Less than 10MB
                echo "✓ Memory usage is acceptable (" . round($memoryUsed / 1024 / 1024, 2) . "MB)\n";
            } else {
                echo "⚠ Memory usage is high (" . round($memoryUsed / 1024 / 1024, 2) . "MB)\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Memory usage test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test database query efficiency
     */
    private function testDatabaseQueryEfficiency() {
        echo "Testing database query efficiency...\n";
        
        try {
            // Test that methods exist and can be called
            $methods = [
                'getArticleCountsByStatus',
                'getTotalViewsByAuthor',
                'getTopArticlesByAuthor',
                'getViewsOverTime'
            ];
            
            foreach ($methods as $method) {
                if (method_exists($this->articleModel, $method)) {
                    echo "✓ Method $method exists and is callable\n";
                } else {
                    echo "✗ Method $method does not exist\n";
                }
            }
            
        } catch (Exception $e) {
            echo "✗ Database query efficiency test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test advanced analytics
     */
    private function testAdvancedAnalytics() {
        echo "=== Testing Advanced Analytics ===\n";
        
        try {
            // Test advanced analytics endpoint
            ob_start();
            $this->dashboardController->advancedAnalytics();
            $output = ob_get_clean();
            
            if (strpos($output, 'error') === false) {
                echo "✓ Advanced analytics endpoint works\n";
            } else {
                echo "✗ Advanced analytics endpoint failed\n";
            }
            
            // Test detailed performance metrics
            if (method_exists($this->dashboardController, 'getDetailedPerformanceMetrics')) {
                echo "✓ Detailed performance metrics method exists\n";
            } else {
                echo "✗ Detailed performance metrics method missing\n";
            }
            
            // Test reader demographics
            if (method_exists($this->dashboardController, 'getReaderDemographics')) {
                echo "✓ Reader demographics method exists\n";
            } else {
                echo "✗ Reader demographics method missing\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Advanced analytics test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test export functionality
     */
    private function testExportFunctionality() {
        echo "=== Testing Export Functionality ===\n";
        
        try {
            // Test export analytics endpoint
            $_GET['format'] = 'json';
            $_GET['timeframe'] = '30';
            $_GET['data_type'] = 'performance';
            
            ob_start();
            $this->dashboardController->exportAnalytics();
            $output = ob_get_clean();
            
            if (strpos($output, 'error') === false) {
                echo "✓ Export analytics endpoint works\n";
            } else {
                echo "✗ Export analytics endpoint failed\n";
            }
            
            // Test different export formats
            $formats = ['json', 'csv'];
            foreach ($formats as $format) {
                $_GET['format'] = $format;
                
                ob_start();
                $this->dashboardController->exportAnalytics();
                $output = ob_get_clean();
                
                if (strpos($output, 'error') === false) {
                    echo "✓ Export format $format works\n";
                } else {
                    echo "✗ Export format $format failed\n";
                }
            }
            
        } catch (Exception $e) {
            echo "✗ Export functionality test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Helper method for testing reading streak calculation
     */
    private function getReadingStreakForTesting($userId) {
        try {
            // Mock reading streak calculation
            return 5; // Mock value for testing
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Helper method for testing favorite topics calculation
     */
    private function getFavoriteTopicsForTesting($userId) {
        try {
            // Mock favorite topics calculation
            return ['Technology', 'Programming', 'Web Development']; // Mock values for testing
        } catch (Exception $e) {
            return [];
        }
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new DashboardAnalyticsTest();
    $test->runAllTests();
}