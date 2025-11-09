<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Bookmark.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class BookmarkController extends BaseController {
    private $bookmarkModel;
    private $authMiddleware;
    
    public function __construct() {
        parent::__construct();
        $this->bookmarkModel = new Bookmark();
        $this->authMiddleware = new AuthMiddleware();
    }
    
    /**
     * Add bookmark
     * POST /api/bookmarks/add
     */
    public function addBookmark() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Get request data
            $data = $this->getRequestData();
            
            // Validate required fields
            if (!isset($data['article_id'])) {
                return $this->sendError('Article ID is required', 400);
            }
            
            $articleId = (int)$data['article_id'];
            
            // Validate article exists
            if (!$this->articleExists($articleId)) {
                return $this->sendError('Article not found', 404);
            }
            
            // Add bookmark
            $result = $this->bookmarkModel->addBookmark($user['id'], $articleId);
            
            if ($result['success']) {
                return $this->sendResponse(null, $result['message']);
            } else {
                return $this->sendError($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in addBookmark: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Remove bookmark
     * DELETE /api/bookmarks/remove
     */
    public function removeBookmark() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Get request data
            $data = $this->getRequestData();
            
            // Validate required fields
            if (!isset($data['article_id'])) {
                return $this->sendError('Article ID is required', 400);
            }
            
            $articleId = (int)$data['article_id'];
            
            // Remove bookmark
            $result = $this->bookmarkModel->removeBookmark($user['id'], $articleId);
            
            if ($result['success']) {
                return $this->sendResponse(null, $result['message']);
            } else {
                return $this->sendError($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in removeBookmark: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get user's bookmarks
     * GET /api/bookmarks/user/{id}
     */
    public function getUserBookmarks($userId = null) {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Use current user if no userId provided
            $targetUserId = $userId ? (int)$userId : $user['id'];
            
            // Only allow users to see their own bookmarks
            if ($targetUserId !== $user['id']) {
                return $this->sendError('Forbidden', 403);
            }
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            // Get bookmarks
            $bookmarks = $this->bookmarkModel->getUserBookmarks($targetUserId, $limit, $offset);
            $totalBookmarks = $this->bookmarkModel->getUserBookmarkCount($targetUserId);
            
            return $this->sendResponse([
                'bookmarks' => $bookmarks,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => $totalBookmarks,
                    'total_pages' => ceil($totalBookmarks / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getUserBookmarks: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Check bookmark status for an article
     * GET /api/bookmarks/status/{articleId}
     */
    public function getBookmarkStatus($articleId) {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $articleId = (int)$articleId;
            
            // Validate article exists
            if (!$this->articleExists($articleId)) {
                return $this->sendError('Article not found', 404);
            }
            
            $isBookmarked = $this->bookmarkModel->isBookmarked($user['id'], $articleId);
            
            return $this->sendResponse([
                'is_bookmarked' => $isBookmarked
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getBookmarkStatus: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get popular bookmarked articles
     * GET /api/bookmarks/popular
     */
    public function getPopularBookmarks() {
        try {
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
            
            $popularBookmarks = $this->bookmarkModel->getPopularBookmarks($limit);
            
            return $this->sendResponse([
                'articles' => $popularBookmarks
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getPopularBookmarks: " . $e->getMessage());
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
            $stmt = $this->db->prepare("SELECT id FROM articles WHERE id = ? AND status = 'published'");
            $stmt->execute([$articleId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error checking article existence: " . $e->getMessage());
            return false;
        }
    }
}