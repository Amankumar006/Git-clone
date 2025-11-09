<?php

require_once __DIR__ . '/BaseRepository.php';

class ArticleSubmission extends BaseRepository {
    protected $table = 'article_submissions';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Submit article to publication
     */
    public function submitArticle($articleId, $publicationId, $submittedBy) {
        try {
            // Check if article is already submitted to this publication
            $existing = $this->getByArticleAndPublication($articleId, $publicationId);
            if ($existing && in_array($existing['status'], ['pending', 'under_review', 'approved'])) {
                return false; // Already submitted
            }
            
            $sql = "INSERT INTO {$this->table} (article_id, publication_id, submitted_by, status) 
                    VALUES (?, ?, ?, 'pending')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$articleId, $publicationId, $submittedBy]);
            
            if ($result) {
                $submissionId = $this->db->lastInsertId();
                
                // Update article with submission reference
                $this->updateArticleSubmission($articleId, $submissionId);
                
                return $this->getById($submissionId);
            }
            
            return false;
        } catch (Exception $e) {
            error_log("ArticleSubmission submitArticle error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get submission by ID
     */
    public function getById($id) {
        $sql = "SELECT s.*, a.title as article_title, a.author_id,
                       p.name as publication_name,
                       u1.username as submitted_by_username,
                       u2.username as reviewed_by_username
                FROM {$this->table} s
                JOIN articles a ON s.article_id = a.id
                JOIN publications p ON s.publication_id = p.id
                JOIN users u1 ON s.submitted_by = u1.id
                LEFT JOIN users u2 ON s.reviewed_by = u2.id
                WHERE s.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get submission by article and publication
     */
    public function getByArticleAndPublication($articleId, $publicationId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE article_id = ? AND publication_id = ? 
                ORDER BY created_at DESC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$articleId, $publicationId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get pending submissions for publication
     */
    public function getPendingSubmissions($publicationId, $limit = 20, $offset = 0) {
        $sql = "SELECT s.*, a.title as article_title, a.subtitle, a.reading_time,
                       a.featured_image_url, a.created_at as article_created_at,
                       u.username as author_username, u.profile_image_url as author_avatar
                FROM {$this->table} s
                JOIN articles a ON s.article_id = a.id
                JOIN users u ON a.author_id = u.id
                WHERE s.publication_id = ? AND s.status = 'pending'
                ORDER BY s.submitted_at ASC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$publicationId, $limit, $offset]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get submissions under review for publication
     */
    public function getUnderReviewSubmissions($publicationId, $reviewerId = null) {
        $sql = "SELECT s.*, a.title as article_title, a.subtitle, a.reading_time,
                       a.featured_image_url, a.created_at as article_created_at,
                       u1.username as author_username, u1.profile_image_url as author_avatar,
                       u2.username as reviewer_username
                FROM {$this->table} s
                JOIN articles a ON s.article_id = a.id
                JOIN users u1 ON a.author_id = u1.id
                LEFT JOIN users u2 ON s.reviewed_by = u2.id
                WHERE s.publication_id = ? AND s.status = 'under_review'";
        
        $params = [$publicationId];
        
        if ($reviewerId) {
            $sql .= " AND s.reviewed_by = ?";
            $params[] = $reviewerId;
        }
        
        $sql .= " ORDER BY s.submitted_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Assign reviewer to submission
     */
    public function assignReviewer($submissionId, $reviewerId) {
        $sql = "UPDATE {$this->table} 
                SET reviewed_by = ?, status = 'under_review', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$reviewerId, $submissionId]);
    }
    
    /**
     * Approve submission
     */
    public function approveSubmission($submissionId, $reviewerId, $reviewNotes = null) {
        try {
            $this->db->beginTransaction();
            
            // Update submission status
            $sql = "UPDATE {$this->table} 
                    SET status = 'approved', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP,
                        review_notes = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$reviewerId, $reviewNotes, $submissionId]);
            
            // Get submission details
            $submission = $this->getById($submissionId);
            
            // Update article status to published
            $sql = "UPDATE articles 
                    SET status = 'published', published_at = CURRENT_TIMESTAMP, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$submission['article_id']]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("ArticleSubmission approveSubmission error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Reject submission
     */
    public function rejectSubmission($submissionId, $reviewerId, $reviewNotes = null) {
        $sql = "UPDATE {$this->table} 
                SET status = 'rejected', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP,
                    review_notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$reviewNotes, $reviewerId, $submissionId]);
    }
    
    /**
     * Request revision
     */
    public function requestRevision($submissionId, $reviewerId, $revisionNotes) {
        $sql = "UPDATE {$this->table} 
                SET status = 'revision_requested', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP,
                    revision_notes = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$reviewerId, $revisionNotes, $submissionId]);
    }
    
    /**
     * Resubmit after revision
     */
    public function resubmitAfterRevision($submissionId) {
        $sql = "UPDATE {$this->table} 
                SET status = 'pending', revision_notes = NULL, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$submissionId]);
    }
    
    /**
     * Get submission statistics for publication
     */
    public function getSubmissionStats($publicationId) {
        $sql = "SELECT 
                    COUNT(*) as total_submissions,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
                    COUNT(CASE WHEN status = 'revision_requested' THEN 1 END) as revision_requested,
                    AVG(CASE WHEN reviewed_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(HOUR, submitted_at, reviewed_at) 
                        END) as avg_review_time_hours
                FROM {$this->table}
                WHERE publication_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$publicationId]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get user's submissions
     */
    public function getUserSubmissions($userId, $status = null, $limit = 20, $offset = 0) {
        $whereClause = "WHERE s.submitted_by = ?";
        $params = [$userId];
        
        if ($status) {
            $whereClause .= " AND s.status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT s.*, a.title as article_title, a.subtitle,
                       p.name as publication_name, p.logo_url as publication_logo,
                       u.username as reviewer_username
                FROM {$this->table} s
                JOIN articles a ON s.article_id = a.id
                JOIN publications p ON s.publication_id = p.id
                LEFT JOIN users u ON s.reviewed_by = u.id
                {$whereClause}
                ORDER BY s.submitted_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update article submission reference
     */
    private function updateArticleSubmission($articleId, $submissionId) {
        $sql = "UPDATE articles SET submission_id = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$submissionId, $articleId]);
    }
    
    /**
     * Get submission workflow history
     */
    public function getWorkflowHistory($submissionId) {
        // This would include status changes, comments, revisions, etc.
        $sql = "SELECT 
                    'status_change' as event_type,
                    status as event_data,
                    reviewed_by as user_id,
                    updated_at as event_time,
                    review_notes as notes
                FROM {$this->table}
                WHERE id = ?
                
                UNION ALL
                
                SELECT 
                    'review_comment' as event_type,
                    comment_type as event_data,
                    user_id,
                    created_at as event_time,
                    content as notes
                FROM article_review_comments
                WHERE submission_id = ?
                
                ORDER BY event_time DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$submissionId, $submissionId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Check if user can review submission
     */
    public function canUserReview($submissionId, $userId) {
        $sql = "SELECT s.publication_id, s.submitted_by, p.owner_id
                FROM {$this->table} s
                JOIN publications p ON s.publication_id = p.id
                WHERE s.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$submissionId]);
        $submission = $stmt->fetch();
        
        if (!$submission) {
            return false;
        }
        
        // Can't review own submission
        if ($submission['submitted_by'] == $userId) {
            return false;
        }
        
        // Check if user has permission in publication
        require_once __DIR__ . '/Publication.php';
        $publicationModel = new Publication();
        
        return $publicationModel->hasPermission($submission['publication_id'], $userId, 'editor');
    }
}