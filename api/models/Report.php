<?php

class Report extends BaseRepository {
    protected $table = 'reports';

    public function createReport($reporterId, $contentType, $contentId, $reason, $description = null) {
        try {
            // Check if user already reported this content
            $existingReport = $this->findExistingReport($reporterId, $contentType, $contentId);
            if ($existingReport) {
                throw new Exception('You have already reported this content');
            }

            $sql = "INSERT INTO {$this->table} (reporter_id, reported_content_type, reported_content_id, reason, description) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$reporterId, $contentType, $contentId, $reason, $description]);
            
            $reportId = $this->db->lastInsertId();
            
            // Auto-flag content for review
            $this->flagContentForReview($contentType, $contentId);
            
            return $this->getReportById($reportId);
        } catch (Exception $e) {
            throw new Exception('Failed to create report: ' . $e->getMessage());
        }
    }

    public function getReportById($id) {
        $sql = "SELECT r.*, 
                       reporter.username as reporter_username,
                       admin.username as admin_username
                FROM {$this->table} r
                LEFT JOIN users reporter ON r.reporter_id = reporter.id
                LEFT JOIN users admin ON r.admin_id = admin.id
                WHERE r.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getPendingReports($limit = 20, $offset = 0) {
        $sql = "SELECT r.*, 
                       reporter.username as reporter_username,
                       CASE 
                           WHEN r.reported_content_type = 'article' THEN a.title
                           WHEN r.reported_content_type = 'comment' THEN SUBSTRING(c.content, 1, 100)
                           WHEN r.reported_content_type = 'user' THEN u.username
                       END as content_preview
                FROM {$this->table} r
                LEFT JOIN users reporter ON r.reporter_id = reporter.id
                LEFT JOIN articles a ON r.reported_content_type = 'article' AND r.reported_content_id = a.id
                LEFT JOIN comments c ON r.reported_content_type = 'comment' AND r.reported_content_id = c.id
                LEFT JOIN users u ON r.reported_content_type = 'user' AND r.reported_content_id = u.id
                WHERE r.status IN ('pending', 'reviewing')
                ORDER BY r.created_at ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function updateReportStatus($reportId, $status, $adminId, $adminNotes = null) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = ?, admin_id = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$status, $adminId, $adminNotes, $reportId]);
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception('Failed to update report status: ' . $e->getMessage());
        }
    }

    public function getReportsByContent($contentType, $contentId) {
        $sql = "SELECT r.*, reporter.username as reporter_username
                FROM {$this->table} r
                LEFT JOIN users reporter ON r.reporter_id = reporter.id
                WHERE r.reported_content_type = ? AND r.reported_content_id = ?
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contentType, $contentId]);
        return $stmt->fetchAll();
    }

    public function getReportStats() {
        $sql = "SELECT 
                    COUNT(*) as total_reports,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
                    SUM(CASE WHEN status = 'reviewing' THEN 1 ELSE 0 END) as reviewing_reports,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports,
                    SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed_reports
                FROM {$this->table}
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function findExistingReport($reporterId, $contentType, $contentId) {
        $sql = "SELECT id FROM {$this->table} 
                WHERE reporter_id = ? AND reported_content_type = ? AND reported_content_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$reporterId, $contentType, $contentId]);
        return $stmt->fetch();
    }

    private function flagContentForReview($contentType, $contentId) {
        try {
            if ($contentType === 'article') {
                $sql = "UPDATE articles SET moderation_status = 'flagged', flagged_at = CURRENT_TIMESTAMP 
                        WHERE id = ? AND moderation_status = 'approved'";
            } elseif ($contentType === 'comment') {
                $sql = "UPDATE comments SET moderation_status = 'flagged', flagged_at = CURRENT_TIMESTAMP 
                        WHERE id = ? AND moderation_status = 'approved'";
            } else {
                return; // User reports don't auto-flag
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$contentId]);
        } catch (Exception $e) {
            // Log error but don't fail the report creation
            error_log('Failed to flag content for review: ' . $e->getMessage());
        }
    }
}