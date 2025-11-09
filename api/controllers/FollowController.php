<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Follow.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class FollowController extends BaseController {
    private $followModel;
    private $authMiddleware;
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->followModel = new Follow();
        $this->authMiddleware = new AuthMiddleware();
        $this->notificationModel = new Notification();
    }
    
    /**
     * Follow a user
     * POST /api/follows/follow
     */
    public function followUser() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Get request data
            $data = $this->getRequestData();
            
            // Validate required fields
            if (!isset($data['user_id'])) {
                return $this->sendError('User ID is required', 400);
            }
            
            $followingId = (int)$data['user_id'];
            
            // Validate user exists
            if (!$this->userExists($followingId)) {
                return $this->sendError('User not found', 404);
            }
            
            // Follow user
            $result = $this->followModel->followUser($user['id'], $followingId);
            
            if ($result['success']) {
                // Create notification for followed user
                $this->createFollowNotification($user['id'], $followingId);
                
                return $this->sendResponse([
                    'is_following' => true,
                    'follower_count' => $this->followModel->getFollowerCount($followingId)
                ], $result['message']);
            } else {
                return $this->sendError($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in followUser: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Unfollow a user
     * DELETE /api/follows/unfollow
     */
    public function unfollowUser() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Get request data
            $data = $this->getRequestData();
            
            // Validate required fields
            if (!isset($data['user_id'])) {
                return $this->sendError('User ID is required', 400);
            }
            
            $followingId = (int)$data['user_id'];
            
            // Unfollow user
            $result = $this->followModel->unfollowUser($user['id'], $followingId);
            
            if ($result['success']) {
                return $this->sendResponse([
                    'is_following' => false,
                    'follower_count' => $this->followModel->getFollowerCount($followingId)
                ], $result['message']);
            } else {
                return $this->sendError($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Error in unfollowUser: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get user's followers
     * GET /api/follows/followers/{userId}
     */
    public function getFollowers($userId) {
        try {
            $userId = (int)$userId;
            
            // Validate user exists
            if (!$this->userExists($userId)) {
                return $this->sendError('User not found', 404);
            }
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            // Get followers
            $followers = $this->followModel->getFollowers($userId, $limit, $offset);
            $totalFollowers = $this->followModel->getFollowerCount($userId);
            
            return $this->sendResponse([
                'followers' => $followers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => $totalFollowers,
                    'total_pages' => ceil($totalFollowers / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getFollowers: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get users that a user is following
     * GET /api/follows/following/{userId}
     */
    public function getFollowing($userId) {
        try {
            $userId = (int)$userId;
            
            // Validate user exists
            if (!$this->userExists($userId)) {
                return $this->sendError('User not found', 404);
            }
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            // Get following
            $following = $this->followModel->getFollowing($userId, $limit, $offset);
            $totalFollowing = $this->followModel->getFollowingCount($userId);
            
            return $this->sendResponse([
                'following' => $following,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => $totalFollowing,
                    'total_pages' => ceil($totalFollowing / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getFollowing: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get follow status between users
     * GET /api/follows/status/{userId}
     */
    public function getFollowStatus($userId) {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $userId = (int)$userId;
            
            // Validate user exists
            if (!$this->userExists($userId)) {
                return $this->sendError('User not found', 404);
            }
            
            $isFollowing = $this->followModel->isFollowing($user['id'], $userId);
            $followerCount = $this->followModel->getFollowerCount($userId);
            $followingCount = $this->followModel->getFollowingCount($userId);
            
            return $this->sendResponse([
                'is_following' => $isFollowing,
                'follower_count' => $followerCount,
                'following_count' => $followingCount
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getFollowStatus: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get following feed (articles from followed users)
     * GET /api/follows/feed
     */
    public function getFollowingFeed() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            // Get pagination parameters
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
            $offset = ($page - 1) * $limit;
            
            // Get following feed
            $articles = $this->followModel->getFollowingFeed($user['id'], $limit, $offset);
            
            return $this->sendResponse([
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => count($articles)
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getFollowingFeed: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get suggested users to follow
     * GET /api/follows/suggestions
     */
    public function getSuggestedFollows() {
        try {
            // Authenticate user
            $user = $this->authMiddleware->authenticate();
            if (!$user) {
                return $this->sendError('Unauthorized', 401);
            }
            
            $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
            
            $suggestions = $this->followModel->getSuggestedFollows($user['id'], $limit);
            
            return $this->sendResponse([
                'suggestions' => $suggestions
            ]);
            
        } catch (Exception $e) {
            error_log("Error in getSuggestedFollows: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Check if user exists
     * @param int $userId
     * @return bool
     */
    private function userExists($userId) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Error checking user existence: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create follow notification
     * @param int $followerId
     * @param int $followingId
     */
    private function createFollowNotification($followerId, $followingId) {
        try {
            // Get follower username
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$followerId]);
            $follower = $stmt->fetch();
            
            if ($follower) {
                $this->notificationModel->createFollowNotification(
                    $followerId,
                    $followingId,
                    $follower['username']
                );
            }
        } catch (Exception $e) {
            error_log("Error creating follow notification: " . $e->getMessage());
        }
    }
}