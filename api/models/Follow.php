<?php

require_once __DIR__ . '/BaseRepository.php';

class Follow extends BaseRepository {
    protected $table = 'follows';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get follower count for a user
     */
    public function getFollowerCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as follower_count
                    FROM follows 
                    WHERE following_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)$result['follower_count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get following count for a user
     */
    public function getFollowingCount($userId) {
        try {
            $sql = "SELECT COUNT(*) as following_count
                    FROM follows 
                    WHERE follower_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            return (int)$result['following_count'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent followers
     */
    public function getRecentFollowers($userId, $limit = 10) {
        try {
            $sql = "SELECT 
                        f.*,
                        u.username,
                        u.profile_image_url
                    FROM follows f
                    JOIN users u ON f.follower_id = u.id
                    WHERE f.following_id = ?
                    ORDER BY f.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Follow a user
     */
    public function followUser($followerId, $followingId) {
        try {
            // Check if already following
            if ($this->isFollowing($followerId, $followingId)) {
                return [
                    'success' => false,
                    'error' => 'Already following this user'
                ];
            }

            // Prevent self-following
            if ($followerId === $followingId) {
                return [
                    'success' => false,
                    'error' => 'Cannot follow yourself'
                ];
            }

            $sql = "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$followerId, $followingId]);

            return [
                'success' => true,
                'message' => 'User followed successfully'
            ];
        } catch (Exception $e) {
            error_log("Error following user: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to follow user'
            ];
        }
    }

    /**
     * Unfollow a user
     */
    public function unfollowUser($followerId, $followingId) {
        try {
            $sql = "DELETE FROM follows WHERE follower_id = ? AND following_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$followerId, $followingId]);

            if ($stmt->rowCount() > 0) {
                return [
                    'success' => true,
                    'message' => 'User unfollowed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Not following this user'
                ];
            }
        } catch (Exception $e) {
            error_log("Error unfollowing user: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to unfollow user'
            ];
        }
    }

    /**
     * Check if user is following another user
     */
    public function isFollowing($followerId, $followingId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM follows WHERE follower_id = ? AND following_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$followerId, $followingId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get followers for a user
     */
    public function getFollowers($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT 
                        f.*,
                        u.id as user_id,
                        u.username,
                        u.profile_image_url,
                        u.bio
                    FROM follows f
                    JOIN users u ON f.follower_id = u.id
                    WHERE f.following_id = ?
                    ORDER BY f.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get users that a user is following
     */
    public function getFollowing($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT 
                        f.*,
                        u.id as user_id,
                        u.username,
                        u.profile_image_url,
                        u.bio
                    FROM follows f
                    JOIN users u ON f.following_id = u.id
                    WHERE f.follower_id = ?
                    ORDER BY f.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get following feed (articles from followed users)
     */
    public function getFollowingFeed($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT 
                        a.*,
                        u.username as author_username,
                        u.profile_image_url as author_image
                    FROM articles a
                    JOIN users u ON a.author_id = u.id
                    JOIN follows f ON a.author_id = f.following_id
                    WHERE f.follower_id = ? AND a.status = 'published'
                    ORDER BY a.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get suggested users to follow
     */
    public function getSuggestedFollows($userId, $limit = 10) {
        try {
            // Get users that the current user's followers are following
            // but the current user is not following
            $sql = "SELECT DISTINCT
                        u.id,
                        u.username,
                        u.profile_image_url,
                        u.bio,
                        COUNT(f2.follower_id) as mutual_followers
                    FROM users u
                    JOIN follows f1 ON u.id = f1.following_id
                    JOIN follows f2 ON f1.follower_id = f2.follower_id
                    WHERE f2.following_id = ?
                    AND u.id != ?
                    AND u.id NOT IN (
                        SELECT following_id FROM follows WHERE follower_id = ?
                    )
                    GROUP BY u.id, u.username, u.profile_image_url, u.bio
                    ORDER BY mutual_followers DESC, u.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId, $limit]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get follows over time for analytics
     */
    public function getFollowsOverTime($userId, $days = 30) {
        try {
            $sql = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as count
                    FROM follows 
                    WHERE following_id = ? 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $days]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}