<?php

require_once __DIR__ . '/BaseRepository.php';

class Search extends BaseRepository {
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Perform comprehensive search across articles, users, and tags
     */
    public function search($query, $filters = [], $page = 1, $limit = 10) {
        $results = [
            'articles' => [],
            'users' => [],
            'tags' => [],
            'total_count' => 0,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_pages' => 0
            ]
        ];

        if (empty(trim($query))) {
            return $results;
        }

        // Search articles
        $articleResults = $this->searchArticles($query, $filters, $page, $limit);
        $results['articles'] = $articleResults['data'];
        $results['total_count'] += $articleResults['total'];

        // Search users (if not filtered to articles only)
        if (!isset($filters['type']) || $filters['type'] !== 'articles') {
            $userResults = $this->searchUsers($query, $page, $limit);
            $results['users'] = $userResults['data'];
            $results['total_count'] += $userResults['total'];
        }

        // Search tags (if not filtered to articles only)
        if (!isset($filters['type']) || $filters['type'] !== 'articles') {
            $tagResults = $this->searchTags($query, $page, $limit);
            $results['tags'] = $tagResults['data'];
            $results['total_count'] += $tagResults['total'];
        }

        // Calculate pagination
        $results['pagination']['total_pages'] = ceil($results['total_count'] / $limit);

        return $results;
    }

    /**
     * Search articles with full-text search and ranking
     */
    public function searchArticles($query, $filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $whereConditions = ['a.status = ?'];
        $params = ['published'];

        // Build search conditions
        $searchConditions = [];
        
        // Full-text search across title, subtitle, and content
        if (!empty($query)) {
            $searchTerm = '%' . $query . '%';
            $searchConditions[] = '(
                a.title LIKE ? OR 
                a.subtitle LIKE ? OR 
                JSON_UNQUOTE(JSON_EXTRACT(a.content, "$")) LIKE ? OR
                EXISTS (
                    SELECT 1 FROM article_tags at2 
                    JOIN tags t2 ON at2.tag_id = t2.id 
                    WHERE at2.article_id = a.id AND t2.name LIKE ?
                )
            )';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        // Apply filters
        if (!empty($filters['author_id'])) {
            $whereConditions[] = 'a.author_id = ?';
            $params[] = $filters['author_id'];
        }

        if (!empty($filters['author'])) {
            $whereConditions[] = 'u.username LIKE ?';
            $params[] = '%' . $filters['author'] . '%';
        }

        if (!empty($filters['tag'])) {
            $whereConditions[] = 'EXISTS (
                SELECT 1 FROM article_tags at3 
                JOIN tags t3 ON at3.tag_id = t3.id 
                WHERE at3.article_id = a.id AND t3.slug = ?
            )';
            $params[] = $filters['tag'];
        }

        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'a.published_at >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'a.published_at <= ?';
            $params[] = $filters['date_to'];
        }

        // Combine conditions
        if (!empty($searchConditions)) {
            $whereConditions = array_merge($whereConditions, $searchConditions);
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Build relevance score for ranking
        $relevanceScore = $this->buildRelevanceScore($query);

        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags,
                       {$relevanceScore} as relevance_score
                FROM articles a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE {$whereClause}
                GROUP BY a.id
                ORDER BY relevance_score DESC, a.published_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
            
            // Add search highlights
            if (!empty($query)) {
                $article['highlights'] = $this->generateHighlights($article, $query);
            }
        }

        // Get total count
        $countSql = "SELECT COUNT(DISTINCT a.id) as total
                     FROM articles a
                     LEFT JOIN users u ON a.author_id = u.id
                     LEFT JOIN article_tags at ON a.id = at.article_id
                     LEFT JOIN tags t ON at.tag_id = t.id
                     WHERE {$whereClause}";

        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($countParams);
        $total = $countStmt->fetch()['total'];

        return [
            'data' => $articles,
            'total' => $total
        ];
    }

    /**
     * Search users by username and bio
     */
    public function searchUsers($query, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $searchTerm = '%' . $query . '%';

        $sql = "SELECT u.id, u.username, u.bio, u.profile_image_url,
                       (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as followers_count,
                       (SELECT COUNT(*) FROM articles WHERE author_id = u.id AND status = 'published') as articles_count,
                       CASE 
                           WHEN u.username LIKE ? THEN 3
                           WHEN u.bio LIKE ? THEN 1
                           ELSE 0
                       END as relevance_score
                FROM users u
                WHERE u.username LIKE ? OR u.bio LIKE ?
                ORDER BY relevance_score DESC, followers_count DESC
                LIMIT ? OFFSET ?";

        $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM users u WHERE u.username LIKE ? OR u.bio LIKE ?";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute([$searchTerm, $searchTerm]);
        $total = $countStmt->fetch()['total'];

        return [
            'data' => $users,
            'total' => $total
        ];
    }

    /**
     * Search tags by name
     */
    public function searchTags($query, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $searchTerm = '%' . $query . '%';

        $sql = "SELECT t.*, COUNT(at.article_id) as article_count,
                       CASE 
                           WHEN t.name LIKE ? THEN 2
                           ELSE 1
                       END as relevance_score
                FROM tags t
                LEFT JOIN article_tags at ON t.id = at.tag_id
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                WHERE t.name LIKE ?
                GROUP BY t.id
                ORDER BY relevance_score DESC, article_count DESC
                LIMIT ? OFFSET ?";

        $params = [$query . '%', $searchTerm, $limit, $offset];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $tags = $stmt->fetchAll();

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM tags WHERE name LIKE ?";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute([$searchTerm]);
        $total = $countStmt->fetch()['total'];

        return [
            'data' => $tags,
            'total' => $total
        ];
    }

    /**
     * Get search suggestions for autocomplete
     */
    public function getSuggestions($query, $limit = 5) {
        $suggestions = [];
        $searchTerm = $query . '%';

        // Article title suggestions
        $sql = "SELECT DISTINCT title as suggestion, 'article' as type
                FROM articles 
                WHERE title LIKE ? AND status = 'published'
                ORDER BY view_count DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $limit]);
        $articleSuggestions = $stmt->fetchAll();

        // Tag suggestions
        $sql = "SELECT DISTINCT name as suggestion, 'tag' as type
                FROM tags 
                WHERE name LIKE ?
                ORDER BY (SELECT COUNT(*) FROM article_tags WHERE tag_id = tags.id) DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $limit]);
        $tagSuggestions = $stmt->fetchAll();

        // User suggestions
        $sql = "SELECT DISTINCT username as suggestion, 'user' as type
                FROM users 
                WHERE username LIKE ?
                ORDER BY (SELECT COUNT(*) FROM follows WHERE following_id = users.id) DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $limit]);
        $userSuggestions = $stmt->fetchAll();

        // Combine and limit suggestions
        $suggestions = array_merge($articleSuggestions, $tagSuggestions, $userSuggestions);
        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Get popular search queries
     */
    public function getPopularSearches($limit = 10) {
        // This would require a search_queries table to track searches
        // For now, return popular tags as search suggestions
        $sql = "SELECT t.name as query, COUNT(at.article_id) as frequency
                FROM tags t
                LEFT JOIN article_tags at ON t.id = at.tag_id
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                GROUP BY t.id
                ORDER BY frequency DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Build relevance score for search ranking
     */
    private function buildRelevanceScore($query) {
        if (empty($query)) {
            return '(a.view_count * 0.1 + a.clap_count * 0.3 + a.comment_count * 0.2)';
        }

        $queryTerm = $this->db->quote('%' . $query . '%');
        
        return "(
            CASE 
                WHEN a.title LIKE {$queryTerm} THEN 10
                WHEN a.subtitle LIKE {$queryTerm} THEN 5
                WHEN JSON_UNQUOTE(JSON_EXTRACT(a.content, '$')) LIKE {$queryTerm} THEN 2
                ELSE 0
            END +
            (a.view_count * 0.1) +
            (a.clap_count * 0.3) +
            (a.comment_count * 0.2) +
            (DATEDIFF(NOW(), a.published_at) * -0.1)
        )";
    }

    /**
     * Generate search result highlights
     */
    private function generateHighlights($article, $query) {
        $highlights = [];
        $queryWords = explode(' ', strtolower($query));

        // Highlight in title
        if (stripos($article['title'], $query) !== false) {
            $highlights['title'] = $this->highlightText($article['title'], $queryWords);
        }

        // Highlight in subtitle
        if (!empty($article['subtitle']) && stripos($article['subtitle'], $query) !== false) {
            $highlights['subtitle'] = $this->highlightText($article['subtitle'], $queryWords);
        }

        // Generate content snippet with highlights
        $contentText = $this->extractTextFromContent($article['content']);
        $snippet = $this->generateSnippet($contentText, $queryWords);
        if ($snippet) {
            $highlights['content'] = $snippet;
        }

        return $highlights;
    }

    /**
     * Highlight search terms in text
     */
    private function highlightText($text, $queryWords) {
        foreach ($queryWords as $word) {
            if (strlen($word) > 2) { // Only highlight words longer than 2 characters
                $text = preg_replace('/(' . preg_quote($word, '/') . ')/i', '<mark>$1</mark>', $text);
            }
        }
        return $text;
    }

    /**
     * Extract plain text from rich content
     */
    private function extractTextFromContent($content) {
        if (is_array($content)) {
            // Handle rich text content structure
            $text = '';
            if (isset($content['blocks'])) {
                foreach ($content['blocks'] as $block) {
                    if (isset($block['text'])) {
                        $text .= $block['text'] . ' ';
                    }
                }
            } else {
                $text = json_encode($content);
            }
            return strip_tags($text);
        }
        return strip_tags($content);
    }

    /**
     * Generate content snippet with highlighted search terms
     */
    private function generateSnippet($text, $queryWords, $maxLength = 200) {
        $text = trim($text);
        if (empty($text)) {
            return null;
        }

        // Find the best position to start the snippet
        $bestPosition = 0;
        $bestScore = 0;

        foreach ($queryWords as $word) {
            if (strlen($word) > 2) {
                $position = stripos($text, $word);
                if ($position !== false) {
                    $score = 1 / ($position + 1); // Earlier positions get higher scores
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestPosition = max(0, $position - 50);
                    }
                }
            }
        }

        // Extract snippet
        $snippet = substr($text, $bestPosition, $maxLength);
        
        // Ensure we don't cut words in the middle
        if ($bestPosition > 0) {
            $firstSpace = strpos($snippet, ' ');
            if ($firstSpace !== false) {
                $snippet = substr($snippet, $firstSpace + 1);
            }
        }

        if (strlen($text) > $bestPosition + $maxLength) {
            $lastSpace = strrpos($snippet, ' ');
            if ($lastSpace !== false) {
                $snippet = substr($snippet, 0, $lastSpace);
            }
            $snippet .= '...';
        }

        if ($bestPosition > 0) {
            $snippet = '...' . $snippet;
        }

        // Highlight search terms
        return $this->highlightText($snippet, $queryWords);
    }

    /**
     * Log search query for analytics
     */
    public function logSearch($query, $userId = null, $resultsCount = 0) {
        try {
            $sql = "INSERT INTO search_logs (query, user_id, results_count, searched_at) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$query, $userId, $resultsCount]);
        } catch (PDOException $e) {
            // Fail silently if search_logs table doesn't exist
            error_log("Search logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get search analytics
     */
    public function getSearchAnalytics($days = 30) {
        try {
            $sql = "SELECT 
                        query,
                        COUNT(*) as search_count,
                        AVG(results_count) as avg_results,
                        DATE(searched_at) as search_date
                    FROM search_logs 
                    WHERE searched_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY query, DATE(searched_at)
                    ORDER BY search_count DESC, search_date DESC
                    LIMIT 100";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search analytics failed: " . $e->getMessage());
            return [];
        }
    }
}