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
                    '', // Always empty for Medium-like experience
                    is_string($data['content']) ? $data['content'] : json_encode($data['content']),
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
                    '', // Always empty for Medium-like experience
                    is_string($data['content']) ? $data['content'] : json_encode($data['content']),
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
                
                // Scan content for moderation if published
                if (($data['status'] ?? 'draft') === 'published') {
                    $this->scanContentForModeration($articleId, $data['content']);
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
            // Handle content - if it's JSON string, decode it
            if (is_string($article['content'])) {
                $decoded = json_decode($article['content'], true);
                $article['content'] = $decoded !== null ? $decoded : $article['content'];
            }
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
            
            // Add slug if column doesn't exist
            if (!$hasSlugColumn) {
                $article['slug'] = $this->generateUniqueSlug($article['title'], $id);
            }
        }

        return $article;
    }

    /**
     * Get articles with filters and pagination
     */
    public function getArticles($filters = [], $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $whereConditions = ['a.status = ?'];
        $params = ['published'];
        
        // Apply filters
        if (!empty($filters['author_id'])) {
            $whereConditions[] = 'a.author_id = ?';
            $params[] = $filters['author_id'];
        }
        
        if (!empty($filters['tag'])) {
            $whereConditions[] = 't.name = ?';
            $params[] = $filters['tag'];
        }
        
        if (!empty($filters['search'])) {
            $whereConditions[] = '(a.title LIKE ? OR a.content LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $hasSlugColumn = $this->hasSlugColumn();
        $slugSelect = $hasSlugColumn ? ', a.slug' : '';
        
        $sql = "SELECT a.*{$slugSelect}, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t.name) as tags
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE " . implode(' AND ', $whereConditions) . "
                GROUP BY a.id
                ORDER BY a.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();
        
        // Process articles
        foreach ($articles as &$article) {
            if (is_string($article['content'])) {
                $decoded = json_decode($article['content'], true);
                $article['content'] = $decoded !== null ? $decoded : $article['content'];
            }
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
            
            if (!$hasSlugColumn) {
                $article['slug'] = $this->generateUniqueSlug($article['title'], $article['id']);
            }
        }
        
        return $articles;
    }

    /**
     * Publish an article
     */
    public function publish($articleId, $userId, $options = []) {
        try {
            // Check if user owns the article
            $article = $this->findById($articleId);
            if (!$article || $article['author_id'] != $userId) {
                return false;
            }
            
            // Update article status to published
            $sql = "UPDATE {$this->table} 
                    SET status = 'published', published_at = NOW(), updated_at = NOW()
                    WHERE id = ? AND author_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$articleId, $userId]);
            
            if ($result) {
                return $this->findById($articleId);
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get article preview (same as findById for now)
     */
    public function getArticlePreview($articleId, $userId = null) {
        $article = $this->findById($articleId);
        
        // If user is provided, check if they own the article or if it's published
        if ($userId && $article) {
            if ($article['author_id'] != $userId && $article['status'] !== 'published') {
                return null; // User can't preview others' unpublished articles
            }
        }
        
        return $article;
    }

    /**
     * Increment view count for an article
     */
    public function incrementViewCount($articleId) {
        try {
            $sql = "UPDATE {$this->table} SET view_count = view_count + 1 WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$articleId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Calculate reading time
     */
    public function calculateReadingTime($content) {
        // Convert content to plain text
        if (is_array($content)) {
            $plainText = $this->extractTextFromContent($content);
        } else {
            $plainText = strip_tags($content);
        }
        
        // Count words
        $wordCount = str_word_count($plainText, 0);
        
        // Calculate reading time (225 words per minute average)
        $readingTime = max(1, ceil($wordCount / 225));
        
        return $readingTime;
    }

    /**
     * Extract text from content
     */
    private function extractTextFromContent($content) {
        if (is_string($content)) {
            return strip_tags($content);
        }
        
        if (is_array($content)) {
            $text = '';
            // Simple extraction - just get text from any 'text' fields
            array_walk_recursive($content, function($value, $key) use (&$text) {
                if ($key === 'text' && is_string($value)) {
                    $text .= $value . ' ';
                }
            });
            return trim($text);
        }
        
        return '';
    }

    /**
     * Override update method to handle tags
     */
    public function update($id, $data) {
        try {
            // Extract tags from data if present
            $tags = null;
            if (isset($data['tags'])) {
                $tags = $data['tags'];
                unset($data['tags']); // Remove tags from data to avoid database error
            }
            
            // Update the article using parent method
            $result = parent::update($id, $data);
            
            // Update tags if provided and article update was successful
            if ($result && $tags !== null) {
                $this->updateArticleTags($id, $tags);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Article update error: " . $e->getMessage());
            return false;
        }
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
            return false;
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
     * Get article counts by status for a user
     */
    public function getArticleCountsByStatus($userId) {
        try {
            $sql = "SELECT 
                        status,
                        COUNT(*) as count
                    FROM {$this->table} 
                    WHERE author_id = ?
                    GROUP BY status";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll();
            
            $counts = [
                'draft' => 0,
                'published' => 0,
                'archived' => 0
            ];
            
            foreach ($results as $result) {
                $counts[$result['status']] = (int)$result['count'];
            }
            
            return $counts;
        } catch (Exception $e) {
            return [
                'draft' => 0,
                'published' => 0,
                'archived' => 0
            ];
        }
    }

    /**
     * Get total views by author
     */
    public function getTotalViewsByAuthor($userId) {
        try {
            $sql = "SELECT SUM(view_count) as total_views
                    FROM {$this->table} 
                    WHERE author_id = ? AND status = 'published'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)($result['total_views'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get top articles by author
     */
    public function getTopArticlesByAuthor($userId, $limit = 5) {
        try {
            $sql = "SELECT 
                        id, title, view_count, clap_count, comment_count,
                        (view_count + clap_count * 2 + comment_count * 3) as engagement_score
                    FROM {$this->table} 
                    WHERE author_id = ? AND status = 'published'
                    ORDER BY engagement_score DESC, view_count DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get views over time for an author
     */
    public function getViewsOverTime($userId, $days = 30) {
        try {
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
            
            // Fill in missing dates with 0 views for consistent frontend display
            $viewsData = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $views = 0;
                
                foreach ($results as $result) {
                    if ($result['date'] === $date) {
                        $views = (int)$result['views'];
                        break;
                    }
                }
                
                $viewsData[] = [
                    'date' => $date,
                    'views' => $views
                ];
            }
            
            return $viewsData;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get article performance comparison for an author
     */
    public function getArticlePerformanceComparison($userId) {
        $sql = "SELECT 
                    id, title, view_count, clap_count, comment_count,
                    published_at,
                    (view_count + clap_count * 2 + comment_count * 3) as engagement_score
                FROM {$this->table} 
                WHERE author_id = ? AND status = 'published'
                ORDER BY engagement_score DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get user articles for dashboard with management options
     */
    public function getUserArticlesForDashboard($userId, $status, $page, $limit, $sortBy, $sortOrder) {
        $offset = ($page - 1) * $limit;
        $whereClause = "author_id = ?";
        $params = [$userId];
        
        if ($status !== 'all') {
            $whereClause .= " AND status = ?";
            $params[] = $status;
        }
        
        // Validate sort parameters
        $allowedSortFields = ['updated_at', 'created_at', 'view_count', 'clap_count', 'published_at'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'updated_at';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        $sql = "SELECT a.*, 
                       GROUP_CONCAT(t.name) as tags,
                       SUBSTRING(a.content, 1, 200) as preview,
                       (a.view_count + a.clap_count * 2 + a.comment_count * 3) as engagement_score
                FROM {$this->table} a
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                WHERE {$whereClause}
                GROUP BY a.id
                ORDER BY {$sortBy} {$sortOrder}
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $articles = $stmt->fetchAll();
        
        // Process articles
        foreach ($articles as &$article) {
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
            $article['preview'] = strip_tags($article['preview']);
        }
        
        return $articles;
    }

    /**
     * Get tag performance by author
     */
    public function getTagPerformanceByAuthor($userId) {
        $sql = "SELECT 
                    t.name,
                    COUNT(DISTINCT a.id) as article_count,
                    SUM(a.view_count) as total_views,
                    SUM(a.clap_count) as total_claps,
                    AVG(a.view_count) as avg_views_per_article
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
     * Submit article to publication
     */
    public function submitToPublication($articleId, $publicationId) {
        $sql = "UPDATE {$this->table} 
                SET publication_id = ?, status = 'draft', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$publicationId, $articleId]);
    }
    
    /**
     * Approve article for publication
     */
    public function approveForPublication($articleId) {
        $sql = "UPDATE {$this->table} 
                SET status = 'published', published_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$articleId]);
    }
    
    /**
     * Reject article submission
     */
    public function rejectSubmission($articleId) {
        $sql = "UPDATE {$this->table} 
                SET publication_id = NULL, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$articleId]);
    }
    
    /**
     * Get articles pending approval for publication
     */
    public function getPendingApproval($publicationId) {
        $hasSlugColumn = $this->hasSlugColumn();
        $slugSelect = $hasSlugColumn ? ', a.slug' : '';
        
        $sql = "SELECT a.*{$slugSelect}, u.username, u.profile_image_url as author_avatar
                FROM {$this->table} a
                JOIN users u ON a.author_id = u.id
                WHERE a.publication_id = ? AND a.status = 'draft'
                ORDER BY a.created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$publicationId]);
        
        $articles = $stmt->fetchAll();
        
        // Process each article
        foreach ($articles as &$article) {
            if (is_string($article['content'])) {
                $decoded = json_decode($article['content'], true);
                $article['content'] = $decoded !== null ? $decoded : $article['content'];
            }
            
            if (!$hasSlugColumn) {
                $article['slug'] = $this->generateUniqueSlug($article['title'], $article['id']);
            }
        }
        
        return $articles;
    }
    
    /**
     * Get articles by publication with status filter
     */
    public function getByPublication($publicationId, $status = null, $limit = 20, $offset = 0) {
        $hasSlugColumn = $this->hasSlugColumn();
        $slugSelect = $hasSlugColumn ? ', a.slug' : '';
        
        $whereClause = "WHERE a.publication_id = ?";
        $params = [$publicationId];
        
        if ($status) {
            $whereClause .= " AND a.status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT a.*{$slugSelect}, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(t.name) as tags
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at ON a.id = at.article_id
                LEFT JOIN tags t ON at.tag_id = t.id
                $whereClause
                GROUP BY a.id
                ORDER BY a.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $articles = $stmt->fetchAll();
        
        // Process each article
        foreach ($articles as &$article) {
            if (is_string($article['content'])) {
                $decoded = json_decode($article['content'], true);
                $article['content'] = $decoded !== null ? $decoded : $article['content'];
            }
            
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
            
            if (!$hasSlugColumn) {
                $article['slug'] = $this->generateUniqueSlug($article['title'], $article['id']);
            }
        }
        
        return $articles;
    }
    
    /**
     * Check if user can manage article in publication context
     */
    public function canManageInPublication($articleId, $userId) {
        $sql = "SELECT a.author_id, a.publication_id, p.owner_id,
                       pm.role
                FROM {$this->table} a
                LEFT JOIN publications p ON a.publication_id = p.id
                LEFT JOIN publication_members pm ON p.id = pm.publication_id AND pm.user_id = ?
                WHERE a.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $articleId]);
        $result = $stmt->fetch();
        
        if (!$result) {
            return false;
        }
        
        // Author can always manage their own article
        if ($result['author_id'] == $userId) {
            return true;
        }
        
        // Publication owner can manage all articles
        if ($result['publication_id'] && $result['owner_id'] == $userId) {
            return true;
        }
        
        // Admin and editor roles can manage articles
        if ($result['role'] && in_array($result['role'], ['admin', 'editor'])) {
            return true;
        }
        
        return false;
    }

    /**
     * Scan article content for moderation
     */
    private function scanContentForModeration($articleId, $content) {
        try {
            require_once __DIR__ . '/ContentFilter.php';
            $contentFilter = new ContentFilter();
            
            // Convert content to string if it's JSON
            $contentText = is_string($content) ? $content : json_encode($content);
            
            // Scan the content
            $contentFilter->scanContent('article', $articleId, $contentText);
        } catch (Exception $e) {
            // Log error but don't fail article creation
            error_log('Content scanning error: ' . $e->getMessage());
        }
    }

    /**
     * Get articles for moderation
     */
    public function getArticlesForModeration($status = '', $limit = 20, $offset = 0) {
        $whereConditions = ['1=1'];
        $params = [];
        
        if (!empty($status)) {
            $whereConditions[] = "moderation_status = ?";
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT a.*, u.username as author_username,
                       (SELECT COUNT(*) FROM claps WHERE article_id = a.id) as clap_count,
                       (SELECT COUNT(*) FROM comments WHERE article_id = a.id) as comment_count
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                WHERE {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get featured articles
     */
    public function getFeaturedArticles() {
        $sql = "SELECT a.*, u.username as author_username
                FROM {$this->table} a
                LEFT JOIN users u ON a.author_id = u.id
                WHERE a.is_featured = TRUE AND a.status = 'published'
                ORDER BY a.featured_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Feature an article
     */
    public function featureArticle($articleId) {
        $sql = "UPDATE {$this->table} 
                SET is_featured = TRUE, featured_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND status = 'published'";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$articleId]);
    }

    /**
     * Unfeature an article
     */
    public function unfeatureArticle($articleId) {
        $sql = "UPDATE {$this->table} 
                SET is_featured = FALSE, featured_at = NULL 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$articleId]);
    }

    /**
     * Update moderation status
     */
    public function updateModerationStatus($articleId, $status, $moderatorId = null) {
        $validStatuses = ['approved', 'pending', 'flagged', 'removed'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid moderation status');
        }
        
        $sql = "UPDATE {$this->table} 
                SET moderation_status = ?, moderated_by = ? 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $moderatorId, $articleId]);
    }

    /**
     * Track article view for analytics
     */
    public function trackView($articleId, $userId = null, $sessionId = null, $ipAddress = null, $userAgent = null) {
        try {
            // First increment the view count on the article
            $this->incrementViewCount($articleId);
            
            // Check if article_views table exists
            $sql = "SHOW TABLES LIKE 'article_views'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                // Insert detailed view tracking
                $sql = "INSERT INTO article_views (article_id, user_id, session_id, ip_address, user_agent, viewed_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $articleId,
                    $userId,
                    $sessionId,
                    $ipAddress,
                    $userAgent
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Track view error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get trending articles based on engagement metrics
     */
    public function getTrendingArticles($limit = 10) {
        try {
            $sql = "SELECT 
                        a.*,
                        u.username,
                        u.profile_image_url as author_avatar,
                        COALESCE(a.clap_count, 0) as clap_count,
                        COALESCE(a.comment_count, 0) as comment_count,
                        COALESCE(a.view_count, 0) as view_count,
                        -- Calculate trending score based on recent engagement
                        (
                            COALESCE(a.clap_count, 0) * 2 + 
                            COALESCE(a.comment_count, 0) * 3 + 
                            COALESCE(a.view_count, 0) * 0.1 +
                            -- Boost recent articles
                            CASE 
                                WHEN a.published_at > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 50
                                WHEN a.published_at > DATE_SUB(NOW(), INTERVAL 3 DAY) THEN 25
                                WHEN a.published_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 10
                                ELSE 0
                            END
                        ) as trending_score
                    FROM {$this->table} a
                    JOIN users u ON a.author_id = u.id
                    WHERE a.status = 'published'
                    AND a.published_at IS NOT NULL
                    AND a.published_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ORDER BY trending_score DESC, a.published_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get trending articles error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get more articles from the same author
     */
    public function getMoreFromAuthor($authorId, $excludeId = null, $limit = 4) {
        try {
            $whereClause = "WHERE a.author_id = ? AND a.status = 'published'";
            $params = [$authorId];
            
            if ($excludeId) {
                $whereClause .= " AND a.id != ?";
                $params[] = $excludeId;
            }
            
            $sql = "SELECT 
                        a.*,
                        u.username,
                        u.profile_image_url as author_avatar,
                        COALESCE(a.clap_count, 0) as clap_count,
                        COALESCE(a.comment_count, 0) as comment_count
                    FROM {$this->table} a
                    JOIN users u ON a.author_id = u.id
                    {$whereClause}
                    ORDER BY a.published_at DESC
                    LIMIT ?";
            
            $params[] = $limit;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get more from author error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get related articles based on tags and content similarity
     */
    public function getRelatedArticles($articleId, $limit = 6) {
        try {
            // First, get the current article's tags
            $currentArticle = $this->findById($articleId);
            if (!$currentArticle) {
                return [];
            }
            
            // Get articles with similar tags or from same author
            $sql = "SELECT DISTINCT
                        a.*,
                        u.username,
                        u.profile_image_url as author_avatar,
                        COALESCE(a.clap_count, 0) as clap_count,
                        COALESCE(a.comment_count, 0) as comment_count,
                        -- Calculate similarity score
                        (
                            CASE WHEN a.author_id = ? THEN 10 ELSE 0 END +
                            COALESCE(a.clap_count, 0) * 0.1 +
                            COALESCE(a.view_count, 0) * 0.01
                        ) as similarity_score
                    FROM {$this->table} a
                    JOIN users u ON a.author_id = u.id
                    WHERE a.id != ? 
                    AND a.status = 'published'
                    AND a.published_at IS NOT NULL
                    ORDER BY similarity_score DESC, a.published_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$currentArticle['author_id'], $articleId, $limit]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get related articles error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete an article (only by owner)
     */
    public function deleteArticle($articleId, $userId) {
        try {
            // Check if user owns the article
            $article = $this->findById($articleId);
            if (!$article || $article['author_id'] != $userId) {
                return false;
            }
            
            // Delete the article
            $sql = "DELETE FROM {$this->table} WHERE id = ? AND author_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$articleId, $userId]);
            
            return $result;
        } catch (Exception $e) {
            error_log("Delete article error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unpublish an article (change status from published to draft)
     */
    public function unpublish($articleId, $userId) {
        try {
            // Check if user owns the article
            $article = $this->findById($articleId);
            if (!$article || $article['author_id'] != $userId) {
                return false;
            }
            
            // Update article status to draft
            $sql = "UPDATE {$this->table} 
                    SET status = 'draft', published_at = NULL, updated_at = NOW()
                    WHERE id = ? AND author_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$articleId, $userId]);
            
            if ($result) {
                return $this->findById($articleId);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Unpublish article error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Archive an article (change status to archived)
     */
    public function archive($articleId, $userId) {
        try {
            // Check if user owns the article
            $article = $this->findById($articleId);
            if (!$article || $article['author_id'] != $userId) {
                return false;
            }
            
            // Update article status to archived
            $sql = "UPDATE {$this->table} 
                    SET status = 'archived', updated_at = NOW()
                    WHERE id = ? AND author_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$articleId, $userId]);
            
            if ($result) {
                return $this->findById($articleId);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Archive article error: " . $e->getMessage());
            return false;
        }
    }
}
