<?php

/**
 * Simple slug generation test without database dependency
 */
class SlugGenerationTest {
    
    /**
     * Create URL-friendly slug
     */
    private function createSlug($text) {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Generate SEO metadata
     */
    private function generateMetaDescription($content, $maxLength = 160) {
        if (is_array($content)) {
            $content = json_encode($content);
        }
        
        $plainText = strip_tags($content);
        
        if (strlen($plainText) <= $maxLength) {
            return $plainText;
        }
        
        $truncated = substr($plainText, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');
        
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            return substr($plainText, 0, $lastSpace) . '...';
        }
        
        return $truncated . '...';
    }

    public function testSlugGeneration() {
        echo "Testing slug generation...\n";
        
        $testCases = [
            'Hello World' => 'hello-world',
            'This is a Test!' => 'this-is-a-test',
            'Special Characters @#$%' => 'special-characters',
            'Multiple   Spaces' => 'multiple-spaces',
            'Article with Numbers 123' => 'article-with-numbers-123',
            'Punctuation: Test, Article!' => 'punctuation-test-article'
        ];
        
        $passed = 0;
        $total = count($testCases);
        
        foreach ($testCases as $input => $expected) {
            $result = $this->createSlug($input);
            if ($result === $expected) {
                echo "✓ '$input' -> '$result'\n";
                $passed++;
            } else {
                echo "✗ '$input' -> '$result' (expected: '$expected')\n";
            }
        }
        
        echo "\nSlug Generation: $passed/$total tests passed\n";
        return $passed === $total;
    }

    public function testMetaDescriptionGeneration() {
        echo "\nTesting meta description generation...\n";
        
        $testCases = [
            [
                'content' => 'This is a short description.',
                'expected_length' => 29,
                'should_truncate' => false
            ],
            [
                'content' => str_repeat('This is a very long description that should be truncated because it exceeds the maximum length limit. ', 3),
                'expected_length' => 160,
                'should_truncate' => true
            ]
        ];
        
        $passed = 0;
        $total = count($testCases);
        
        foreach ($testCases as $i => $testCase) {
            $result = $this->generateMetaDescription($testCase['content']);
            $actualLength = strlen($result);
            
            if ($testCase['should_truncate']) {
                if ($actualLength <= 163 && str_ends_with($result, '...')) { // 160 + "..."
                    echo "✓ Test " . ($i + 1) . ": Truncated correctly ($actualLength chars)\n";
                    $passed++;
                } else {
                    echo "✗ Test " . ($i + 1) . ": Failed to truncate properly ($actualLength chars)\n";
                }
            } else {
                if ($actualLength === $testCase['expected_length']) {
                    echo "✓ Test " . ($i + 1) . ": Length correct ($actualLength chars)\n";
                    $passed++;
                } else {
                    echo "✗ Test " . ($i + 1) . ": Length incorrect ($actualLength chars, expected {$testCase['expected_length']})\n";
                }
            }
        }
        
        echo "\nMeta Description: $passed/$total tests passed\n";
        return $passed === $total;
    }

    public function runAllTests() {
        echo "=== Article Publishing Workflow Unit Tests ===\n\n";
        
        $slugTest = $this->testSlugGeneration();
        $metaTest = $this->testMetaDescriptionGeneration();
        
        echo "\n=== Test Summary ===\n";
        echo "Slug Generation: " . ($slugTest ? "PASSED" : "FAILED") . "\n";
        echo "Meta Description: " . ($metaTest ? "PASSED" : "FAILED") . "\n";
        echo "Overall: " . ($slugTest && $metaTest ? "PASSED" : "FAILED") . "\n";
    }
}

// Run tests
$test = new SlugGenerationTest();
$test->runAllTests();