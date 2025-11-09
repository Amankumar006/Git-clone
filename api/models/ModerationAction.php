<?php

class ModerationAction extends BaseRepository {
    protected $table = 'moderation_actions';

    public function logAction($adminId, $actionType, $targetType, $targetId, $reason, $details = null) {
        try {
            $sql = "INSERT INTO {$this->table} (admin_id, action_type, target_type, target_id, reason, details) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $adminId, 
                $actionType, 
                $targetType, 
                $targetId, 
                $reason, 
                $details ? json_encode($details) : null
            ]);
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception('Failed to log moderation action: ' . $e->getMessage());
        }
    }

    public function approveContent($adminId, $contentType, $contentId, $reason) {
        try {
            $this->db->beginTransaction();
            
            // Update content status
            if ($contentType === 'article') {
                $sql = "UPDATE articles SET moderation_status = 'approved', moderated_by = ? WHERE id = ?";
            } elseif ($contentType === 'comment') {
                $sql = "UPDATE comments SET moderation_status = 'approved', moderated_by = ? WHERE id = ?";
            } else {
                throw new Exception('Invalid content type for approval');
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$adminId, $contentId]);
            
            // Log the action
            $this->logAction($adminId, 'approve', $contentType, $contentId, $reason);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Failed to approve content: ' . $e->getMessage());
        }
    }

    public function removeContent($adminId, $contentType, $contentId, $reason) {
        try {
            $this->db->beginTransaction();
            
            // Update content status
            if ($contentType === 'article') {
                $sql = "UPDATE articles SET moderation_status = 'removed', moderated_by = ? WHERE id = ?";
            } elseif ($contentType === 'comment') {
                $sql = "UPDATE comments SET moderation_status = 'removed', moderated_by = ? WHERE id = ?";
            } else {
                throw new Exception('Invalid content type for removal');
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$adminId, $contentId]);
            
            // Log the action
            $this->logAction($adminId, 'remove', $contentType, $contentId, $reason);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Failed to remove content: ' . $e->getMessage());
        }
    }

    public function warnUser($adminId, $userId, $reason) {
        try {
            $this->db->beginTransaction();
            
            // Create user penalty
            $sql = "INSERT INTO user_penalties (user_id, admin_id, penalty_type, reason) 
                    VALUES (?, ?, 'warning', ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $adminId, $reason]);
            
            // Log the action
            $this->logAction($adminId, 'warn', 'user', $userId, $reason);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Failed to warn user: ' . $e->getMessage());
        }
    }

    public function suspendUser($adminId, $userId, $reason, $duration = null) {
        try {
            $this->db->beginTransaction();
            
            // Calculate expiration date
            $expiresAt = null;
            if ($duration) {
                $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
            }
            
            // Update user status
            $sql = "UPDATE users SET is_suspended = TRUE, suspension_expires_at = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$expiresAt, $userId]);
            
            // Create user penalty
            $penaltyType = $duration ? 'temporary_suspension' : 'permanent_ban';
            $sql = "INSERT INTO user_penalties (user_id, admin_id, penalty_type, reason, expires_at) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $adminId, $penaltyType, $reason, $expiresAt]);
            
            // Log the action
            $actionType = $duration ? 'suspend' : 'ban';
            $details = $duration ? ['duration_days' => $duration] : null;
            $this->logAction($adminId, $actionType, 'user', $userId, $reason, $details);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception('Failed to suspend user: ' . $e->getMessage());
        }
    }

    public function getActionHistory($targetType = null, $targetId = null, $limit = 50, $offset = 0) {
        $whereClause = '';
        $params = [];
        
        if ($targetType && $targetId) {
            $whereClause = 'WHERE ma.target_type = ? AND ma.target_id = ?';
            $params = [$targetType, $targetId];
        }
        
        $sql = "SELECT ma.*, admin.username as admin_username,
                       CASE 
                           WHEN ma.target_type = 'article' THEN a.title
                           WHEN ma.target_type = 'comment' THEN SUBSTRING(c.content, 1, 100)
                           WHEN ma.target_type = 'user' THEN u.username
                       END as target_info
                FROM {$this->table} ma
                LEFT JOIN users admin ON ma.admin_id = admin.id
                LEFT JOIN articles a ON ma.target_type = 'article' AND ma.target_id = a.id
                LEFT JOIN comments c ON ma.target_type = 'comment' AND ma.target_id = c.id
                LEFT JOIN users u ON ma.target_type = 'user' AND ma.target_id = u.id
                {$whereClause}
                ORDER BY ma.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getModerationStats($days = 30) {
        $sql = "SELECT 
                    COUNT(*) as total_actions,
                    SUM(CASE WHEN action_type = 'approve' THEN 1 ELSE 0 END) as approvals,
                    SUM(CASE WHEN action_type = 'remove' THEN 1 ELSE 0 END) as removals,
                    SUM(CASE WHEN action_type = 'warn' THEN 1 ELSE 0 END) as warnings,
                    SUM(CASE WHEN action_type = 'suspend' THEN 1 ELSE 0 END) as suspensions,
                    SUM(CASE WHEN action_type = 'ban' THEN 1 ELSE 0 END) as bans
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetch();
    }
}