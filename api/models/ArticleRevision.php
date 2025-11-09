<?php

require_once __DIR__ . '/BaseRepository.php';

class ArticleRevision extends BaseRepository {
    protected $table = 'article_revisions';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new revision
     */
    public function createRevision($articleId, $revisionData, $createdBy, $changeSummary = null, $isMajor = false) {
        try {
            // Get next revision number
            $revisionNumber = $this->getNextRevisionNumber($articleId);
            
            $sql = "INSERT INTO {$this->table} 
                    (article_id, revision_number, title, subtitle, content, featured_image_url, 
                     tags, created_by, change_summary, is_major_revision)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $articleId,
                $revisionNumber,
                $revisionData['title'],
                $revisionData['subtitle'] ?? null,
                $revisionData['content'],
                $revisionData['featured_image_url'] ?? null,
                json_encode($revisionData['tags'] ?? []),
                $createdBy,
                $changeSummary,
                $isMajor ? 1 : 0
            ]);
            
            if ($result) {
                $revisionId = $this->db->lastInsertId();
                
                // Update article revision count and last edited info
                $this->updateArticleRevisionInfo($articleId, $createdBy);
                
                return $this->getById($revisionId);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("ArticleRevision createRevision error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get revision by ID
     */
    public function getById($id) {
        $sql = "SELECT r.*, u.username as created_by_username, u.profile_image_url as creator_avatar
                FROM {$this->table} r
                JOIN users u ON r.created_by = u.id
                WHERE r.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        $revision = $stmt->fetch();
        
        if ($revision && $revision['tags']) {
            $revision['tags'] = json_decode($revision['tags'], true) ?: [];
        }
        
        return $revision;
    }
    
    /**
     * Get all revisions for an article
     */
    public function getArticleRevisions($articleId, $limit = 50, $offset = 0) {
        $sql = "SELECT r.*, u.username as created_by_username, u.profile_image_url as creator_avatar
                FROM {$this->table} r
                JOIN users u ON r.created_by = u.id
                WHERE r.article_id = ?
                ORDER BY r.revision_number DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId, $limit, $offset]);
        
        $revisions = $stmt->fetchAll();
        
        // Decode tags for each revision
        foreach ($revisions as &$revision) {
            if ($revision['tags']) {
                $revision['tags'] = json_decode($revision['tags'], true) ?: [];
            }
        }
        
        return $revisions;
    }
    
    /**
     * Get latest revision for an article
     */
    public function getLatestRevision($articleId) {
        $sql = "SELECT r.*, u.username as created_by_username, u.profile_image_url as creator_avatar
                FROM {$this->table} r
                JOIN users u ON r.created_by = u.id
                WHERE r.article_id = ?
                ORDER BY r.revision_number DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        
        $revision = $stmt->fetch();
        
        if ($revision && $revision['tags']) {
            $revision['tags'] = json_decode($revision['tags'], true) ?: [];
        }
        
        return $revision;
    }
    
    /**
     * Get specific revision by article and revision number
     */
    public function getRevisionByNumber($articleId, $revisionNumber) {
        $sql = "SELECT r.*, u.username as created_by_username, u.profile_image_url as creator_avatar
                FROM {$this->table} r
                JOIN users u ON r.created_by = u.id
                WHERE r.article_id = ? AND r.revision_number = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId, $revisionNumber]);
        
        $revision = $stmt->fetch();
        
        if ($revision && $revision['tags']) {
            $revision['tags'] = json_decode($revision['tags'], true) ?: [];
        }
        
        return $revision;
    }
    
    /**
     * Compare two revisions
     */
    public function compareRevisions($articleId, $fromRevision, $toRevision) {
        $from = $this->getRevisionByNumber($articleId, $fromRevision);
        $to = $this->getRevisionByNumber($articleId, $toRevision);
        
        if (!$from || !$to) {
            return false;
        }
        
        return [
            'from' => $from,
            'to' => $to,
            'changes' => [
                'title' => $from['title'] !== $to['title'],
                'subtitle' => $from['subtitle'] !== $to['subtitle'],
                'content' => $from['content'] !== $to['content'],
                'featured_image' => $from['featured_image_url'] !== $to['featured_image_url'],
                'tags' => $from['tags'] !== $to['tags']
            ],
            'diff' => $this->generateContentDiff($from['content'], $to['content'])
        ];
    }
    
    /**
     * Restore article to a specific revision
     */
    public function restoreToRevision($articleId, $revisionNumber, $restoredBy) {
        try {
            $revision = $this->getRevisionByNumber($articleId, $revisionNumber);
            if (!$revision) {
                return false;
            }
            
            $this->db->beginTransaction();
            
            // Update the article with revision data
            $sql = "UPDATE articles 
                    SET title = ?, subtitle = ?, content = ?, featured_image_url = ?,
                        last_edited_by = ?, last_edited_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $revision['title'],
                $revision['subtitle'],
                $revision['content'],
                $revision['featured_image_url'],
                $restoredBy,
                $articleId
            ]);
            
            // Update article tags
            $this->updateArticleTags($articleId, $revision['tags']);
            
            // Create a new revision marking the restoration
            $this->createRevision(
                $articleId,
                [
                    'title' => $revision['title'],
                    'subtitle' => $revision['subtitle'],
                    'content' => $revision['content'],
                    'featured_image_url' => $revision['featured_image_url'],
                    'tags' => $revision['tags']
                ],
                $restoredBy,
                "Restored to revision #{$revisionNumber}",
                true
            );
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("ArticleRevision restoreToRevision error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get revision statistics for an article
     */
    public function getRevisionStats($articleId) {
        $sql = "SELECT 
                    COUNT(*) as total_revisions,
                    COUNT(CASE WHEN is_major_revision = 1 THEN 1 END) as major_revisions,
                    COUNT(DISTINCT created_by) as unique_contributors,
                    MIN(created_at) as first_revision,
                    MAX(created_at) as last_revision
                FROM {$this->table}
                WHERE article_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get contributors to an article
     */
    public function getArticleContributors($articleId) {
        $sql = "SELECT u.id, u.username, u.profile_image_url,
                       COUNT(r.id) as revision_count,
                       MAX(r.created_at) as last_contribution
                FROM {$this->table} r
                JOIN users u ON r.created_by = u.id
                WHERE r.article_id = ?
                GROUP BY u.id, u.username, u.profile_image_url
                ORDER BY revision_count DESC, last_contribution DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get next revision number for an article
     */
    private function getNextRevisionNumber($articleId) {
        $sql = "SELECT COALESCE(MAX(revision_number), 0) + 1 as next_number
                FROM {$this->table}
                WHERE article_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        
        $result = $stmt->fetch();
        return $result['next_number'];
    }
    
    /**
     * Update article revision info
     */
    private function updateArticleRevisionInfo($articleId, $editedBy) {
        $sql = "UPDATE articles 
                SET revision_count = revision_count + 1,
                    last_edited_by = ?,
                    last_edited_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$editedBy, $articleId]);
    }
    
    /**
     * Update article tags
     */
    private function updateArticleTags($articleId, $tags) {
        try {
            // Remove existing tags
            $sql = "DELETE FROM article_tags WHERE article_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId]);
            
            // Add new tags
            if (!empty($tags)) {
                foreach ($tags as $tagName) {
                    $tagId = $this->getOrCreateTag($tagName);
                    if ($tagId) {
                        $sql = "INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)";
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([$articleId, $tagId]);
                    }
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("ArticleRevision updateArticleTags error: " . $e->getMessage());
            return false;
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
     * Generate content diff between two versions
     */
    private function generateContentDiff($oldContent, $newContent) {
        // Simple word-level diff
        $oldWords = explode(' ', strip_tags($oldContent));
        $newWords = explode(' ', strip_tags($newContent));
        
        // This is a simplified diff - in production you might want to use a proper diff library
        $diff = [
            'added_words' => array_diff($newWords, $oldWords),
            'removed_words' => array_diff($oldWords, $newWords),
            'word_count_change' => count($newWords) - count($oldWords)
        ];
        
        return $diff;
    }
    
    /**
     * Check if user can create revision for article
     */
    public function canUserCreateRevision($articleId, $userId) {
        // Check if user is author or has publication permissions
        $sql = "SELECT a.author_id, a.publication_id, p.owner_id
                FROM articles a
                LEFT JOIN publications p ON a.publication_id = p.id
                WHERE a.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId]);
        $article = $stmt->fetch();
        
        if (!$article) {
            return false;
        }
        
        // Author can always create revisions
        if ($article['author_id'] == $userId) {
            return true;
        }
        
        // Check publication permissions if article belongs to publication
        if ($article['publication_id']) {
            require_once __DIR__ . '/Publication.php';
            $publicationModel = new Publication();
            return $publicationModel->hasPermission($article['publication_id'], $userId, 'editor');
        }
        
        return false;
    }
}