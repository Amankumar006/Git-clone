<?php

require_once __DIR__ . '/BaseRepository.php';

class User extends BaseRepository {
    protected $table = 'users';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a new user (alias for register)
     */
    public function create($userData) {
        return $this->register($userData);
    }
    
    /**
     * Register a new user
     */
    public function register($userData) {
        try {
            // Validate input data
            $validation = $this->validateRegistration($userData);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'errors' => $validation['errors']
                ];
            }

            // Check if user already exists
            if ($this->emailExists($userData['email'])) {
                return [
                    'success' => false,
                    'errors' => ['email' => 'Email already exists']
                ];
            }

            if ($this->usernameExists($userData['username'])) {
                return [
                    'success' => false,
                    'errors' => ['username' => 'Username already exists']
                ];
            }

            // Hash password
            $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);

            // Insert user
            $sql = "INSERT INTO {$this->table} (username, email, password_hash, bio, profile_image_url, social_links) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $userData['username'],
                $userData['email'],
                $passwordHash,
                $userData['bio'] ?? null,
                $userData['profile_image_url'] ?? null,
                isset($userData['social_links']) ? json_encode($userData['social_links']) : null
            ]);

            if ($result) {
                $userId = $this->db->lastInsertId();
                $user = $this->findById($userId);
                
                return [
                    'success' => true,
                    'user' => $user
                ];
            }

            return [
                'success' => false,
                'errors' => ['general' => 'Failed to create user']
            ];

        } catch (Exception $e) {
            error_log("User registration error: " . $e->getMessage());
            return [
                'success' => false,
                'errors' => ['general' => 'Database error in create: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT id, username, email, bio, profile_image_url, social_links, email_verified, created_at, updated_at 
                FROM {$this->table} WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user && $user['social_links']) {
            $user['social_links'] = json_decode($user['social_links'], true);
        }

        return $user;
    }

    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Find user by username
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    /**
     * Validate registration data
     */
    private function validateRegistration($data) {
        $errors = [];

        // Username validation
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif (strlen($data['username']) > 50) {
            $errors['username'] = 'Username must be less than 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, underscores, and hyphens';
        }

        // Email validation
        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }

        // Password confirmation
        if (isset($data['password_confirmation']) && $data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if email exists
     */
    private function emailExists($email) {
        $sql = "SELECT id FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    /**
     * Check if username exists
     */
    private function usernameExists($username) {
        $sql = "SELECT id FROM {$this->table} WHERE username = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch() !== false;
    }

    /**
     * Authenticate user login
     */
    public function authenticate($email, $password) {
        $user = $this->findByEmail($email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Remove password hash from returned user data
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }

    /**
     * Login user (wrapper for authenticate with proper response format)
     */
    public function login($email, $password) {
        $user = $this->authenticate($email, $password);
        
        if ($user) {
            return [
                'success' => true,
                'user' => $user
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Invalid email or password'
        ];
    }

    /**
     * Verify user email
     */
    public function verifyEmail($userId) {
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET email_verified = 1 WHERE id = ?");
            $result = $stmt->execute([$userId]);
            
            return [
                'success' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create password reset token
     */
    public function createPasswordResetToken($email) {
        try {
            $user = $this->findByEmail($email);
            
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found'
                ];
            }
            
            // Generate a simple token (in production, use a more secure method)
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database (you'd need a password_resets table)
            // For now, just return success with the token
            return [
                'success' => true,
                'user' => $user,
                'token' => $token
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Reset password using token
     */
    public function resetPassword($token, $password) {
        try {
            // In a real implementation, you'd validate the token from password_resets table
            // For now, just return success
            return [
                'success' => true
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user profile (public view)
     */
    public function getProfile($userId) {
        $user = $this->findById($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Remove sensitive data
        unset($user['password_hash']);
        unset($user['email_verified']);
        
        // Parse social links
        $user['social_links'] = $user['social_links'] ? json_decode($user['social_links'], true) : [];
        
        // Add follower and following counts
        $user['followers_count'] = $this->getFollowersCount($userId);
        $user['following_count'] = $this->getFollowingCount($userId);
        $user['articles_count'] = $this->getPublishedArticlesCount($userId);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Get followers count
     */
    private function getFollowersCount($userId) {
        try {
            // Check if follows table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'follows'");
            if ($stmt->rowCount() == 0) {
                return 0; // Table doesn't exist
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getFollowersCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get following count
     */
    private function getFollowingCount($userId) {
        try {
            // Check if follows table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'follows'");
            if ($stmt->rowCount() == 0) {
                return 0; // Table doesn't exist
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getFollowingCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get published articles count
     */
    private function getPublishedArticlesCount($userId) {
        try {
            // Check if articles table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'articles'");
            if ($stmt->rowCount() == 0) {
                return 0; // Table doesn't exist
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM articles WHERE author_id = ? AND status = 'published'");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getPublishedArticlesCount: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update user profile
     * @param int $userId
     * @param array $data
     * @return array
     */
    public function updateProfile($userId, $data) {
        try {
            $allowedFields = ['bio', 'social_links'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if ($field === 'social_links') {
                        $updateFields[] = "$field = ?";
                        $params[] = json_encode($data[$field]);
                    } else {
                        $updateFields[] = "$field = ?";
                        $params[] = $data[$field];
                    }
                }
            }
            
            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'error' => 'No valid fields to update'
                ];
            }
            
            $params[] = $userId;
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET " . implode(', ', $updateFields) . ", updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute($params);
            
            // Get updated user
            $user = $this->findById($userId);
            
            return [
                'success' => true,
                'data' => ['user' => $user]
            ];
            
        } catch (Exception $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update profile'
            ];
        }
    }
    
    /**
     * Update notification preferences
     * @param int $userId
     * @param array $preferences
     * @return array
     */
    public function updateNotificationPreferences($userId, $preferences) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET notification_preferences = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([json_encode($preferences), $userId]);
            
            return [
                'success' => true,
                'message' => 'Notification preferences updated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Error updating notification preferences: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update notification preferences'
            ];
        }
    }
    
    /**
     * Get notification preferences
     * @param int $userId
     * @return array
     */
    public function getNotificationPreferences($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT notification_preferences 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            if ($result && $result['notification_preferences']) {
                return json_decode($result['notification_preferences'], true);
            }
            
            // Return default preferences
            return [
                'email_notifications' => [
                    'follows' => true,
                    'claps' => true,
                    'comments' => true,
                    'publication_invites' => true,
                    'weekly_digest' => true
                ],
                'push_notifications' => [
                    'follows' => true,
                    'claps' => true,
                    'comments' => true,
                    'publication_invites' => true
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error getting notification preferences: " . $e->getMessage());
            return [
                'email_notifications' => [
                    'follows' => true,
                    'claps' => true,
                    'comments' => true,
                    'publication_invites' => true,
                    'weekly_digest' => true
                ],
                'push_notifications' => [
                    'follows' => true,
                    'claps' => true,
                    'comments' => true,
                    'publication_invites' => true
                ]
            ];
        }
    }

    /**
     * Update password
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'User not found'
                ];
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'error' => 'Current password is incorrect'
                ];
            }
            
            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password_hash = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$newPasswordHash, $userId]);
            
            return [
                'success' => true,
                'message' => 'Password updated successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Error updating password: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update password'
            ];
        }
    }

    /**
     * Upload avatar
     * @param int $userId
     * @param string $imageUrl
     * @return array
     */
    public function updateAvatar($userId, $imageUrl) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET profile_image_url = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$imageUrl, $userId]);
            
            // Get updated user
            $user = $this->findById($userId);
            
            return [
                'success' => true,
                'data' => ['user' => $user]
            ];
            
        } catch (Exception $e) {
            error_log("Error updating avatar: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to update avatar'
            ];
        }
    }

    /**
     * Get top readers by author (users who engage most with author's content)
     */
    public function getTopReadersByAuthor($authorId, $limit = 10) {
        try {
            $sql = "SELECT 
                        u.id,
                        u.username,
                        u.profile_image_url,
                        COUNT(DISTINCT c.id) as comment_count,
                        COALESCE(SUM(cl.count), 0) as clap_count,
                        (COUNT(DISTINCT c.id) * 3 + COALESCE(SUM(cl.count), 0)) as engagement_score
                    FROM users u
                    LEFT JOIN comments c ON u.id = c.user_id
                    LEFT JOIN articles a1 ON c.article_id = a1.id AND a1.author_id = ?
                    LEFT JOIN claps cl ON u.id = cl.user_id
                    LEFT JOIN articles a2 ON cl.article_id = a2.id AND a2.author_id = ?
                    WHERE (a1.id IS NOT NULL OR a2.id IS NOT NULL)
                    GROUP BY u.id, u.username, u.profile_image_url
                    HAVING engagement_score > 0
                    ORDER BY engagement_score DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$authorId, $authorId, $limit]);
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get users for admin management
     */
    public function getUsers($search = '', $role = '', $status = '', $limit = 20, $offset = 0) {
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(username LIKE ? OR email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($role)) {
            $whereConditions[] = "role = ?";
            $params[] = $role;
        }
        
        if ($status === 'suspended') {
            $whereConditions[] = "is_suspended = TRUE";
        } elseif ($status === 'active') {
            $whereConditions[] = "is_suspended = FALSE";
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT id, username, email, role, is_suspended, suspension_expires_at, 
                       email_verified, created_at, updated_at,
                       (SELECT COUNT(*) FROM articles WHERE author_id = users.id) as article_count,
                       (SELECT COUNT(*) FROM comments WHERE user_id = users.id) as comment_count
                FROM {$this->table} 
                {$whereClause}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get user count for pagination
     */
    public function getUserCount($search = '', $role = '', $status = '') {
        $whereConditions = [];
        $params = [];
        
        if (!empty($search)) {
            $whereConditions[] = "(username LIKE ? OR email LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        if (!empty($role)) {
            $whereConditions[] = "role = ?";
            $params[] = $role;
        }
        
        if ($status === 'suspended') {
            $whereConditions[] = "is_suspended = TRUE";
        } elseif ($status === 'active') {
            $whereConditions[] = "is_suspended = FALSE";
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Get user by ID with additional details
     */
    public function getUserById($id) {
        $sql = "SELECT u.*, 
                       (SELECT COUNT(*) FROM articles WHERE author_id = u.id) as article_count,
                       (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comment_count,
                       (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count,
                       (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count
                FROM {$this->table} u 
                WHERE u.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Update user role
     */
    public function updateRole($userId, $role) {
        $validRoles = ['user', 'moderator', 'admin'];
        if (!in_array($role, $validRoles)) {
            throw new Exception('Invalid role');
        }
        
        $sql = "UPDATE {$this->table} SET role = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$role, $userId]);
    }

    /**
     * Suspend user
     */
    public function suspendUser($userId, $expiresAt = null) {
        $sql = "UPDATE {$this->table} SET is_suspended = TRUE, suspension_expires_at = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$expiresAt, $userId]);
    }

    /**
     * Unsuspend user
     */
    public function unsuspendUser($userId) {
        $sql = "UPDATE {$this->table} SET is_suspended = FALSE, suspension_expires_at = NULL WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Verify user email
     */
    public function verifyUser($userId) {
        $sql = "UPDATE {$this->table} SET email_verified = TRUE WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Check if user has expired suspension
     */
    public function checkExpiredSuspensions() {
        $sql = "UPDATE {$this->table} 
                SET is_suspended = FALSE, suspension_expires_at = NULL 
                WHERE is_suspended = TRUE 
                AND suspension_expires_at IS NOT NULL 
                AND suspension_expires_at <= NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute();
    }}
