<?php

require_once __DIR__ . '/BaseRepository.php';

class Article extends BaseRepository {
    protected $table = 'articles';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a new article
     */
    public function create($data) {
        try {
            // Check if slug column exists
            $hasSlugColumn = $this->hasSlugColumn();
            
            // Generate unique slug if column exists
            $slug = $hasSlugColumn ? $this->generateUniqueSlug($data['title']) : null;
            
            if ($hasSlugColumn) {
                $sql = "INSERT INTO {$this->table} (
                    author_id, publication_id, title, subtitle, content, 
                    featured_image_url, status, reading_time, published_at, slug
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params = [
                    $data['author_id'],
                    $data['publication_id'] ?? null,
                    $data['title'],
                    $data['subtitle'] ?? '',
                    json_encode($data['content']),
                    $data['featured_image_url'] ?? null,
                    $data['status'] ?? 'draft',
                    $data['reading_time'] ?? 0,
                    $data['status'] === 'published' ? date('Y-m-d H:i:s') : null,
                    $slug
                ];
            } else {
                $sql = "INSERT INTO {$this->table} (
                    author_id, publication_id, title, subtitle, content, 
                    featured_image_url, status, reading_time, published_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $params = [
                    $data['author_id'],
                    $data['publication_id'] ?? null,
                    $data['title'],
                    $data['subtitle'] ?? '',
                    json_encode($data['content']),
                    $data['featured_image_url'] ?? null,
                    $data['status'] ?? 'draft',
                    $data['reading_time'] ?? 0,
                    $data['status'] === 'published' ? date('Y-m-d H:i:s') : null
                ];
            }
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);

            if ($result) {
                $articleId = $this->db->lastInsertId();
                
                // Handle tags if provided
                if (!empty($data['tags'])) {
                    $this->updateArticleTags($articleId, $data['tags']);
                }
                
                return $this->findById($articleId);
            }

            return false;
        } catch (Exception $e) {
            error_log("Article model create error: " . $e->getMessage());
            error_log("SQL: " . ($sql ?? 'SQL not set'));
            error_log("Params: " . json_encode($params ?? []));
            throw $e;
        }
    }

    /**
     * Update an existing article
     */
    public function update($id, $data) {
        // Check if article exists and user has permission
        $article = $this->findById($id);
        if (!$article) {
            return false;
        }

        $hasSlugColumn = $this->hasSlugColumn();

        $sql = "UPDATE {$this->table} SET 
            title = ?, subtitle = ?, content = ?, featured_image_url = ?, 
            status = ?, reading_time = ?, updated_at = CURRENT_TIMESTAMP";
        
        $params = [
            $data['title'],
            $data['subtitle'] ?? '',
            json_encode($data['content']),
            $data['featured_image_url'] ?? $article['featured_image_url'],
            $data['status'] ?? $article['status'],
            $data['reading_time'] ?? $article['reading_time']
        ];

        // Update slug if title changed and column exists
        if ($hasSlugColumn && isset($data['title']) && $data['title'] !== $article['title']) {
            $newSlug = $this->generateUniqueSlug($data['title'], $id);
            $sql .= ", slug = ?";
            $params[] = $newSlug;
        }

        // Update published_at if status changes to published
        if (isset($data['status']) && $data['status'] === 'published' && $article['status'] !== 'published') {
            $sql .= ", published_at = CURRENT_TIMESTAMP";
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        if ($result) {
            // Handle tags if provided
            if (isset($data['tags'])) {
                $this->updateArticleTags($id, $data['tags']);
            }
            
            return $this->findById($id);
        }

        return false;
    }

    /**
     * Find article by ID with author and tags
     */
    public function findById($id) {
        $hasSlugColumn = $this->hasSlugColumn();
        $slugSelect = $hasSlugColumn ? ', a.slug' : '';
        
        $sql = "SELECT a.*{$slugSelect}, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(t.name) as tags
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.id = ?
                GROUP BY a.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $article = $stmt->fetch();

        if ($article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
            
            // Add slug if column doesn't exist
            if (!$hasSlugColumn) {
                $article['slug'] = $this->generateUniqueSlug($article['title'], $id);
            }
        }

        return $article;
    }

    /**
     * Get articles with pagination and filters
     */
    public function getArticles($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $whereConditions = ['a.status = ?'];
        $params = ['published'];

        // Build WHERE clause based on filters
        if (!empty($filters['author_id'])) {
            $whereConditions[] = 'a.author_id = ?';
            $params[] = $filters['author_id'];
        }

        if (!empty($filters['tag'])) {
            $whereConditions[] = 't.slug = ?';
            $params[] = $filters['tag'];
        }

        if (!empty($filters['search'])) {
            $whereConditions[] = '(a.title LIKE ? OR a.subtitle LIKE ? OR a.content LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(' AND ', $whereConditions);

        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE {$whereClause}
                GROUP BY a.id
                ORDER BY a.published_at DESC
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
        }

        return $articles;
    }

    /**
     * Get user's drafts
     */
    public function getUserDrafts($userId, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT a.*, GROUP_CONCAT(t.name) as tags
                FROM {$this->table} a
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.author_id = ? AND a.status = 'draft'
                GROUP BY a.id
                ORDER BY a.updated_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        $drafts = $stmt->fetchAll();

        // Process drafts
        foreach ($drafts as &$draft) {
            $draft['content'] = json_decode($draft['content'], true);
            $draft['tags'] = $draft['tags'] ? explode(',', $draft['tags']) : [];
        }

        return $drafts;
    }

    /**
     * Delete article with ownership verification
     */
    public function deleteArticle($id, $userId) {
        // Verify ownership
        $article = $this->findById($id);
        if (!$article || $article['author_id'] != $userId) {
            return false;
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ? AND author_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id, $userId]);
    }

    /**
     * Update article tags
     */
    private function updateArticleTags($articleId, $tags) {
        // Remove existing tags
        $sql = "DELETE FROM article_tags WHERE article_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);

        // Add new tags
        foreach ($tags as $tagName) {
            $tagId = $this->getOrCreateTag($tagName);
            if ($tagId) {
                $sql = "INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$articleId, $tagId]);
            }
        }
    }

    /**
     * Get or create tag
     */
    private function getOrCreateTag($tagName) {
        $slug = $this->createSlug($tagName);
        
        // Check if tag exists
        $sql = "SELECT id FROM tags WHERE slug = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        $tag = $stmt->fetch();

        if ($tag) {
            return $tag['id'];
        }

        // Create new tag
        $sql = "INSERT INTO tags (name, slug) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$tagName, $slug]);

        return $result ? $this->db->lastInsertId() : null;
    }

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
     * Generate unique slug for article
     */
    private function generateUniqueSlug($title, $excludeId = null) {
        $baseSlug = $this->createSlug($title);
        $slug = $baseSlug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     */
    private function slugExists($slug, $excludeId = null) {
        if (!$this->hasSlugColumn()) {
            return false; // Slug doesn't exist if column doesn't exist
        }
        
        $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
        $params = [$slug];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }

    /**
     * Find article by slug
     */
    public function findBySlug($slug) {
        if (!$this->hasSlugColumn()) {
            return false; // Can't find by slug if column doesn't exist
        }
        
        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(t.name) as tags
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.slug = ?
                GROUP BY a.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        $article = $stmt->fetch();

        if ($article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
        }

        return $article;
    }



    /**
     * Increment view count
     */
    public function incrementViewCount($id) {
        $sql = "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Track article view with detailed information
     */
    public function trackView($articleId, $userId = null, $userAgent = '', $referrer = '', $ipAddress = '') {
        // First increment the basic view count
        $this->incrementViewCount($articleId);

        // Then record detailed view tracking
        $sql = "INSERT INTO article_views (article_id, user_id, user_agent, referrer, ip_address, viewed_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$articleId, $userId, $userAgent, $referrer, $ipAddress]);
    }

    /**
     * Track when an article is actually read (not just viewed)
     */
    public function trackRead($articleId, $userId = null, $timeSpent = 0, $scrollDepth = 0) {
        $sql = "INSERT INTO article_reads (article_id, user_id, time_spent, scroll_depth, read_at) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                time_spent = GREATEST(time_spent, VALUES(time_spent)),
                scroll_depth = GREATEST(scroll_depth, VALUES(scroll_depth)),
                read_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$articleId, $userId, $timeSpent, $scrollDepth]);
    }

    /**
     * Record detailed analytics data
     */
    public function recordAnalytics($articleId, $userId = null, $timeSpent = 0, $scrollDepth = 0, $isRead = false, $ipAddress = '') {
        $sql = "INSERT INTO article_analytics (article_id, user_id, time_spent, scroll_depth, is_read, ip_address, recorded_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$articleId, $userId, $timeSpent, $scrollDepth, $isRead ? 1 : 0, $ipAddress]);
    }

    /**
     * Get analytics data for an article
     */
    public function getArticleAnalytics($articleId) {
        // Basic stats
        $basicStats = $this->getBasicAnalytics($articleId);
        
        // View trends (last 30 days)
        $viewTrends = $this->getViewTrends($articleId, 30);
        
        // Reading stats
        $readingStats = $this->getReadingStats($articleId);
        
        // Referrer stats
        $referrerStats = $this->getReferrerStats($articleId);
        
        return [
            'basic_stats' => $basicStats,
            'view_trends' => $viewTrends,
            'reading_stats' => $readingStats,
            'referrer_stats' => $referrerStats
        ];
    }

    /**
     * Get basic analytics stats
     */
    private function getBasicAnalytics($articleId) {
        $sql = "SELECT 
                    COUNT(DISTINCT av.id) as total_views,
                    COUNT(DISTINCT ar.id) as total_reads,
                    AVG(ar.time_spent) as avg_time_spent,
                    AVG(ar.scroll_depth) as avg_scroll_depth,
                    COUNT(DISTINCT av.user_id) as unique_viewers,
                    COUNT(DISTINCT ar.user_id) as unique_readers
                FROM articles a
                LEFT JOIN article_views av ON a.id = av.article_id
                LEFT JOIN article_reads ar ON a.id = ar.article_id
                WHERE a.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        return $stmt->fetch();
    }

    /**
     * Get view trends over time
     */
    private function getViewTrends($articleId, $days = 30) {
        $sql = "SELECT 
                    DATE(viewed_at) as date,
                    COUNT(*) as views,
                    COUNT(DISTINCT user_id) as unique_views
                FROM article_views 
                WHERE article_id = ? 
                AND viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(viewed_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Get reading statistics
     */
    private function getReadingStats($articleId) {
        $sql = "SELECT 
                    AVG(time_spent) as avg_time_spent,
                    MAX(time_spent) as max_time_spent,
                    AVG(scroll_depth) as avg_scroll_depth,
                    COUNT(CASE WHEN scroll_depth >= 75 THEN 1 END) as deep_reads,
                    COUNT(*) as total_reads
                FROM article_reads 
                WHERE article_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        return $stmt->fetch();
    }

    /**
     * Get referrer statistics
     */
    private function getReferrerStats($articleId) {
        $sql = "SELECT 
                    CASE 
                        WHEN referrer = '' OR referrer IS NULL THEN 'Direct'
                        WHEN referrer LIKE '%google%' THEN 'Google'
                        WHEN referrer LIKE '%facebook%' THEN 'Facebook'
                        WHEN referrer LIKE '%twitter%' THEN 'Twitter'
                        WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn'
                        ELSE 'Other'
                    END as source,
                    COUNT(*) as views
                FROM article_views 
                WHERE article_id = ?
                GROUP BY source
                ORDER BY views DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }

    /**
     * Get popular articles based on view metrics
     */
    public function getPopularArticles($limit = 10, $timeframe = 'week') {
        $interval = match($timeframe) {
            'day' => '1 DAY',
            'week' => '7 DAY',
            'month' => '30 DAY',
            default => '365 DAY' // all time
        };

        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags,
                       COUNT(DISTINCT av.id) as recent_views,
                       COUNT(DISTINCT ar.id) as recent_reads,
                       (COUNT(DISTINCT av.id) + COUNT(DISTINCT ar.id) * 2 + a.clap_count * 0.5) as popularity_score
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                LEFT JOIN article_views av ON a.id = av.article_id AND av.viewed_at >= DATE_SUB(NOW(), INTERVAL {$interval})
                LEFT JOIN article_reads ar ON a.id = ar.article_id AND ar.read_at >= DATE_SUB(NOW(), INTERVAL {$interval})
                WHERE a.status = 'published'
                GROUP BY a.id
                ORDER BY popularity_score DESC, a.published_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        $articles = $stmt->fetchAll();

        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
        }

        return $articles;
    }

    /**
     * Get trending articles based on recent engagement
     */
    public function getTrendingArticles($limit = 10) {
        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags,
                       (a.view_count + a.clap_count * 2 + a.comment_count * 3) as engagement_score
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.status = 'published' 
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY a.id
                ORDER BY engagement_score DESC, a.published_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        $articles = $stmt->fetchAll();

        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
        }

        return $articles;
    }

    /**
     * Publish an article (change status from draft to published)
     */
    public function publish($id, $userId, $options = []) {
        // Verify ownership
        $article = $this->findById($id);
        if (!$article || $article['author_id'] != $userId) {
            return false;
        }

        // Only allow publishing drafts or archived articles
        if (!in_array($article['status'], ['draft', 'archived'])) {
            return false;
        }

        // Ensure article has a slug
        if (empty($article['slug'])) {
            $slug = $this->generateUniqueSlug($article['title'], $id);
            $this->updateSlug($id, $slug);
        }

        $sql = "UPDATE {$this->table} SET 
                status = 'published', 
                published_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND author_id = ?";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$id, $userId]);

        if ($result && !empty($options['notify_followers'])) {
            $this->notifyFollowersOfNewArticle($userId, $id);
        }

        return $result ? $this->findById($id) : false;
    }

    /**
     * Unpublish an article (change status from published to draft)
     */
    public function unpublish($id, $userId) {
        // Verify ownership
        $article = $this->findById($id);
        if (!$article || $article['author_id'] != $userId) {
            return false;
        }

        // Only allow unpublishing published articles
        if ($article['status'] !== 'published') {
            return false;
        }

        $sql = "UPDATE {$this->table} SET 
                status = 'draft', 
                published_at = NULL,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND author_id = ?";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$id, $userId]);

        return $result ? $this->findById($id) : false;
    }

    /**
     * Archive an article
     */
    public function archive($id, $userId) {
        // Verify ownership
        $article = $this->findById($id);
        if (!$article || $article['author_id'] != $userId) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET 
                status = 'archived',
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND author_id = ?";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$id, $userId]);

        return $result ? $this->findById($id) : false;
    }

    /**
     * Get articles by status for a user
     */
    public function getArticlesByStatus($userId, $status, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT a.*, GROUP_CONCAT(t.name) as tags
                FROM {$this->table} a
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.author_id = ? AND a.status = ?
                GROUP BY a.id
                ORDER BY a.updated_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $status, $limit, $offset]);
        $articles = $stmt->fetchAll();

        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
        }

        return $articles;
    }

    /**
     * Enhanced reading time calculation with more accurate algorithm
     */
    public function calculateReadingTime($content) {
        // Convert content to plain text
        if (is_array($content)) {
            // Handle rich text content (JSON format)
            $plainText = $this->extractTextFromRichContent($content);
        } else {
            // Handle plain text or HTML content
            $plainText = strip_tags($content);
        }
        
        // Count words (more accurate word counting)
        $wordCount = str_word_count($plainText, 0, 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ');
        
        // Calculate reading time based on average reading speed
        // Average adult reading speed: 200-250 words per minute
        // We use 225 as a middle ground
        $wordsPerMinute = 225;
        $readingTime = max(1, ceil($wordCount / $wordsPerMinute));
        
        return $readingTime;
    }

    /**
     * Update article slug
     */
    private function updateSlug($id, $slug) {
        if (!$this->hasSlugColumn()) {
            return false; // Can't update slug if column doesn't exist
        }
        
        $sql = "UPDATE {$this->table} SET slug = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$slug, $id]);
    }

    /**
     * Notify followers of new published article
     */
    private function notifyFollowersOfNewArticle($authorId, $articleId) {
        // Get all followers of the author
        $sql = "SELECT follower_id FROM follows WHERE following_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$authorId]);
        $followers = $stmt->fetchAll();

        if (empty($followers)) {
            return;
        }

        // Get article details for notification
        $article = $this->findById($articleId);
        if (!$article) {
            return;
        }

        // Create notifications for all followers
        $notificationSql = "INSERT INTO notifications (user_id, type, content, related_id) VALUES (?, 'article_published', ?, ?)";
        $notificationStmt = $this->db->prepare($notificationSql);

        $notificationContent = json_encode([
            'author_name' => $article['username'],
            'article_title' => $article['title'],
            'article_slug' => $article['slug']
        ]);

        foreach ($followers as $follower) {
            $notificationStmt->execute([
                $follower['follower_id'],
                $notificationContent,
                $articleId
            ]);
        }
    }

    /**
     * Get article preview data for publishing dialog
     */
    public function getArticlePreview($id, $userId) {
        // Verify ownership
        $article = $this->findById($id);
        if (!$article || $article['author_id'] != $userId) {
            return false;
        }

        // Generate preview data
        $preview = [
            'id' => $article['id'],
            'title' => $article['title'],
            'subtitle' => $article['subtitle'],
            'content' => $article['content'],
            'featured_image_url' => $article['featured_image_url'],
            'tags' => $article['tags'],
            'reading_time' => $article['reading_time'],
            'status' => $article['status'],
            'slug' => $article['slug'] ?: $this->generateUniqueSlug($article['title'], $id),
            'word_count' => $this->getWordCount($article['content']),
            'estimated_url' => $this->generateArticleUrl($article['slug'] ?: $this->generateUniqueSlug($article['title'], $id))
        ];

        return $preview;
    }

    /**
     * Get word count from content
     */
    private function getWordCount($content) {
        $plainText = $this->extractTextFromRichContent($content);
        return str_word_count($plainText, 0, 'àáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ');
    }

    /**
     * Generate article URL
     */
    private function generateArticleUrl($slug) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host . '/article/' . $slug;
    }

    /**
     * Check if slug column exists in articles table
     */
    private function hasSlugColumn() {
        try {
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'slug'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Add slug column if it doesn't exist
     */
    public function ensureSlugColumn() {
        if (!$this->hasSlugColumn()) {
            try {
                // Add slug column
                $sql = "ALTER TABLE {$this->table} ADD COLUMN slug VARCHAR(255) UNIQUE AFTER title";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                
                // Create index
                $sql = "CREATE INDEX idx_articles_slug ON {$this->table}(slug)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                
                // Update existing articles with slugs
                $sql = "UPDATE {$this->table} 
                        SET slug = CONCAT(
                            LOWER(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(title, ' ', '-'),
                                            '?', ''
                                        ),
                                        '!', ''
                                    ),
                                    '.', ''
                                )
                            ),
                            '-',
                            id
                        ) 
                        WHERE slug IS NULL";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                
                return true;
            } catch (Exception $e) {
                error_log("Failed to add slug column: " . $e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * Get related articles based on tags and content similarity
     */
    public function getRelatedArticles($articleId, $limit = 5) {
        $article = $this->findById($articleId);
        if (!$article) {
            return [];
        }

        // Get articles with similar tags
        $sql = "SELECT DISTINCT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags,
                       COUNT(DISTINCT shared_tags.tag_id) as shared_tag_count
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                LEFT JOIN article_tags shared_tags ON shared_tags.article_id = a.id
                LEFT JOIN article_tags current_tags ON current_tags.tag_id = shared_tags.tag_id AND current_tags.article_id = ?
                WHERE a.status = 'published' 
                AND a.id != ?
                AND current_tags.tag_id IS NOT NULL
                GROUP BY a.id
                ORDER BY shared_tag_count DESC, a.published_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId, $articleId, $limit]);
        $articles = $stmt->fetchAll();

        // Process articles
        foreach ($articles as &$relatedArticle) {
            $relatedArticle['content'] = json_decode($relatedArticle['content'], true);
            $relatedArticle['tags'] = $relatedArticle['tags'] ? explode(',', $relatedArticle['tags']) : [];
        }

        return $articles;
    }

    /**
     * Get more articles from the same author
     */
    public function getMoreFromAuthor($authorId, $excludeArticleId = null, $limit = 5) {
        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE a.author_id = ? 
                AND a.status = 'published'";
        
        $params = [$authorId];
        
        if ($excludeArticleId) {
            $sql .= " AND a.id != ?";
            $params[] = $excludeArticleId;
        }
        
        $sql .= " GROUP BY a.id
                  ORDER BY a.published_at DESC
                  LIMIT ?";
        
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
        }

        return $articles;
    }

    /**
     * Get recommended articles for a user based on their reading history and interests
     */
    public function getRecommendedArticles($userId = null, $limit = 10) {
        if ($userId) {
            // Get personalized recommendations based on user's reading history and follows
            $sql = "SELECT DISTINCT a.*, u.username, u.profile_image_url as author_avatar,
                           GROUP_CONCAT(DISTINCT t.name) as tags,
                           (a.view_count * 0.3 + a.clap_count * 0.5 + a.comment_count * 0.2) as engagement_score
                    FROM {$this->table} a
                    LEFT JOIN users u ON a.author_id = u.id
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    LEFT JOIN tags t ON at.tag_id = t.id
                    LEFT JOIN follows f ON f.following_id = a.author_id AND f.follower_id = ?
                    WHERE a.status = 'published'
                    AND a.published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY a.id
                    ORDER BY 
                        CASE WHEN f.follower_id IS NOT NULL THEN 1 ELSE 0 END DESC,
                        engagement_score DESC,
                        a.published_at DESC
                    LIMIT ?";
            
            $params = [$userId, $limit];
        } else {
            // Get general recommendations based on engagement
            $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                           GROUP_CONCAT(DISTINCT t.name) as tags,
                           (a.view_count * 0.3 + a.clap_count * 0.5 + a.comment_count * 0.2) as engagement_score
                    FROM {$this->table} a
                    LEFT JOIN users u ON a.author_id = u.id
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    LEFT JOIN tags t ON at.tag_id = t.id
                    WHERE a.status = 'published'
                    AND a.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY a.id
                    ORDER BY engagement_score DESC, a.published_at DESC
                    LIMIT ?";
            
            $params = [$limit];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();

        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
        }

        return $articles;
    }

    /**
     * Extract plain text from rich content structure
     */
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
                        case 'codeBlock':
                            if (isset($block['content'])) {
                                // Code blocks count as text but with different weight
                                $text .= $this->extractTextFromRichContent($block['content']) . ' ';
                            }
                            break;
                    }
                }
            }
        } elseif (is_string($content)) {
            $text = strip_tags($content);
        }
        
        return $text;
    }

    /**
     * Get article counts by status for a user
     */
    public function getArticleCountsByStatus($userId) {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count
                FROM {$this->table} 
                WHERE author_id = ?
                GROUP BY status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $results = $stmt->fetchAll();
        
        // Format results as associative array
        $counts = [
            'draft' => 0,
            'published' => 0,
            'archived' => 0
        ];
        
        foreach ($results as $result) {
            $counts[$result['status']] = (int)$result['count'];
        }
        
        return $counts;
    }

    /**
     * Get total views for all articles by author
     */
    public function getTotalViewsByAuthor($userId) {
        $sql = "SELECT COALESCE(SUM(view_count), 0) as total_views
                FROM {$this->table} 
                WHERE author_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return (int)$result['total_views'];
    }

    /**
     * Get top performing articles by author
     */
    public function getTopArticlesByAuthor($userId, $limit = 5) {
        $sql = "SELECT 
                    id, title, view_count, clap_count, comment_count, published_at,
                    (view_count + clap_count * 2 + comment_count * 3) as engagement_score
                FROM {$this->table} 
                WHERE author_id = ? AND status = 'published'
                ORDER BY engagement_score DESC, published_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get views over time for author's articles
     */
    public function getViewsOverTime($userId, $days = 30) {
        $sql = "SELECT 
                    DATE(av.viewed_at) as date,
                    COUNT(*) as views
                FROM article_views av
                JOIN {$this->table} a ON av.article_id = a.id
                WHERE a.author_id = ? 
                AND av.viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(av.viewed_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $days]);
        $results = $stmt->fetchAll();
        
        // Fill in missing dates with 0 views
        $viewsByDate = [];
        foreach ($results as $result) {
            $viewsByDate[$result['date']] = (int)$result['views'];
        }
        
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $data[] = [
                'date' => $date,
                'views' => $viewsByDate[$date] ?? 0
            ];
        }
        
        return $data;
    }

    /**
     * Get article performance comparison for author
     */
    public function getArticlePerformanceComparison($userId) {
        $sql = "SELECT 
                    id, title, view_count, clap_count, comment_count, published_at,
                    (view_count + clap_count * 2 + comment_count * 3) as engagement_score
                FROM {$this->table} 
                WHERE author_id = ? AND status = 'published'
                ORDER BY published_at DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get user articles for dashboard with enhanced data
     */
    public function getUserArticlesForDashboard($userId, $status = 'all', $page = 1, $limit = 10, $sortBy = 'updated_at', $sortOrder = 'desc') {
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $whereConditions = ['a.author_id = ?'];
        $params = [$userId];
        
        if ($status !== 'all') {
            $whereConditions[] = 'a.status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Validate sort parameters
        $allowedSortFields = ['updated_at', 'created_at', 'view_count', 'clap_count', 'title', 'published_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'updated_at';
        }
        
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT 
                    a.*, 
                    GROUP_CONCAT(DISTINCT t.name) as tags,
                    (a.view_count + a.clap_count * 2 + a.comment_count * 3) as engagement_score
                FROM {$this->table} a
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE {$whereClause}
                GROUP BY a.id
                ORDER BY a.{$sortBy} {$sortOrder}
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
            
            // Add preview text
            $article['preview'] = $this->generatePreviewText($article['content']);
        }
        
        return $articles;
    }

    /**
     * Get tag performance by author
     */
    public function getTagPerformanceByAuthor($userId) {
        $sql = "SELECT 
                    t.name as tag_name,
                    COUNT(DISTINCT a.id) as article_count,
                    AVG(a.view_count) as avg_views,
                    AVG(a.clap_count) as avg_claps,
                    AVG(a.comment_count) as avg_comments,
                    SUM(a.view_count) as total_views
                FROM {$this->table} a
                JOIN article_tags at ON a.id = at.article_id
                JOIN tags t ON at.tag_id = t.id
                WHERE a.author_id = ? AND a.status = 'published'
                GROUP BY t.id, t.name
                ORDER BY total_views DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Generate preview text from content
     */
    private function generatePreviewText($content, $maxLength = 150) {
        $plainText = $this->extractTextFromRichContent($content);
        
        if (strlen($plainText) <= $maxLength) {
            return $plainText;
        }
        
        // Truncate at word boundary
        $truncated = substr($plainText, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');
        
        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            return substr($plainText, 0, $lastSpace) . '...';
        }
        
        return $truncated . '...';
    }



    /**
     * Get related articles based on tags and content similarity
     */
    public function getRelatedArticles($articleId, $limit = 5) {
        // Get the current article's tags
        $sql = "SELECT t.id, t.name 
                FROM article_tags at 
                JOIN tags t ON at.tag_id = t.id 
                WHERE at.article_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        $tags = $stmt->fetchAll();
        
        if (empty($tags)) {
            // If no tags, return recent articles from same author
            $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar
                    FROM {$this->table} a
                    JOIN users u ON a.author_id = u.id
                    WHERE a.id != ? AND a.status = 'published'
                    AND a.author_id = (SELECT author_id FROM {$this->table} WHERE id = ?)
                    ORDER BY a.published_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId, $articleId, $limit]);
        } else {
            // Find articles with similar tags
            $tagIds = array_column($tags, 'id');
            $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
            
            $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                           COUNT(at.tag_id) as tag_matches
                    FROM {$this->table} a
                    JOIN users u ON a.author_id = u.id
                    JOIN article_tags at ON a.id = at.article_id
                    WHERE a.id != ? AND a.status = 'published'
                    AND at.tag_id IN ({$placeholders})
                    GROUP BY a.id
                    ORDER BY tag_matches DESC, a.published_at DESC
                    LIMIT ?";
            
            $params = array_merge([$articleId], $tagIds, [$limit]);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }
        
        $articles = $stmt->fetchAll();
        
        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['preview'] = $this->generatePreviewText($article['content']);
        }
        
        return $articles;
    }

    /**
     * Get more articles from the same author
     */
    public function getMoreFromAuthor($authorId, $excludeId = null, $limit = 5) {
        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar
                FROM {$this->table} a
                JOIN users u ON a.author_id = u.id
                WHERE a.author_id = ? AND a.status = 'published'";
        
        $params = [$authorId];
        
        if ($excludeId) {
            $sql .= " AND a.id != ?";
            $params[] = $excludeId;
        }
        
        $sql .= " ORDER BY a.published_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();
        
        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['preview'] = $this->generatePreviewText($article['content']);
        }
        
        return $articles;
    }

    /**
     * Get recommended articles for user
     */
    public function getRecommendedArticles($userId = null, $limit = 10) {
        if ($userId) {
            // Get personalized recommendations based on user's reading history and follows
            $sql = "SELECT DISTINCT a.*, u.username, u.profile_image_url as author_avatar,
                           GROUP_CONCAT(DISTINCT t.name) as tags,
                           (a.view_count + a.clap_count * 2 + a.comment_count * 3) as engagement_score
                    FROM {$this->table} a
                    JOIN users u ON a.author_id = u.id
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    LEFT JOIN tags t ON at.tag_id = t.id
                    LEFT JOIN follows f ON a.author_id = f.following_id AND f.follower_id = ?
                    WHERE a.status = 'published'
                    AND a.author_id != ?
                    AND a.published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY a.id
                    ORDER BY 
                        CASE WHEN f.following_id IS NOT NULL THEN 1 ELSE 0 END DESC,
                        engagement_score DESC,
                        a.published_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $limit]);
        } else {
            // Get general recommendations for anonymous users
            $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                           GROUP_CONCAT(DISTINCT t.name) as tags,
                           (a.view_count + a.clap_count * 2 + a.comment_count * 3) as engagement_score
                    FROM {$this->table} a
                    JOIN users u ON a.author_id = u.id
                    LEFT JOIN article_tags at ON a.id = at.article_id
                    LEFT JOIN tags t ON at.tag_id = t.id
                    WHERE a.status = 'published'
                    AND a.published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY a.id
                    ORDER BY engagement_score DESC, a.published_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
        }
        
        $articles = $stmt->fetchAll();
        
        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
            $article['preview'] = $this->generatePreviewText($article['content']);
        }
        
        return $articles;
    }
}