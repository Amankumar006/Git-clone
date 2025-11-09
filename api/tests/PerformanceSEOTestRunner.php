<?php

require_once __DIR__ . '/PerformanceTest.php';
require_once __DIR__ . '/SEOTest.php';

class PerformanceSEOTestRunner {
    private $results = [];
    
    public function runAllTests() {
        echo "=== MEDIUM CLONE PERFORMANCE & SEO TEST SUITE ===\n";
        echo "Starting comprehensive testing...\n\n";
        
        $startTime = microtime(true);
        
        // Run Performance Tests
        echo "1. PERFORMANCE TESTS\n";
        echo str_repeat("=", 50) . "\n";
        $performanceTest = new PerformanceTest();
        ob_start();
        $performanceTest->runAllTests();
        $performanceOutput = ob_get_clean();
        echo $performanceOutput;
        
        // Run SEO Tests
        echo "\n2. SEO TESTS\n";
        echo str_repeat("=", 50) . "\n";
        $seoTest = new SEOTest();
        ob_start();
        $seoTest->runAllTests();
        $seoOutput = ob_get_clean();
        echo $seoOutput;
        
        // Additional Integration Tests
        echo "\n3. INTEGRATION TESTS\n";
        echo str_repeat("=", 50) . "\n";
        $this->runIntegrationTests();
        
        $totalTime = microtime(true) - $startTime;
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "TOTAL TEST EXECUTION TIME: " . round($totalTime, 2) . " seconds\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->generateTestReport();
    }
    
    /**
     * Run integration tests
     */
    private function runIntegrationTests() {
        echo "Testing Performance & SEO Integration...\n";
        
        // Test sitemap performance under load
        $this->testSitemapPerformanceUnderLoad();
        
        // Test SEO metadata generation performance
        $this->testSEOMetadataPerformance();
        
        // Test image optimization with SEO
        $this->testImageOptimizationSEO();
        
        // Test caching with SEO data
        $this->testSEOCaching();
    }
    
    /**
     * Test sitemap performance under load
     */
    private function testSitemapPerformanceUnderLoad() {
        echo "  Testing sitemap generation under load...\n";
        
        $seoService = new SEOService();
        $times = [];
        
        // Generate sitemap multiple times to test consistency
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            $sitemap = $seoService->generateSitemap();
            $endTime = microtime(true);
            $times[] = ($endTime - $startTime) * 1000;
        }
        
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        
        $this->results['sitemap_load_test'] = [
            'average_time' => round($avgTime, 2) . 'ms',
            'max_time' => round($maxTime, 2) . 'ms',
            'min_time' => round($minTime, 2) . 'ms',
            'consistent_performance' => ($maxTime - $minTime) < 100, // Less than 100ms variance
            'acceptable_performance' => $avgTime < 500 // Less than 500ms average
        ];
        
        echo "    Average time: " . round($avgTime, 2) . "ms\n";
        echo "    Performance consistent: " . (($maxTime - $minTime) < 100 ? 'YES' : 'NO') . "\n";
    }
    
    /**
     * Test SEO metadata generation performance
     */
    private function testSEOMetadataPerformance() {
        echo "  Testing SEO metadata generation performance...\n";
        
        $seoService = new SEOService();
        $testArticles = [];
        
        // Create test articles with varying content sizes
        for ($i = 0; $i < 10; $i++) {
            $testArticles[] = [
                'id' => $i + 1,
                'title' => "Test Article {$i}",
                'subtitle' => "Subtitle for test article {$i}",
                'content' => str_repeat("This is test content. ", rand(50, 500)),
                'username' => "user{$i}",
                'published_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'slug' => "test-article-{$i}",
                'featured_image_url' => "https://example.com/image{$i}.jpg"
            ];
        }
        
        $startTime = microtime(true);
        $metadataResults = [];
        
        foreach ($testArticles as $article) {
            $structuredData = $seoService->generateArticleStructuredData($article);
            $openGraph = $seoService->generateOpenGraphData($article);
            $twitterCard = $seoService->generateTwitterCardData($article);
            
            $metadataResults[] = [
                'structured_data' => $structuredData,
                'open_graph' => $openGraph,
                'twitter_card' => $twitterCard
            ];
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        $avgTimePerArticle = $totalTime / count($testArticles);
        
        $this->results['metadata_performance'] = [
            'total_time' => round($totalTime, 2) . 'ms',
            'average_per_article' => round($avgTimePerArticle, 2) . 'ms',
            'articles_processed' => count($testArticles),
            'acceptable_performance' => $avgTimePerArticle < 50 // Less than 50ms per article
        ];
        
        echo "    Total time: " . round($totalTime, 2) . "ms\n";
        echo "    Average per article: " . round($avgTimePerArticle, 2) . "ms\n";
    }
    
    /**
     * Test image optimization with SEO
     */
    private function testImageOptimizationSEO() {
        echo "  Testing image optimization with SEO integration...\n";
        
        // Create a test image
        $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $testImagePath = sys_get_temp_dir() . '/seo_test_image.png';
        file_put_contents($testImagePath, $testImageData);
        
        try {
            $optimizer = new ImageOptimizer();
            
            $startTime = microtime(true);
            
            // Optimize image
            $optimizationResult = $optimizer->optimizeImage($testImagePath);
            
            // Generate responsive images for SEO
            $srcSet = $optimizer->generateSrcSet($testImagePath, [480, 768, 1024]);
            
            $totalTime = (microtime(true) - $startTime) * 1000;
            
            $this->results['image_seo_integration'] = [
                'optimization_time' => round($totalTime, 2) . 'ms',
                'srcset_generated' => !empty($srcSet),
                'compression_achieved' => $optimizationResult['compression_ratio'] > 0,
                'acceptable_performance' => $totalTime < 1000 // Less than 1 second
            ];
            
            echo "    Optimization time: " . round($totalTime, 2) . "ms\n";
            echo "    SrcSet generated: " . (!empty($srcSet) ? 'YES' : 'NO') . "\n";
            
        } catch (Exception $e) {
            echo "    Error: " . $e->getMessage() . "\n";
            $this->results['image_seo_integration'] = [
                'error' => $e->getMessage(),
                'acceptable_performance' => false
            ];
        } finally {
            if (file_exists($testImagePath)) {
                unlink($testImagePath);
            }
        }
    }
    
    /**
     * Test SEO data caching
     */
    private function testSEOCaching() {
        echo "  Testing SEO data caching...\n";
        
        $cache = new CacheService();
        $seoService = new SEOService();
        
        $testArticle = [
            'id' => 999,
            'title' => 'Cache Test Article',
            'content' => 'Test content for caching',
            'username' => 'cacheuser',
            'published_at' => date('Y-m-d H:i:s'),
            'slug' => 'cache-test-article'
        ];
        
        $cacheKey = 'seo_metadata_' . $testArticle['id'];
        
        // Test cache miss (first generation)
        $startTime = microtime(true);
        $metadata = $seoService->generateArticleStructuredData($testArticle);
        $cache->set($cacheKey, $metadata, 3600);
        $missTime = (microtime(true) - $startTime) * 1000;
        
        // Test cache hit
        $startTime = microtime(true);
        $cachedMetadata = $cache->get($cacheKey);
        $hitTime = (microtime(true) - $startTime) * 1000;
        
        // Verify data integrity
        $dataIntegrity = $metadata === $cachedMetadata;
        
        // Clean up
        $cache->delete($cacheKey);
        
        $this->results['seo_caching'] = [
            'cache_miss_time' => round($missTime, 2) . 'ms',
            'cache_hit_time' => round($hitTime, 2) . 'ms',
            'performance_improvement' => round((($missTime - $hitTime) / $missTime) * 100, 1) . '%',
            'data_integrity' => $dataIntegrity,
            'cache_effective' => $hitTime < ($missTime * 0.1) // Cache should be at least 10x faster
        ];
        
        echo "    Cache miss: " . round($missTime, 2) . "ms\n";
        echo "    Cache hit: " . round($hitTime, 2) . "ms\n";
        echo "    Performance improvement: " . round((($missTime - $hitTime) / $missTime) * 100, 1) . "%\n";
    }
    
    /**
     * Generate comprehensive test report
     */
    private function generateTestReport() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "COMPREHENSIVE TEST REPORT\n";
        echo str_repeat("=", 60) . "\n";
        
        $totalTests = 0;
        $passedTests = 0;
        
        foreach ($this->results as $testName => $result) {
            $totalTests++;
            $testPassed = true;
            
            // Check if test passed based on performance criteria
            foreach ($result as $key => $value) {
                if (strpos($key, 'acceptable') !== false || strpos($key, 'effective') !== false) {
                    if (!$value) {
                        $testPassed = false;
                        break;
                    }
                }
            }
            
            if ($testPassed) {
                $passedTests++;
            }
            
            echo strtoupper(str_replace('_', ' ', $testName)) . ": " . 
                 ($testPassed ? 'PASS' : 'FAIL') . "\n";
            
            foreach ($result as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? 'YES' : 'NO';
                }
                echo "  " . str_replace('_', ' ', $key) . ": {$value}\n";
            }
            echo "\n";
        }
        
        // Performance recommendations
        echo "PERFORMANCE RECOMMENDATIONS:\n";
        echo str_repeat("-", 30) . "\n";
        
        if (isset($this->results['sitemap_load_test']) && 
            !$this->results['sitemap_load_test']['acceptable_performance']) {
            echo "- Consider implementing sitemap caching\n";
        }
        
        if (isset($this->results['metadata_performance']) && 
            !$this->results['metadata_performance']['acceptable_performance']) {
            echo "- Optimize SEO metadata generation algorithms\n";
        }
        
        if (isset($this->results['image_seo_integration']) && 
            !$this->results['image_seo_integration']['acceptable_performance']) {
            echo "- Consider background image processing\n";
        }
        
        if (isset($this->results['seo_caching']) && 
            !$this->results['seo_caching']['cache_effective']) {
            echo "- Improve caching strategy for SEO data\n";
        }
        
        echo "\nOVERALL INTEGRATION TEST RESULT: " . 
             ($passedTests === $totalTests ? 'PASS' : 'FAIL') . 
             " ({$passedTests}/{$totalTests})\n";
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $testRunner = new PerformanceSEOTestRunner();
    $testRunner->runAllTests();
}