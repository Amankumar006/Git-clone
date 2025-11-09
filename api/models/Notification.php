<?php

require_once __DIR__ . '/BaseRepository.php';

class Notification extends BaseRepository {
    protected $table = 'notifications';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new notification
     * @param int $userId
     * @param string $type
     * @param string $content
     * @param int|null $relatedId
     * @return array
     */
    public function createNotification($userId, $type, $content, $relatedId = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, content, related_id) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $type, $content, $relatedId]);
            
            $notificationId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'data' => [
                    'id' => $notificationId,
                    'user_id' => $userId,
                    'type' => $type,
                    'content' => $content,
                    'related_id' => $relatedId,
                    'is_read' => false,
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error creating notification: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create notification'
            ];
        }
    }
    
    /**
     * Get user notifications
     * @param int $userId
     * @param bool $unreadOnly
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = 20, $offset = 0) {
        try {
            $whereClause = "WHERE user_id = ?";
            $params = [$userId];
            
            if ($unreadOnly) {
                $whereClause .= " AND is_read = FALSE";
            }
            
            $stmt = $this->db->prepare("
                SELECT * FROM notifications 
                $whereClause
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Error getting user notifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     * @param int $notificationId
     * @param int $userId
     * @return array
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            return [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
            
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to mark notification as read'
            ];
        }
    }
    
    /**
     * Mark all notifications as read for a user
     * @param int $userId
     * @return array
     */
    public function markAllAsRead($userId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications 
                SET is_read = TRUE 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            
            $affectedRows = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Marked $affectedRows notifications as read"
            ];
            
        } catch (Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to mark notifications as read'
            ];
        }
    }
    
    /**
     * Delete a notification
     * @param int $notificationId
     * @param int $userId
     * @return array
     */
    public function deleteNotification($notificationId, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            return [
                'success' => true,
                'message' => 'Notification deleted'
            ];
            
        } catch (Exception $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to delete notification'
            ];
        }
    }
    
    /**
     * Get unread notification count for user
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Create follow notification
     * @param int $followerId
     * @param int $followingId
     * @param string $followerUsername
     * @return array
     */
    public function createFollowNotification($followerId, $followingId, $followerUsername) {
        // Check if user wants follow notifications
        if (!$this->shouldCreateNotification($followingId, 'follows')) {
            return ['success' => true, 'message' => 'Notification disabled by user preferences'];
        }
        
        $content = "$followerUsername started following you";
        return $this->createNotification($followingId, 'follow', $content, $followerId);
    }
    
    /**
     * Create or update clap notification
     * @param int $clapperId
     * @param int $authorId
     * @param int $articleId
     * @param string $clapperUsername
     * @param string $articleTitle
     * @param int $totalClapCount - Total claps from this user for this article
     * @return array
     */
    public function createClapNotification($clapperId, $authorId, $articleId, $clapperUsername, $articleTitle, $totalClapCount = 1) {
        // Don't notify if user clapped their own article
        if ($clapperId === $authorId) {
            return ['success' => true, 'message' => 'No self-notification created'];
        }
        
        // Check if user wants clap notifications
        if (!$this->shouldCreateNotification($authorId, 'claps')) {
            return ['success' => true, 'message' => 'Notification disabled by user preferences'];
        }
        
        try {
            // Check if there's already a clap notification from this user for this article
            $stmt = $this->db->prepare("
                SELECT id, content FROM notifications 
                WHERE user_id = ? AND type = 'clap' AND related_id = ? 
                AND content LIKE ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $searchPattern = "$clapperUsername gave % to your article \"$articleTitle\"";
            $stmt->execute([$authorId, $articleId, $searchPattern]);
            $existingNotification = $stmt->fetch();
            
            $clapText = $totalClapCount === 1 ? 'clap' : 'claps';
            $content = "$clapperUsername gave $totalClapCount $clapText to your article \"$articleTitle\"";
            
            if ($existingNotification) {
                // Update existing notification
                $updateStmt = $this->db->prepare("
                    UPDATE notifications 
                    SET content = ?, is_read = FALSE, created_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$content, $existingNotification['id']]);
                
                return [
                    'success' => true,
                    'message' => 'Clap notification updated',
                    'data' => [
                        'id' => $existingNotification['id'],
                        'updated' => true
                    ]
                ];
            } else {
                // Create new notification
                return $this->createNotification($authorId, 'clap', $content, $articleId);
            }
            
        } catch (Exception $e) {
            error_log("Error creating/updating clap notification: " . $e->getMessage());
            // Fallback to creating a new notification
            $clapText = $totalClapCount === 1 ? 'clap' : 'claps';
            $content = "$clapperUsername gave $totalClapCount $clapText to your article \"$articleTitle\"";
            return $this->createNotification($authorId, 'clap', $content, $articleId);
        }
    }
    
    /**
     * Create comment notification
     * @param int $commenterId
     * @param int $authorId
     * @param int $articleId
     * @param string $commenterUsername
     * @param string $articleTitle
     * @return array
     */
    public function createCommentNotification($commenterId, $authorId, $articleId, $commenterUsername, $articleTitle) {
        // Don't notify if user commented on their own article
        if ($commenterId === $authorId) {
            return ['success' => true, 'message' => 'No self-notification created'];
        }
        
        // Check if user wants comment notifications
        if (!$this->shouldCreateNotification($authorId, 'comments')) {
            return ['success' => true, 'message' => 'Notification disabled by user preferences'];
        }
        
        $content = "$commenterUsername commented on your article \"$articleTitle\"";
        return $this->createNotification($authorId, 'comment', $content, $articleId);
    }
    
    /**
     * Create publication invite notification
     * @param int $inviterId
     * @param int $inviteeId
     * @param int $publicationId
     * @param string $inviterUsername
     * @param string $publicationName
     * @param string $role
     * @return array
     */
    public function createPublicationInviteNotification($inviterId, $inviteeId, $publicationId, $inviterUsername, $publicationName, $role) {
        // Check if user wants publication invite notifications
        if (!$this->shouldCreateNotification($inviteeId, 'publication_invites')) {
            return ['success' => true, 'message' => 'Notification disabled by user preferences'];
        }
        
        $content = "$inviterUsername invited you to join \"$publicationName\" as a $role";
        return $this->createNotification($inviteeId, 'publication_invite', $content, $publicationId);
    }
    
    /**
     * Clean up old notifications (older than 30 days)
     * @return array
     */
    public function cleanupOldNotifications() {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();
            
            $deletedCount = $stmt->rowCount();
            
            return [
                'success' => true,
                'message' => "Cleaned up $deletedCount old notifications"
            ];
            
        } catch (Exception $e) {
            error_log("Error cleaning up notifications: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to cleanup notifications'
            ];
        }
    }
    
    /**
     * Get notification statistics for user
     * @param int $userId
     * @return array
     */
    public function getNotificationStats($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread,
                    SUM(CASE WHEN type = 'follow' THEN 1 ELSE 0 END) as follows,
                    SUM(CASE WHEN type = 'clap' THEN 1 ELSE 0 END) as claps,
                    SUM(CASE WHEN type = 'comment' THEN 1 ELSE 0 END) as comments,
                    SUM(CASE WHEN type = 'publication_invite' THEN 1 ELSE 0 END) as invites
                FROM notifications 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Error getting notification stats: " . $e->getMessage());
            return [
                'total' => 0,
                'unread' => 0,
                'follows' => 0,
                'claps' => 0,
                'comments' => 0,
                'invites' => 0
            ];
        }
    }
    
    /**
     * Check if notification should be created based on user preferences
     * @param int $userId
     * @param string $notificationType
     * @return bool
     */
    private function shouldCreateNotification($userId, $notificationType) {
        try {
            $stmt = $this->db->prepare("
                SELECT notification_preferences 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result && $result['notification_preferences']) {
                $preferences = json_decode($result['notification_preferences'], true);
                
                // Check push notifications preference (in-app notifications)
                if (isset($preferences['push_notifications'][$notificationType])) {
                    return (bool)$preferences['push_notifications'][$notificationType];
                }
            }
            
            // Default to true if no preferences set
            return true;
            
        } catch (Exception $e) {
            error_log("Error checking notification preferences: " . $e->getMessage());
            // Default to true on error
            return true;
        }
    }
}