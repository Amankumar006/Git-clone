<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Article.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/ModerationAction.php';
require_once __DIR__ . '/../models/SystemSettings.php';
require_once __DIR__ . '/../models/FeaturedContent.php';
require_once __DIR__ . '/../models/HomepageSection.php';

class AdminController extends BaseController {
    private $userModel;
    private $articleModel;
    private $commentModel;
    private $moderationModel;
    private $settingsModel;
    private $featuredContentModel;
    private $homepageSectionModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->articleModel = new Article();
        $this->commentModel = new Comment();
        $this->moderationModel = new ModerationAction();
        $this->settingsModel = new SystemSettings();
        $this->featuredContentModel = new FeaturedContent();
        $this->homepageSectionModel = new HomepageSection();
    }

    public function getDashboardStats() {
        try {
            $this->requireAdmin();
            
            $stats = [
                'users' => $this->getUserStats(),
                'content' => $this->getContentStats(),
                'engagement' => $this->getEngagementStats(),
                'moderation' => $this->getModerationStats()
            ];
            
            $this->sendResponse($stats);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getUsers() {
        try {
            $this->requireAdmin();
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $users = $this->userModel->getUsers($search, $role, $status, $limit, $offset);
            $totalUsers = $this->userModel->getUserCount($search, $role, $status);
            
            $this->sendResponse([
                'users' => $users,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalUsers,
                    'total_pages' => ceil($totalUsers / $limit)
                ]
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function updateUserStatus() {
        try {
            $this->requireAdmin();
            
            $userId = $this->getPathParam('id');
            $input = $this->getJsonInput();
            $this->validateRequired($input, ['action']);
            
            $validActions = ['suspend', 'unsuspend', 'ban', 'unban', 'verify', 'promote', 'demote'];
            if (!in_array($input['action'], $validActions)) {
                throw new Exception('Invalid action');
            }
            
            $reason = $input['reason'] ?? 'Admin action';
            $duration = $input['duration'] ?? null;
            
            switch ($input['action']) {
                case 'suspend':
                    $this->suspendUser($userId, $reason, $duration);
                    break;
                case 'unsuspend':
                    $this->unsuspendUser($userId, $reason);
                    break;
                case 'ban':
                    $this->banUser($userId, $reason);
                    break;
                case 'unban':
                    $this->unbanUser($userId, $reason);
                    break;
                case 'verify':
                    $this->verifyUser($userId, $reason);
                    break;
                case 'promote':
                    $this->promoteUser($userId, $input['role'] ?? 'moderator', $reason);
                    break;
                case 'demote':
                    $this->demoteUser($userId, $reason);
                    break;
            }
            
            $this->sendResponse(['message' => 'User status updated successfully']);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getFeaturedContent() {
        try {
            $this->requireAdmin();
            
            $contentType = $_GET['type'] ?? null;
            $featuredContent = $this->featuredContentModel->getFeaturedContent($contentType);
            
            $this->sendResponse([
                'featured_content' => $featuredContent
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function updateFeaturedContent() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            $this->validateRequired($input, ['content_id', 'action']);
            
            $contentId = $input['content_id'];
            $contentType = $input['content_type'] ?? 'article';
            $action = $input['action']; // 'feature' or 'unfeature'
            
            if ($action === 'feature') {
                $position = $input['position'] ?? 0;
                $expiresAt = $input['expires_at'] ?? null;
                
                if ($contentType === 'article') {
                    $success = $this->featuredContentModel->featureArticle(
                        $contentId, 
                        $this->currentUser['id'], 
                        $position, 
                        $expiresAt
                    );
                } else {
                    throw new Exception('Content type not supported yet');
                }
                $message = ucfirst($contentType) . ' featured successfully';
            } else {
                if ($contentType === 'article') {
                    $success = $this->featuredContentModel->unfeatureArticle($contentId);
                } else {
                    throw new Exception('Content type not supported yet');
                }
                $message = ucfirst($contentType) . ' unfeatured successfully';
            }
            
            if ($success) {
                // Log the action
                $this->moderationModel->logAction(
                    $this->currentUser['id'],
                    $action,
                    $contentType,
                    $contentId,
                    ucfirst($contentType) . " {$action}d by admin"
                );
                
                $this->sendResponse(['message' => $message]);
            } else {
                throw new Exception('Failed to update featured status');
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getSystemSettings() {
        try {
            $this->requireAdmin();
            
            $category = $_GET['category'] ?? null;
            
            if ($category) {
                $settings = $this->settingsModel->getSettingsByCategory($category);
            } else {
                $settings = $this->settingsModel->getAllSettings();
            }
            
            $this->sendResponse([
                'settings' => $settings
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function updateSystemSettings() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            
            $allowedSettings = [
                'site_name',
                'site_description',
                'registration_enabled',
                'content_approval_required',
                'max_articles_per_day',
                'max_comments_per_day',
                'featured_articles_limit',
                'email_notifications_enabled',
                'maintenance_mode',
                'max_upload_size',
                'allowed_file_types',
                'spam_detection_enabled',
                'comment_moderation_enabled',
                'user_verification_required',
                'social_login_enabled'
            ];
            
            $updatedCount = 0;
            foreach ($input as $key => $value) {
                if (in_array($key, $allowedSettings)) {
                    if ($this->settingsModel->updateSetting($key, $value)) {
                        $updatedCount++;
                    }
                }
            }
            
            if ($updatedCount > 0) {
                // Log the settings update
                $this->moderationModel->logAction(
                    $this->currentUser['id'],
                    'update_settings',
                    'system',
                    null,
                    "Updated {$updatedCount} system settings"
                );
            }
            
            $this->sendResponse([
                'message' => 'Settings updated successfully',
                'updated_count' => $updatedCount
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getContentManagement() {
        try {
            $this->requireAdmin();
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = min((int)($_GET['limit'] ?? 20), 100);
            $offset = ($page - 1) * $limit;
            $status = $_GET['status'] ?? '';
            $type = $_GET['type'] ?? '';
            $search = $_GET['search'] ?? '';
            
            $content = [];
            
            if ($type === 'articles' || !$type) {
                $articles = $this->getArticlesForManagement($status, $search, $limit, $offset);
                $content['articles'] = $articles;
            }
            
            if ($type === 'comments' || !$type) {
                $comments = $this->getCommentsForManagement($status, $search, $limit, $offset);
                $content['comments'] = $comments;
            }
            
            if ($type === 'featured' || !$type) {
                $featured = $this->featuredContentModel->getFeaturedContent();
                $content['featured'] = $featured;
            }
            
            $this->sendResponse($content);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function getHomepageManagement() {
        try {
            $this->requireAdmin();
            
            $sections = $this->homepageSectionModel->getAllSections();
            
            $this->sendResponse([
                'sections' => $sections
            ]);
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function updateHomepageSection() {
        try {
            $this->requireAdmin();
            
            $sectionId = $this->getPathParam('section_id');
            $input = $this->getJsonInput();
            
            $success = $this->homepageSectionModel->updateSection($sectionId, $input);
            
            if ($success) {
                $this->moderationModel->logAction(
                    $this->currentUser['id'],
                    'update_homepage_section',
                    'system',
                    $sectionId,
                    "Updated homepage section"
                );
                
                $this->sendResponse(['message' => 'Homepage section updated successfully']);
            } else {
                throw new Exception('Failed to update homepage section');
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function reorderHomepageSections() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            $this->validateRequired($input, ['section_ids']);
            
            $success = $this->homepageSectionModel->reorderSections($input['section_ids']);
            
            if ($success) {
                $this->moderationModel->logAction(
                    $this->currentUser['id'],
                    'reorder_homepage_sections',
                    'system',
                    null,
                    "Reordered homepage sections"
                );
                
                $this->sendResponse(['message' => 'Homepage sections reordered successfully']);
            } else {
                throw new Exception('Failed to reorder homepage sections');
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    public function updateContentStatus() {
        try {
            $this->requireAdmin();
            
            $input = $this->getJsonInput();
            $this->validateRequired($input, ['content_type', 'content_id', 'action']);
            
            $contentType = $input['content_type']; // 'article' or 'comment'
            $contentId = $input['content_id'];
            $action = $input['action']; // 'approve', 'reject', 'delete', 'feature'
            $reason = $input['reason'] ?? 'Admin action';
            
            $success = false;
            $message = '';
            
            switch ($contentType) {
                case 'article':
                    $success = $this->handleArticleAction($contentId, $action, $reason);
                    $message = "Article {$action}d successfully";
                    break;
                case 'comment':
                    $success = $this->handleCommentAction($contentId, $action, $reason);
                    $message = "Comment {$action}d successfully";
                    break;
                default:
                    throw new Exception('Invalid content type');
            }
            
            if ($success) {
                $this->moderationModel->logAction(
                    $this->currentUser['id'],
                    $action,
                    $contentType,
                    $contentId,
                    $reason
                );
                
                $this->sendResponse(['message' => $message]);
            } else {
                throw new Exception("Failed to {$action} {$contentType}");
            }
            
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 400);
        }
    }

    private function getUserStats() {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_users_30d,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as new_users_7d,
                    SUM(CASE WHEN is_suspended = TRUE THEN 1 ELSE 0 END) as suspended_users,
                    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_users,
                    SUM(CASE WHEN role = 'moderator' THEN 1 ELSE 0 END) as moderator_users
                FROM users";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function getContentStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM articles) as total_articles,
                    (SELECT COUNT(*) FROM articles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_articles_30d,
                    (SELECT COUNT(*) FROM articles WHERE status = 'published') as published_articles,
                    (SELECT COUNT(*) FROM articles WHERE moderation_status = 'flagged') as flagged_articles,
                    (SELECT COUNT(*) FROM comments) as total_comments,
                    (SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_comments_30d,
                    (SELECT COUNT(*) FROM comments WHERE moderation_status = 'flagged') as flagged_comments";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function getEngagementStats() {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM claps WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as claps_30d,
                    (SELECT COUNT(*) FROM comments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as comments_30d,
                    (SELECT COUNT(*) FROM bookmarks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as bookmarks_30d,
                    (SELECT COUNT(*) FROM follows WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as follows_30d";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    private function getModerationStats() {
        return $this->moderationModel->getModerationStats(30);
    }

    private function suspendUser($userId, $reason, $duration = null) {
        $expiresAt = $duration ? date('Y-m-d H:i:s', strtotime("+{$duration} days")) : null;
        
        $sql = "UPDATE users SET is_suspended = TRUE, suspension_expires_at = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expiresAt, $userId]);
        
        $this->moderationModel->logAction($this->currentUser['id'], 'suspend', 'user', $userId, $reason);
    }

    private function unsuspendUser($userId, $reason) {
        $sql = "UPDATE users SET is_suspended = FALSE, suspension_expires_at = NULL WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $this->moderationModel->logAction($this->currentUser['id'], 'unsuspend', 'user', $userId, $reason);
    }

    private function banUser($userId, $reason) {
        $this->suspendUser($userId, $reason, null); // Permanent suspension
    }

    private function unbanUser($userId, $reason) {
        $this->unsuspendUser($userId, $reason);
    }

    private function verifyUser($userId, $reason) {
        $sql = "UPDATE users SET email_verified = TRUE WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $this->moderationModel->logAction($this->currentUser['id'], 'verify', 'user', $userId, $reason);
    }

    private function promoteUser($userId, $role, $reason) {
        $validRoles = ['moderator', 'admin'];
        if (!in_array($role, $validRoles)) {
            throw new Exception('Invalid role');
        }
        
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role, $userId]);
        
        $this->moderationModel->logAction($this->currentUser['id'], 'promote', 'user', $userId, $reason);
    }

    private function demoteUser($userId, $reason) {
        $sql = "UPDATE users SET role = 'user' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $this->moderationModel->logAction($this->currentUser['id'], 'demote', 'user', $userId, $reason);
    }

    private function getArticlesForManagement($status, $search, $limit, $offset) {
        $sql = "SELECT a.*, u.username, u.email,
                       (SELECT COUNT(*) FROM claps WHERE article_id = a.id) as clap_count,
                       (SELECT COUNT(*) FROM comments WHERE article_id = a.id) as comment_count,
                       (SELECT COUNT(*) FROM bookmarks WHERE article_id = a.id) as bookmark_count
                FROM articles a
                JOIN users u ON a.author_id = u.id
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        
        if ($search) {
            $sql .= " AND (a.title LIKE ? OR u.username LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function getCommentsForManagement($status, $search, $limit, $offset) {
        $sql = "SELECT c.*, u.username, u.email, a.title as article_title
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN articles a ON c.article_id = a.id
                WHERE 1=1";
        
        $params = [];
        
        if ($status) {
            $sql .= " AND c.moderation_status = ?";
            $params[] = $status;
        }
        
        if ($search) {
            $sql .= " AND (c.content LIKE ? OR u.username LIKE ? OR a.title LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $sql .= " ORDER BY c.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function handleArticleAction($articleId, $action, $reason) {
        switch ($action) {
            case 'approve':
                $sql = "UPDATE articles SET status = 'published', moderation_status = 'approved' WHERE id = ?";
                break;
            case 'reject':
                $sql = "UPDATE articles SET status = 'draft', moderation_status = 'rejected' WHERE id = ?";
                break;
            case 'delete':
                $sql = "UPDATE articles SET status = 'archived', moderation_status = 'removed' WHERE id = ?";
                break;
            case 'feature':
                return $this->featuredContentModel->featureArticle($articleId, $this->currentUser['id']);
            default:
                return false;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$articleId]);
    }

    private function handleCommentAction($commentId, $action, $reason) {
        switch ($action) {
            case 'approve':
                $sql = "UPDATE comments SET moderation_status = 'approved' WHERE id = ?";
                break;
            case 'reject':
                $sql = "UPDATE comments SET moderation_status = 'rejected' WHERE id = ?";
                break;
            case 'delete':
                $sql = "UPDATE comments SET moderation_status = 'removed' WHERE id = ?";
                break;
            default:
                return false;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$commentId]);
    }

    private function requireAdmin() {
        $this->requireAuth();
        
        if (!in_array($this->currentUser['role'], ['admin', 'moderator'])) {
            throw new Exception('Admin access required');
        }
    }

    private function getPathParam($param) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        
        $key = array_search($param, $segments);
        if ($key !== false && isset($segments[$key + 1])) {
            return $segments[$key + 1];
        }
        
        return $_GET[$param] ?? null;
    }
}