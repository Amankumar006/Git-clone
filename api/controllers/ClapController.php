<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Clap.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ClapController extends BaseController {
    private $clapModel;
    private $authMiddleware;
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->clapModel = new Clap();
        $this->authMiddleware = new AuthMiddleware();
        $this->notificationModel = new Notification();
    }
    
    /**
     * Add clap to article
     * POST /api/claps/add
     */
    public function addClap() {
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
            $count = isset($data['count']) ? min((int)$data['count'], 50) : 1;
            
            // Validate article exists
            if (!$this->articleExists($articleId)) {
                return $this->sendError('Article not found', 404);
            }
            
            // Check if user can clap
            if (!$this->clapModel->canUserClap($user['id'], $articleId)) {
                return $this->sendError('Maximum clap limit reached (50 claps per article)', 400);
            }
            
            // Add clap
            $result = $this->clapModel->addClap($user['id'], $articleId, $count);
            
            if ($result['success']) {
                // Get total claps from this user for this article
                $totalUserClaps = $this->clapModel->getUserClapCount($user['id'], $articleId);
                
                // Create notification for article author with total clap count
                $this->createClapNotification($user['id'], $articleId, $totalUserClaps);
                
                return $this->sendResponse([
                    'clap' => $result['data'],
                    'total_claps' => $result['total_claps'],
                    'user_clap_count' => $totalUserClaps
                ], 'Clap added successfully');
            } else {
                return $this->sendError($result['error'], 500);
            }
            
        } catch (Exception $e) {
            error_log("Error in addClap: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Remove clap from article
     * DELETE /api/claps/remove
     */
    public function removeClap() {
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
            
            // Remove clap
            $result = $this->clapModel->removeClap($user['id'], $articleId);
            
            if ($result['success']) {
                return $this->sendResponse([
                    'total_claps' => $result['total_claps'],
                    'user_clap_count' => 0
                ], 'Clap removed successfully');
            } else {
                return $this->sendError($result['error'], 500);
            }
            
        } catch (Exception $e) {
            error_log("Error in removeClap: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get clap information for an article
     * GET /api/claps/article/{id}
     */
    public function getArticleClaps($articleId) {
        try {
            $articleId = (int)$articleId;
            
            // Validate article exists
            if (!$this->articleExists($articleId)) {
                return $this->sendError('Article not found', 404);
            }
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
            $offset = ($page - 1) * $limit;
            
            // Get claps with user information
            $claps = $this->clapModel->getArticleClapsWithUsers($articleId, $limit, $offset);
            $totalClaps = $this->clapModel->getArticleTotalClaps($articleId);
            
            // Get current user's clap count if authenticated
            $userClapCount = 0;
            $user = $this->authMiddleware->authenticate(false); // Don't require auth
            if ($user) {
                $userClapCount = $this->clapModel->getUserClapCount($user['id'], $articleId);
            }
            
            return $this->sendResponse([
                'claps' => $claps,
                'total_claps' => $totalClaps,
                'user_clap_count' => $userClapCount,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => count($claps)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getArticleClaps: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get user's clap status for an article
     * GET /api/claps/status/{articleId}
     */
    public function getClapStatus($articleId) {
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
            
            $userClapCount = $this->clapModel->getUserClapCount($user['id'], $articleId);
            $totalClaps = $this->clapModel->getArticleTotalClaps($articleId);
            $canClap = $this->clapModel->canUserClap($user['id'], $articleId);
            
            return $this->sendResponse([
                'user_clap_count' => $userClapCount,
                'total_claps' => $totalClaps,
                'can_clap' => $canClap,
                'max_claps' => 50
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getClapStatus: " . $e->getMessage());
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
     * Create clap notification
     * @param int $clapperId
     * @param int $articleId
     * @param int $totalClapCount - Total claps from this user for this article
     */
    private function createClapNotification($clapperId, $articleId, $totalClapCount) {
        try {
            // Get article and author info
            $stmt = $this->db->prepare("
                SELECT a.author_id, a.title, u.username 
                FROM articles a 
                JOIN users u ON a.author_id = u.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$articleId]);
            $article = $stmt->fetch();
            
            if ($article) {
                // Get clapper username
                $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$clapperId]);
                $clapper = $stmt->fetch();
                
                if ($clapper) {
                    $this->notificationModel->createClapNotification(
                        $clapperId,
                        $article['author_id'],
                        $articleId,
                        $clapper['username'],
                        $article['title'],
                        $totalClapCount
                    );
                }
            }
        } catch (Exception $e) {
            error_log("Error creating clap notification: " . $e->getMessage());
        }
    }
}