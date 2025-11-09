<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/CacheService.php';
require_once __DIR__ . '/../utils/PerformanceMonitor.php';
require_once __DIR__ . '/../utils/ImageOptimizer.php';
require_once __DIR__ . '/../utils/SEOService.php';

class PerformanceTest {
    private $db;
    private $cache;
    private $testResults = [];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->cache = new CacheService();
    }
    
    /**
     * Run all performance tests
     */
    public function runAllTests() {
        echo "Starting Performance Tests...\n\n";
        
        $this->testDatabasePerformance();
        $this->testCachePerformance();
        $this->testImageOptimization();
        $this->testSEOGeneration();
        $this->testMemoryUsage();
        $this->testQueryOptimization();
        
        $this->printResults();
    }
    
    /**
     * Test database query performance
     */
    public function testDatabasePerformance() {
        echo "Testing Database Performance...\n";
        
        PerformanceMonitor::start();
        
        // Test simple query
        $startTime = microtime(true);
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM articles WHERE status = 'published'");
        $stmt->execute();
        $result = $stmt->fetch();
        $simpleQueryTime = (microtime(true) - $startTime) * 1000;
        
        // Test complex query with joins
        $startTime = microtime(true);
        $stmt = $this->db->prepare("
            SELECT a.*, u.username, COUNT(c.id) as comment_count, COUNT(cl.id) as clap_count
            FROM articles a
            LEFT JOIN users u ON a.author_id = u.id
            LEFT JOIN comments c ON a.id = c.article_id
            LEFT JOIN claps cl ON a.id = cl.article_id
            WHERE a.status = 'published'
            GROUP BY a.id
            ORDER BY a.published_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();
        $complexQueryTime = (microtime(true) - $startTime) * 1000;
        
        // Test pagination query
        $startTime = microtime(true);
        $stmt = $this->db->prepare("
            SELECT a.*, u.username
            FROM articles a
            JOIN users u ON a.author_id = u.id
            WHERE a.status = 'published'
            ORDER BY a.published_at DESC
            LIMIT 20 OFFSET 100
        ");
        $stmt->execute();
        $paginationResults = $stmt->fetchAll();
        $paginationQueryTime = (microtime(true) - $startTime) * 1000;
        
        $stats = PerformanceMonitor::end();
        
        $this->testResults['database'] = [
            'simple_query_time' => round($simpleQueryTime, 2) . 'ms',
            'complex_query_time' => round($complexQueryTime, 2) . 'ms',
            'pagination_query_time' => round($paginationQueryTime, 2) . 'ms',
            'total_execution_time' => $stats['execution_time'] . 'ms',
            'memory_used' => $stats['memory_usage']['used'],
            'query_count' => $stats['queries']['count'],
            'performance_acceptable' => $complexQueryTime < 100 && $paginationQueryTime < 50
        ];
        
        echo "  Simple query: " . round($simpleQueryTime, 2) . "ms\n";
        echo "  Complex query: " . round($complexQueryTime, 2) . "ms\n";
        echo "  Pagination query: " . round($paginationQueryTime, 2) . "ms\n";
        echo "  Memory used: " . $stats['memory_usage']['used'] . "\n\n";
    }
    
    /**
     * Test cache performance
     */
    public function testCachePerformance() {
        echo "Testing Cache Performance...\n";
        
        $testData = [
            'test_key_1' => ['data' => 'test_value_1', 'timestamp' => time()],
            'test_key_2' => ['data' => 'test_value_2', 'timestamp' => time()],
            'test_key_3' => ['data' => 'test_value_3', 'timestamp' => time()]
        ];
        
        // Test cache write performance
        $startTime = microtime(true);
        foreach ($testData as $key => $value) {
            $this->cache->set($key, $value);
        }
        $writeTime = (microtime(true) - $startTime) * 1000;
        
        // Test cache read performance
        $startTime = microtime(true);
        $readResults = [];
        foreach (array_keys($testData) as $key) {
            $readResults[$key] = $this->cache->get($key);
        }
        $readTime = (microtime(true) - $startTime) * 1000;
        
        // Test cache miss performance
        $startTime = microtime(true);
        $missResult = $this->cache->get('non_existent_key');
        $missTime = (microtime(true) - $startTime) * 1000;
        
        // Test cache remember function
        $startTime = microtime(true);
        $rememberResult = $this->cache->remember('expensive_operation', function() {
            // Simulate expensive operation
            usleep(10000); // 10ms
            return 'expensive_result';
        });
        $rememberTime = (microtime(true) - $startTime) * 1000;
        
        // Clean up test data
        foreach (array_keys($testData) as $key) {
            $this->cache->delete($key);
        }
        $this->cache->delete('expensive_operation');
        
        $this->testResults['cache'] = [
            'write_time' => round($writeTime, 2) . 'ms',
            'read_time' => round($readTime, 2) . 'ms',
            'miss_time' => round($missTime, 2) . 'ms',
            'remember_time' => round($rememberTime, 2) . 'ms',
            'data_integrity' => $readResults === $testData,
            'performance_acceptable' => $writeTime < 10 && $readTime < 5
        ];
        
        echo "  Cache write: " . round($writeTime, 2) . "ms\n";
        echo "  Cache read: " . round($readTime, 2) . "ms\n";
        echo "  Cache miss: " . round($missTime, 2) . "ms\n";
        echo "  Cache remember: " . round($rememberTime, 2) . "ms\n\n";
    }
    
    /**
     * Test image optimization performance
     */
    public function testImageOptimization() {
        echo "Testing Image Optimization...\n";
        
        // Create a test image (1x1 pixel PNG)
        $testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
        $testImagePath = sys_get_temp_dir() . '/test_image.png';
        file_put_contents($testImagePath, $testImageData);
        
        $optimizer = new ImageOptimizer();
        
        try {
            $startTime = microtime(true);
            $result = $optimizer->optimizeImage($testImagePath, null, [
                'max_width' => 800,
                'max_height' => 600,
                'quality' => 85
            ]);
            $optimizationTime = (microtime(true) - $startTime) * 1000;
            
            // Test thumbnail generation
            $startTime = microtime(true);
            $thumbnails = $optimizer->generateThumbnails($testImagePath, [
                'small' => ['width' => 150, 'height' => 150],
                'medium' => ['width' => 300, 'height' => 300]
            ]);
            $thumbnailTime = (microtime(true) - $startTime) * 1000;
            
            $this->testResults['image_optimization'] = [
                'optimization_time' => round($optimizationTime, 2) . 'ms',
                'thumbnail_generation_time' => round($thumbnailTime, 2) . 'ms',
                'original_size' => $result['original_size'] . ' bytes',
                'optimized_size' => $result['optimized_size'] . ' bytes',
                'compression_ratio' => $result['compression_ratio'] . '%',
                'thumbnails_generated' => count($thumbnails),
                'performance_acceptable' => $optimizationTime < 500
            ];
            
            echo "  Optimization time: " . round($optimizationTime, 2) . "ms\n";
            echo "  Thumbnail generation: " . round($thumbnailTime, 2) . "ms\n";
            echo "  Compression ratio: " . $result['compression_ratio'] . "%\n\n";
            
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n\n";
            $this->testResults['image_optimization'] = [
                'error' => $e->getMessage(),
                'performance_acceptable' => false
            ];
        } finally {
            // Clean up
            if (file_exists($testImagePath)) {
                unlink($testImagePath);
            }
        }
    }
    
    /**
     * Test SEO generation performance
     */
    public function testSEOGeneration() {
        echo "Testing SEO Generation...\n";
        
        $seoService = new SEOService();
        
        // Test sitemap generation
        $startTime = microtime(true);
        $sitemap = $seoService->generateSitemap();
        $sitemapTime = (microtime(true) - $startTime) * 1000;
        
        // Test robots.txt generation
        $startTime = microtime(true);
        $robots = $seoService->generateRobotsTxt();
        $robotsTime = (microtime(true) - $startTime) * 1000;
        
        // Test structured data generation
        $testArticle = [
            'id' => 1,
            'title' => 'Test Article',
            'subtitle' => 'Test subtitle',
            'content' => 'Test content for SEO generation',
            'username' => 'testuser',
            'published_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'slug' => 'test-article',
            'featured_image_url' => 'https://example.com/image.jpg'
        ];
        
        $startTime = microtime(true);
        $structuredData = $seoService->generateArticleStructuredData($testArticle);
        $structuredDataTime = (microtime(true) - $startTime) * 1000;
        
        $this->testResults['seo_generation'] = [
            'sitemap_generation_time' => round($sitemapTime, 2) . 'ms',
            'robots_generation_time' => round($robotsTime, 2) . 'ms',
            'structured_data_time' => round($structuredDataTime, 2) . 'ms',
            'sitemap_size' => strlen($sitemap) . ' bytes',
            'robots_size' => strlen($robots) . ' bytes',
            'structured_data_valid' => isset($structuredData['@context']) && isset($structuredData['@type']),
            'performance_acceptable' => $sitemapTime < 1000 && $structuredDataTime < 10
        ];
        
        echo "  Sitemap generation: " . round($sitemapTime, 2) . "ms\n";
        echo "  Robots.txt generation: " . round($robotsTime, 2) . "ms\n";
        echo "  Structured data: " . round($structuredDataTime, 2) . "ms\n";
        echo "  Sitemap size: " . strlen($sitemap) . " bytes\n\n";
    }
    
    /**
     * Test memory usage
     */
    public function testMemoryUsage() {
        echo "Testing Memory Usage...\n";
        
        $initialMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        // Simulate memory-intensive operation
        $largeArray = [];
        for ($i = 0; $i < 10000; $i++) {
            $largeArray[] = str_repeat('x', 100);
        }
        
        $afterOperationMemory = memory_get_usage(true);
        $memoryIncrease = $afterOperationMemory - $initialMemory;
        
        // Clean up
        unset($largeArray);
        $afterCleanupMemory = memory_get_usage(true);
        
        $this->testResults['memory_usage'] = [
            'initial_memory' => $this->formatBytes($initialMemory),
            'peak_memory' => $this->formatBytes($peakMemory),
            'after_operation' => $this->formatBytes($afterOperationMemory),
            'memory_increase' => $this->formatBytes($memoryIncrease),
            'after_cleanup' => $this->formatBytes($afterCleanupMemory),
            'memory_limit' => ini_get('memory_limit'),
            'memory_efficient' => $memoryIncrease < (1024 * 1024 * 5) // Less than 5MB increase
        ];
        
        echo "  Initial memory: " . $this->formatBytes($initialMemory) . "\n";
        echo "  Peak memory: " . $this->formatBytes($peakMemory) . "\n";
        echo "  Memory increase: " . $this->formatBytes($memoryIncrease) . "\n";
        echo "  After cleanup: " . $this->formatBytes($afterCleanupMemory) . "\n\n";
    }
    
    /**
     * Test query optimization with EXPLAIN
     */
    public function testQueryOptimization() {
        echo "Testing Query Optimization...\n";
        
        $queries = [
            'simple_select' => "SELECT * FROM articles WHERE status = 'published' LIMIT 10",
            'join_query' => "SELECT a.*, u.username FROM articles a JOIN users u ON a.author_id = u.id WHERE a.status = 'published' LIMIT 10",
            'complex_aggregation' => "SELECT COUNT(*) as total, AVG(view_count) as avg_views FROM articles WHERE status = 'published' AND published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ];
        
        $queryAnalysis = [];
        
        foreach ($queries as $name => $query) {
            try {
                $stmt = $this->db->prepare("EXPLAIN " . $query);
                $stmt->execute();
                $explanation = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $queryAnalysis[$name] = [
                    'query' => $query,
                    'explanation' => $explanation,
                    'uses_index' => $this->checkIndexUsage($explanation),
                    'estimated_rows' => $this->getEstimatedRows($explanation)
                ];
                
                echo "  {$name}: " . ($queryAnalysis[$name]['uses_index'] ? 'Uses index' : 'No index') . "\n";
                
            } catch (Exception $e) {
                echo "  Error analyzing {$name}: " . $e->getMessage() . "\n";
            }
        }
        
        $this->testResults['query_optimization'] = $queryAnalysis;
        echo "\n";
    }
    
    /**
     * Check if query uses index
     */
    private function checkIndexUsage($explanation) {
        foreach ($explanation as $row) {
            if (isset($row['key']) && $row['key'] !== null) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get estimated rows from EXPLAIN
     */
    private function getEstimatedRows($explanation) {
        $totalRows = 0;
        foreach ($explanation as $row) {
            if (isset($row['rows'])) {
                $totalRows += (int)$row['rows'];
            }
        }
        return $totalRows;
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Print test results
     */
    private function printResults() {
        echo "=== PERFORMANCE TEST RESULTS ===\n\n";
        
        foreach ($this->testResults as $category => $results) {
            echo strtoupper($category) . ":\n";
            foreach ($results as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? 'PASS' : 'FAIL';
                } elseif (is_array($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                echo "  {$key}: {$value}\n";
            }
            echo "\n";
        }
        
        // Overall performance summary
        $overallPass = true;
        $passCount = 0;
        $totalCount = 0;
        
        foreach ($this->testResults as $category => $results) {
            if (isset($results['performance_acceptable'])) {
                $totalCount++;
                if ($results['performance_acceptable']) {
                    $passCount++;
                } else {
                    $overallPass = false;
                }
            }
        }
        
        echo "OVERALL PERFORMANCE: " . ($overallPass ? 'PASS' : 'FAIL') . " ({$passCount}/{$totalCount})\n";
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new PerformanceTest();
    $test->runAllTests();
}