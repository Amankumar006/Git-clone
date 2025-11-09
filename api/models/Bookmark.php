<?php

require_once __DIR__ . '/BaseRepository.php';

class Bookmark extends BaseRepository {
    protected $table = 'bookmarks';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Add bookmark for an article
     * @param int $userId
     * @param int $articleId
     * @return array
     */
    public function addBookmark($userId, $articleId) {
        try {
            // Check if bookmark already exists
            if ($this->isBookmarked($userId, $articleId)) {
                return [
                    'success' => false,
                    'error' => 'Article is already bookmarked'
                ];
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO bookmarks (user_id, article_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$userId, $articleId]);
            
            return [
                'success' => true,
                'message' => 'Article bookmarked successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Error adding bookmark: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to bookmark article'
            ];
        }
    }
    
    /**
     * Remove bookmark for an article
     * @param int $userId
     * @param int $articleId
     * @return array
     */
    public function removeBookmark($userId, $articleId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM bookmarks 
                WHERE user_id = ? AND article_id = ?
            ");
            $stmt->execute([$userId, $articleId]);
            
            return [
                'success' => true,
                'message' => 'Bookmark removed successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Error removing bookmark: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to remove bookmark'
            ];
        }
    }
    
    /**
     * Check if article is bookmarked by user
     * @param int $userId
     * @param int $articleId
     * @return bool
     */
    public function isBookmarked($userId, $articleId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 1 FROM bookmarks 
                WHERE user_id = ? AND article_id = ?
            ");
            $stmt->execute([$userId, $articleId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error checking bookmark status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's bookmarked articles
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserBookmarks($userId, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.username, u.profile_image_url, b.created_at as bookmarked_at
                FROM bookmarks b
                JOIN articles a ON b.article_id = a.id
                JOIN users u ON a.author_id = u.id
                WHERE b.user_id = ? AND a.status = 'published'
                ORDER BY b.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$userId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting user bookmarks: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get bookmark count for user
     * @param int $userId
     * @return int
     */
    public function getUserBookmarkCount($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM bookmarks b
                JOIN articles a ON b.article_id = a.id
                WHERE b.user_id = ? AND a.status = 'published'
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getting bookmark count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get articles bookmarked by multiple users (popular bookmarks)
     * @param int $limit
     * @return array
     */
    public function getPopularBookmarks($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT a.*, u.username, u.profile_image_url, COUNT(b.user_id) as bookmark_count
                FROM articles a
                JOIN users u ON a.author_id = u.id
                JOIN bookmarks b ON a.id = b.article_id
                WHERE a.status = 'published'
                GROUP BY a.id
                ORDER BY bookmark_count DESC, a.published_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting popular bookmarks: " . $e->getMessage());
            return [];
        }
    }
}