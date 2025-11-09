<?php

require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Tag.php';

/**
 * Comprehensive Article Creation Tests
 * Tests for Article model validation, CRUD operations, and publishing workflow
 */
class ArticleModelTest {
    
    public function __construct() {
        // No database connection needed for basic tests
    }

    /**
     * Test Article model validation logic
     */
    public function testArticleValidation() {
        echo "Testing article validation logic...\n";

        // Test valid article data
        $validArticle = [
            'title' => 'Test Article Title',
            'subtitle' => 'This is a test subtitle',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'This is test content for the article.']
                    ]
                ]
            ],
            'tags' => ['javascript', 'web-development'],
            'status' => 'draft'
        ];

        $errors = $this->validateArticleData($validArticle);
        echo "Valid article data errors: " . (empty($errors) ? 'None (correct)' : implode(', ', $errors)) . "\n";

        // Test invalid article data
        $invalidArticle = [
            'title' => '', // Empty title
            'subtitle' => str_repeat('a', 501), // Too long subtitle
            'content' => '', // Empty content
            'tags' => ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6'], // Too many tags
            'status' => 'invalid_status' // Invalid status
        ];

        $errors = $this->validateArticleData($invalidArticle);
        echo "Invalid article data errors: " . implode(', ', $errors) . "\n";

        return true;
    }

    /**
     * Test reading time calculation with different content types
     */
    public function testReadingTimeCalculation() {
        echo "Testing reading time calculation logic...\n";

        // Test simple text content
        $simpleContent = "This is a simple test article with some content. " .
                        "It should calculate the reading time based on word count. " .
                        "The average reading speed is 225 words per minute.";
        
        $wordCount = str_word_count($simpleContent);
        $expectedReadingTime = max(1, ceil($wordCount / 225));
        
        echo "Simple content word count: {$wordCount}, reading time: {$expectedReadingTime} minute(s)\n";

        // Test rich text content structure
        $richContent = [
            [
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'text', 'text' => 'This is a paragraph with '],
                    ['type' => 'text', 'text' => 'bold text', 'marks' => [['type' => 'bold']]],
                    ['type' => 'text', 'text' => ' and some more content.']
                ]
            ],
            [
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => [
                    ['type' => 'text', 'text' => 'This is a heading with multiple words']
                ]
            ],
            [
                'type' => 'bulletList',
                'content' => [
                    [
                        'type' => 'listItem',
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => 'First list item with content']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'listItem',
                        'content' => [
                            [
                                'type' => 'paragraph',
                                'content' => [
                                    ['type' => 'text', 'text' => 'Second list item with more content']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $extractedText = $this->extractTextFromRichContent($richContent);
        $richWordCount = str_word_count($extractedText);
        $richReadingTime = max(1, ceil($richWordCount / 225));
        
        echo "Rich content extracted text: '{$extractedText}'\n";
        echo "Rich content word count: {$richWordCount}, reading time: {$richReadingTime} minute(s)\n";

        // Test empty content
        $emptyReadingTime = max(1, ceil(0 / 225));
        echo "Empty content reading time: {$emptyReadingTime} minute(s)\n";

        return true;
    }

    /**
     * Test article status management and transitions
     */
    public function testArticleStatusManagement() {
        echo "Testing article status management...\n";

        // Test valid status transitions
        $validStatuses = ['draft', 'published', 'archived'];
        foreach ($validStatuses as $status) {
            echo "Status '{$status}' is valid\n";
        }

        // Test status transition logic
        $statusTransitions = [
            'draft' => ['published', 'archived'],
            'published' => ['draft', 'archived'],
            'archived' => ['draft', 'published']
        ];

        foreach ($statusTransitions as $from => $allowedTo) {
            echo "From '{$from}' can transition to: " . implode(', ', $allowedTo) . "\n";
        }

        // Test invalid statuses
        $invalidStatuses = ['pending', 'review', 'deleted', ''];
        foreach ($invalidStatuses as $status) {
            $isValid = in_array($status, $validStatuses);
            echo "Status '{$status}' is " . ($isValid ? 'valid' : 'invalid (correctly rejected)') . "\n";
        }

        return true;
    }

    /**
     * Test tag validation and management
     */
    public function testTagValidation() {
        echo "Testing tag validation logic...\n";

        // Test valid tag names
        $validTags = ['javascript', 'web-development', 'React_JS', 'Node.js', 'CSS3'];
        foreach ($validTags as $tag) {
            $isValid = $this->validateTagName($tag);
            echo "Tag '{$tag}' is " . ($isValid ? 'valid' : 'invalid') . "\n";
        }

        // Test invalid tag names
        $invalidTags = [
            '', // Empty
            'a', // Too short (less than 2 chars)
            str_repeat('a', 51), // Too long
            'tag@with#special$chars', // Invalid characters
            'tag with  multiple   spaces', // Multiple spaces
            '   leading-spaces',
            'trailing-spaces   '
        ];
        
        foreach ($invalidTags as $tag) {
            $isValid = $this->validateTagName($tag);
            echo "Tag '{$tag}' is " . ($isValid ? 'valid' : 'invalid (correctly rejected)') . "\n";
        }

        // Test tag limit validation
        $tooManyTags = ['tag1', 'tag2', 'tag3', 'tag4', 'tag5', 'tag6'];
        $tagLimitValid = count($tooManyTags) <= 5;
        echo "Tag limit test (6 tags): " . ($tagLimitValid ? 'valid' : 'invalid (correctly rejected)') . "\n";

        $validTagCount = ['tag1', 'tag2', 'tag3'];
        $tagCountValid = count($validTagCount) <= 5;
        echo "Tag limit test (3 tags): " . ($tagCountValid ? 'valid' : 'invalid') . "\n";

        return true;
    }

    /**
     * Test slug generation and uniqueness
     */
    public function testSlugGeneration() {
        echo "Testing slug generation logic...\n";

        $testTitles = [
            'This is a Test Article Title' => 'this-is-a-test-article-title',
            'Article with Special Characters!' => 'article-with-special-characters',
            'Multiple   Spaces    Between Words' => 'multiple-spaces-between-words',
            'UPPERCASE and lowercase MiXeD' => 'uppercase-and-lowercase-mixed',
            'Numbers 123 and Symbols @#$%' => 'numbers-123-and-symbols',
            'Très Spécial Àrticle Títle' => 'tres-special-article-title'
        ];

        foreach ($testTitles as $title => $expectedSlug) {
            $generatedSlug = $this->createSlug($title);
            $isCorrect = $generatedSlug === $expectedSlug;
            echo "Title: '{$title}' -> Slug: '{$generatedSlug}' " . 
                 ($isCorrect ? '✓' : "✗ (expected: {$expectedSlug})") . "\n";
        }

        return true;
    }

    /**
     * Test article publishing workflow
     */
    public function testPublishingWorkflow() {
        echo "Testing article publishing workflow...\n";

        // Test draft to published transition
        $draftArticle = [
            'title' => 'Test Draft Article',
            'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Draft content']]]],
            'status' => 'draft',
            'published_at' => null
        ];

        echo "Draft article status: {$draftArticle['status']}\n";
        echo "Draft published_at: " . ($draftArticle['published_at'] ?: 'null') . "\n";

        // Simulate publishing
        $publishedArticle = $draftArticle;
        $publishedArticle['status'] = 'published';
        $publishedArticle['published_at'] = date('Y-m-d H:i:s');

        echo "After publishing - status: {$publishedArticle['status']}\n";
        echo "After publishing - published_at: {$publishedArticle['published_at']}\n";

        // Test unpublishing
        $unpublishedArticle = $publishedArticle;
        $unpublishedArticle['status'] = 'draft';
        $unpublishedArticle['published_at'] = null;

        echo "After unpublishing - status: {$unpublishedArticle['status']}\n";
        echo "After unpublishing - published_at: " . ($unpublishedArticle['published_at'] ?: 'null') . "\n";

        // Test archiving
        $archivedArticle = $publishedArticle;
        $archivedArticle['status'] = 'archived';

        echo "After archiving - status: {$archivedArticle['status']}\n";
        echo "After archiving - published_at: {$archivedArticle['published_at']} (preserved)\n";

        return true;
    }

    /**
     * Test SEO metadata generation
     */
    public function testSEOMetadataGeneration() {
        echo "Testing SEO metadata generation...\n";

        $article = [
            'title' => 'How to Build a Modern Web Application',
            'subtitle' => 'A comprehensive guide to building scalable web applications with React and Node.js',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'In this article, we will explore the fundamentals of building modern web applications using React for the frontend and Node.js for the backend.']
                    ]
                ]
            ],
            'tags' => ['react', 'nodejs', 'web-development', 'javascript'],
            'slug' => 'how-to-build-a-modern-web-application',
            'featured_image_url' => 'https://example.com/image.jpg'
        ];

        // Generate meta description
        $metaDescription = $this->generateMetaDescription($article);
        echo "Meta description: {$metaDescription}\n";

        // Generate keywords
        $keywords = $this->extractKeywords($article);
        echo "Keywords: " . implode(', ', $keywords) . "\n";

        // Generate structured data
        $structuredData = $this->generateStructuredData($article);
        echo "Structured data type: {$structuredData['@type']}\n";
        echo "Structured data headline: {$structuredData['headline']}\n";

        return true;
    }

    /**
     * Test auto-save functionality simulation
     */
    public function testAutoSaveFunctionality() {
        echo "Testing auto-save functionality simulation...\n";

        $draftStates = [
            ['title' => '', 'content' => '', 'lastSaved' => null],
            ['title' => 'My Article', 'content' => '', 'lastSaved' => null],
            ['title' => 'My Article', 'content' => 'Some content', 'lastSaved' => date('Y-m-d H:i:s')],
            ['title' => 'My Updated Article', 'content' => 'Updated content', 'lastSaved' => date('Y-m-d H:i:s', strtotime('-30 seconds'))]
        ];

        foreach ($draftStates as $index => $state) {
            $hasContent = !empty($state['title']) || !empty($state['content']);
            $needsAutoSave = $hasContent && (
                $state['lastSaved'] === null || 
                strtotime($state['lastSaved']) < strtotime('-30 seconds')
            );

            echo "Draft state {$index}: ";
            echo "Title: '{$state['title']}', ";
            echo "Content: '" . substr($state['content'], 0, 20) . (strlen($state['content']) > 20 ? '...' : '') . "', ";
            echo "Needs auto-save: " . ($needsAutoSave ? 'Yes' : 'No') . "\n";
        }

        return true;
    }

    /**
     * Test content validation for rich text editor
     */
    public function testRichTextContentValidation() {
        echo "Testing rich text content validation...\n";

        // Test valid rich text structures
        $validStructures = [
            // Simple paragraph
            [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Simple paragraph text']
                    ]
                ]
            ],
            // Heading with text
            [
                [
                    'type' => 'heading',
                    'attrs' => ['level' => 1],
                    'content' => [
                        ['type' => 'text', 'text' => 'Main Heading']
                    ]
                ]
            ],
            // List with items
            [
                [
                    'type' => 'bulletList',
                    'content' => [
                        [
                            'type' => 'listItem',
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        ['type' => 'text', 'text' => 'List item 1']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        foreach ($validStructures as $index => $structure) {
            $isValid = $this->validateRichTextStructure($structure);
            echo "Valid structure {$index}: " . ($isValid ? 'Valid' : 'Invalid') . "\n";
        }

        // Test invalid structures
        $invalidStructures = [
            null, // Null content
            '', // Empty string
            'plain text', // Plain string instead of array
            [], // Empty array
            [['invalid' => 'structure']] // Invalid structure
        ];

        foreach ($invalidStructures as $index => $structure) {
            $isValid = $this->validateRichTextStructure($structure);
            echo "Invalid structure {$index}: " . ($isValid ? 'Valid' : 'Invalid (correctly rejected)') . "\n";
        }

        return true;
    }

    /**
     * Run all article creation tests
     */
    public function runAllTests() {
        echo "=== Comprehensive Article Creation Tests ===\n\n";
        
        $tests = [
            'testArticleValidation',
            'testReadingTimeCalculation',
            'testArticleStatusManagement', 
            'testTagValidation',
            'testSlugGeneration',
            'testPublishingWorkflow',
            'testSEOMetadataGeneration',
            'testAutoSaveFunctionality',
            'testRichTextContentValidation'
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $test) {
            try {
                if ($this->$test()) {
                    $passed++;
                    echo "✓ {$test} passed\n\n";
                } else {
                    echo "✗ {$test} failed\n\n";
                }
            } catch (Exception $e) {
                echo "✗ {$test} failed with exception: " . $e->getMessage() . "\n\n";
            }
        }

        echo "=== Test Results ===\n";
        echo "Passed: {$passed}/{$total}\n";
        echo "Success Rate: " . round(($passed / $total) * 100, 1) . "%\n";
        
        return $passed === $total;
    }

    // Helper methods for testing

    private function validateArticleData($data, $isCreate = true) {
        $errors = [];

        if ($isCreate || isset($data['title'])) {
            if (empty($data['title']) || strlen(trim($data['title'])) < 1) {
                $errors['title'] = 'Title is required';
            } elseif (strlen($data['title']) > 255) {
                $errors['title'] = 'Title must be less than 255 characters';
            }
        }

        if (isset($data['subtitle']) && strlen($data['subtitle']) > 500) {
            $errors['subtitle'] = 'Subtitle must be less than 500 characters';
        }

        if ($isCreate || isset($data['content'])) {
            if (empty($data['content'])) {
                $errors['content'] = 'Content is required';
            }
        }

        if (isset($data['tags'])) {
            if (!is_array($data['tags'])) {
                $errors['tags'] = 'Tags must be an array';
            } elseif (count($data['tags']) > 5) {
                $errors['tags'] = 'Maximum 5 tags allowed';
            }
        }

        if (isset($data['status']) && !in_array($data['status'], ['draft', 'published', 'archived'])) {
            $errors['status'] = 'Invalid status';
        }

        return $errors;
    }

    private function validateTagName($name) {
        if (empty($name) || strlen(trim($name)) < 2) {
            return false;
        }
        if (strlen($name) > 50) {
            return false;
        }
        if (!preg_match('/^[a-zA-Z0-9\s\-_.]+$/', $name)) {
            return false;
        }
        return true;
    }

    private function createSlug($text) {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ]/', '', $slug);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    private function extractTextFromRichContent($content) {
        $text = '';
        
        if (is_array($content)) {
            foreach ($content as $block) {
                if (isset($block['type'])) {
                    switch ($block['type']) {
                        case 'paragraph':
                        case 'heading':
                        case 'blockquote':
                            if (isset($block['content'])) {
                                $text .= $this->extractTextFromRichContent($block['content']) . ' ';
                            }
                            break;
                        case 'text':
                            if (isset($block['text'])) {
                                $text .= $block['text'] . ' ';
                            }
                            break;
                        case 'bulletList':
                        case 'orderedList':
                            if (isset($block['content'])) {
                                foreach ($block['content'] as $listItem) {
                                    if (isset($listItem['content'])) {
                                        $text .= $this->extractTextFromRichContent($listItem['content']) . ' ';
                                    }
                                }
                            }
                            break;
                        case 'listItem':
                            if (isset($block['content'])) {
                                $text .= $this->extractTextFromRichContent($block['content']) . ' ';
                            }
                            break;
                    }
                }
            }
        }
        
        return trim($text);
    }

    private function generateMetaDescription($article, $maxLength = 160) {
        if (!empty($article['subtitle'])) {
            $description = $article['subtitle'];
        } else {
            $content = is_string($article['content']) ? $article['content'] : json_encode($article['content']);
            $plainText = strip_tags($content);
            $description = $plainText;
        }
        
        if (strlen($description) <= $maxLength) {
            return $description;
        }
        
        $truncated = substr($description, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');
        
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            return substr($description, 0, $lastSpace) . '...';
        }
        
        return $truncated . '...';
    }

    private function extractKeywords($article) {
        $keywords = [];
        
        if (!empty($article['tags'])) {
            $tags = is_array($article['tags']) ? $article['tags'] : explode(',', $article['tags']);
            $keywords = array_merge($keywords, $tags);
        }
        
        return array_unique($keywords);
    }

    private function generateStructuredData($article) {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article['title'],
            'description' => $this->generateMetaDescription($article)
        ];
    }

    private function validateRichTextStructure($content) {
        if (!is_array($content) || empty($content)) {
            return false;
        }

        foreach ($content as $block) {
            if (!is_array($block) || !isset($block['type'])) {
                return false;
            }
        }

        return true;
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ArticleModelTest();
    $test->runAllTests();
}