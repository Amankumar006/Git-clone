<?php

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../controllers/ArticleController.php';

/**
 * Test the article publishing workflow
 */
class PublishingWorkflowTest {
    private $article;
    private $controller;

    public function __construct() {
        $this->article = new Article();
        $this->controller = new ArticleController();
    }

    public function testSlugGeneration() {
        echo "Testing slug generation...\n";
        
        // Test basic slug generation
        $reflection = new ReflectionClass($this->article);
        $method = $reflection->getMethod('createSlug');
        $method->setAccessible(true);
        
        $testCases = [
            'Hello World' => 'hello-world',
            'This is a Test!' => 'this-is-a-test',
            'Special Characters @#$%' => 'special-characters',
            'Multiple   Spaces' => 'multiple-spaces'
        ];
        
        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($this->article, $input);
            if ($result === $expected) {
                echo "✓ Slug generation test passed for: '$input'\n";
            } else {
                echo "✗ Slug generation test failed for: '$input'. Expected: '$expected', Got: '$result'\n";
            }
        }
    }

    public function testSEOMetadataGeneration() {
        echo "\nTesting SEO metadata generation...\n";
        
        $sampleArticle = [
            'id' => 1,
            'title' => 'Test Article Title',
            'subtitle' => 'This is a test subtitle',
            'content' => json_encode([
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'This is test content for the article.']]]
            ]),
            'tags' => ['test', 'article', 'seo'],
            'featured_image_url' => 'https://example.com/image.jpg',
            'username' => 'testuser',
            'published_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00',
            'slug' => 'test-article-title'
        ];
        
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('generateSEOMetadata');
        $method->setAccessible(true);
        
        try {
            $seoData = $method->invoke($this->controller, $sampleArticle);
            
            if (isset($seoData['title']) && isset($seoData['description']) && isset($seoData['og_title'])) {
                echo "✓ SEO metadata generation test passed\n";
                echo "  - Title: " . $seoData['title'] . "\n";
                echo "  - Description: " . substr($seoData['description'], 0, 50) . "...\n";
            } else {
                echo "✗ SEO metadata generation test failed - missing required fields\n";
            }
        } catch (Exception $e) {
            echo "✗ SEO metadata generation test failed: " . $e->getMessage() . "\n";
        }
    }

    public function testReadingTimeCalculation() {
        echo "\nTesting reading time calculation...\n";
        
        $testContent = str_repeat('This is a test sentence with exactly ten words. ', 50); // 500 words
        
        $readingTime = $this->article->calculateReadingTime($testContent);
        
        // Should be around 2-3 minutes for 500 words (225 words per minute)
        if ($readingTime >= 2 && $readingTime <= 3) {
            echo "✓ Reading time calculation test passed: $readingTime minutes for ~500 words\n";
        } else {
            echo "✗ Reading time calculation test failed: $readingTime minutes for ~500 words (expected 2-3)\n";
        }
    }

    public function runAllTests() {
        echo "=== Article Publishing Workflow Tests ===\n\n";
        
        $this->testSlugGeneration();
        $this->testSEOMetadataGeneration();
        $this->testReadingTimeCalculation();
        
        echo "\n=== Tests Completed ===\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new PublishingWorkflowTest();
    $test->runAllTests();
}