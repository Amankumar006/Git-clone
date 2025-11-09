<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Publication.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../utils/Validator.php';

class PublicationController extends BaseController {
    private $publicationModel;
    private $userModel;
    private $notificationModel;
    private $currentUser;
    
    public function __construct() {
        parent::__construct();
        $this->publicationModel = new Publication();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
    }

    /**
     * Require authentication and set current user
     */
    private function requireAuth() {
        require_once __DIR__ . '/../middleware/AuthMiddleware.php';
        $this->currentUser = AuthMiddleware::authenticate();
    }
    
    /**
     * Create a new publication
     * POST /api/publications/create
     */
    public function create() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            
            // Validate input
            $validator = new Validator();
            $validator->required('name', $data['name'] ?? '');
            
            if (!$validator->isValid()) {
                return $this->error('Validation failed', 400, $validator->getErrors());
            }
            
            // Prepare publication data
            $publicationData = [
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'logo_url' => $data['logo_url'] ?? null,
                'website_url' => $data['website_url'] ?? null,
                'social_links' => $data['social_links'] ?? null,
                'theme_color' => $data['theme_color'] ?? '#3B82F6',
                'custom_css' => $data['custom_css'] ?? null,
                'owner_id' => $this->currentUser['id']
            ];
            
            $publicationId = $this->publicationModel->create($publicationData);
            
            if ($publicationId) {
                $publication = $this->publicationModel->getById($publicationId);
                return $this->success($publication, 'Publication created successfully');
            } else {
                return $this->error('Failed to create publication', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get publication by ID
     * GET /api/publications/show?id=:id
     */
    public function show() {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                return $this->error('Publication ID is required', 400);
            }
            
            $publication = $this->publicationModel->getById($id);
            
            if (!$publication) {
                return $this->error('Publication not found', 404);
            }
            
            // Get additional data
            $publication['stats'] = $this->publicationModel->getStats($id);
            $publication['members'] = $this->publicationModel->getMembers($id);
            
            // Check if current user has access
            if ($this->currentUser) {
                $publication['user_role'] = $this->getUserRole($id, $this->currentUser['id']);
                $publication['can_manage'] = $this->canManage($id, $this->currentUser['id']);
                $publication['is_following'] = $this->publicationModel->isFollowing($id, $this->currentUser['id']);
                
                // Add submission stats and recent activity for members/admins
                if ($publication['user_role']) {
                    $publication['submission_stats'] = $this->publicationModel->getSubmissionStats($id);
                    $publication['recent_activity'] = $this->publicationModel->getRecentActivity($id);
                }
            }
            
            // Add followers count for all users
            $publication['followers_count'] = $this->publicationModel->getFollowersCount($id);
            
            return $this->success($publication);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update publication
     * PUT /api/publications/update
     */
    public function update() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $id = $data['id'] ?? null;
            
            if (!$id) {
                return $this->error('Publication ID is required', 400);
            }
            
            // Check permissions
            if (!$this->canManage($id, $this->currentUser['id'])) {
                return $this->error('Insufficient permissions', 403);
            }
            
            // Validate input
            $validator = new Validator();
            $validator->required('name', $data['name'] ?? '');
            
            if (!$validator->isValid()) {
                return $this->error('Validation failed', 400, $validator->getErrors());
            }
            
            $updateData = [
                'name' => trim($data['name']),
                'description' => trim($data['description'] ?? ''),
                'logo_url' => $data['logo_url'] ?? null,
                'website_url' => $data['website_url'] ?? null,
                'social_links' => $data['social_links'] ?? null,
                'theme_color' => $data['theme_color'] ?? '#3B82F6',
                'custom_css' => $data['custom_css'] ?? null
            ];
            
            $success = $this->publicationModel->update($id, $updateData);
            
            if ($success) {
                $publication = $this->publicationModel->getById($id);
                return $this->success($publication, 'Publication updated successfully');
            } else {
                return $this->error('Failed to update publication', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete publication
     * DELETE /api/publications/delete
     */
    public function delete() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $id = $data['id'] ?? null;
            
            if (!$id) {
                return $this->error('Publication ID is required', 400);
            }
            
            // Only owner can delete publication
            $publication = $this->publicationModel->getById($id);
            if (!$publication || $publication['owner_id'] != $this->currentUser['id']) {
                return $this->error('Insufficient permissions', 403);
            }
            
            $success = $this->publicationModel->delete($id);
            
            if ($success) {
                return $this->success(null, 'Publication deleted successfully');
            } else {
                return $this->error('Failed to delete publication', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get user's publications (owned and member)
     * GET /api/publications/my
     */
    public function getUserPublications() {
        try {
            $this->requireAuth();
            
            $owned = $this->publicationModel->getByOwner($this->currentUser['id']);
            $member = $this->publicationModel->getByMember($this->currentUser['id']);
            
            return $this->success([
                'owned' => $owned,
                'member' => $member
            ]);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get publication invitations for current user
     * GET /api/publications/invitations
     */
    public function getInvitations() {
        try {
            $this->requireAuth();
            
            // Get publication invite notifications
            $notifications = $this->notificationModel->getUserNotifications(
                $this->currentUser['id'], 
                true, // unread only
                50, 
                0
            );
            
            $invitations = [];
            foreach ($notifications as $notification) {
                if ($notification['type'] === 'publication_invite') {
                    $publication = $this->publicationModel->getById($notification['related_id']);
                    if ($publication) {
                        $invitations[] = [
                            'notification_id' => $notification['id'],
                            'publication' => $publication,
                            'content' => $notification['content'],
                            'created_at' => $notification['created_at']
                        ];
                    }
                }
            }
            
            return $this->success($invitations);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Invite member to publication
     * POST /api/publications/invite
     */
    public function inviteMember() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            
            $publicationId = $data['publication_id'] ?? null;
            $email = $data['email'] ?? null;
            $role = $data['role'] ?? 'writer';
            
            if (!$publicationId || !$email) {
                return $this->error('Publication ID and email are required', 400);
            }
            
            // Validate role
            if (!in_array($role, ['writer', 'editor', 'admin'])) {
                return $this->error('Invalid role. Must be writer, editor, or admin', 400);
            }
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->error('Invalid email format', 400);
            }
            
            // Check permissions (admin or owner)
            if (!$this->publicationModel->hasPermission($publicationId, $this->currentUser['id'], 'admin')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            // Find user by email
            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                return $this->error('User not found', 404);
            }
            
            // Check if user is already a member
            $members = $this->publicationModel->getMembers($publicationId);
            foreach ($members as $member) {
                if ($member['id'] == $user['id']) {
                    return $this->error('User is already a member of this publication', 400);
                }
            }
            
            // Check if user is the owner
            $publication = $this->publicationModel->getById($publicationId);
            if ($publication['owner_id'] == $user['id']) {
                return $this->error('Cannot invite publication owner as member', 400);
            }
            
            // Add member
            $success = $this->publicationModel->addMember($publicationId, $user['id'], $role);
            
            if ($success) {
                // Get publication details for notification
                $publication = $this->publicationModel->getById($publicationId);
                
                // Send notification to invited user
                $this->notificationModel->createPublicationInviteNotification(
                    $this->currentUser['id'],
                    $user['id'],
                    $publicationId,
                    $this->currentUser['username'],
                    $publication['name'],
                    $role
                );
                
                // Send email invitation
                require_once __DIR__ . '/../utils/EmailService.php';
                $emailService = new EmailService();
                $emailService->sendPublicationInvitation(
                    $user,
                    $publication,
                    $this->currentUser['username'],
                    $role
                );
                
                return $this->success([
                    'member' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'role' => $role,
                        'joined_at' => date('Y-m-d H:i:s')
                    ]
                ], 'Member invited successfully');
            } else {
                return $this->error('Failed to invite member', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Invite multiple members to publication
     * POST /api/publications/invite-bulk
     */
    public function inviteBulkMembers() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            
            $publicationId = $data['publication_id'] ?? null;
            $invitations = $data['invitations'] ?? []; // Array of {email, role}
            
            if (!$publicationId || empty($invitations)) {
                return $this->error('Publication ID and invitations array are required', 400);
            }
            
            // Check permissions (admin or owner)
            if (!$this->publicationModel->hasPermission($publicationId, $this->currentUser['id'], 'admin')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            $results = [];
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($invitations as $invitation) {
                $email = $invitation['email'] ?? null;
                $role = $invitation['role'] ?? 'writer';
                
                if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $results[] = [
                        'email' => $email,
                        'success' => false,
                        'error' => 'Invalid email format'
                    ];
                    $errorCount++;
                    continue;
                }
                
                // Validate role
                if (!in_array($role, ['writer', 'editor', 'admin'])) {
                    $results[] = [
                        'email' => $email,
                        'success' => false,
                        'error' => 'Invalid role'
                    ];
                    $errorCount++;
                    continue;
                }
                
                // Find user by email
                $user = $this->userModel->findByEmail($email);
                if (!$user) {
                    $results[] = [
                        'email' => $email,
                        'success' => false,
                        'error' => 'User not found'
                    ];
                    $errorCount++;
                    continue;
                }
                
                // Check if user is already a member
                $members = $this->publicationModel->getMembers($publicationId);
                $isAlreadyMember = false;
                foreach ($members as $member) {
                    if ($member['id'] == $user['id']) {
                        $isAlreadyMember = true;
                        break;
                    }
                }
                
                if ($isAlreadyMember) {
                    $results[] = [
                        'email' => $email,
                        'success' => false,
                        'error' => 'User is already a member'
                    ];
                    $errorCount++;
                    continue;
                }
                
                // Check if user is the owner
                $publication = $this->publicationModel->getById($publicationId);
                if ($publication['owner_id'] == $user['id']) {
                    $results[] = [
                        'email' => $email,
                        'success' => false,
                        'error' => 'Cannot invite publication owner as member'
                    ];
                    $errorCount++;
                    continue;
                }
                
                // Add member
                $success = $this->publicationModel->addMember($publicationId, $user['id'], $role);
                
                if ($success) {
                    // Send notification
                    $this->notificationModel->createPublicationInviteNotification(
                        $this->currentUser['id'],
                        $user['id'],
                        $publicationId,
                        $this->currentUser['username'],
                        $publication['name'],
                        $role
                    );
                    
                    // Send email invitation
                    require_once __DIR__ . '/../utils/EmailService.php';
                    $emailService = new EmailService();
                    $emailService->sendPublicationInvitation(
                        $user,
                        $publication,
                        $this->currentUser['username'],
                        $role
                    );
                    
                    $results[] = [
                        'email' => $email,
                        'success' => true,
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'role' => $role
                        ]
                    ];
                    $successCount++;
                } else {
                    $results[] = [
                        'email' => $email,
                        'success' => false,
                        'error' => 'Failed to add member'
                    ];
                    $errorCount++;
                }
            }
            
            return $this->success([
                'results' => $results,
                'summary' => [
                    'total' => count($invitations),
                    'successful' => $successCount,
                    'failed' => $errorCount
                ]
            ], "Bulk invitation completed: {$successCount} successful, {$errorCount} failed");
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Remove member from publication
     * POST /api/publications/remove-member
     */
    public function removeMember() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            
            $publicationId = $data['publication_id'] ?? null;
            $userId = $data['user_id'] ?? null;
            
            if (!$publicationId || !$userId) {
                return $this->error('Publication ID and user ID are required', 400);
            }
            
            // Check permissions (admin or owner)
            if (!$this->publicationModel->hasPermission($publicationId, $this->currentUser['id'], 'admin')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            $success = $this->publicationModel->removeMember($publicationId, $userId);
            
            if ($success) {
                return $this->success(null, 'Member removed successfully');
            } else {
                return $this->error('Failed to remove member', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update member role
     * POST /api/publications/update-role
     */
    public function updateMemberRole() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            
            $publicationId = $data['publication_id'] ?? null;
            $userId = $data['user_id'] ?? null;
            $role = $data['role'] ?? null;
            
            if (!$publicationId || !$userId || !$role) {
                return $this->error('Publication ID, user ID, and role are required', 400);
            }
            
            // Validate role
            if (!in_array($role, ['writer', 'editor', 'admin'])) {
                return $this->error('Invalid role', 400);
            }
            
            // Check permissions (admin or owner)
            if (!$this->publicationModel->hasPermission($publicationId, $this->currentUser['id'], 'admin')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            $success = $this->publicationModel->updateMemberRole($publicationId, $userId, $role);
            
            if ($success) {
                return $this->success(null, 'Member role updated successfully');
            } else {
                return $this->error('Failed to update member role', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get publication articles
     * GET /api/publications/articles?id=:id&status=:status
     */
    public function getArticles() {
        try {
            $id = $_GET['id'] ?? null;
            $status = $_GET['status'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            if (!$id) {
                return $this->error('Publication ID is required', 400);
            }
            
            $articles = $this->publicationModel->getArticles($id, $status, $limit, $offset);
            
            return $this->success($articles);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Accept publication invitation
     * POST /api/publications/accept-invitation
     */
    public function acceptInvitation() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$publicationId) {
                return $this->error('Publication ID is required', 400);
            }
            
            // Check if user is already a member
            $members = $this->publicationModel->getMembers($publicationId);
            $isMember = false;
            foreach ($members as $member) {
                if ($member['id'] == $this->currentUser['id']) {
                    $isMember = true;
                    break;
                }
            }
            
            if ($isMember) {
                return $this->error('You are already a member of this publication', 400);
            }
            
            // Add user as writer by default (invitation should specify role)
            $role = $data['role'] ?? 'writer';
            $success = $this->publicationModel->addMember($publicationId, $this->currentUser['id'], $role);
            
            if ($success) {
                $publication = $this->publicationModel->getById($publicationId);
                return $this->success([
                    'publication' => $publication,
                    'role' => $role
                ], 'Invitation accepted successfully');
            } else {
                return $this->error('Failed to accept invitation', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Decline publication invitation
     * POST /api/publications/decline-invitation
     */
    public function declineInvitation() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$publicationId) {
                return $this->error('Publication ID is required', 400);
            }
            
            // Simply return success - no need to store declined invitations
            return $this->success(null, 'Invitation declined');
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Search publications
     * GET /api/publications/search?q=:query
     */
    public function search() {
        try {
            $query = $_GET['q'] ?? '';
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            if (empty($query)) {
                return $this->error('Search query is required', 400);
            }
            
            $publications = $this->publicationModel->search($query, $limit, $offset);
            
            return $this->success($publications);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Follow a publication
     * POST /api/publications/follow
     */
    public function follow() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$publicationId) {
                return $this->error('Publication ID is required', 400);
            }
            
            // Check if publication exists
            $publication = $this->publicationModel->getById($publicationId);
            if (!$publication) {
                return $this->error('Publication not found', 404);
            }
            
            // Check if already following
            if ($this->publicationModel->isFollowing($publicationId, $this->currentUser['id'])) {
                return $this->error('Already following this publication', 400);
            }
            
            $success = $this->publicationModel->followPublication($publicationId, $this->currentUser['id']);
            
            if ($success) {
                $followersCount = $this->publicationModel->getFollowersCount($publicationId);
                return $this->success([
                    'is_following' => true,
                    'followers_count' => $followersCount
                ], 'Successfully followed publication');
            } else {
                return $this->error('Failed to follow publication', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Unfollow a publication
     * POST /api/publications/unfollow
     */
    public function unfollow() {
        try {
            $this->requireAuth();
            
            $data = $this->getJsonInput();
            $publicationId = $data['publication_id'] ?? null;
            
            if (!$publicationId) {
                return $this->error('Publication ID is required', 400);
            }
            
            // Check if publication exists
            $publication = $this->publicationModel->getById($publicationId);
            if (!$publication) {
                return $this->error('Publication not found', 404);
            }
            
            // Check if currently following
            if (!$this->publicationModel->isFollowing($publicationId, $this->currentUser['id'])) {
                return $this->error('Not following this publication', 400);
            }
            
            $success = $this->publicationModel->unfollowPublication($publicationId, $this->currentUser['id']);
            
            if ($success) {
                $followersCount = $this->publicationModel->getFollowersCount($publicationId);
                return $this->success([
                    'is_following' => false,
                    'followers_count' => $followersCount
                ], 'Successfully unfollowed publication');
            } else {
                return $this->error('Failed to unfollow publication', 500);
            }
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get publication workflow status and statistics
     * GET /api/publications/workflow-status?id=:id
     */
    public function getWorkflowStatus() {
        try {
            $this->requireAuth();
            
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                return $this->error('Publication ID is required', 400);
            }
            
            // Check if user has access to this publication
            if (!$this->publicationModel->hasPermission($id, $this->currentUser['id'], 'writer')) {
                return $this->error('Insufficient permissions', 403);
            }
            
            // Get publication details
            $publication = $this->publicationModel->getById($id);
            if (!$publication) {
                return $this->error('Publication not found', 404);
            }
            
            // Get workflow statistics
            $submissionStats = $this->publicationModel->getSubmissionStats($id);
            $recentActivity = $this->publicationModel->getRecentActivity($id, 20);
            
            // Get pending articles if user has editor+ permissions
            $pendingArticles = [];
            if ($this->publicationModel->hasPermission($id, $this->currentUser['id'], 'editor')) {
                require_once __DIR__ . '/../models/Article.php';
                $articleModel = new Article();
                $pendingArticles = $articleModel->getPendingApproval($id);
            }
            
            // Get user's submitted articles
            $userSubmissions = [];
            require_once __DIR__ . '/../models/Article.php';
            $articleModel = new Article();
            $userSubmissions = $articleModel->getByPublication($id, 'draft', 10, 0);
            
            // Filter to only user's articles
            $userSubmissions = array_filter($userSubmissions, function($article) {
                return $article['author_id'] == $this->currentUser['id'];
            });
            
            return $this->success([
                'publication' => $publication,
                'submission_stats' => $submissionStats,
                'recent_activity' => $recentActivity,
                'pending_articles' => $pendingArticles,
                'user_submissions' => array_values($userSubmissions),
                'user_role' => $this->getUserRole($id, $this->currentUser['id']),
                'can_approve' => $this->publicationModel->hasPermission($id, $this->currentUser['id'], 'editor')
            ]);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get filtered articles for publication
     * GET /api/publications/filtered-articles?id=:id&search=:search&sort=:sort&author=:author&tag=:tag&date_from=:date_from&date_to=:date_to
     */
    public function getFilteredArticles() {
        try {
            $id = $_GET['id'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            if (!$id) {
                return $this->error('Publication ID is required', 400);
            }
            
            // Build filters array
            $filters = [];
            if (!empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            if (!empty($_GET['sort'])) {
                $filters['sort'] = $_GET['sort'];
            }
            if (!empty($_GET['author'])) {
                $filters['author_id'] = $_GET['author'];
            }
            if (!empty($_GET['tag'])) {
                $filters['tag'] = $_GET['tag'];
            }
            if (!empty($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (!empty($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }
            
            $articles = $this->publicationModel->getFilteredArticles($id, $filters, $limit, $offset);
            
            return $this->success($articles);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get user's followed publications
     * GET /api/publications/followed
     */
    public function getFollowedPublications() {
        try {
            $this->requireAuth();
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $publications = $this->publicationModel->getFollowedByUser($this->currentUser['id'], $limit, $offset);
            
            return $this->success($publications);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get articles from followed publications
     * GET /api/publications/followed-articles
     */
    public function getFollowedPublicationsArticles() {
        try {
            $this->requireAuth();
            
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $articles = $this->publicationModel->getFollowedPublicationsArticles($this->currentUser['id'], $limit, $offset);
            
            return $this->success($articles);
            
        } catch (Exception $e) {
            return $this->error('Server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Helper method to check if user can manage publication
     */
    private function canManage($publicationId, $userId) {
        return $this->publicationModel->hasPermission($publicationId, $userId, 'admin');
    }
    
    /**
     * Helper method to get user role in publication
     */
    private function getUserRole($publicationId, $userId) {
        // Check if owner
        $publication = $this->publicationModel->getById($publicationId);
        if ($publication && $publication['owner_id'] == $userId) {
            return 'owner';
        }
        
        // Check member role
        $members = $this->publicationModel->getMembers($publicationId);
        foreach ($members as $member) {
            if ($member['id'] == $userId) {
                return $member['role'];
            }
        }
        
        return null;
    }
}