<?php

require_once __DIR__ . '/BaseRepository.php';

class Clap extends BaseRepository {
    protected $table = 'claps';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Add or update clap for an article by a user
     * @param int $userId
     * @param int $articleId
     * @param int $count Number of claps to add (1-50 total limit)
     * @return array Result with success status and data
     */
    public function addClap($userId, $articleId, $count = 1) {
        try {
            // Check if user already clapped this article
            $existingClap = $this->getUserClapForArticle($userId, $articleId);
            
            if ($existingClap) {
                // Update existing clap count, ensuring it doesn't exceed 50
                $newCount = min($existingClap['count'] + $count, 50);
                
                $stmt = $this->db->prepare("
                    UPDATE claps 
                    SET count = ? 
                    WHERE user_id = ? AND article_id = ?
                ");
                $stmt->execute([$newCount, $userId, $articleId]);
                
                $clapData = [
                    'id' => $existingClap['id'],
                    'user_id' => $userId,
                    'article_id' => $articleId,
                    'count' => $newCount,
                    'created_at' => $existingClap['created_at']
                ];
            } else {
                // Create new clap record
                $clapCount = min($count, 50);
                
                $stmt = $this->db->prepare("
                    INSERT INTO claps (user_id, article_id, count) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$userId, $articleId, $clapCount]);
                
                $clapData = [
                    'id' => $this->db->lastInsertId(),
                    'user_id' => $userId,
                    'article_id' => $articleId,
                    'count' => $clapCount,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            
            // Update article clap count
            $this->updateArticleClapCount($articleId);
            
            return [
                'success' => true,
                'data' => $clapData,
                'total_claps' => $this->getArticleTotalClaps($articleId)
            ];
            
        } catch (Exception $e) {
            error_log("Error adding clap: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to add clap'
            ];
        }
    }
    
    /**
     * Get user's clap for a specific article
     * @param int $userId
     * @param int $articleId
     * @return array|null
     */
    public function getUserClapForArticle($userId, $articleId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM claps 
                WHERE user_id = ? AND article_id = ?
            ");
            $stmt->execute([$userId, $articleId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting user clap: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get total claps for an article
     * @param int $articleId
     * @return int
     */
    public function getArticleTotalClaps($articleId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(count), 0) as total_claps 
                FROM claps 
                WHERE article_id = ?
            ");
            $stmt->execute([$articleId]);
            $result = $stmt->fetch();
            return (int)$result['total_claps'];
        } catch (Exception $e) {
            error_log("Error getting article claps: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Update the clap count in articles table
     * @param int $articleId
     */
    private function updateArticleClapCount($articleId) {
        try {
            $totalClaps = $this->getArticleTotalClaps($articleId);
            
            $stmt = $this->db->prepare("
                UPDATE articles 
                SET clap_count = ? 
                WHERE id = ?
            ");
            $stmt->execute([$totalClaps, $articleId]);
        } catch (Exception $e) {
            error_log("Error updating article clap count: " . $e->getMessage());
        }
    }
    
    /**
     * Get claps with user information for an article
     * @param int $articleId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getArticleClapsWithUsers($articleId, $limit = 10, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.username, u.profile_image_url
                FROM claps c
                JOIN users u ON c.user_id = u.id
                WHERE c.article_id = ?
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$articleId, $limit, $offset]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting article claps with users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Remove clap from article
     * @param int $userId
     * @param int $articleId
     * @return array
     */
    public function removeClap($userId, $articleId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM claps 
                WHERE user_id = ? AND article_id = ?
            ");
            $stmt->execute([$userId, $articleId]);
            
            // Update article clap count
            $this->updateArticleClapCount($articleId);
            
            return [
                'success' => true,
                'total_claps' => $this->getArticleTotalClaps($articleId)
            ];
        } catch (Exception $e) {
            error_log("Error removing clap: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to remove clap'
            ];
        }
    }
    
    /**
     * Check if user can clap (hasn't reached 50 clap limit)
     * @param int $userId
     * @param int $articleId
     * @return bool
     */
    public function canUserClap($userId, $articleId) {
        $userClap = $this->getUserClapForArticle($userId, $articleId);
        return !$userClap || $userClap['count'] < 50;
    }
    
    /**
     * Get user's clap count for article
     * @param int $userId
     * @param int $articleId
     * @return int
     */
    public function getUserClapCount($userId, $articleId) {
        $userClap = $this->getUserClapForArticle($userId, $articleId);
        return $userClap ? (int)$userClap['count'] : 0;
    }

    /**
     * Get total claps for all articles by author
     */
    public function getTotalClapsByAuthor($authorId) {
        try {
            $sql = "SELECT COALESCE(SUM(c.count), 0) as total_claps
                    FROM claps c
                    JOIN articles a ON c.article_id = a.id
                    WHERE a.author_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$authorId]);
            $result = $stmt->fetch();
            
            return (int)$result['total_claps'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent claps on user's articles
     */
    public function getRecentClapsOnUserArticles($authorId, $limit = 10) {
        try {
            $sql = "SELECT 
                        c.*, 
                        u.username as clapper_username,
                        u.profile_image_url as clapper_avatar,
                        a.title as article_title,
                        a.id as article_id
                    FROM claps c
                    JOIN articles a ON c.article_id = a.id
                    JOIN users u ON c.user_id = u.id
                    WHERE a.author_id = ?
                    ORDER BY c.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$authorId, $limit]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get claps over time for an author
     */
    public function getClapsOverTime($authorId, $days) {
        try {
            $sql = "SELECT 
                        DATE(c.created_at) as date,
                        COUNT(*) as clap_events,
                        SUM(c.count) as total_claps
                    FROM claps c
                    JOIN articles a ON c.article_id = a.id
                    WHERE a.author_id = ? 
                        AND c.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(c.created_at)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$authorId, $days]);
            $results = $stmt->fetchAll();
            
            // Convert to associative array by date
            $clapsData = [];
            foreach ($results as $result) {
                $clapsData[$result['date']] = (int)$result['total_claps'];
            }
            
            return $clapsData;
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get engagement by day of week
     */
    public function getEngagementByDayOfWeek($authorId) {
        try {
            $sql = "SELECT 
                        DAYOFWEEK(c.created_at) as day_number,
                        DAYNAME(c.created_at) as day_name,
                        COUNT(*) as clap_events,
                        SUM(c.count) as total_claps
                    FROM claps c
                    JOIN articles a ON c.article_id = a.id
                    WHERE a.author_id = ?
                        AND c.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                    GROUP BY DAYOFWEEK(c.created_at), DAYNAME(c.created_at)
                    ORDER BY day_number";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$authorId]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get engagement by hour of day
     */
    public function getEngagementByHourOfDay($authorId) {
        try {
            $sql = "SELECT 
                        HOUR(c.created_at) as hour,
                        COUNT(*) as clap_events,
                        SUM(c.count) as total_claps
                    FROM claps c
                    JOIN articles a ON c.article_id = a.id
                    WHERE a.author_id = ?
                        AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY HOUR(c.created_at)
                    ORDER BY hour";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$authorId]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }



}