<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/SEOService.php';
require_once __DIR__ . '/../controllers/SEOController.php';

class SEOTest {
    private $seoService;
    private $testResults = [];
    
    public function __construct() {
        $this->seoService = new SEOService();
    }
    
    /**
     * Run all SEO tests
     */
    public function runAllTests() {
        echo "Starting SEO Tests...\n\n";
        
        $this->testSitemapGeneration();
        $this->testRobotsGeneration();
        $this->testStructuredDataGeneration();
        $this->testMetaDescriptionGeneration();
        $this->testOpenGraphGeneration();
        $this->testTwitterCardGeneration();
        $this->testCanonicalURLGeneration();
        $this->testSEOValidation();
        
        $this->printResults();
    }
    
    /**
     * Test sitemap generation
     */
    public function testSitemapGeneration() {
        echo "Testing Sitemap Generation...\n";
        
        try {
            $sitemap = $this->seoService->generateSitemap();
            
            // Validate XML structure
            $dom = new DOMDocument();
            $isValidXML = $dom->loadXML($sitemap);
            
            // Check for required elements
            $hasUrlset = strpos($sitemap, '<urlset') !== false;
            $hasNamespace = strpos($sitemap, 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"') !== false;
            $hasUrls = strpos($sitemap, '<url>') !== false;
            
            // Count URLs
            $urlCount = substr_count($sitemap, '<url>');
            
            // Check for required URL elements
            $hasLoc = strpos($sitemap, '<loc>') !== false;
            $hasLastmod = strpos($sitemap, '<lastmod>') !== false;
            $hasChangefreq = strpos($sitemap, '<changefreq>') !== false;
            $hasPriority = strpos($sitemap, '<priority>') !== false;
            
            $this->testResults['sitemap'] = [
                'valid_xml' => $isValidXML,
                'has_urlset' => $hasUrlset,
                'has_namespace' => $hasNamespace,
                'has_urls' => $hasUrls,
                'url_count' => $urlCount,
                'has_required_elements' => $hasLoc && $hasLastmod && $hasChangefreq && $hasPriority,
                'sitemap_size' => strlen($sitemap),
                'test_passed' => $isValidXML && $hasUrlset && $hasNamespace && $hasUrls && $urlCount > 0
            ];
            
            echo "  Valid XML: " . ($isValidXML ? 'YES' : 'NO') . "\n";
            echo "  URL count: {$urlCount}\n";
            echo "  Sitemap size: " . strlen($sitemap) . " bytes\n";
            echo "  Has required elements: " . ($hasLoc && $hasLastmod ? 'YES' : 'NO') . "\n\n";
            
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n\n";
            $this->testResults['sitemap'] = [
                'error' => $e->getMessage(),
                'test_passed' => false
            ];
        }
    }
    
    /**
     * Test robots.txt generation
     */
    public function testRobotsGeneration() {
        echo "Testing Robots.txt Generation...\n";
        
        try {
            $robots = $this->seoService->generateRobotsTxt();
            
            // Check for required directives
            $hasUserAgent = strpos($robots, 'User-agent:') !== false;
            $hasAllow = strpos($robots, 'Allow:') !== false;
            $hasDisallow = strpos($robots, 'Disallow:') !== false;
            $hasSitemap = strpos($robots, 'Sitemap:') !== false;
            
            // Check for security directives
            $blocksAPI = strpos($robots, 'Disallow: /api/') !== false;
            $blocksAdmin = strpos($robots, 'Disallow: /admin/') !== false;
            
            $this->testResults['robots'] = [
                'has_user_agent' => $hasUserAgent,
                'has_allow' => $hasAllow,
                'has_disallow' => $hasDisallow,
                'has_sitemap' => $hasSitemap,
                'blocks_api' => $blocksAPI,
                'blocks_admin' => $blocksAdmin,
                'robots_size' => strlen($robots),
                'test_passed' => $hasUserAgent && $hasDisallow && $hasSitemap && $blocksAPI
            ];
            
            echo "  Has User-agent: " . ($hasUserAgent ? 'YES' : 'NO') . "\n";
            echo "  Has Sitemap: " . ($hasSitemap ? 'YES' : 'NO') . "\n";
            echo "  Blocks API: " . ($blocksAPI ? 'YES' : 'NO') . "\n";
            echo "  Size: " . strlen($robots) . " bytes\n\n";
            
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n\n";
            $this->testResults['robots'] = [
                'error' => $e->getMessage(),
                'test_passed' => false
            ];
        }
    }
    
    /**
     * Test structured data generation
     */
    public function testStructuredDataGeneration() {
        echo "Testing Structured Data Generation...\n";
        
        $testArticle = [
            'id' => 1,
            'title' => 'Test Article for SEO',
            'subtitle' => 'This is a test article subtitle',
            'content' => '<p>This is the content of the test article. It contains multiple sentences to test meta description generation. The content should be properly processed for SEO purposes.</p>',
            'username' => 'testuser',
            'published_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 12:00:00',
            'slug' => 'test-article-for-seo',
            'featured_image_url' => 'https://example.com/test-image.jpg',
            'reading_time' => 5,
            'tags' => json_encode([
                ['name' => 'test'],
                ['name' => 'seo'],
                ['name' => 'article']
            ])
        ];
        
        try {
            $structuredData = $this->seoService->generateArticleStructuredData($testArticle);
            
            // Validate JSON-LD structure
            $isValidJSON = is_array($structuredData);
            $hasContext = isset($structuredData['@context']) && $structuredData['@context'] === 'https://schema.org';
            $hasType = isset($structuredData['@type']) && $structuredData['@type'] === 'Article';
            $hasHeadline = isset($structuredData['headline']);
            $hasAuthor = isset($structuredData['author']) && isset($structuredData['author']['@type']);
            $hasPublisher = isset($structuredData['publisher']) && isset($structuredData['publisher']['@type']);
            $hasDatePublished = isset($structuredData['datePublished']);
            $hasMainEntity = isset($structuredData['mainEntityOfPage']);
            
            // Test JSON encoding
            $jsonString = json_encode($structuredData);
            $isValidJSONString = json_last_error() === JSON_ERROR_NONE;
            
            $this->testResults['structured_data'] = [
                'valid_json' => $isValidJSON,
                'has_context' => $hasContext,
                'has_type' => $hasType,
                'has_headline' => $hasHeadline,
                'has_author' => $hasAuthor,
                'has_publisher' => $hasPublisher,
                'has_date_published' => $hasDatePublished,
                'has_main_entity' => $hasMainEntity,
                'valid_json_string' => $isValidJSONString,
                'json_size' => strlen($jsonString),
                'test_passed' => $isValidJSON && $hasContext && $hasType && $hasHeadline && $hasAuthor
            ];
            
            echo "  Valid JSON-LD: " . ($isValidJSON ? 'YES' : 'NO') . "\n";
            echo "  Has required fields: " . ($hasContext && $hasType && $hasHeadline ? 'YES' : 'NO') . "\n";
            echo "  JSON size: " . strlen($jsonString) . " bytes\n\n";
            
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n\n";
            $this->testResults['structured_data'] = [
                'error' => $e->getMessage(),
                'test_passed' => false
            ];
        }
    }
    
    /**
     * Test meta description generation
     */
    public function testMetaDescriptionGeneration() {
        echo "Testing Meta Description Generation...\n";
        
        $testCases = [
            'short_text' => 'Short content.',
            'long_text' => 'This is a very long piece of content that should be truncated properly to fit within the meta description character limit. It contains multiple sentences and should be handled gracefully.',
            'html_content' => '<p>This is <strong>HTML</strong> content with <a href="#">links</a> and <em>formatting</em>.</p>',
            'sentence_boundary' => 'First sentence. Second sentence that might be cut off. Third sentence.',
            'no_punctuation' => 'This is content without proper punctuation that goes on and on without any sentence breaks'
        ];
        
        $results = [];
        
        foreach ($testCases as $name => $content) {
            $description = $this->seoService->generateMetaDescription($content, 160);
            
            $results[$name] = [
                'original_length' => strlen($content),
                'description_length' => strlen($description),
                'within_limit' => strlen($description) <= 160,
                'not_empty' => !empty($description),
                'no_html_tags' => strip_tags($description) === $description,
                'description' => $description
            ];
            
            echo "  {$name}: " . strlen($description) . " chars - " . 
                 (strlen($description) <= 160 ? 'PASS' : 'FAIL') . "\n";
        }
        
        $allPassed = true;
        foreach ($results as $result) {
            if (!$result['within_limit'] || !$result['not_empty'] || !$result['no_html_tags']) {
                $allPassed = false;
                break;
            }
        }
        
        $this->testResults['meta_description'] = [
            'test_cases' => $results,
            'all_passed' => $allPassed,
            'test_passed' => $allPassed
        ];
        
        echo "\n";
    }
    
    /**
     * Test Open Graph data generation
     */
    public function testOpenGraphGeneration() {
        echo "Testing Open Graph Generation...\n";
        
        $testArticle = [
            'id' => 1,
            'title' => 'Test Article',
            'subtitle' => 'Test subtitle',
            'content' => 'Test content',
            'username' => 'testuser',
            'published_at' => '2023-01-01 12:00:00',
            'updated_at' => '2023-01-01 12:00:00',
            'slug' => 'test-article',
            'featured_image_url' => 'https://example.com/image.jpg'
        ];
        
        try {
            $ogData = $this->seoService->generateOpenGraphData($testArticle);
            
            $requiredFields = ['og:title', 'og:description', 'og:type', 'og:url', 'og:image'];
            $hasAllRequired = true;
            
            foreach ($requiredFields as $field) {
                if (!isset($ogData[$field]) || empty($ogData[$field])) {
                    $hasAllRequired = false;
                    break;
                }
            }
            
            $hasArticleFields = isset($ogData['article:author']) && isset($ogData['article:published_time']);
            
            $this->testResults['open_graph'] = [
                'has_all_required' => $hasAllRequired,
                'has_article_fields' => $hasArticleFields,
                'field_count' => count($ogData),
                'og_type_correct' => $ogData['og:type'] === 'article',
                'test_passed' => $hasAllRequired && $hasArticleFields
            ];
            
            echo "  Required fields: " . ($hasAllRequired ? 'YES' : 'NO') . "\n";
            echo "  Article fields: " . ($hasArticleFields ? 'YES' : 'NO') . "\n";
            echo "  Field count: " . count($ogData) . "\n\n";
            
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n\n";
            $this->testResults['open_graph'] = [
                'error' => $e->getMessage(),
                'test_passed' => false
            ];
        }
    }
    
    /**
     * Test Twitter Card generation
     */
    public function testTwitterCardGeneration() {
        echo "Testing Twitter Card Generation...\n";
        
        $testArticle = [
            'title' => 'Test Article',
            'subtitle' => 'Test subtitle',
            'username' => 'testuser',
            'featured_image_url' => 'https://example.com/image.jpg'
        ];
        
        try {
            $twitterData = $this->seoService->generateTwitterCardData($testArticle);
            
            $requiredFields = ['twitter:card', 'twitter:title', 'twitter:description', 'twitter:image'];
            $hasAllRequired = true;
            
            foreach ($requiredFields as $field) {
                if (!isset($twitterData[$field]) || empty($twitterData[$field])) {
                    $hasAllRequired = false;
                    break;
                }
            }
            
            $correctCardType = $twitterData['twitter:card'] === 'summary_large_image';
            
            $this->testResults['twitter_card'] = [
                'has_all_required' => $hasAllRequired,
                'correct_card_type' => $correctCardType,
                'field_count' => count($twitterData),
                'test_passed' => $hasAllRequired && $correctCardType
            ];
            
            echo "  Required fields: " . ($hasAllRequired ? 'YES' : 'NO') . "\n";
            echo "  Correct card type: " . ($correctCardType ? 'YES' : 'NO') . "\n";
            echo "  Field count: " . count($twitterData) . "\n\n";
            
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n\n";
            $this->testResults['twitter_card'] = [
                'error' => $e->getMessage(),
                'test_passed' => false
            ];
        }
    }
    
    /**
     * Test canonical URL generation
     */
    public function testCanonicalURLGeneration() {
        echo "Testing Canonical URL Generation...\n";
        
        $testArticle = [
            'slug' => 'test-article-slug'
        ];
        
        try {
            $canonicalUrl = $this->seoService->getCanonicalUrl($testArticle);
            
            $isValidUrl = filter_var($canonicalUrl, FILTER_VALIDATE_URL) !== false;
            $hasSlug = strpos($canonicalUrl, $testArticle['slug']) !== false;
            $isHttps = strpos($canonicalUrl, 'https://') === 0 || strpos($canonicalUrl, 'http://') === 0;
            
            $this->testResults['canonical_url'] = [
                'valid_url' => $isValidUrl,
                'has_slug' => $hasSlug,
                'has_protocol' => $isHttps,
                'url' => $canonicalUrl,
                'test_passed' => $isValidUrl && $hasSlug && $isHttps
            ];
            
            echo "  Valid URL: " . ($isValidUrl ? 'YES' : 'NO') . "\n";
            echo "  Has slug: " . ($hasSlug ? 'YES' : 'NO') . "\n";
            echo "  URL: {$canonicalUrl}\n\n";
            
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n\n";
            $this->testResults['canonical_url'] = [
                'error' => $e->getMessage(),
                'test_passed' => false
            ];
        }
    }
    
    /**
     * Test SEO validation
     */
    public function testSEOValidation() {
        echo "Testing SEO Validation...\n";
        
        // Test title length validation
        $shortTitle = 'Short';
        $goodTitle = 'This is a Good SEO Title That is Not Too Long';
        $longTitle = 'This is a Very Long Title That Exceeds the Recommended Length for SEO Purposes and Should Be Flagged as Too Long';
        
        $titleTests = [
            'short' => strlen($shortTitle) >= 10 && strlen($shortTitle) <= 60,
            'good' => strlen($goodTitle) >= 10 && strlen($goodTitle) <= 60,
            'long' => strlen($longTitle) >= 10 && strlen($longTitle) <= 60
        ];
        
        // Test meta description length
        $shortDesc = 'Short description.';
        $goodDesc = 'This is a good meta description that provides a clear summary of the content and is within the recommended length for search engines.';
        $longDesc = str_repeat('This is a very long meta description that exceeds the recommended length. ', 5);
        
        $descTests = [
            'short' => strlen($shortDesc) >= 120 && strlen($shortDesc) <= 160,
            'good' => strlen($goodDesc) >= 120 && strlen($goodDesc) <= 160,
            'long' => strlen($longDesc) >= 120 && strlen($longDesc) <= 160
        ];
        
        // Test slug validation
        $goodSlug = 'this-is-a-good-slug';
        $badSlug = 'This Is A Bad Slug!@#$';
        
        $slugTests = [
            'good' => preg_match('/^[a-z0-9-]+$/', $goodSlug),
            'bad' => preg_match('/^[a-z0-9-]+$/', $badSlug)
        ];
        
        $this->testResults['seo_validation'] = [
            'title_tests' => $titleTests,
            'description_tests' => $descTests,
            'slug_tests' => $slugTests,
            'test_passed' => $titleTests['good'] && $descTests['good'] && $slugTests['good'] && !$slugTests['bad']
        ];
        
        echo "  Good title length: " . ($titleTests['good'] ? 'PASS' : 'FAIL') . "\n";
        echo "  Good description length: " . ($descTests['good'] ? 'PASS' : 'FAIL') . "\n";
        echo "  Good slug format: " . ($slugTests['good'] ? 'PASS' : 'FAIL') . "\n";
        echo "  Bad slug rejected: " . (!$slugTests['bad'] ? 'PASS' : 'FAIL') . "\n\n";
    }
    
    /**
     * Print test results
     */
    private function printResults() {
        echo "=== SEO TEST RESULTS ===\n\n";
        
        $totalTests = 0;
        $passedTests = 0;
        
        foreach ($this->testResults as $category => $results) {
            echo strtoupper($category) . ": ";
            
            if (isset($results['test_passed'])) {
                $totalTests++;
                if ($results['test_passed']) {
                    echo "PASS\n";
                    $passedTests++;
                } else {
                    echo "FAIL\n";
                }
            } else {
                echo "NO RESULT\n";
            }
            
            // Show key metrics
            foreach ($results as $key => $value) {
                if ($key !== 'test_passed' && !is_array($value) && !is_object($value)) {
                    if (is_bool($value)) {
                        $value = $value ? 'YES' : 'NO';
                    }
                    echo "  {$key}: {$value}\n";
                }
            }
            echo "\n";
        }
        
        echo "OVERALL SEO COMPLIANCE: " . ($passedTests === $totalTests ? 'PASS' : 'FAIL') . 
             " ({$passedTests}/{$totalTests})\n";
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new SEOTest();
    $test->runAllTests();
}