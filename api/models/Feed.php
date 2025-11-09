<?php

require_once __DIR__ . '/BaseRepository.php';

class Feed extends BaseRepository {
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get personalized homepage feed for authenticated user
     */
    public function getPersonalizedFeed($userId, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // Get user's interests based on reading history and follows
        $userInterests = $this->getUserInterests($userId);
        
        // Combine different content types for the feed
        $feedItems = [];
        
        // 1. Articles from followed authors (30% of feed)
        $followedArticles = $this->getFollowedAuthorsArticles($userId, ceil($limit * 0.3));
        $feedItems = array_merge($feedItems, $this->addFeedType($followedArticles, 'following'));
        
        // 2. Articles with user's interested tags (40% of feed)
        $interestedArticles = $this->getArticlesByUserInterests($userId, $userInterests, ceil($limit * 0.4));
        $feedItems = array_merge($feedItems, $this->addFeedType($interestedArticles, 'recommended'));
        
        // 3. Trending articles (20% of feed)
        $trendingArticles = $this->getTrendingArticles(ceil($limit * 0.2));
        $feedItems = array_merge($feedItems, $this->addFeedType($trendingArticles, 'trending'));
        
        // 4. Latest articles (10% of feed)
        $latestArticles = $this->getLatestArticles(ceil($limit * 0.1), $userId);
        $feedItems = array_merge($feedItems, $this->addFeedType($latestArticles, 'latest'));
        
        // Shuffle and limit the feed
        shuffle($feedItems);
        $feedItems = array_slice($feedItems, $offset, $limit);
        
        return [
            'data' => $feedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'has_more' => count($feedItems) === $limit
            ]
        ];
    }

    /**
     * Get public homepage feed for non-authenticated users
     */
    public function getPublicFeed($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $feedItems = [];
        
        // 1. Trending articles (50% of feed)
        $trendingArticles = $this->getTrendingArticles(ceil($limit * 0.5));
        $feedItems = array_merge($feedItems, $this->addFeedType($trendingArticles, 'trending'));
        
        // 2. Popular articles (30% of feed)
        $popularArticles = $this->getPopularArticles(ceil($limit * 0.3));
        $feedItems = array_merge($feedItems, $this->addFeedType($popularArticles, 'popular'));
        
        // 3. Latest articles (20% of feed)
        $latestArticles = $this->getLatestArticles(ceil($limit * 0.2));
        $feedItems = array_merge($feedItems, $this->addFeedType($latestArticles, 'latest'));
        
        // Sort by engagement score and recency
        usort($feedItems, function($a, $b) {
            $scoreA = $this->calculateEngagementScore($a);
            $scoreB = $this->calculateEngagementScore($b);
            return $scoreB - $scoreA;
        });
        
        $feedItems = array_slice($feedItems, $offset, $limit);
        
        return [
            'data' => $feedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'has_more' => count($feedItems) === $limit
            ]
        ];
    }

    /**
     * Get trending articles based on recent engagement
     */
    public function getTrendingArticles($limit = 10, $timeframe = '7 days') {
        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags,
                       (
                           (a.view_count * 0.1) + 
                           (a.clap_count * 0.3) + 
                           (a.comment_count * 0.4) +
                           (DATEDIFF(NOW(), a.published_at) * -0.1)
                       ) as trending_score
                FROM articles a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.status = 'published' 
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY a.id
                HAVING trending_score > 0
                ORDER BY trending_score DESC, a.published_at DESC
                LIMIT ?";

        $days = $this->parseTimeframeToDays($timeframe);
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $limit]);
        $articles = $stmt->fetchAll();

        return $this->processArticles($articles);
    }

    /**
     * Get popular articles based on all-time engagement
     */
    public function getPopularArticles($limit = 10, $timeframe = '30 days') {
        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags,
                       (a.view_count + a.clap_count * 2 + a.comment_count * 3) as popularity_score
                FROM articles a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.status = 'published'
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY a.id
                ORDER BY popularity_score DESC, a.published_at DESC
                LIMIT ?";

        $days = $this->parseTimeframeToDays($timeframe);
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days, $limit]);
        $articles = $stmt->fetchAll();

        return $this->processArticles($articles);
    }

    /**
     * Get latest published articles
     */
    public function getLatestArticles($limit = 10, $excludeUserId = null) {
        $whereClause = "a.status = 'published'";
        $params = [];
        
        if ($excludeUserId) {
            $whereClause .= " AND a.author_id != ?";
            $params[] = $excludeUserId;
        }

        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags
                FROM articles a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE {$whereClause}
                GROUP BY a.id
                ORDER BY a.published_at DESC
                LIMIT ?";

        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        return $this->processArticles($articles);
    }

    /**
     * Get articles from authors the user follows
     */
    private function getFollowedAuthorsArticles($userId, $limit = 10) {
        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags
                FROM articles a
                INNER JOIN follows f ON a.author_id = f.following_id
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE f.follower_id = ? 
                AND a.status = 'published'
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY a.id
                ORDER BY a.published_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        $articles = $stmt->fetchAll();

        return $this->processArticles($articles);
    }

    /**
     * Get articles based on user's interests (tags they've interacted with)
     */
    private function getArticlesByUserInterests($userId, $interests, $limit = 10) {
        if (empty($interests)) {
            return [];
        }

        $tagIds = array_column($interests, 'tag_id');
        $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';

        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags,
                       SUM(ui.interest_score) as relevance_score
                FROM articles a
                INNER JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN tags t ON at.tag_id = t.id
                INNER JOIN (
                    SELECT tag_id, interest_score FROM (VALUES " . 
                    implode(',', array_map(function($interest) {
                        return "({$interest['tag_id']}, {$interest['interest_score']})";
                    }, $interests)) . ") AS ui(tag_id, interest_score)
                ) ui ON at.tag_id = ui.tag_id
                WHERE a.status = 'published'
                AND a.author_id != ?
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                GROUP BY a.id
                ORDER BY relevance_score DESC, a.published_at DESC
                LIMIT ?";

        $params = array_merge($tagIds, [$userId, $limit]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        return $this->processArticles($articles);
    }

    /**
     * Get user's interests based on their activity
     */
    private function getUserInterests($userId) {
        $interests = [];

        // Get tags from articles user has clapped
        $sql = "SELECT t.id as tag_id, t.name, COUNT(*) * 3 as interest_score
                FROM claps c
                INNER JOIN articles a ON c.article_id = a.id
                INNER JOIN article_tags at ON a.id = at.article_id
                INNER JOIN tags t ON at.tag_id = t.id
                WHERE c.user_id = ?
                GROUP BY t.id
                ORDER BY interest_score DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $clapInterests = $stmt->fetchAll();
        $interests = array_merge($interests, $clapInterests);

        // Get tags from articles user has commented on
        $sql = "SELECT t.id as tag_id, t.name, COUNT(*) * 2 as interest_score
                FROM comments c
                INNER JOIN articles a ON c.article_id = a.id
                INNER JOIN article_tags at ON a.id = at.article_id
                INNER JOIN tags t ON at.tag_id = t.id
                WHERE c.user_id = ?
                GROUP BY t.id
                ORDER BY interest_score DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $commentInterests = $stmt->fetchAll();
        $interests = array_merge($interests, $commentInterests);

        // Get tags from articles user has bookmarked
        $sql = "SELECT t.id as tag_id, t.name, COUNT(*) * 2 as interest_score
                FROM bookmarks b
                INNER JOIN articles a ON b.article_id = a.id
                INNER JOIN article_tags at ON a.id = at.article_id
                INNER JOIN tags t ON at.tag_id = t.id
                WHERE b.user_id = ?
                GROUP BY t.id
                ORDER BY interest_score DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $bookmarkInterests = $stmt->fetchAll();
        $interests = array_merge($interests, $bookmarkInterests);

        // Aggregate and normalize interest scores
        $aggregatedInterests = [];
        foreach ($interests as $interest) {
            $tagId = $interest['tag_id'];
            if (!isset($aggregatedInterests[$tagId])) {
                $aggregatedInterests[$tagId] = [
                    'tag_id' => $tagId,
                    'name' => $interest['name'],
                    'interest_score' => 0
                ];
            }
            $aggregatedInterests[$tagId]['interest_score'] += $interest['interest_score'];
        }

        // Sort by interest score and return top interests
        usort($aggregatedInterests, function($a, $b) {
            return $b['interest_score'] - $a['interest_score'];
        });

        return array_slice($aggregatedInterests, 0, 10);
    }

    /**
     * Get feed with specific filters
     */
    public function getFilteredFeed($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $whereConditions = ['a.status = ?'];
        $params = ['published'];

        // Apply filters
        if (!empty($filters['tag'])) {
            $whereConditions[] = 'EXISTS (
                SELECT 1 FROM article_tags at2 
                JOIN tags t2 ON at2.tag_id = t2.id 
                WHERE at2.article_id = a.id AND t2.slug = ?
            )';
            $params[] = $filters['tag'];
        }

        if (!empty($filters['author_id'])) {
            $whereConditions[] = 'a.author_id = ?';
            $params[] = $filters['author_id'];
        }

        if (!empty($filters['timeframe'])) {
            $days = $this->parseTimeframeToDays($filters['timeframe']);
            $whereConditions[] = 'a.published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)';
            $params[] = $days;
        }

        if (!empty($filters['min_reading_time'])) {
            $whereConditions[] = 'a.reading_time >= ?';
            $params[] = $filters['min_reading_time'];
        }

        if (!empty($filters['max_reading_time'])) {
            $whereConditions[] = 'a.reading_time <= ?';
            $params[] = $filters['max_reading_time'];
        }

        $whereClause = implode(' AND ', $whereConditions);

        // Determine sort order
        $orderBy = 'a.published_at DESC';
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'popular':
                    $orderBy = '(a.view_count + a.clap_count * 2 + a.comment_count * 3) DESC, a.published_at DESC';
                    break;
                case 'trending':
                    $orderBy = '((a.view_count * 0.1) + (a.clap_count * 0.3) + (a.comment_count * 0.4) + (DATEDIFF(NOW(), a.published_at) * -0.1)) DESC';
                    break;
                case 'oldest':
                    $orderBy = 'a.published_at ASC';
                    break;
                default:
                    $orderBy = 'a.published_at DESC';
            }
        }

        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags
                FROM articles a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE {$whereClause}
                GROUP BY a.id
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        $processedArticles = $this->processArticles($articles);
        $feedItems = $this->addFeedType($processedArticles, 'filtered');

        return [
            'data' => $feedItems,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'has_more' => count($feedItems) === $limit
            ]
        ];
    }

    /**
     * Process articles array (decode content, parse tags)
     */
    private function processArticles($articles) {
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
        }
        return $articles;
    }

    /**
     * Add feed type to articles
     */
    private function addFeedType($articles, $type) {
        return array_map(function($article) use ($type) {
            $article['feed_type'] = $type;
            return $article;
        }, $articles);
    }

    /**
     * Calculate engagement score for sorting
     */
    private function calculateEngagementScore($article) {
        $views = $article['view_count'] ?? 0;
        $claps = $article['clap_count'] ?? 0;
        $comments = $article['comment_count'] ?? 0;
        $daysOld = (time() - strtotime($article['published_at'])) / (24 * 60 * 60);
        
        return ($views * 0.1) + ($claps * 0.3) + ($comments * 0.4) - ($daysOld * 0.1);
    }

    /**
     * Parse timeframe string to days
     */
    private function parseTimeframeToDays($timeframe) {
        switch (strtolower($timeframe)) {
            case '1 day':
            case 'day':
                return 1;
            case '3 days':
                return 3;
            case '1 week':
            case 'week':
            case '7 days':
                return 7;
            case '2 weeks':
                return 14;
            case '1 month':
            case 'month':
            case '30 days':
                return 30;
            case '3 months':
                return 90;
            case '6 months':
                return 180;
            case '1 year':
            case 'year':
                return 365;
            default:
                return 7; // Default to 1 week
        }
    }

    /**
     * Get recommended articles for a specific user based on collaborative filtering
     */
    public function getRecommendedArticles($userId, $limit = 10) {
        // Find users with similar interests (collaborative filtering)
        $sql = "SELECT DISTINCT c2.user_id, COUNT(*) as similarity_score
                FROM claps c1
                INNER JOIN claps c2 ON c1.article_id = c2.article_id
                WHERE c1.user_id = ? AND c2.user_id != ?
                GROUP BY c2.user_id
                ORDER BY similarity_score DESC
                LIMIT 20";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId]);
        $similarUsers = $stmt->fetchAll();

        if (empty($similarUsers)) {
            // Fallback to popular articles if no similar users found
            return $this->getPopularArticles($limit);
        }

        $similarUserIds = array_column($similarUsers, 'user_id');
        $placeholders = str_repeat('?,', count($similarUserIds) - 1) . '?';

        // Get articles liked by similar users that current user hasn't interacted with
        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags,
                       COUNT(DISTINCT c.user_id) as recommendation_score
                FROM articles a
                INNER JOIN claps c ON a.id = c.article_id
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE c.user_id IN ({$placeholders})
                AND a.status = 'published'
                AND a.author_id != ?
                AND a.id NOT IN (
                    SELECT article_id FROM claps WHERE user_id = ?
                    UNION
                    SELECT article_id FROM bookmarks WHERE user_id = ?
                    UNION
                    SELECT article_id FROM comments WHERE user_id = ?
                )
                GROUP BY a.id
                ORDER BY recommendation_score DESC, a.published_at DESC
                LIMIT ?";

        $params = array_merge($similarUserIds, [$userId, $userId, $userId, $userId, $limit]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        return $this->processArticles($articles);
    }
}