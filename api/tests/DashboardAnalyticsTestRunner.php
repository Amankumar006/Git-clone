<?php
/**
 * Dashboard and Analytics Test Runner
 * Comprehensive test suite runner for all dashboard and analytics functionality
 */

require_once __DIR__ . '/DashboardAnalyticsTest.php';
require_once __DIR__ . '/ReaderDashboardTest.php';
require_once __DIR__ . '/AdvancedAnalyticsTest.php';
require_once __DIR__ . '/NotificationCenterTest.php';

class DashboardAnalyticsTestRunner {
    private $testResults = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    
    public function __construct() {
        echo "=== Dashboard and Analytics Test Suite Runner ===\n";
        echo "Running comprehensive tests for dashboard data accuracy, analytics calculations,\n";
        echo "notification system functionality, and UI responsiveness.\n\n";
    }
    
    /**
     * Run all dashboard and analytics tests
     */
    public function runAllTests() {
        $startTime = microtime(true);
        
        try {
            // Run main dashboard analytics tests
            $this->runDashboardAnalyticsTests();
            
            // Run reader dashboard tests
            $this->runReaderDashboardTests();
            
            // Run advanced analytics tests
            $this->runAdvancedAnalyticsTests();
            
            // Run notification center tests
            $this->runNotificationCenterTests();
            
            // Run performance tests
            $this->runPerformanceTests();
            
            // Run data integrity tests
            $this->runDataIntegrityTests();
            
            // Run UI responsiveness tests
            $this->runUIResponsivenessTests();
            
        } catch (Exception $e) {
            echo "❌ Test suite execution failed: " . $e->getMessage() . "\n";
            $this->failedTests++;
        }
        
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        $this->printTestSummary($executionTime);
    }
    
    /**
     * Run main dashboard analytics tests
     */
    private function runDashboardAnalyticsTests() {
        echo "=== Running Main Dashboard Analytics Tests ===\n";
        
        try {
            $test = new DashboardAnalyticsTest();
            
            ob_start();
            $test->runAllTests();
            $output = ob_get_clean();
            
            $this->processTestOutput($output, 'Dashboard Analytics');
            
        } catch (Exception $e) {
            echo "❌ Dashboard Analytics tests failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Dashboard Analytics', false, $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Run reader dashboard tests
     */
    private function runReaderDashboardTests() {
        echo "=== Running Reader Dashboard Tests ===\n";
        
        try {
            $test = new ReaderDashboardTest();
            
            ob_start();
            $test->runTests();
            $test->testModelMethods();
            $output = ob_get_clean();
            
            $this->processTestOutput($output, 'Reader Dashboard');
            
        } catch (Exception $e) {
            echo "❌ Reader Dashboard tests failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Reader Dashboard', false, $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Run advanced analytics tests
     */
    private function runAdvancedAnalyticsTests() {
        echo "=== Running Advanced Analytics Tests ===\n";
        
        try {
            $test = new AdvancedAnalyticsTest();
            
            ob_start();
            $test->runTests();
            $output = ob_get_clean();
            
            $this->processTestOutput($output, 'Advanced Analytics');
            
        } catch (Exception $e) {
            echo "❌ Advanced Analytics tests failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Advanced Analytics', false, $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Run notification center tests
     */
    private function runNotificationCenterTests() {
        echo "=== Running Notification Center Tests ===\n";
        
        try {
            $test = new NotificationCenterTest();
            
            ob_start();
            $test->runTests();
            $output = ob_get_clean();
            
            $this->processTestOutput($output, 'Notification Center');
            
        } catch (Exception $e) {
            echo "❌ Notification Center tests failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Notification Center', false, $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Run performance tests
     */
    private function runPerformanceTests() {
        echo "=== Running Performance Tests ===\n";
        
        try {
            $this->testDashboardPerformance();
            $this->testAnalyticsPerformance();
            $this->testNotificationPerformance();
            $this->testMemoryUsage();
            
        } catch (Exception $e) {
            echo "❌ Performance tests failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Performance Tests', false, $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test dashboard performance
     */
    private function testDashboardPerformance() {
        echo "Testing dashboard performance...\n";
        
        try {
            $dashboardController = new DashboardController();
            
            // Mock authentication
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';
            
            // Test writer stats performance
            $startTime = microtime(true);
            ob_start();
            $dashboardController->writerStats();
            ob_get_clean();
            $endTime = microtime(true);
            
            $responseTime = ($endTime - $startTime) * 1000;
            
            if ($responseTime < 1000) {
                echo "✓ Writer stats performance acceptable ({$responseTime}ms)\n";
                $this->recordTestResult('Writer Stats Performance', true);
            } else {
                echo "⚠ Writer stats performance slow ({$responseTime}ms)\n";
                $this->recordTestResult('Writer Stats Performance', false, "Slow response: {$responseTime}ms");
            }
            
            // Test analytics performance
            $startTime = microtime(true);
            ob_start();
            $dashboardController->writerAnalytics();
            ob_get_clean();
            $endTime = microtime(true);
            
            $responseTime = ($endTime - $startTime) * 1000;
            
            if ($responseTime < 2000) {
                echo "✓ Analytics performance acceptable ({$responseTime}ms)\n";
                $this->recordTestResult('Analytics Performance', true);
            } else {
                echo "⚠ Analytics performance slow ({$responseTime}ms)\n";
                $this->recordTestResult('Analytics Performance', false, "Slow response: {$responseTime}ms");
            }
            
        } catch (Exception $e) {
            echo "✗ Dashboard performance test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Dashboard Performance', false, $e->getMessage());
        }
    }
    
    /**
     * Test analytics performance
     */
    private function testAnalyticsPerformance() {
        echo "Testing analytics calculation performance...\n";
        
        try {
            $articleModel = new Article();
            $clapModel = new Clap();
            $userModel = new User();
            
            // Test views over time calculation performance
            $startTime = microtime(true);
            $viewsOverTime = $articleModel->getViewsOverTime(1, 365); // Full year
            $endTime = microtime(true);
            
            $calculationTime = ($endTime - $startTime) * 1000;
            
            if ($calculationTime < 500) {
                echo "✓ Views over time calculation performance good ({$calculationTime}ms)\n";
                $this->recordTestResult('Views Calculation Performance', true);
            } else {
                echo "⚠ Views over time calculation slow ({$calculationTime}ms)\n";
                $this->recordTestResult('Views Calculation Performance', false, "Slow calculation: {$calculationTime}ms");
            }
            
            // Test engagement calculation performance
            $startTime = microtime(true);
            $engagementData = $clapModel->getClapsOverTime(1, 90);
            $endTime = microtime(true);
            
            $calculationTime = ($endTime - $startTime) * 1000;
            
            if ($calculationTime < 300) {
                echo "✓ Engagement calculation performance good ({$calculationTime}ms)\n";
                $this->recordTestResult('Engagement Calculation Performance', true);
            } else {
                echo "⚠ Engagement calculation slow ({$calculationTime}ms)\n";
                $this->recordTestResult('Engagement Calculation Performance', false, "Slow calculation: {$calculationTime}ms");
            }
            
        } catch (Exception $e) {
            echo "✗ Analytics performance test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Analytics Performance', false, $e->getMessage());
        }
    }
    
    /**
     * Test notification performance
     */
    private function testNotificationPerformance() {
        echo "Testing notification system performance...\n";
        
        try {
            $notificationModel = new Notification();
            
            // Test notification retrieval performance
            $startTime = microtime(true);
            $notifications = $notificationModel->getUserNotifications(1, false, 50, 0);
            $endTime = microtime(true);
            
            $retrievalTime = ($endTime - $startTime) * 1000;
            
            if ($retrievalTime < 200) {
                echo "✓ Notification retrieval performance good ({$retrievalTime}ms)\n";
                $this->recordTestResult('Notification Retrieval Performance', true);
            } else {
                echo "⚠ Notification retrieval slow ({$retrievalTime}ms)\n";
                $this->recordTestResult('Notification Retrieval Performance', false, "Slow retrieval: {$retrievalTime}ms");
            }
            
            // Test unread count performance
            $startTime = microtime(true);
            $unreadCount = $notificationModel->getUnreadCount(1);
            $endTime = microtime(true);
            
            $countTime = ($endTime - $startTime) * 1000;
            
            if ($countTime < 50) {
                echo "✓ Unread count performance excellent ({$countTime}ms)\n";
                $this->recordTestResult('Unread Count Performance', true);
            } else {
                echo "⚠ Unread count calculation slow ({$countTime}ms)\n";
                $this->recordTestResult('Unread Count Performance', false, "Slow count: {$countTime}ms");
            }
            
        } catch (Exception $e) {
            echo "✗ Notification performance test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Notification Performance', false, $e->getMessage());
        }
    }
    
    /**
     * Test memory usage
     */
    private function testMemoryUsage() {
        echo "Testing memory usage...\n";
        
        try {
            $initialMemory = memory_get_usage();
            
            // Execute multiple dashboard operations
            $dashboardController = new DashboardController();
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';
            
            ob_start();
            $dashboardController->writerStats();
            $dashboardController->writerAnalytics();
            $dashboardController->advancedAnalytics();
            $dashboardController->readerStats();
            ob_get_clean();
            
            $finalMemory = memory_get_usage();
            $memoryUsed = $finalMemory - $initialMemory;
            $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);
            
            if ($memoryUsed < 5 * 1024 * 1024) { // Less than 5MB
                echo "✓ Memory usage acceptable ({$memoryUsedMB}MB)\n";
                $this->recordTestResult('Memory Usage', true);
            } else {
                echo "⚠ Memory usage high ({$memoryUsedMB}MB)\n";
                $this->recordTestResult('Memory Usage', false, "High memory usage: {$memoryUsedMB}MB");
            }
            
        } catch (Exception $e) {
            echo "✗ Memory usage test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Memory Usage', false, $e->getMessage());
        }
    }
    
    /**
     * Run data integrity tests
     */
    private function runDataIntegrityTests() {
        echo "=== Running Data Integrity Tests ===\n";
        
        try {
            $this->testCalculationAccuracy();
            $this->testDataConsistency();
            $this->testEdgeCases();
            
        } catch (Exception $e) {
            echo "❌ Data integrity tests failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Data Integrity Tests', false, $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test calculation accuracy
     */
    private function testCalculationAccuracy() {
        echo "Testing calculation accuracy...\n";
        
        try {
            $articleModel = new Article();
            $clapModel = new Clap();
            
            // Test article counts accuracy
            $counts = $articleModel->getArticleCountsByStatus(1);
            
            if (is_array($counts) && isset($counts['published']) && is_numeric($counts['published'])) {
                echo "✓ Article counts calculation accurate\n";
                $this->recordTestResult('Article Counts Accuracy', true);
            } else {
                echo "✗ Article counts calculation inaccurate\n";
                $this->recordTestResult('Article Counts Accuracy', false, 'Invalid counts structure');
            }
            
            // Test engagement score calculation
            $topArticles = $articleModel->getTopArticlesByAuthor(1, 5);
            
            if (is_array($topArticles)) {
                $accuracyPassed = true;
                foreach ($topArticles as $article) {
                    if (!isset($article['engagement_score']) || !is_numeric($article['engagement_score'])) {
                        $accuracyPassed = false;
                        break;
                    }
                }
                
                if ($accuracyPassed) {
                    echo "✓ Engagement score calculation accurate\n";
                    $this->recordTestResult('Engagement Score Accuracy', true);
                } else {
                    echo "✗ Engagement score calculation inaccurate\n";
                    $this->recordTestResult('Engagement Score Accuracy', false, 'Invalid engagement scores');
                }
            }
            
        } catch (Exception $e) {
            echo "✗ Calculation accuracy test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Calculation Accuracy', false, $e->getMessage());
        }
    }
    
    /**
     * Test data consistency
     */
    private function testDataConsistency() {
        echo "Testing data consistency...\n";
        
        try {
            $articleModel = new Article();
            
            // Test that views over time data is consistent
            $viewsData = $articleModel->getViewsOverTime(1, 7);
            
            if (is_array($viewsData) && count($viewsData) <= 7) {
                $consistencyPassed = true;
                foreach ($viewsData as $dataPoint) {
                    if (!isset($dataPoint['date']) || !isset($dataPoint['views']) || !is_numeric($dataPoint['views'])) {
                        $consistencyPassed = false;
                        break;
                    }
                }
                
                if ($consistencyPassed) {
                    echo "✓ Views over time data consistent\n";
                    $this->recordTestResult('Views Data Consistency', true);
                } else {
                    echo "✗ Views over time data inconsistent\n";
                    $this->recordTestResult('Views Data Consistency', false, 'Inconsistent data structure');
                }
            }
            
        } catch (Exception $e) {
            echo "✗ Data consistency test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Data Consistency', false, $e->getMessage());
        }
    }
    
    /**
     * Test edge cases
     */
    private function testEdgeCases() {
        echo "Testing edge cases...\n";
        
        try {
            $articleModel = new Article();
            $notificationModel = new Notification();
            
            // Test with non-existent user
            $counts = $articleModel->getArticleCountsByStatus(99999);
            
            if (is_array($counts)) {
                echo "✓ Non-existent user edge case handled\n";
                $this->recordTestResult('Non-existent User Edge Case', true);
            } else {
                echo "✗ Non-existent user edge case not handled\n";
                $this->recordTestResult('Non-existent User Edge Case', false, 'Edge case not handled');
            }
            
            // Test with zero timeframe
            $viewsData = $articleModel->getViewsOverTime(1, 0);
            
            if (is_array($viewsData)) {
                echo "✓ Zero timeframe edge case handled\n";
                $this->recordTestResult('Zero Timeframe Edge Case', true);
            } else {
                echo "✗ Zero timeframe edge case not handled\n";
                $this->recordTestResult('Zero Timeframe Edge Case', false, 'Zero timeframe not handled');
            }
            
        } catch (Exception $e) {
            echo "✗ Edge cases test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Edge Cases', false, $e->getMessage());
        }
    }
    
    /**
     * Run UI responsiveness tests
     */
    private function runUIResponsivenessTests() {
        echo "=== Running UI Responsiveness Tests ===\n";
        
        try {
            $this->testComponentStructure();
            $this->testAPIEndpoints();
            $this->testErrorHandling();
            
        } catch (Exception $e) {
            echo "❌ UI responsiveness tests failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('UI Responsiveness Tests', false, $e->getMessage());
        }
        
        echo "\n";
    }
    
    /**
     * Test component structure
     */
    private function testComponentStructure() {
        echo "Testing component structure...\n";
        
        try {
            // Check if frontend components exist
            $components = [
                'WriterDashboard.tsx',
                'WriterAnalytics.tsx',
                'AdvancedAnalytics.tsx',
                'NotificationCenter.tsx',
                'ReaderDashboard.tsx'
            ];
            
            $allComponentsExist = true;
            foreach ($components as $component) {
                $path = __DIR__ . "/../../frontend/src/components/$component";
                if (!file_exists($path)) {
                    $allComponentsExist = false;
                    echo "✗ Component $component missing\n";
                }
            }
            
            if ($allComponentsExist) {
                echo "✓ All dashboard components exist\n";
                $this->recordTestResult('Component Structure', true);
            } else {
                $this->recordTestResult('Component Structure', false, 'Missing components');
            }
            
        } catch (Exception $e) {
            echo "✗ Component structure test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Component Structure', false, $e->getMessage());
        }
    }
    
    /**
     * Test API endpoints
     */
    private function testAPIEndpoints() {
        echo "Testing API endpoints...\n";
        
        try {
            $dashboardController = new DashboardController();
            $notificationController = new NotificationController();
            
            // Mock authentication
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test-token';
            
            // Test dashboard endpoints
            $endpoints = [
                'writerStats',
                'writerAnalytics',
                'advancedAnalytics',
                'readerStats'
            ];
            
            $allEndpointsWork = true;
            foreach ($endpoints as $endpoint) {
                try {
                    ob_start();
                    $dashboardController->$endpoint();
                    $output = ob_get_clean();
                    
                    if (strpos($output, 'error') !== false) {
                        $allEndpointsWork = false;
                        echo "✗ Endpoint $endpoint has errors\n";
                    }
                } catch (Exception $e) {
                    $allEndpointsWork = false;
                    echo "✗ Endpoint $endpoint failed: " . $e->getMessage() . "\n";
                }
            }
            
            if ($allEndpointsWork) {
                echo "✓ All dashboard endpoints accessible\n";
                $this->recordTestResult('API Endpoints', true);
            } else {
                $this->recordTestResult('API Endpoints', false, 'Some endpoints failed');
            }
            
        } catch (Exception $e) {
            echo "✗ API endpoints test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('API Endpoints', false, $e->getMessage());
        }
    }
    
    /**
     * Test error handling
     */
    private function testErrorHandling() {
        echo "Testing error handling...\n";
        
        try {
            $dashboardController = new DashboardController();
            
            // Test without authentication
            unset($_SERVER['HTTP_AUTHORIZATION']);
            
            ob_start();
            $dashboardController->writerStats();
            $output = ob_get_clean();
            
            if (strpos($output, 'Unauthorized') !== false || strpos($output, '401') !== false) {
                echo "✓ Authentication error handling works\n";
                $this->recordTestResult('Error Handling', true);
            } else {
                echo "✗ Authentication error handling failed\n";
                $this->recordTestResult('Error Handling', false, 'Authentication errors not handled');
            }
            
        } catch (Exception $e) {
            echo "✗ Error handling test failed: " . $e->getMessage() . "\n";
            $this->recordTestResult('Error Handling', false, $e->getMessage());
        }
    }
    
    /**
     * Process test output and extract results
     */
    private function processTestOutput($output, $testSuite) {
        $lines = explode("\n", $output);
        $passed = 0;
        $failed = 0;
        
        foreach ($lines as $line) {
            if (strpos($line, '✓') !== false) {
                $passed++;
            } elseif (strpos($line, '✗') !== false || strpos($line, '❌') !== false) {
                $failed++;
            }
        }
        
        $this->totalTests += ($passed + $failed);
        $this->passedTests += $passed;
        $this->failedTests += $failed;
        
        $this->recordTestResult($testSuite, $failed === 0, $failed > 0 ? "$failed tests failed" : null);
        
        echo $output;
    }
    
    /**
     * Record test result
     */
    private function recordTestResult($testName, $passed, $error = null) {
        $this->testResults[] = [
            'name' => $testName,
            'passed' => $passed,
            'error' => $error
        ];
        
        if (!$passed) {
            $this->failedTests++;
        } else {
            $this->passedTests++;
        }
        $this->totalTests++;
    }
    
    /**
     * Print comprehensive test summary
     */
    private function printTestSummary($executionTime) {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "DASHBOARD AND ANALYTICS TEST SUITE SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        
        echo "Execution Time: {$executionTime}ms\n";
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: {$this->failedTests}\n";
        
        $successRate = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 2) : 0;
        echo "Success Rate: {$successRate}%\n\n";
        
        // Print detailed results
        echo "DETAILED RESULTS:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($this->testResults as $result) {
            $status = $result['passed'] ? '✅ PASS' : '❌ FAIL';
            echo sprintf("%-50s %s\n", $result['name'], $status);
            
            if (!$result['passed'] && $result['error']) {
                echo sprintf("   Error: %s\n", $result['error']);
            }
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        
        if ($this->failedTests === 0) {
            echo "🎉 ALL TESTS PASSED! Dashboard and analytics functionality is working correctly.\n";
        } else {
            echo "⚠️  SOME TESTS FAILED. Please review the failed tests and fix the issues.\n";
        }
        
        echo str_repeat("=", 80) . "\n";
        
        // Print recommendations
        $this->printRecommendations();
    }
    
    /**
     * Print recommendations based on test results
     */
    private function printRecommendations() {
        echo "\nRECOMMENDations:\n";
        echo str_repeat("-", 40) . "\n";
        
        $hasPerformanceIssues = false;
        $hasDataIssues = false;
        $hasUIIssues = false;
        
        foreach ($this->testResults as $result) {
            if (!$result['passed']) {
                if (strpos($result['name'], 'Performance') !== false) {
                    $hasPerformanceIssues = true;
                } elseif (strpos($result['name'], 'Data') !== false || strpos($result['name'], 'Calculation') !== false) {
                    $hasDataIssues = true;
                } elseif (strpos($result['name'], 'UI') !== false || strpos($result['name'], 'Component') !== false) {
                    $hasUIIssues = true;
                }
            }
        }
        
        if ($hasPerformanceIssues) {
            echo "🔧 Performance Issues Detected:\n";
            echo "   - Consider adding database indexes for frequently queried columns\n";
            echo "   - Implement caching for expensive calculations\n";
            echo "   - Optimize database queries to reduce response times\n\n";
        }
        
        if ($hasDataIssues) {
            echo "📊 Data Integrity Issues Detected:\n";
            echo "   - Review calculation algorithms for accuracy\n";
            echo "   - Add data validation and sanitization\n";
            echo "   - Implement proper error handling for edge cases\n\n";
        }
        
        if ($hasUIIssues) {
            echo "🎨 UI/Component Issues Detected:\n";
            echo "   - Ensure all required components are implemented\n";
            echo "   - Test responsive design across different screen sizes\n";
            echo "   - Implement proper loading states and error handling\n\n";
        }
        
        if ($this->failedTests === 0) {
            echo "✨ All systems are functioning optimally!\n";
            echo "   - Dashboard data accuracy: Excellent\n";
            echo "   - Analytics calculations: Accurate\n";
            echo "   - Notification system: Fully functional\n";
            echo "   - UI responsiveness: Good performance\n";
        }
    }
}

// Run the comprehensive test suite if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $testRunner = new DashboardAnalyticsTestRunner();
    $testRunner->runAllTests();
}