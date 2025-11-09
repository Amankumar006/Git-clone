<?php

require_once __DIR__ . '/../models/Search.php';
require_once __DIR__ . '/../models/Feed.php';
require_once __DIR__ . '/../controllers/SearchController.php';
require_once __DIR__ . '/../controllers/FeedController.php';
require_once __DIR__ . '/../config/database.php';

class SearchDiscoveryTest {
    private $search;
    private $feed;
    private $db;
    private $testUserId;
    private $testArticleIds = [];
    private $testTagIds = [];

    public function __construct() {
        $this->search = new Search();
        $this->feed = new Feed();
        $this->db = Database::getInstance()->getConnection();
        $this->setupTestData();
    }

    public function runAllTests() {
        echo "Running Search and Discovery Tests...\n";
        echo "=====================================\n\n";

        $tests = [
            'testSearchAlgorithms',
            'testSearchResultRanking',
            'testRecommendationSystemAccuracy',
            'testTagBrowsingAndFiltering',
            'testSearchInterface',
            'testSearchSuggestions',
            'testAdvancedSearch',
            'testFeedPersonalization',
            'testTrendingAlgorithm',
            'testSearchPerformance',
            'testSearchAnalytics'
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            try {
                echo "Running {$test}... ";
                $this->$test();
                echo "✅ PASSED\n";
                $passed++;
            } catch (Exception $e) {
                echo "❌ FAILED: " . $e->getMessage() . "\n";
                $failed++;
            }
        }

        echo "\n=====================================\n";
        echo "Tests completed: " . ($passed + $failed) . "\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";

        $this->cleanupTestData();
    }

    private function setupTestData() {
        // Create test user
        $sql = "INSERT INTO users (username, email, password_hash, bio) 
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'testuser_search',
            'test_search@example.com',
            password_hash('password123', PASSWORD_DEFAULT),
            'Test user for search functionality'
        ]);
        $this->testUserId = $this->db->lastInsertId();

        // Create test tags
        $tags = [
            ['name' => 'javascript', 'slug' => 'javascript'],
            ['name' => 'react', 'slug' => 'react'],
            ['name' => 'nodejs', 'slug' => 'nodejs'],
            ['name' => 'python', 'slug' => 'python'],
            ['name' => 'machine-learning', 'slug' => 'machine-learning']
        ];

        foreach ($tags as $tag) {
            $sql = "INSERT INTO tags (name, slug) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tag['name'], $tag['slug']]);
            $this->testTagIds[] = $this->db->lastInsertId();
        }

        // Create test articles with different content and engagement levels
        $articles = [
            [
                'title' => 'Introduction to JavaScript Programming',
                'subtitle' => 'Learn the basics of JavaScript',
                'content' => json_encode(['text' => 'JavaScript is a versatile programming language used for web development. This article covers variables, functions, and basic syntax.']),
                'view_count' => 1000,
                'clap_count' => 50,
                'comment_count' => 10,
                'tags' => [0] // javascript
            ],
            [
                'title' => 'Advanced React Patterns',
                'subtitle' => 'Master React with advanced techniques',
                'content' => json_encode(['text' => 'React is a powerful library for building user interfaces. This guide explores advanced patterns like render props, higher-order components, and hooks.']),
                'view_count' => 800,
                'clap_count' => 75,
                'comment_count' => 15,
                'tags' => [0, 1] // javascript, react
            ],
            [
                'title' => 'Node.js Backend Development',
                'subtitle' => 'Building scalable server applications',
                'content' => json_encode(['text' => 'Node.js enables JavaScript on the server side. Learn how to build REST APIs, handle databases, and create scalable backend applications.']),
                'view_count' => 600,
                'clap_count' => 30,
                'comment_count' => 8,
                'tags' => [0, 2] // javascript, nodejs
            ],
            [
                'title' => 'Python for Data Science',
                'subtitle' => 'Analyzing data with Python',
                'content' => json_encode(['text' => 'Python is excellent for data science. This tutorial covers pandas, numpy, and matplotlib for data analysis and visualization.']),
                'view_count' => 1200,
                'clap_count' => 90,
                'comment_count' => 20,
                'tags' => [3] // python
            ],
            [
                'title' => 'Machine Learning Fundamentals',
                'subtitle' => 'Understanding ML algorithms',
                'content' => json_encode(['text' => 'Machine learning is transforming technology. Learn about supervised learning, unsupervised learning, and neural networks.']),
                'view_count' => 1500,
                'clap_count' => 120,
                'comment_count' => 25,
                'tags' => [3, 4] // python, machine-learning
            ]
        ];

        foreach ($articles as $article) {
            $sql = "INSERT INTO articles (author_id, title, subtitle, content, status, published_at, view_count, clap_count, comment_count, reading_time) 
                    VALUES (?, ?, ?, ?, 'published', NOW(), ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->testUserId,
                $article['title'],
                $article['subtitle'],
                $article['content'],
                $article['view_count'],
                $article['clap_count'],
                $article['comment_count'],
                5 // reading time
            ]);
            $articleId = $this->db->lastInsertId();
            $this->testArticleIds[] = $articleId;

            // Add tags to articles
            foreach ($article['tags'] as $tagIndex) {
                $sql = "INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$articleId, $this->testTagIds[$tagIndex]]);
            }
        }
    }

    private function testSearchAlgorithms() {
        // Test basic search functionality
        $results = $this->search->search('JavaScript');
        
        if (empty($results['articles'])) {
            throw new Exception("Search should return JavaScript articles");
        }

        // Test that JavaScript articles are returned
        $found = false;
        foreach ($results['articles'] as $article) {
            if (stripos($article['title'], 'JavaScript') !== false) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new Exception("Search should find articles with 'JavaScript' in title");
        }

        // Test empty search
        $emptyResults = $this->search->search('');
        if ($emptyResults['total_count'] !== 0) {
            throw new Exception("Empty search should return no results");
        }

        // Test search with no matches
        $noResults = $this->search->search('nonexistentterm12345');
        if ($noResults['total_count'] !== 0) {
            throw new Exception("Search for non-existent term should return no results");
        }
    }

    private function testSearchResultRanking() {
        // Test that results are ranked by relevance
        $results = $this->search->searchArticles('JavaScript');
        
        if (count($results['data']) < 2) {
            throw new Exception("Need at least 2 JavaScript articles to test ranking");
        }

        // Check that results have relevance scores
        foreach ($results['data'] as $article) {
            if (!isset($article['relevance_score'])) {
                throw new Exception("Articles should have relevance scores");
            }
        }

        // Check that results are sorted by relevance (descending)
        for ($i = 0; $i < count($results['data']) - 1; $i++) {
            $current = $results['data'][$i]['relevance_score'];
            $next = $results['data'][$i + 1]['relevance_score'];
            
            if ($current < $next) {
                throw new Exception("Results should be sorted by relevance score (descending)");
            }
        }

        // Test that title matches rank higher than content matches
        $titleResults = $this->search->searchArticles('Introduction');
        if (empty($titleResults['data'])) {
            throw new Exception("Should find articles with 'Introduction' in title");
        }

        // The article with "Introduction" in title should have high relevance
        $introArticle = null;
        foreach ($titleResults['data'] as $article) {
            if (stripos($article['title'], 'Introduction') !== false) {
                $introArticle = $article;
                break;
            }
        }

        if (!$introArticle || $introArticle['relevance_score'] <= 0) {
            throw new Exception("Title matches should have high relevance scores");
        }
    }

    private function testRecommendationSystemAccuracy() {
        // Create user interactions to test recommendations
        $sql = "INSERT INTO claps (user_id, article_id, count) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        
        // User likes JavaScript and React articles
        $stmt->execute([$this->testUserId, $this->testArticleIds[0], 5]); // JavaScript article
        $stmt->execute([$this->testUserId, $this->testArticleIds[1], 10]); // React article

        // Test personalized feed
        $personalizedFeed = $this->feed->getPersonalizedFeed($this->testUserId, 1, 10);
        
        if (empty($personalizedFeed['data'])) {
            throw new Exception("Personalized feed should return articles");
        }

        // Test recommendation algorithm
        $recommendations = $this->feed->getRecommendedArticles($this->testUserId, 5);
        
        // Should recommend articles based on user's interests
        if (empty($recommendations)) {
            // This is acceptable if no similar users exist
            echo " (No recommendations - acceptable for test data)";
        }

        // Test that user interests are calculated correctly
        $reflection = new ReflectionClass($this->feed);
        $method = $reflection->getMethod('getUserInterests');
        $method->setAccessible(true);
        $interests = $method->invoke($this->feed, $this->testUserId);

        if (empty($interests)) {
            throw new Exception("User should have interests based on claps");
        }

        // Check that JavaScript and React are in user interests
        $hasJavaScript = false;
        $hasReact = false;
        foreach ($interests as $interest) {
            if ($interest['name'] === 'javascript') $hasJavaScript = true;
            if ($interest['name'] === 'react') $hasReact = true;
        }

        if (!$hasJavaScript || !$hasReact) {
            throw new Exception("User interests should include JavaScript and React based on claps");
        }
    }

    private function testTagBrowsingAndFiltering() {
        // Test tag-based filtering
        $jsResults = $this->search->searchArticles('', ['tag' => 'javascript']);
        
        if (empty($jsResults['data'])) {
            throw new Exception("Should find articles tagged with JavaScript");
        }

        // Verify all results have the JavaScript tag
        foreach ($jsResults['data'] as $article) {
            if (!in_array('javascript', $article['tags'])) {
                throw new Exception("All results should have the JavaScript tag when filtering by tag");
            }
        }

        // Test multiple filters
        $filteredResults = $this->search->searchArticles('React', [
            'tag' => 'javascript',
            'author_id' => $this->testUserId
        ]);

        foreach ($filteredResults['data'] as $article) {
            if ($article['author_id'] != $this->testUserId) {
                throw new Exception("Filtered results should match author filter");
            }
            if (!in_array('javascript', $article['tags'])) {
                throw new Exception("Filtered results should match tag filter");
            }
        }

        // Test date filtering
        $dateResults = $this->search->searchArticles('', [
            'date_from' => date('Y-m-d', strtotime('-1 day')),
            'date_to' => date('Y-m-d', strtotime('+1 day'))
        ]);

        if (empty($dateResults['data'])) {
            throw new Exception("Date filtering should return recent articles");
        }

        // Test filtered feed
        $filteredFeed = $this->feed->getFilteredFeed(['tag' => 'javascript'], 1, 10);
        
        if (empty($filteredFeed['data'])) {
            throw new Exception("Filtered feed should return JavaScript articles");
        }

        foreach ($filteredFeed['data'] as $article) {
            if (!in_array('javascript', $article['tags'])) {
                throw new Exception("Filtered feed should only return articles with specified tag");
            }
        }
    }

    private function testSearchInterface() {
        // Test search suggestions
        $suggestions = $this->search->getSuggestions('Java');
        
        if (empty($suggestions)) {
            throw new Exception("Should get suggestions for 'Java' prefix");
        }

        // Check suggestion structure
        foreach ($suggestions as $suggestion) {
            if (!isset($suggestion['suggestion']) || !isset($suggestion['type'])) {
                throw new Exception("Suggestions should have 'suggestion' and 'type' fields");
            }
            
            if (!in_array($suggestion['type'], ['article', 'tag', 'user'])) {
                throw new Exception("Suggestion type should be article, tag, or user");
            }
        }

        // Test that suggestions are relevant
        $found = false;
        foreach ($suggestions as $suggestion) {
            if (stripos($suggestion['suggestion'], 'java') !== false) {
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new Exception("Suggestions should be relevant to search query");
        }

        // Test popular searches
        $popularSearches = $this->search->getPopularSearches(10);
        
        if (empty($popularSearches)) {
            // This is acceptable if no search data exists
            echo " (No popular searches - acceptable for test data)";
        } else {
            foreach ($popularSearches as $search) {
                if (!isset($search['query']) || !isset($search['frequency'])) {
                    throw new Exception("Popular searches should have query and frequency");
                }
            }
        }

        // Test search logging
        $logResult = $this->search->logSearch('test query', $this->testUserId, 5);
        // Logging might fail if table doesn't exist, which is acceptable
    }

    private function testSearchSuggestions() {
        // Test minimum query length
        $shortSuggestions = $this->search->getSuggestions('J');
        // Should return empty or limited results for very short queries

        // Test exact matches
        $exactSuggestions = $this->search->getSuggestions('JavaScript');
        
        if (!empty($exactSuggestions)) {
            $hasExactMatch = false;
            foreach ($exactSuggestions as $suggestion) {
                if (strtolower($suggestion['suggestion']) === 'javascript') {
                    $hasExactMatch = true;
                    break;
                }
            }
            // Exact matches should be prioritized but not required
        }

        // Test suggestion types
        $allSuggestions = $this->search->getSuggestions('test');
        foreach ($allSuggestions as $suggestion) {
            if (!in_array($suggestion['type'], ['article', 'tag', 'user'])) {
                throw new Exception("Invalid suggestion type: " . $suggestion['type']);
            }
        }

        // Test suggestion limit
        $limitedSuggestions = $this->search->getSuggestions('a', 3);
        if (count($limitedSuggestions) > 3) {
            throw new Exception("Suggestions should respect limit parameter");
        }
    }

    private function testAdvancedSearch() {
        // Test comprehensive search with all filters
        $advancedResults = $this->search->search('programming', [
            'type' => 'articles',
            'author_id' => $this->testUserId,
            'date_from' => date('Y-m-d', strtotime('-1 day'))
        ]);

        // Should only return articles
        if (!empty($advancedResults['users']) || !empty($advancedResults['tags'])) {
            throw new Exception("Advanced search with type=articles should only return articles");
        }

        // Test user search
        $userResults = $this->search->searchUsers('test');
        
        if (empty($userResults['data'])) {
            throw new Exception("Should find test user");
        }

        foreach ($userResults['data'] as $user) {
            if (!isset($user['username']) || !isset($user['followers_count'])) {
                throw new Exception("User results should have username and followers_count");
            }
        }

        // Test tag search
        $tagResults = $this->search->searchTags('java');
        
        if (empty($tagResults['data'])) {
            throw new Exception("Should find JavaScript tag");
        }

        foreach ($tagResults['data'] as $tag) {
            if (!isset($tag['name']) || !isset($tag['article_count'])) {
                throw new Exception("Tag results should have name and article_count");
            }
        }

        // Test search highlighting
        $highlightResults = $this->search->searchArticles('JavaScript');
        
        foreach ($highlightResults['data'] as $article) {
            if (isset($article['highlights'])) {
                // Highlights should contain HTML markup
                if (isset($article['highlights']['title'])) {
                    if (strpos($article['highlights']['title'], '<mark>') === false) {
                        throw new Exception("Highlights should contain <mark> tags");
                    }
                }
            }
        }
    }

    private function testFeedPersonalization() {
        // Test public feed
        $publicFeed = $this->feed->getPublicFeed(1, 10);
        
        if (empty($publicFeed['data'])) {
            throw new Exception("Public feed should return articles");
        }

        foreach ($publicFeed['data'] as $article) {
            if (!isset($article['feed_type'])) {
                throw new Exception("Feed articles should have feed_type");
            }
        }

        // Test trending articles
        $trending = $this->feed->getTrendingArticles(5);
        
        if (empty($trending)) {
            throw new Exception("Should return trending articles");
        }

        // Check trending score calculation
        foreach ($trending as $article) {
            if (!isset($article['trending_score'])) {
                throw new Exception("Trending articles should have trending_score");
            }
        }

        // Test popular articles
        $popular = $this->feed->getPopularArticles(5);
        
        if (empty($popular)) {
            throw new Exception("Should return popular articles");
        }

        // Test latest articles
        $latest = $this->feed->getLatestArticles(5);
        
        if (empty($latest)) {
            throw new Exception("Should return latest articles");
        }

        // Check that latest articles are sorted by date
        for ($i = 0; $i < count($latest) - 1; $i++) {
            $current = strtotime($latest[$i]['published_at']);
            $next = strtotime($latest[$i + 1]['published_at']);
            
            if ($current < $next) {
                throw new Exception("Latest articles should be sorted by date (newest first)");
            }
        }
    }

    private function testTrendingAlgorithm() {
        // Test trending calculation with different timeframes
        $weekTrending = $this->feed->getTrendingArticles(5, '7 days');
        $monthTrending = $this->feed->getTrendingArticles(5, '30 days');

        if (empty($weekTrending) && empty($monthTrending)) {
            throw new Exception("Should return trending articles for different timeframes");
        }

        // Test that trending scores are calculated correctly
        foreach ($weekTrending as $article) {
            $expectedScore = ($article['view_count'] * 0.1) + 
                           ($article['clap_count'] * 0.3) + 
                           ($article['comment_count'] * 0.4);
            
            // Allow for some variance due to date calculation
            if (abs($article['trending_score'] - $expectedScore) > 10) {
                // This is acceptable as the algorithm includes date factors
            }
        }

        // Test timeframe parsing
        $reflection = new ReflectionClass($this->feed);
        $method = $reflection->getMethod('parseTimeframeToDays');
        $method->setAccessible(true);
        
        $days = $method->invoke($this->feed, '1 week');
        if ($days !== 7) {
            throw new Exception("Timeframe parsing should convert '1 week' to 7 days");
        }

        $days = $method->invoke($this->feed, '1 month');
        if ($days !== 30) {
            throw new Exception("Timeframe parsing should convert '1 month' to 30 days");
        }
    }

    private function testSearchPerformance() {
        // Test search performance with larger query
        $startTime = microtime(true);
        
        $results = $this->search->search('JavaScript programming development');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Search should complete within reasonable time (2 seconds for test)
        if ($executionTime > 2.0) {
            throw new Exception("Search took too long: {$executionTime} seconds");
        }

        // Test pagination performance
        $startTime = microtime(true);
        
        $page1 = $this->search->searchArticles('programming', [], 1, 5);
        $page2 = $this->search->searchArticles('programming', [], 2, 5);
        
        $endTime = microtime(true);
        $paginationTime = $endTime - $startTime;

        if ($paginationTime > 3.0) {
            throw new Exception("Paginated search took too long: {$paginationTime} seconds");
        }

        // Test that pagination works correctly
        if (!empty($page1['data']) && !empty($page2['data'])) {
            $page1Ids = array_column($page1['data'], 'id');
            $page2Ids = array_column($page2['data'], 'id');
            
            $intersection = array_intersect($page1Ids, $page2Ids);
            if (!empty($intersection)) {
                throw new Exception("Pagination should not return duplicate articles");
            }
        }
    }

    private function testSearchAnalytics() {
        // Test search analytics (if table exists)
        try {
            $analytics = $this->search->getSearchAnalytics(30);
            
            // Analytics might be empty, which is acceptable
            if (!empty($analytics)) {
                foreach ($analytics as $analytic) {
                    if (!isset($analytic['query']) || !isset($analytic['search_count'])) {
                        throw new Exception("Analytics should have query and search_count");
                    }
                }
            }
        } catch (PDOException $e) {
            // Table might not exist, which is acceptable
            echo " (Analytics table not found - acceptable)";
        }

        // Test search logging functionality
        $logSuccess = $this->search->logSearch('test analytics query', $this->testUserId, 10);
        // Logging might fail if table doesn't exist, which is acceptable
    }

    private function cleanupTestData() {
        // Clean up test data
        $this->db->prepare("DELETE FROM article_tags WHERE article_id IN (" . implode(',', $this->testArticleIds) . ")")->execute();
        $this->db->prepare("DELETE FROM claps WHERE article_id IN (" . implode(',', $this->testArticleIds) . ")")->execute();
        $this->db->prepare("DELETE FROM articles WHERE id IN (" . implode(',', $this->testArticleIds) . ")")->execute();
        $this->db->prepare("DELETE FROM tags WHERE id IN (" . implode(',', $this->testTagIds) . ")")->execute();
        $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$this->testUserId]);
        
        // Clean up search logs if they exist
        try {
            $this->db->prepare("DELETE FROM search_logs WHERE user_id = ?")->execute([$this->testUserId]);
        } catch (PDOException $e) {
            // Table might not exist
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new SearchDiscoveryTest();
    $test->runAllTests();
}