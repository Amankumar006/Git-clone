<?php

require_once __DIR__ . '/BaseRepository.php';

class Tag extends BaseRepository {
    protected $table = 'tags';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get all tags with article counts
     */
    public function getAllTags($limit = null) {
        $sql = "SELECT t.*, COUNT(at.article_id) as article_count
                FROM {$this->table} t
                LEFT JOIN article_tags at ON t.id = at.tag_id
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                GROUP BY t.id
                ORDER BY article_count DESC, t.name ASC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
        }

        $stmt = $this->db->prepare($sql);
        
        if ($limit) {
            $stmt->execute([$limit]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Get popular tags (most used)
     */
    public function getPopularTags($limit = 20) {
        return $this->getAllTags($limit);
    }

    /**
     * Search tags by name
     */
    public function searchTags($query, $limit = 10) {
        $sql = "SELECT t.*, COUNT(at.article_id) as article_count
                FROM {$this->table} t
                LEFT JOIN article_tags at ON t.id = at.tag_id
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                WHERE t.name LIKE ?
                GROUP BY t.id
                ORDER BY article_count DESC, t.name ASC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['%' . $query . '%', $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get tag by slug
     */
    public function getBySlug($slug) {
        $sql = "SELECT t.*, COUNT(at.article_id) as article_count
                FROM {$this->table} t
                LEFT JOIN article_tags at ON t.id = at.tag_id
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                WHERE t.slug = ?
                GROUP BY t.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    /**
     * Get articles by tag
     */
    public function getArticlesByTag($tagSlug, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        $sql = "SELECT a.*, u.username, u.profile_image_url as author_avatar,
                       GROUP_CONCAT(DISTINCT t2.name) as tags
                FROM articles a
                INNER JOIN article_tags at ON a.id = at.article_id
                INNER JOIN tags t ON at.tag_id = t.id
                LEFT JOIN users u ON a.author_id = u.id
                LEFT JOIN article_tags at2 ON a.id = at2.article_id
                LEFT JOIN tags t2 ON at2.tag_id = t2.id
                WHERE t.slug = ? AND a.status = 'published'
                GROUP BY a.id
                ORDER BY a.published_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tagSlug, $limit, $offset]);
        $articles = $stmt->fetchAll();

        // Process articles
        foreach ($articles as &$article) {
            $article['content'] = json_decode($article['content'], true);
            $article['tags'] = $article['tags'] ? explode(',', $article['tags']) : [];
        }

        return $articles;
    }

    /**
     * Create a new tag
     */
    public function create($name, $description = '') {
        $slug = $this->createSlug($name);
        
        // Check if tag already exists
        $existing = $this->getBySlug($slug);
        if ($existing) {
            return $existing;
        }

        $sql = "INSERT INTO {$this->table} (name, slug, description) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$name, $slug, $description]);

        if ($result) {
            return $this->findById($this->db->lastInsertId());
        }

        return false;
    }

    /**
     * Update tag
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET name = ?, description = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $id
        ]);

        return $result ? $this->findById($id) : false;
    }

    /**
     * Delete tag (only if no articles are using it)
     */
    public function delete($id) {
        // Check if tag is being used
        $sql = "SELECT COUNT(*) as count FROM article_tags WHERE tag_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            return false; // Cannot delete tag that's in use
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Get tag suggestions for autocomplete
     */
    public function getSuggestions($query, $limit = 5) {
        $sql = "SELECT name FROM {$this->table} 
                WHERE name LIKE ? 
                ORDER BY name ASC 
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['%' . $query . '%', $limit]);
        $results = $stmt->fetchAll();

        return array_column($results, 'name');
    }

    /**
     * Get or create tag by name (used by Article model)
     */
    public function getOrCreateTag($tagName) {
        $slug = $this->createSlug($tagName);
        
        // Check if tag exists
        $sql = "SELECT id FROM {$this->table} WHERE slug = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        $tag = $stmt->fetch();

        if ($tag) {
            return $tag['id'];
        }

        // Create new tag
        $sql = "INSERT INTO {$this->table} (name, slug) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([$tagName, $slug]);

        return $result ? $this->db->lastInsertId() : null;
    }

    /**
     * Get trending tags based on recent usage
     */
    public function getTrendingTags($limit = 10) {
        $sql = "SELECT t.*, COUNT(at.article_id) as recent_usage
                FROM {$this->table} t
                INNER JOIN article_tags at ON t.id = at.tag_id
                INNER JOIN articles a ON at.article_id = a.id 
                WHERE a.status = 'published' 
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY t.id
                ORDER BY recent_usage DESC, t.name ASC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get related tags based on co-occurrence with given tag
     */
    public function getRelatedTags($tagId, $limit = 5) {
        $sql = "SELECT t.*, COUNT(*) as co_occurrence
                FROM {$this->table} t
                INNER JOIN article_tags at1 ON t.id = at1.tag_id
                INNER JOIN article_tags at2 ON at1.article_id = at2.article_id
                INNER JOIN articles a ON at1.article_id = a.id
                WHERE at2.tag_id = ? AND t.id != ? AND a.status = 'published'
                GROUP BY t.id
                ORDER BY co_occurrence DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tagId, $tagId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Validate tag name
     */
    public function validateTagName($name) {
        $errors = [];

        if (empty($name) || strlen(trim($name)) < 1) {
            $errors[] = 'Tag name is required';
        } elseif (strlen($name) > 50) {
            $errors[] = 'Tag name must be less than 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-_]+$/', $name)) {
            $errors[] = 'Tag name can only contain letters, numbers, spaces, hyphens, and underscores';
        }

        return $errors;
    }

    /**
     * Get tag cloud data with different sizes based on usage
     */
    public function getTagCloud($limit = 50) {
        $sql = "SELECT t.*, COUNT(at.article_id) as article_count,
                       CASE 
                           WHEN COUNT(at.article_id) >= 50 THEN 'xl'
                           WHEN COUNT(at.article_id) >= 20 THEN 'lg'
                           WHEN COUNT(at.article_id) >= 10 THEN 'md'
                           WHEN COUNT(at.article_id) >= 5 THEN 'sm'
                           ELSE 'xs'
                       END as size_class
                FROM {$this->table} t
                LEFT JOIN article_tags at ON t.id = at.tag_id
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                GROUP BY t.id
                HAVING article_count > 0
                ORDER BY article_count DESC, t.name ASC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get tags by category/topic (hierarchical organization)
     */
    public function getTagsByCategory() {
        // This is a simplified categorization based on common patterns
        // In a real application, you might have a separate categories table
        $categories = [
            'Technology' => ['javascript', 'python', 'react', 'nodejs', 'programming', 'web-development', 'software', 'coding', 'tech', 'ai', 'machine-learning'],
            'Business' => ['startup', 'entrepreneurship', 'business', 'marketing', 'finance', 'leadership', 'management', 'productivity'],
            'Design' => ['design', 'ui', 'ux', 'graphic-design', 'web-design', 'typography', 'branding', 'creative'],
            'Lifestyle' => ['health', 'fitness', 'travel', 'food', 'lifestyle', 'wellness', 'mindfulness', 'self-improvement'],
            'Writing' => ['writing', 'storytelling', 'poetry', 'journalism', 'blogging', 'content', 'literature'],
            'Science' => ['science', 'research', 'biology', 'physics', 'chemistry', 'environment', 'climate', 'space']
        ];

        $result = [];
        
        foreach ($categories as $categoryName => $tagSlugs) {
            $placeholders = str_repeat('?,', count($tagSlugs) - 1) . '?';
            
            $sql = "SELECT t.*, COUNT(at.article_id) as article_count
                    FROM {$this->table} t
                    LEFT JOIN article_tags at ON t.id = at.tag_id
                    LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                    WHERE t.slug IN ({$placeholders})
                    GROUP BY t.id
                    HAVING article_count > 0
                    ORDER BY article_count DESC
                    LIMIT 10";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($tagSlugs);
            $tags = $stmt->fetchAll();

            if (!empty($tags)) {
                $result[] = [
                    'category' => $categoryName,
                    'tags' => $tags
                ];
            }
        }

        return $result;
    }

    /**
     * Follow a tag (for personalized feeds)
     */
    public function followTag($userId, $tagId) {
        try {
            $sql = "INSERT IGNORE INTO tag_follows (user_id, tag_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $tagId]);
        } catch (PDOException $e) {
            // Table might not exist yet, fail silently
            error_log("Tag follow failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Unfollow a tag
     */
    public function unfollowTag($userId, $tagId) {
        try {
            $sql = "DELETE FROM tag_follows WHERE user_id = ? AND tag_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $tagId]);
        } catch (PDOException $e) {
            error_log("Tag unfollow failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user follows a tag
     */
    public function isFollowingTag($userId, $tagId) {
        try {
            $sql = "SELECT COUNT(*) FROM tag_follows WHERE user_id = ? AND tag_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $tagId]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get user's followed tags
     */
    public function getUserFollowedTags($userId) {
        try {
            $sql = "SELECT t.*, tf.created_at as followed_at, COUNT(at.article_id) as article_count
                    FROM {$this->table} t
                    INNER JOIN tag_follows tf ON t.id = tf.tag_id
                    LEFT JOIN article_tags at ON t.id = at.tag_id
                    LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                    WHERE tf.user_id = ?
                    GROUP BY t.id
                    ORDER BY tf.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Get tag statistics
     */
    public function getTagStats($tagId) {
        $sql = "SELECT 
                    COUNT(DISTINCT at.article_id) as total_articles,
                    COUNT(DISTINCT a.author_id) as unique_authors,
                    AVG(a.view_count) as avg_views,
                    AVG(a.clap_count) as avg_claps,
                    MAX(a.published_at) as latest_article_date,
                    MIN(a.published_at) as first_article_date
                FROM article_tags at
                INNER JOIN articles a ON at.article_id = a.id
                WHERE at.tag_id = ? AND a.status = 'published'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tagId]);
        $stats = $stmt->fetch();

        // Get follower count if tag_follows table exists
        try {
            $sql = "SELECT COUNT(*) as followers FROM tag_follows WHERE tag_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tagId]);
            $stats['followers'] = (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $stats['followers'] = 0;
        }

        return $stats;
    }

    /**
     * Get similar tags based on article co-occurrence
     */
    public function getSimilarTags($tagId, $limit = 10) {
        $sql = "SELECT t2.*, COUNT(*) as co_occurrence_count,
                       COUNT(DISTINCT at2.article_id) as shared_articles
                FROM article_tags at1
                INNER JOIN article_tags at2 ON at1.article_id = at2.article_id
                INNER JOIN {$this->table} t2 ON at2.tag_id = t2.id
                INNER JOIN articles a ON at1.article_id = a.id
                WHERE at1.tag_id = ? 
                AND at2.tag_id != ? 
                AND a.status = 'published'
                GROUP BY t2.id
                ORDER BY co_occurrence_count DESC, shared_articles DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tagId, $tagId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get top authors for a tag
     */
    public function getTopAuthorsForTag($tagId, $limit = 10) {
        $sql = "SELECT u.id, u.username, u.profile_image_url, u.bio,
                       COUNT(DISTINCT a.id) as article_count,
                       SUM(a.view_count) as total_views,
                       SUM(a.clap_count) as total_claps
                FROM users u
                INNER JOIN articles a ON u.id = a.author_id
                INNER JOIN article_tags at ON a.id = at.article_id
                WHERE at.tag_id = ? 
                AND a.status = 'published'
                GROUP BY u.id
                ORDER BY article_count DESC, total_claps DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tagId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get tag activity timeline
     */
    public function getTagActivity($tagId, $days = 30) {
        $sql = "SELECT 
                    DATE(a.published_at) as date,
                    COUNT(*) as articles_published,
                    SUM(a.view_count) as total_views,
                    SUM(a.clap_count) as total_claps
                FROM articles a
                INNER JOIN article_tags at ON a.id = at.article_id
                WHERE at.tag_id = ? 
                AND a.status = 'published'
                AND a.published_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(a.published_at)
                ORDER BY date DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tagId, $days]);
        return $stmt->fetchAll();
    }

    /**
     * Search tags with advanced filters
     */
    public function advancedTagSearch($query, $filters = [], $limit = 20) {
        $whereConditions = [];
        $params = [];

        // Base search condition
        if (!empty($query)) {
            $whereConditions[] = 't.name LIKE ?';
            $params[] = '%' . $query . '%';
        }

        // Filter by minimum article count
        if (!empty($filters['min_articles'])) {
            $whereConditions[] = 'article_count >= ?';
            $params[] = (int)$filters['min_articles'];
        }

        // Filter by creation date
        if (!empty($filters['created_after'])) {
            $whereConditions[] = 't.created_at >= ?';
            $params[] = $filters['created_after'];
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT t.*, COUNT(at.article_id) as article_count
                FROM {$this->table} t
                LEFT JOIN article_tags at ON t.id = at.tag_id
                LEFT JOIN articles a ON at.article_id = a.id AND a.status = 'published'
                {$whereClause}
                GROUP BY t.id
                ORDER BY article_count DESC, t.name ASC
                LIMIT ?";

        $params[] = $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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
}