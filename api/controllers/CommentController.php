<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/ContentFilter.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/Validator.php';

class CommentController extends BaseController {
    private $commentModel;
    private $validator;
    private $notificationModel;
    private $contentFilter;
    
    public function __construct() {
        parent::__construct();
        $this->commentModel = new Comment();
        $this->validator = new Validator();
        $this->notificationModel = new Notification();
        $this->contentFilter = new ContentFilter();
    }
    
    /**
     * Create a new comment
     * POST /api/comments/create
     */
    public function createComment() {
        try {
            // Authenticate user
            $user = AuthMiddleware::validateUser();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Get request data
            $data = $this->getRequestData();
            
            // Validate required fields
            $validation = $this->validator->validate($data, [
                'article_id' => 'required|integer',
                'content' => 'required|string|min:1|max:2000'
            ]);
            
            if (!$validation['valid']) {
                return $this->sendError('Validation failed', 400, $validation['errors']);
            }
            
            $articleId = (int)$data['article_id'];
            $content = trim($data['content']);
            $parentCommentId = isset($data['parent_comment_id']) ? (int)$data['parent_comment_id'] : null;
            
            // Validate article exists
            if (!$this->articleExists($articleId)) {
                return $this->sendError('Article not found', 404);
            }
            
            // Validate parent comment exists if provided
            if ($parentCommentId && !$this->commentExists($parentCommentId)) {
                return $this->sendError('Parent comment not found', 404);
            }
            
            // Create comment
            $result = $this->commentModel->createComment($articleId, $user['id'], $content, $parentCommentId);
            
            if ($result['success']) {
                // Scan comment content for potential issues
                if (isset($result['data']['id'])) {
                    $this->contentFilter->scanContent('comment', $result['data']['id'], $content);
                }
                
                // Create notification for article author
                $this->createCommentNotification($user['id'], $articleId);
                
                return $this->sendResponse($result['data'], 'Comment created successfully', 201);
            } else {
                return $this->sendError($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in createComment: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get comments for an article
     * GET /api/comments/article/{id}
     */
    public function getArticleComments($articleId) {
        try {
            $articleId = (int)$articleId;
            
            // Validate article exists
            if (!$this->articleExists($articleId)) {
                return $this->sendError('Article not found', 404);
            }
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            // Get comments
            $comments = $this->commentModel->getArticleComments($articleId, $limit, $offset);
            $totalComments = $this->commentModel->getArticleCommentCount($articleId);
            $topLevelComments = $this->commentModel->getTopLevelCommentCount($articleId);
            
            return $this->sendResponse([
                'comments' => $comments,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => $totalComments, // Total comments including replies
                    'total_top_level' => $topLevelComments, // Top-level comments for pagination
                    'total_pages' => ceil($topLevelComments / $limit) // Pagination based on top-level comments
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getArticleComments: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Update a comment
     * PUT /api/comments/update/{id}
     */
    public function updateComment($commentId) {
        try {
            // Authenticate user
            $user = AuthMiddleware::validateUser();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $commentId = (int)$commentId;
            
            // Get request data
            $data = $this->getRequestData();
            
            // Validate required fields
            $validation = $this->validator->validate($data, [
                'content' => 'required|string|min:1|max:2000'
            ]);
            
            if (!$validation['valid']) {
                return $this->sendError('Validation failed', 400, $validation['errors']);
            }
            
            $content = trim($data['content']);
            
            // Update comment
            $result = $this->commentModel->updateComment($commentId, $user['id'], $content);
            
            if ($result['success']) {
                return $this->sendResponse($result['data'], 'Comment updated successfully');
            } else {
                return $this->sendError($result['error'], $result['error'] === 'Unauthorized to edit this comment' ? 403 : 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in updateComment: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Delete a comment
     * DELETE /api/comments/delete/{id}
     */
    public function deleteComment($commentId) {
        try {
            // Authenticate user
            $user = AuthMiddleware::validateUser();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $commentId = (int)$commentId;
            
            // Delete comment
            $result = $this->commentModel->deleteComment($commentId, $user['id']);
            
            if ($result['success']) {
                return $this->sendResponse(null, $result['message']);
            } else {
                return $this->sendError($result['error'], $result['error'] === 'Unauthorized to delete this comment' ? 403 : 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in deleteComment: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get a specific comment
     * GET /api/comments/show/{id}
     */
    public function getComment($commentId) {
        try {
            $commentId = (int)$commentId;
            
            $comment = $this->commentModel->getCommentById($commentId);
            
            if ($comment) {
                return $this->sendResponse($comment);
            } else {
                return $this->sendError('Comment not found', 404);
            }
            
        } catch (Exception $e) {
            error_log("Error in getComment: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get user's comments
     * GET /api/comments/user/{id}
     */
    public function getUserComments($userId) {
        try {
            $userId = (int)$userId;
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            // Get user comments
            $comments = $this->commentModel->getUserComments($userId, $limit, $offset);
            
            return $this->sendResponse([
                'comments' => $comments,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => count($comments)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getUserComments: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Check if article exists
     * @param int $articleId
     * @return bool
     */
    private function articleExists($articleId) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM articles WHERE id = ?");
            $stmt->execute([$articleId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error checking article existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if comment exists
     * @param int $commentId
     * @return bool
     */
    private function commentExists($commentId) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM comments WHERE id = ?");
            $stmt->execute([$commentId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error checking comment existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create comment notification
     * @param int $commenterId
     * @param int $articleId
     */
    private function createCommentNotification($commenterId, $articleId) {
        try {
            // Get article and author info
            $stmt = $this->db->prepare("
                SELECT a.author_id, a.title 
                FROM articles a 
                WHERE a.id = ?
            ");
            $stmt->execute([$articleId]);
            $article = $stmt->fetch();
            
            if ($article) {
                // Get commenter username
                $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$commenterId]);
                $commenter = $stmt->fetch();
                
                if ($commenter) {
                    $this->notificationModel->createCommentNotification(
                        $commenterId,
                        $article['author_id'],
                        $articleId,
                        $commenter['username'],
                        $article['title']
                    );
                }
            }
        } catch (Exception $e) {
            error_log("Error creating comment notification: " . $e->getMessage());
        }
    }
}