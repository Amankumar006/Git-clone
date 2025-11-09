<?php
/**
 * User Controller
 * Handles user profile management, following, and user-related operations
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../utils/FileUpload.php';
require_once __DIR__ . '/../config/database.php';

class UserController extends BaseController {
    private $userModel;
    private $fileUpload;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->fileUpload = new FileUpload();
    }
    
    /**
     * Get user profile
     * GET /api/users/profile?id=:id (public view by ID)
     * GET /api/users/profile?username=:username (public view by username)
     * GET /api/users/profile (own profile - requires auth)
     */
    public function getProfile() {
        try {
            $userId = $_GET['id'] ?? null;
            $username = $_GET['username'] ?? null;
            
            if ($userId || $username) {
                // Public profile view
                if ($username) {
                    $user = $this->userModel->findByUsername($username);
                    if (!$user) {
                        return $this->sendError('User not found', 404);
                    }
                    $userId = $user['id'];
                }
                
                $result = $this->userModel->getProfile($userId);
                
                if ($result['success']) {
                    // For now, just return the profile without follow status
                    return $this->sendResponse([
                        'user' => $result['user'],
                        'is_following' => false
                    ], 'Profile retrieved successfully');
                } else {
                    return $this->sendError($result['error'], 404);
                }
            } else {
                // Own profile - requires authentication
                $authUser = AuthMiddleware::authenticate();
                $result = $this->userModel->getProfile($authUser['user_id']);
                
                if ($result['success']) {
                    // Include private data for own profile
                    $user = $this->userModel->findById($authUser['user_id']);
                    unset($user['password_hash']);
                    $user['social_links'] = json_decode($user['social_links'], true) ?? [];
                    
                    return $this->sendResponse(['user' => $user], 'Profile retrieved successfully');
                } else {
                    return $this->sendError($result['error'], 404);
                }
            }
        } catch (Exception $e) {
            error_log("Get profile error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Update user profile
     * PUT /api/users/profile
     */
    public function updateProfile() {
        try {
            $authUser = AuthMiddleware::authenticate();
            $input = $this->getJsonInput();
            
            $result = $this->userModel->updateProfile($authUser['user_id'], $input);
            
            if ($result['success']) {
                return $this->sendResponse(['user' => $result['user']], 'Profile updated successfully');
            } else {
                return $this->sendError('Profile update failed', 400, $result['errors']);
            }
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Upload profile avatar
     * POST /api/users/upload-avatar
     */
    public function uploadAvatar() {
        try {
            $authUser = AuthMiddleware::authenticate();
            
            if (!isset($_FILES['avatar'])) {
                return $this->sendError('No file uploaded', 400);
            }
            
            $file = $_FILES['avatar'];
            
            // Validate file
            $validation = $this->fileUpload->validateImage($file);
            if (!$validation['valid']) {
                return $this->sendError('Invalid file', 400, $validation['errors']);
            }
            
            // Upload file
            $uploadResult = $this->fileUpload->uploadImage($file, 'profiles/');
            
            if ($uploadResult['success']) {
                // Update user profile with new image URL
                $updateResult = $this->userModel->updateProfile($authUser['user_id'], [
                    'profile_image_url' => $uploadResult['url']
                ]);
                
                if ($updateResult['success']) {
                    return $this->sendResponse([
                        'user' => $updateResult['user'],
                        'image_url' => $uploadResult['url']
                    ], 'Avatar uploaded successfully');
                } else {
                    return $this->sendError('Failed to update profile with new avatar', 500);
                }
            } else {
                return $this->sendError($uploadResult['error'], 500);
            }
        } catch (Exception $e) {
            error_log("Upload avatar error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Follow a user
     * POST /api/users/follow
     */
    public function follow() {
        try {
            $authUser = AuthMiddleware::authenticate();
            $input = $this->getJsonInput();
            
            if (empty($input['user_id'])) {
                return $this->sendError('User ID is required', 400);
            }
            
            $targetUserId = $input['user_id'];
            
            // Can't follow yourself
            if ($authUser['user_id'] == $targetUserId) {
                return $this->sendError('Cannot follow yourself', 400);
            }
            
            // Check if target user exists
            $targetUser = $this->userModel->findById($targetUserId);
            if (!$targetUser) {
                return $this->sendError('User not found', 404);
            }
            
            // Check if already following
            $existingFollow = $this->checkFollowStatus($authUser['user_id'], $targetUserId);
            if ($existingFollow) {
                return $this->sendError('Already following this user', 400);
            }
            
            // Create follow relationship
            $result = $this->createFollow($authUser['user_id'], $targetUserId);
            
            if ($result) {
                // Create notification for the followed user
                $this->createNotification($targetUserId, 'follow', 
                    "{$authUser['username']} started following you", $authUser['user_id']);
                
                return $this->sendResponse([], 'User followed successfully');
            } else {
                return $this->sendError('Failed to follow user', 500);
            }
        } catch (Exception $e) {
            error_log("Follow user error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Unfollow a user
     * DELETE /api/users/follow?user_id=:id
     */
    public function unfollow() {
        try {
            $authUser = AuthMiddleware::authenticate();
            $targetUserId = $_GET['user_id'] ?? null;
            
            if (!$targetUserId) {
                return $this->sendError('User ID is required', 400);
            }
            
            // Check if following
            $existingFollow = $this->checkFollowStatus($authUser['user_id'], $targetUserId);
            if (!$existingFollow) {
                return $this->sendError('Not following this user', 400);
            }
            
            // Remove follow relationship
            $result = $this->removeFollow($authUser['user_id'], $targetUserId);
            
            if ($result) {
                return $this->sendResponse([], 'User unfollowed successfully');
            } else {
                return $this->sendError('Failed to unfollow user', 500);
            }
        } catch (Exception $e) {
            error_log("Unfollow user error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get user's articles
     * GET /api/users/articles?id=:id
     */
    public function getUserArticles() {
        try {
            $userId = $_GET['id'] ?? null;
            
            if (!$userId) {
                return $this->sendError('User ID is required', 400);
            }
            
            // Check if user exists
            $user = $this->userModel->findById($userId);
            if (!$user) {
                return $this->sendError('User not found', 404);
            }
            
            // Get published articles only for public view
            $articles = $this->getUserPublishedArticles($userId);
            
            return $this->sendResponse([
                'articles' => $articles,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'bio' => $user['bio'],
                    'profile_image_url' => $user['profile_image_url']
                ]
            ], 'User articles retrieved successfully');
        } catch (Exception $e) {
            error_log("Get user articles error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get user's followers
     * GET /api/users/followers?id=:id
     */
    public function getFollowers() {
        try {
            $userId = $_GET['id'] ?? null;
            
            if (!$userId) {
                return $this->sendError('User ID is required', 400);
            }
            
            $followers = $this->getUserFollowers($userId);
            
            return $this->sendResponse(['followers' => $followers], 'Followers retrieved successfully');
        } catch (Exception $e) {
            error_log("Get followers error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get users that user is following
     * GET /api/users/following?id=:id
     */
    public function getFollowing() {
        try {
            $userId = $_GET['id'] ?? null;
            
            if (!$userId) {
                return $this->sendError('User ID is required', 400);
            }
            
            $following = $this->getUserFollowing($userId);
            
            return $this->sendResponse(['following' => $following], 'Following retrieved successfully');
        } catch (Exception $e) {
            error_log("Get following error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Update password
     * PUT /api/users/password
     */
    public function updatePassword() {
        try {
            $authUser = AuthMiddleware::authenticate();
            $input = $this->getJsonInput();
            
            if (empty($input['current_password']) || empty($input['new_password'])) {
                return $this->sendError('Current password and new password are required', 400);
            }
            
            $result = $this->userModel->updatePassword(
                $authUser['user_id'], 
                $input['current_password'], 
                $input['new_password']
            );
            
            if ($result['success']) {
                return $this->sendResponse([], 'Password updated successfully');
            } else {
                return $this->sendError($result['error'], 400, $result['errors'] ?? []);
            }
        } catch (Exception $e) {
            error_log("Update password error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }

    /**
     * Get notification preferences
     * GET /api/users/notification-preferences
     */
    public function getNotificationPreferences() {
        try {
            $authUser = AuthMiddleware::authenticate();
            
            $preferences = $this->userModel->getNotificationPreferences($authUser['user_id']);
            
            return $this->sendResponse(['preferences' => $preferences], 'Notification preferences retrieved successfully');
        } catch (Exception $e) {
            error_log("Get notification preferences error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }

    /**
     * Update notification preferences
     * PUT /api/users/notification-preferences
     */
    public function updateNotificationPreferences() {
        try {
            $authUser = AuthMiddleware::authenticate();
            $input = $this->getJsonInput();
            
            if (empty($input['preferences'])) {
                return $this->sendError('Preferences are required', 400);
            }
            
            $result = $this->userModel->updateNotificationPreferences(
                $authUser['user_id'], 
                $input['preferences']
            );
            
            if ($result['success']) {
                return $this->sendResponse([], $result['message']);
            } else {
                return $this->sendError($result['error'], 400);
            }
        } catch (Exception $e) {
            error_log("Update notification preferences error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Helper methods
     */
    
    private function checkFollowStatus($followerId, $followingId) {
        try {
            // Check if follows table exists first
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SHOW TABLES LIKE 'follows'");
            if ($stmt->rowCount() == 0) {
                return false; // Table doesn't exist
            }
            
            $stmt = $db->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
            $stmt->execute([$followerId, $followingId]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Check follow status error: " . $e->getMessage());
            return false;
        }
    }
    
    private function createFollow($followerId, $followingId) {
        try {
            $stmt = $this->userModel->query(
                "INSERT INTO follows (follower_id, following_id, created_at) VALUES (?, ?, ?)",
                [$followerId, $followingId, date('Y-m-d H:i:s')]
            );
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("Create follow error: " . $e->getMessage());
            return false;
        }
    }
    
    private function removeFollow($followerId, $followingId) {
        try {
            $stmt = $this->userModel->query(
                "DELETE FROM follows WHERE follower_id = ? AND following_id = ?",
                [$followerId, $followingId]
            );
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("Remove follow error: " . $e->getMessage());
            return false;
        }
    }
    
    private function createNotification($userId, $type, $content, $relatedId = null) {
        try {
            $stmt = $this->userModel->query(
                "INSERT INTO notifications (user_id, type, content, related_id, created_at) VALUES (?, ?, ?, ?, ?)",
                [$userId, $type, $content, $relatedId, date('Y-m-d H:i:s')]
            );
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("Create notification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getUserPublishedArticles($userId) {
        try {
            // Check if articles table exists first
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SHOW TABLES LIKE 'articles'");
            if ($stmt->rowCount() == 0) {
                return []; // Table doesn't exist
            }
            
            $stmt = $db->prepare(
                "SELECT id, title, subtitle, featured_image_url, published_at, reading_time, view_count, clap_count, comment_count 
                 FROM articles 
                 WHERE author_id = ? AND status = 'published' 
                 ORDER BY published_at DESC"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get user articles error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getUserFollowers($userId) {
        try {
            $stmt = $this->userModel->query(
                "SELECT u.id, u.username, u.bio, u.profile_image_url, f.created_at as followed_at
                 FROM follows f
                 JOIN users u ON f.follower_id = u.id
                 WHERE f.following_id = ?
                 ORDER BY f.created_at DESC",
                [$userId]
            );
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get followers error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getUserFollowing($userId) {
        try {
            $stmt = $this->userModel->query(
                "SELECT u.id, u.username, u.bio, u.profile_image_url, f.created_at as followed_at
                 FROM follows f
                 JOIN users u ON f.following_id = u.id
                 WHERE f.follower_id = ?
                 ORDER BY f.created_at DESC",
                [$userId]
            );
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get following error: " . $e->getMessage());
            return [];
        }
    }
}