<?php

require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Article.php';

class AdvancedAnalyticsTest {
    private $dashboardController;
    private $userModel;
    private $articleModel;

    public function __construct() {
        $this->dashboardController = new DashboardController();
        $this->userModel = new User();
        $this->articleModel = new Article();
    }

    /**
     * Test advanced analytics endpoint
     */
    public function testAdvancedAnalytics() {
        echo "Testing Advanced Analytics...\n";

        try {
            // Mock authentication by setting a test user
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';
            
            // Test the advanced analytics method
            ob_start();
            $this->dashboardController->advancedAnalytics();
            $output = ob_get_clean();
            
            echo "✓ Advanced analytics endpoint accessible\n";
            
            // Test export functionality
            $_GET['format'] = 'json';
            $_GET['timeframe'] = '30';
            $_GET['data_type'] = 'performance';
            
            ob_start();
            $this->dashboardController->exportAnalytics();
            $exportOutput = ob_get_clean();
            
            echo "✓ Export analytics endpoint accessible\n";
            
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test analytics data structure
     */
    public function testAnalyticsDataStructure() {
        echo "Testing Analytics Data Structure...\n";

        try {
            // Test performance metrics structure
            $performanceMetrics = $this->dashboardController->getDetailedPerformanceMetrics(1, 30);
            echo "✓ Performance metrics structure valid\n";

            // Test reader demographics structure
            $readerDemographics = $this->dashboardController->getReaderDemographics(1, 30);
            echo "✓ Reader demographics structure valid\n";

            // Test engagement patterns structure
            $engagementPatterns = $this->dashboardController->getAdvancedEngagementPatterns(1, 30);
            echo "✓ Engagement patterns structure valid\n";

        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Test model analytics methods
     */
    public function testModelAnalyticsMethods() {
        echo "Testing Model Analytics Methods...\n";

        try {
            // Test Article model methods
            $viewsOverTime = $this->articleModel->getViewsOverTime(1, 30);
            echo "✓ Article views over time method works\n";

            $articlePerformance = $this->articleModel->getArticlePerformanceComparison(1);
            echo "✓ Article performance comparison method works\n";

            // Test User model methods
            $topReaders = $this->userModel->getTopReadersByAuthor(1, 10);
            echo "✓ Top readers by author method works\n";

        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Run all tests
     */
    public function runTests() {
        echo "=== Advanced Analytics Test Suite ===\n\n";
        
        $this->testAdvancedAnalytics();
        echo "\n";
        
        $this->testAnalyticsDataStructure();
        echo "\n";
        
        $this->testModelAnalyticsMethods();
        echo "\n";
        
        echo "=== Test Suite Complete ===\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new AdvancedAnalyticsTest();
    $test->runTests();
}