<?php

require_once __DIR__ . '/BaseRepository.php';

class FeaturedContent extends BaseRepository {
    protected $table = 'featured_content';

    public function getFeaturedArticles($limit = 10) {
        try {
            $sql = "SELECT fc.*, a.title, a.subtitle, a.featured_image_url, a.reading_time,
                           u.username, u.profile_image_url,
                           fc.featured_at, fc.expires_at
                    FROM {$this->table} fc
                    JOIN articles a ON fc.content_id = a.id
                    JOIN users u ON a.author_id = u.id
                    WHERE fc.content_type = 'article' 
                    AND fc.is_active = TRUE 
                    AND (fc.expires_at IS NULL OR fc.expires_at > NOW())
                    AND a.status = 'published'
                    ORDER BY fc.position ASC, fc.featured_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting featured articles: " . $e->getMessage());
            return [];
        }
    }

    public function featureArticle($articleId, $featuredBy, $position = 0, $expiresAt = null) {
        try {
            // Check if article is already featured
            $sql = "SELECT id FROM {$this->table} 
                    WHERE content_type = 'article' AND content_id = ? AND is_active = TRUE";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId]);
            
            if ($stmt->fetch()) {
                return false; // Already featured
            }
            
            // Get next position if not specified
            if ($position === 0) {
                $sql = "SELECT COALESCE(MAX(position), 0) + 1 as next_position 
                        FROM {$this->table} 
                        WHERE content_type = 'article' AND is_active = TRUE";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetch();
                $position = $result['next_position'];
            }
            
            $sql = "INSERT INTO {$this->table} 
                    (content_type, content_id, position, featured_by, expires_at) 
                    VALUES ('article', ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$articleId, $position, $featuredBy, $expiresAt]);
        } catch (Exception $e) {
            error_log("Error featuring article: " . $e->getMessage());
            return false;
        }
    }

    public function unfeatureArticle($articleId) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET is_active = FALSE 
                    WHERE content_type = 'article' AND content_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$articleId]);
        } catch (Exception $e) {
            error_log("Error unfeaturing article: " . $e->getMessage());
            return false;
        }
    }

    public function updateFeaturedPosition($id, $newPosition) {
        try {
            $sql = "UPDATE {$this->table} SET position = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$newPosition, $id]);
        } catch (Exception $e) {
            error_log("Error updating featured position: " . $e->getMessage());
            return false;
        }
    }

    public function getFeaturedContent($contentType = null, $limit = 50) {
        try {
            $whereClause = "WHERE fc.is_active = TRUE AND (fc.expires_at IS NULL OR fc.expires_at > NOW())";
            $params = [];
            
            if ($contentType) {
                $whereClause .= " AND fc.content_type = ?";
                $params[] = $contentType;
            }
            
            $sql = "SELECT fc.*, 
                           CASE 
                               WHEN fc.content_type = 'article' THEN a.title
                               WHEN fc.content_type = 'user' THEN u.username
                               WHEN fc.content_type = 'publication' THEN p.name
                           END as content_title,
                           CASE 
                               WHEN fc.content_type = 'article' THEN au.username
                               ELSE NULL
                           END as author_name
                    FROM {$this->table} fc
                    LEFT JOIN articles a ON fc.content_type = 'article' AND fc.content_id = a.id
                    LEFT JOIN users u ON fc.content_type = 'user' AND fc.content_id = u.id
                    LEFT JOIN publications p ON fc.content_type = 'publication' AND fc.content_id = p.id
                    LEFT JOIN users au ON a.author_id = au.id
                    {$whereClause}
                    ORDER BY fc.position ASC, fc.featured_at DESC
                    LIMIT ?";
            
            $params[] = $limit;
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting featured content: " . $e->getMessage());
            return [];
        }
    }

    public function cleanupExpiredContent() {
        try {
            $sql = "UPDATE {$this->table} 
                    SET is_active = FALSE 
                    WHERE expires_at IS NOT NULL AND expires_at <= NOW() AND is_active = TRUE";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error cleaning up expired content: " . $e->getMessage());
            return false;
        }
    }
}