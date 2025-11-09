<?php
/**
 * Authentication Middleware
 * Handles JWT token validation and user authentication
 */

require_once __DIR__ . '/../utils/JWTHelper.php';
require_once __DIR__ . '/../utils/ErrorHandler.php';

class AuthMiddleware {
    
    /**
     * Validate JWT token and authenticate user
     */
    public static function authenticate() {
        $token = JWTHelper::getTokenFromHeader();
        
        if (!$token) {
            ErrorHandler::authenticationError('Authorization token missing');
        }
        
        $validation = JWTHelper::validateToken($token);
        
        if (!$validation['valid']) {
            ErrorHandler::authenticationError($validation['error']);
        }
        
        // Return user data from token
        return [
            'id' => $validation['payload']['user_id'],
            'user_id' => $validation['payload']['user_id'],
            'email' => $validation['payload']['email'],
            'username' => $validation['payload']['username']
        ];
    }
    
    /**
     * Validate JWT token and return user data or null (doesn't exit on failure)
     */
    public static function validateUser() {
        try {
            $token = JWTHelper::getTokenFromHeader();
            
            if (!$token) {
                return null;
            }
            
            $validation = JWTHelper::validateToken($token);
            
            if (!$validation['valid']) {
                return null;
            }
            
            // Return user data from token
            return [
                'id' => $validation['payload']['user_id'],
                'user_id' => $validation['payload']['user_id'],
                'email' => $validation['payload']['email'],
                'username' => $validation['payload']['username']
            ];
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Optional authentication - returns user data if token is valid, null otherwise
     */
    public static function optionalAuth() {
        try {
            return self::authenticate();
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if user has required role/permission
     */
    public static function authorize($requiredRole = null, $userId = null, $resourceOwnerId = null) {
        $user = self::authenticate();
        
        // Check if user owns the resource
        if ($resourceOwnerId !== null && $user['user_id'] != $resourceOwnerId) {
            ErrorHandler::authorizationError('Access denied - not resource owner');
        }
        
        // Additional role-based checks can be added here
        if ($requiredRole !== null) {
            // This would require a roles system in the database
            // For now, we'll skip this check
        }
        
        return $user;
    }
    
    /**
     * Enhanced rate limiting middleware with multiple strategies
     */
    public static function rateLimit($action, $maxAttempts = 10, $timeWindow = 300, $blockDuration = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $clientIp = self::getClientIp();
        $key = $action . '_' . $clientIp;
        $blockDuration = $blockDuration ?? $timeWindow;
        
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
        
        $now = time();
        
        // Check if client is currently blocked
        if (isset($_SESSION['rate_limits'][$key]['blocked_until']) && 
            $_SESSION['rate_limits'][$key]['blocked_until'] > $now) {
            $remainingTime = $_SESSION['rate_limits'][$key]['blocked_until'] - $now;
            ErrorHandler::error("Too many attempts. Blocked for {$remainingTime} more seconds.", 429, [
                'retry_after' => $remainingTime,
                'blocked_until' => $_SESSION['rate_limits'][$key]['blocked_until']
            ]);
            return false;
        }
        
        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [
                'count' => 1, 
                'first_attempt' => $now,
                'attempts' => [['timestamp' => $now, 'success' => false]]
            ];
            return true;
        }
        
        $rateLimit = $_SESSION['rate_limits'][$key];
        
        // Clean up old attempts outside the time window
        $rateLimit['attempts'] = array_filter($rateLimit['attempts'], function($attempt) use ($now, $timeWindow) {
            return ($now - $attempt['timestamp']) <= $timeWindow;
        });
        
        // Reset if time window has passed
        if ($now - $rateLimit['first_attempt'] > $timeWindow) {
            $_SESSION['rate_limits'][$key] = [
                'count' => 1, 
                'first_attempt' => $now,
                'attempts' => [['timestamp' => $now, 'success' => false]]
            ];
            return true;
        }
        
        // Count recent failed attempts
        $recentFailedAttempts = count(array_filter($rateLimit['attempts'], function($attempt) {
            return !$attempt['success'];
        }));
        
        // Check if limit exceeded
        if ($recentFailedAttempts >= $maxAttempts) {
            // Block the client
            $_SESSION['rate_limits'][$key]['blocked_until'] = $now + $blockDuration;
            $_SESSION['rate_limits'][$key]['total_blocks'] = ($_SESSION['rate_limits'][$key]['total_blocks'] ?? 0) + 1;
            
            ErrorHandler::error("Too many failed attempts. Blocked for {$blockDuration} seconds.", 429, [
                'retry_after' => $blockDuration,
                'blocked_until' => $_SESSION['rate_limits'][$key]['blocked_until']
            ]);
            return false;
        }
        
        // Add current attempt
        $_SESSION['rate_limits'][$key]['attempts'][] = ['timestamp' => $now, 'success' => false];
        $_SESSION['rate_limits'][$key]['count'] = count($_SESSION['rate_limits'][$key]['attempts']);
        
        return true;
    }
    
    /**
     * Mark successful authentication to reset rate limiting
     */
    public static function markSuccessfulAuth($action) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $clientIp = self::getClientIp();
        $key = $action . '_' . $clientIp;
        
        if (isset($_SESSION['rate_limits'][$key])) {
            // Mark last attempt as successful
            $lastIndex = count($_SESSION['rate_limits'][$key]['attempts']) - 1;
            if ($lastIndex >= 0) {
                $_SESSION['rate_limits'][$key]['attempts'][$lastIndex]['success'] = true;
            }
            
            // Reset counter for successful auth
            $_SESSION['rate_limits'][$key]['count'] = 0;
            $_SESSION['rate_limits'][$key]['first_attempt'] = time();
        }
    }
    
    /**
     * Get client IP address with proxy support
     */
    private static function getClientIp() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Advanced rate limiting for different authentication actions
     */
    public static function authRateLimit($action) {
        $limits = [
            'login' => ['max' => 5, 'window' => 300, 'block' => 900], // 5 attempts per 5 min, block for 15 min
            'register' => ['max' => 3, 'window' => 300, 'block' => 600], // 3 attempts per 5 min, block for 10 min
            'forgot_password' => ['max' => 3, 'window' => 300, 'block' => 1800], // 3 attempts per 5 min, block for 30 min
            'verify_email' => ['max' => 10, 'window' => 300, 'block' => 300], // 10 attempts per 5 min, block for 5 min
            'resend_verification' => ['max' => 3, 'window' => 600, 'block' => 1800] // 3 attempts per 10 min, block for 30 min
        ];
        
        $config = $limits[$action] ?? ['max' => 10, 'window' => 300, 'block' => 300];
        
        return self::rateLimit($action, $config['max'], $config['window'], $config['block']);
    }
    
    /**
     * Require email verification
     */
    public static function requireEmailVerification() {
        $user = self::authenticate();
        
        // Get full user data to check email verification status
        require_once __DIR__ . '/../models/User.php';
        $userModel = new User();
        $fullUser = $userModel->findById($user['user_id']);
        
        if (!$fullUser || !$fullUser['email_verified']) {
            ErrorHandler::authorizationError('Email verification required');
        }
        
        return $user;
    }
    
    /**
     * Validate request input with comprehensive rules
     */
    public static function validateInput($rules) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            ErrorHandler::validationError('Invalid JSON input');
        }
        
        require_once __DIR__ . '/../utils/Validator.php';
        $validator = new Validator($input);
        
        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule => $params) {
                switch ($rule) {
                    case 'required':
                        $validator->required($field);
                        break;
                    case 'email':
                        $validator->email($field);
                        break;
                    case 'min':
                        $validator->minLength($field, $params);
                        break;
                    case 'max':
                        $validator->maxLength($field, $params);
                        break;
                    case 'password':
                        $validator->password($field);
                        break;
                    case 'matches':
                        $validator->matches($field, $params);
                        break;
                    case 'in':
                        $validator->in($field, $params);
                        break;
                    case 'url':
                        $validator->url($field);
                        break;
                    case 'unique':
                        $validator->unique($field, $params['table'], $params['column'] ?? $field, $params['exclude_id'] ?? null);
                        break;
                    case 'regex':
                        $validator->regex($field, $params);
                        break;
                    case 'numeric':
                        $validator->numeric($field);
                        break;
                    case 'integer':
                        $validator->integer($field);
                        break;
                }
            }
        }
        
        if ($validator->fails()) {
            ErrorHandler::validationError('Validation failed', $validator->getErrors());
        }
        
        return $input;
    }
    
    /**
     * Validate registration input with comprehensive rules
     */
    public static function validateRegistration() {
        return self::validateInput([
            'username' => [
                'required' => true,
                'min' => 3,
                'max' => 50,
                'regex' => '/^[a-zA-Z0-9_-]+$/',
                'unique' => ['table' => 'users', 'column' => 'username']
            ],
            'email' => [
                'required' => true,
                'email' => true,
                'max' => 255,
                'unique' => ['table' => 'users', 'column' => 'email']
            ],
            'password' => [
                'required' => true,
                'password' => true
            ],
            'bio' => [
                'max' => 500
            ]
        ]);
    }
    
    /**
     * Validate login input
     */
    public static function validateLogin() {
        return self::validateInput([
            'email' => [
                'required' => true,
                'email' => true
            ],
            'password' => [
                'required' => true,
                'min' => 1
            ]
        ]);
    }
    
    /**
     * Validate password reset request
     */
    public static function validatePasswordResetRequest() {
        return self::validateInput([
            'email' => [
                'required' => true,
                'email' => true
            ]
        ]);
    }
    
    /**
     * Validate password reset
     */
    public static function validatePasswordReset() {
        return self::validateInput([
            'token' => [
                'required' => true,
                'min' => 10
            ],
            'password' => [
                'required' => true,
                'password' => true
            ]
        ]);
    }
    
    /**
     * CSRF protection middleware
     */
    public static function csrfProtection() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Only check CSRF for state-changing methods
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $input['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            ErrorHandler::authorizationError('CSRF token mismatch');
        }
        
        return true;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}