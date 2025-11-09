<?php
/**
 * User Model
 * Handles user authentication, registration, and profile management
 */

require_once __DIR__ . '/BaseRepository.php';
require_once __DIR__ . '/../utils/Validator.php';

class User extends BaseRepository {
    protected $table = 'users';
    
    /**
     * Register a new user
     */
    public function register($userData) {
        // Validate input data
        $validation = $this->validateRegistration($userData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        // Check if email already exists
        if ($this->findByEmail($userData['email'])) {
            return ['success' => false, 'errors' => ['email' => 'Email already exists']];
        }
        
        // Check if username already exists
        if ($this->findByUsername($userData['username'])) {
            return ['success' => false, 'errors' => ['username' => 'Username already exists']];
        }
        
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Prepare user data
        $userToCreate = [
            'username' => $userData['username'],
            'email' => $userData['email'],
            'password_hash' => $hashedPassword,
            'bio' => $userData['bio'] ?? null,
            'profile_image_url' => null,
            'social_links' => json_encode($userData['social_links'] ?? []),
            'email_verified' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $userId = $this->create($userToCreate);
        
        if ($userId) {
            $user = $this->findById($userId);
            unset($user['password_hash']); // Don't return password hash
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to create user']];
    }
    
    /**
     * Authenticate user login
     */
    public function login($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Remove password hash from returned user data
        unset($user['password_hash']);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in findByEmail: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database error in findByUsername: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($userId, $profileData) {
        $validation = $this->validateProfileUpdate($profileData);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $updateData = [
            'bio' => $profileData['bio'] ?? null,
            'social_links' => json_encode($profileData['social_links'] ?? []),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Only update profile image if provided
        if (isset($profileData['profile_image_url'])) {
            $updateData['profile_image_url'] = $profileData['profile_image_url'];
        }
        
        $result = $this->update($userId, $updateData);
        
        if ($result) {
            $user = $this->findById($userId);
            unset($user['password_hash']);
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'errors' => ['general' => 'Failed to update profile']];
    }
    
    /**
     * Update password
     */
    public function updatePassword($userId, $currentPassword, $newPassword) {
        $user = $this->findById($userId);
        
        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }
        
        $validation = Validator::validatePassword($newPassword);
        if (!$validation['valid']) {
            return ['success' => false, 'errors' => $validation['errors']];
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $result = $this->update($userId, [
            'password_hash' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['success' => $result];
    }
    
    /**
     * Create password reset token
     */
    public function createPasswordResetToken($email) {
        $user = $this->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'error' => 'Email not found'];
        }
        
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        try {
            // Delete any existing tokens for this email
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);
            
            // Insert new token
            $stmt = $this->db->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $result = $stmt->execute([$email, $token, $expiresAt]);
            
            if ($result) {
                return ['success' => true, 'token' => $token, 'user' => $user];
            }
        } catch (PDOException $e) {
            error_log("Database error in createPasswordResetToken: " . $e->getMessage());
        }
        
        return ['success' => false, 'error' => 'Failed to create reset token'];
    }
    
    /**
     * Reset password using token
     */
    public function resetPassword($token, $newPassword) {
        try {
            // Find valid token
            $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$token]);
            $resetRecord = $stmt->fetch();
            
            if (!$resetRecord) {
                return ['success' => false, 'error' => 'Invalid or expired token'];
            }
            
            // Validate new password
            $validation = Validator::validatePassword($newPassword);
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => $validation['errors']];
            }
            
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, updated_at = ? WHERE email = ?");
            $result = $stmt->execute([$hashedPassword, date('Y-m-d H:i:s'), $resetRecord['email']]);
            
            if ($result) {
                // Delete used token
                $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);
                
                return ['success' => true];
            }
        } catch (PDOException $e) {
            error_log("Database error in resetPassword: " . $e->getMessage());
        }
        
        return ['success' => false, 'error' => 'Failed to reset password'];
    }
    
    /**
     * Verify email address
     */
    public function verifyEmail($userId) {
        $result = $this->update($userId, [
            'email_verified' => true,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return ['success' => $result];
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
        $user['social_links'] = json_decode($user['social_links'], true) ?? [];
        
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
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM articles WHERE author_id = ? AND status = 'published'");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getPublishedArticlesCount: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Validate registration data
     */
    private function validateRegistration($data) {
        $errors = [];
        
        // Username validation
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3 || strlen($data['username']) > 50) {
            $errors['username'] = 'Username must be between 3 and 50 characters';
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
        $passwordValidation = Validator::validatePassword($data['password'] ?? '');
        if (!$passwordValidation['valid']) {
            $errors = array_merge($errors, $passwordValidation['errors']);
        }
        
        // Bio validation (optional)
        if (isset($data['bio']) && strlen($data['bio']) > 500) {
            $errors['bio'] = 'Bio must be less than 500 characters';
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Validate profile update data
     */
    private function validateProfileUpdate($data) {
        $errors = [];
        
        // Bio validation (optional)
        if (isset($data['bio']) && strlen($data['bio']) > 500) {
            $errors['bio'] = 'Bio must be less than 500 characters';
        }
        
        // Social links validation (optional)
        if (isset($data['social_links']) && is_array($data['social_links'])) {
            foreach ($data['social_links'] as $platform => $url) {
                if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors['social_links'] = "Invalid URL for $platform";
                    break;
                }
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Get top readers for an author (users who engage most with author's content)
     */
    public function getTopReadersByAuthor($authorId, $limit = 10) {
        $sql = "SELECT 
                    u.id, u.username, u.profile_image_url,
                    COUNT(DISTINCT c.id) as comment_count,
                    COUNT(DISTINCT cl.id) as clap_count,
                    (COUNT(DISTINCT c.id) * 3 + COUNT(DISTINCT cl.id)) as engagement_score
                FROM users u
                LEFT JOIN comments c ON u.id = c.user_id 
                    AND c.article_id IN (SELECT id FROM articles WHERE author_id = ?)
                LEFT JOIN claps cl ON u.id = cl.user_id 
                    AND cl.article_id IN (SELECT id FROM articles WHERE author_id = ?)
                WHERE u.id != ?
                AND (c.id IS NOT NULL OR cl.id IS NOT NULL)
                GROUP BY u.id, u.username, u.profile_image_url
                HAVING engagement_score > 0
                ORDER BY engagement_score DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$authorId, $authorId, $authorId, $limit]);
        
        return $stmt->fetchAll();
    } 
   /**
     * Get top readers for an author (users who engage most with author's content)
     */
    public function getTopReadersByAuthor($authorId, $limit = 10) {
        $sql = "SELECT 
                    u.id, u.username, u.profile_image_url,
                    COUNT(DISTINCT c.id) as comment_count,
                    COUNT(DISTINCT cl.id) as clap_count,
                    COUNT(DISTINCT ar.id) as read_count,
                    (COUNT(DISTINCT c.id) * 3 + COUNT(DISTINCT cl.id) * 2 + COUNT(DISTINCT ar.id)) as engagement_score
                FROM users u
                LEFT JOIN comments c ON u.id = c.user_id 
                    AND c.article_id IN (SELECT id FROM articles WHERE author_id = ?)
                LEFT JOIN claps cl ON u.id = cl.user_id 
                    AND cl.article_id IN (SELECT id FROM articles WHERE author_id = ?)
                LEFT JOIN article_reads ar ON u.id = ar.user_id 
                    AND ar.article_id IN (SELECT id FROM articles WHERE author_id = ?)
                WHERE u.id != ?
                GROUP BY u.id, u.username, u.profile_image_url
                HAVING engagement_score > 0
                ORDER BY engagement_score DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$authorId, $authorId, $authorId, $authorId, $limit]);
        
        return $stmt->fetchAll();
    }
}