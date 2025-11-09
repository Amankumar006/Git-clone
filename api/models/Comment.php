<?php

require_once __DIR__ . '/BaseRepository.php';

class Comment extends BaseRepository {
    protected $table = 'comments';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get total comments for all articles by author
     */
    public function getTotalCommentsByAuthor($authorId) {
        try {
            $sql = "SELECT COUNT(*) as total_comments
                    FROM comments c
                    JOIN articles a ON c.article_id = a.id
                    WHERE a.author_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$authorId]);
            $result = $stmt->fetch();
            
            return (int)$result['total_comments'];
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent comments on user's articles
     */
    public function getRecentCommentsOnUserArticles($authorId, $limit = 10) {
        try {
            $sql = "SELECT 
                        c.*, 
                        u.username as commenter_username,
                        u.profile_image_url as commenter_avatar,
                        a.title as article_title,
                        a.id as article_id
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    JOIN articles a ON c.article_id = a.id
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
     * Get comments for a specific article
     */
    public function getArticleComments($articleId, $limit = 20, $offset = 0) {
        try {
            // Get all comments for the article (not just limited ones for threading)
            $sql = "SELECT 
                        c.*,
                        u.username,
                        u.profile_image_url as author_avatar
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.article_id = ?
                    ORDER BY c.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId]);
            $allComments = $stmt->fetchAll();
            
            // Build threaded structure
            $threadedComments = $this->buildCommentTree($allComments);
            
            // Apply pagination to top-level comments only
            $topLevelComments = array_slice($threadedComments, $offset, $limit);
            
            return $topLevelComments;
        } catch (Exception $e) {
            error_log("Get article comments error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Build threaded comment structure
     */
    private function buildCommentTree($comments) {
        $commentMap = [];
        $topLevelComments = [];
        
        // First pass: create a map of all comments
        foreach ($comments as $comment) {
            $comment['replies'] = [];
            $commentMap[$comment['id']] = $comment;
        }
        
        // Second pass: build the tree structure
        foreach ($commentMap as $comment) {
            if ($comment['parent_comment_id'] === null) {
                // Top-level comment
                $topLevelComments[] = &$commentMap[$comment['id']];
            } else {
                // Reply - add to parent's replies array
                if (isset($commentMap[$comment['parent_comment_id']])) {
                    $commentMap[$comment['parent_comment_id']]['replies'][] = &$commentMap[$comment['id']];
                }
            }
        }
        
        // Sort top-level comments by creation date (newest first)
        usort($topLevelComments, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $topLevelComments;
    }

    /**
     * Create a new comment
     */
    public function createComment($articleId, $userId, $content, $parentId = null) {
        try {
            $sql = "INSERT INTO comments (article_id, user_id, content, parent_comment_id) 
                    VALUES (?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$articleId, $userId, $content, $parentId]);
            
            $commentId = $this->db->lastInsertId();
            
            // Update article comment count
            $this->updateArticleCommentCount($articleId);
            
            // Get the created comment with user info
            $comment = $this->getCommentById($commentId);
            
            return [
                'success' => true,
                'data' => $comment
            ];
        } catch (Exception $e) {
            error_log("Create comment error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create comment'
            ];
        }
    }

    /**
     * Update article comment count
     */
    private function updateArticleCommentCount($articleId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE articles 
                SET comment_count = (
                    SELECT COUNT(*) FROM comments WHERE article_id = ?
                ) 
                WHERE id = ?
            ");
            $stmt->execute([$articleId, $articleId]);
        } catch (Exception $e) {
            error_log("Update article comment count error: " . $e->getMessage());
        }
    }

    /**
     * Delete a comment
     */
    public function deleteComment($commentId, $userId) {
        try {
            // First get the comment to check ownership and get article_id
            $stmt = $this->db->prepare("SELECT article_id, user_id FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch();
            
            if (!$comment) {
                return [
                    'success' => false,
                    'error' => 'Comment not found'
                ];
            }
            
            if ($comment['user_id'] != $userId) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized to delete this comment'
                ];
            }
            
            // Delete the comment
            $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            
            // Update article comment count
            $this->updateArticleCommentCount($comment['article_id']);
            
            return [
                'success' => true,
                'message' => 'Comment deleted successfully'
            ];
        } catch (Exception $e) {
            error_log("Delete comment error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to delete comment'
            ];
        }
    }

    /**
     * Update a comment
     */
    public function updateComment($commentId, $userId, $content) {
        try {
            // First check ownership
            $stmt = $this->db->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch();
            
            if (!$comment) {
                return [
                    'success' => false,
                    'error' => 'Comment not found'
                ];
            }
            
            if ($comment['user_id'] != $userId) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized to update this comment'
                ];
            }
            
            // Update the comment
            $stmt = $this->db->prepare("
                UPDATE comments 
                SET content = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$content, $commentId]);
            
            // Get the updated comment with user info
            $updatedComment = $this->getCommentById($commentId);
            
            return [
                'success' => true,
                'data' => $updatedComment,
                'message' => 'Comment updated successfully'
            ];
        } catch (Exception $e) {
            error_log("Update comment error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update comment'
            ];
        }
    }

    /**
     * Get comment count for a specific article
     */
    public function getArticleCommentCount($articleId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM comments WHERE article_id = ?");
            $stmt->execute([$articleId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Get article comment count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get top-level comment count for pagination
     */
    public function getTopLevelCommentCount($articleId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM comments WHERE article_id = ? AND parent_comment_id IS NULL");
            $stmt->execute([$articleId]);
            $result = $stmt->fetch();
            
            return (int)$result['count'];
        } catch (Exception $e) {
            error_log("Get top-level comment count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get a specific comment by ID
     */
    public function getCommentById($commentId) {
        try {
            $sql = "SELECT 
                        c.*,
                        u.username,
                        u.profile_image_url as author_avatar
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    WHERE c.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$commentId]);
            
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Get comment by ID error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get comments by user
     */
    public function getUserComments($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT 
                        c.*,
                        u.username,
                        u.profile_image_url as author_avatar,
                        a.title as article_title,
                        a.id as article_id
                    FROM comments c
                    JOIN users u ON c.user_id = u.id
                    JOIN articles a ON c.article_id = a.id
                    WHERE c.user_id = ?
                    ORDER BY c.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get user comments error: " . $e->getMessage());
            return [];
        }
    }
}