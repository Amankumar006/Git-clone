<?php
/**
 * Authentication Controller
 * Handles user registration, login, token management, and password reset
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/JWTHelper.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/EmailService.php';

class AuthController extends BaseController {
    private $userModel;
    private $jwtHelper;
    private $emailService;
    
    public function __construct() {
        parent::__construct();
        
        // Start session for rate limiting
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->userModel = new User();
        $this->jwtHelper = new JWTHelper();
        $this->emailService = new EmailService();
    }
    
    /**
     * Register new user
     * POST /api/auth/register
     */
    public function register() {
        try {
            // Rate limiting check with enhanced middleware
            require_once __DIR__ . '/../middleware/AuthMiddleware.php';
            if (!AuthMiddleware::authRateLimit('register')) {
                return; // Error already sent by middleware
            }
            
            // Validate input with comprehensive rules
            $input = AuthMiddleware::validateRegistration();
            
            $result = $this->userModel->register($input);
            
            if ($result['success']) {
                // Mark successful registration for rate limiting
                AuthMiddleware::markSuccessfulAuth('register');
                
                // Generate tokens
                $tokens = JWTHelper::generateTokens($result['user']);
                
                // Send email verification
                $verificationToken = $this->emailService->generateEmailVerificationToken($result['user']['id']);
                $emailSent = $this->emailService->sendEmailVerification($result['user'], $verificationToken);
                
                $response = [
                    'user' => $result['user'],
                    'tokens' => $tokens,
                    'message' => 'Registration successful. Please check your email to verify your account.'
                ];
                
                if (!$emailSent) {
                    $response['warning'] = 'Account created but verification email could not be sent. You can request a new verification email.';
                }
                
                return $this->sendResponse($response, 'User registered successfully', 201);
            } else {
                return $this->sendError('Registration failed', 400, $result['errors']);
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->sendError('Registration failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * User login
     * POST /api/auth/login
     */
    public function login() {
        try {
            // Rate limiting check with enhanced middleware
            require_once __DIR__ . '/../middleware/AuthMiddleware.php';
            if (!AuthMiddleware::authRateLimit('login')) {
                return; // Error already sent by middleware
            }
            
            // Validate input with comprehensive rules
            $input = AuthMiddleware::validateLogin();
            
            $result = $this->userModel->login($input['email'], $input['password']);
            
            if ($result['success']) {
                // Mark successful login for rate limiting
                AuthMiddleware::markSuccessfulAuth('login');
                
                // Generate tokens
                $tokens = JWTHelper::generateTokens($result['user']);
                
                // Check if email is verified
                $emailVerified = $result['user']['email_verified'] ?? false;
                $response = [
                    'user' => $result['user'],
                    'tokens' => $tokens
                ];
                
                if (!$emailVerified) {
                    $response['warning'] = 'Please verify your email address to access all features.';
                }
                
                return $this->sendResponse($response, 'Login successful');
            } else {
                return $this->sendError($result['error'], 401);
            }
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Refresh access token
     * POST /api/auth/refresh
     */
    public function refresh() {
        try {
            $input = $this->getJsonInput();
            
            if (empty($input['refresh_token'])) {
                return $this->sendError('Refresh token is required', 400);
            }
            
            $result = JWTHelper::refreshAccessToken($input['refresh_token'], $this->userModel);
            
            if ($result['success']) {
                return $this->sendResponse([
                    'access_token' => $result['access_token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in']
                ], 'Token refreshed successfully');
            } else {
                return $this->sendError($result['error'], 401);
            }
        } catch (Exception $e) {
            error_log("Token refresh error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * User logout
     * POST /api/auth/logout
     */
    public function logout() {
        try {
            // In a stateless JWT system, logout is handled client-side
            // But we can validate the token and return success
            $token = JWTHelper::getTokenFromHeader();
            
            if (!$token) {
                return $this->sendError('No token provided', 401);
            }
            
            $validation = JWTHelper::validateToken($token);
            
            if (!$validation['valid']) {
                return $this->sendError('Invalid token', 401);
            }
            
            // In a more advanced implementation, you might add the token to a blacklist
            // For now, we'll just return success
            return $this->sendResponse([], 'Logout successful');
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Request password reset
     * POST /api/auth/forgot-password
     */
    public function forgotPassword() {
        try {
            // Rate limiting check with enhanced middleware
            require_once __DIR__ . '/../middleware/AuthMiddleware.php';
            if (!AuthMiddleware::authRateLimit('forgot_password')) {
                return; // Error already sent by middleware
            }
            
            // Validate input
            $input = AuthMiddleware::validatePasswordResetRequest();
            
            $result = $this->userModel->createPasswordResetToken($input['email']);
            
            if ($result['success']) {
                // Send password reset email
                $emailSent = $this->emailService->sendPasswordReset($result['user'], $result['token']);
                
                if ($emailSent) {
                    return $this->sendResponse([], 'Password reset email sent successfully');
                } else {
                    error_log("Failed to send password reset email for: " . $input['email']);
                    // Still return success to prevent email enumeration
                    return $this->sendResponse([], 'If the email exists, a password reset link has been sent');
                }
            } else {
                // Always return success to prevent email enumeration
                return $this->sendResponse([], 'If the email exists, a password reset link has been sent');
            }
        } catch (Exception $e) {
            error_log("Forgot password error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Reset password using token
     * POST /api/auth/reset-password
     */
    public function resetPassword() {
        try {
            // Validate input with comprehensive rules
            require_once __DIR__ . '/../middleware/AuthMiddleware.php';
            $input = AuthMiddleware::validatePasswordReset();
            
            $result = $this->userModel->resetPassword($input['token'], $input['password']);
            
            if ($result['success']) {
                return $this->sendResponse([], 'Password reset successfully');
            } else {
                return $this->sendError($result['error'], 400, $result['errors'] ?? []);
            }
        } catch (Exception $e) {
            error_log("Reset password error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Verify email address
     * POST /api/auth/verify-email
     */
    public function verifyEmail() {
        try {
            // Rate limiting for email verification
            require_once __DIR__ . '/../middleware/AuthMiddleware.php';
            if (!AuthMiddleware::authRateLimit('verify_email')) {
                return; // Error already sent by middleware
            }
            
            $input = $this->getJsonInput();
            
            if (empty($input['token'])) {
                return $this->sendError('Verification token is required', 400);
            }
            
            // Validate token and get user ID
            $validation = JWTHelper::validateToken($input['token']);
            
            if (!$validation['valid']) {
                return $this->sendError('Invalid or expired verification token', 400);
            }
            
            // Check if it's an email verification token
            if ($validation['payload']['type'] !== 'email_verification') {
                return $this->sendError('Invalid token type', 400);
            }
            
            $userId = $validation['payload']['user_id'];
            
            // Check if user exists and is not already verified
            $user = $this->userModel->findById($userId);
            if (!$user) {
                return $this->sendError('User not found', 404);
            }
            
            if ($user['email_verified']) {
                return $this->sendResponse([], 'Email is already verified');
            }
            
            $result = $this->userModel->verifyEmail($userId);
            
            if ($result['success']) {
                // Send welcome email
                $this->emailService->sendWelcomeEmail($user);
                
                return $this->sendResponse([
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'username' => $user['username'],
                        'email_verified' => true
                    ]
                ], 'Email verified successfully');
            } else {
                return $this->sendError('Failed to verify email', 500);
            }
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Get current user profile
     * GET /api/auth/me
     */
    public function me() {
        try {
            $token = JWTHelper::getTokenFromHeader();
            
            if (!$token) {
                return $this->sendError('No token provided', 401);
            }
            
            $validation = JWTHelper::validateToken($token);
            
            if (!$validation['valid']) {
                return $this->sendError('Invalid token', 401);
            }
            
            $userId = $validation['payload']['user_id'];
            $user = $this->userModel->findById($userId);
            
            if (!$user) {
                return $this->sendError('User not found', 404);
            }
            
            // Remove sensitive data
            unset($user['password_hash']);
            $user['social_links'] = $user['social_links'] ? json_decode($user['social_links'], true) : [];
            
            return $this->sendResponse(['user' => $user], 'User profile retrieved successfully');
        } catch (Exception $e) {
            error_log("Get user profile error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Resend email verification
     * POST /api/auth/resend-verification
     */
    public function resendVerification() {
        try {
            // Rate limiting for resend verification
            require_once __DIR__ . '/../middleware/AuthMiddleware.php';
            if (!AuthMiddleware::authRateLimit('resend_verification')) {
                return; // Error already sent by middleware
            }
            
            $input = $this->getJsonInput();
            
            if (empty($input['email'])) {
                return $this->sendError('Email is required', 400);
            }
            
            // Validate email format
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                return $this->sendError('Invalid email format', 400);
            }
            
            $user = $this->userModel->findByEmail($input['email']);
            
            if (!$user) {
                // Don't reveal if email exists
                return $this->sendResponse([], 'If the email exists and is not verified, a verification email has been sent');
            }
            
            if ($user['email_verified']) {
                return $this->sendResponse([], 'Email is already verified');
            }
            
            // Generate new verification token and send email
            $verificationToken = $this->emailService->generateEmailVerificationToken($user['id']);
            $emailSent = $this->emailService->sendEmailVerification($user, $verificationToken);
            
            if ($emailSent) {
                return $this->sendResponse([], 'Verification email sent successfully');
            } else {
                error_log("Failed to send verification email for: " . $input['email']);
                return $this->sendError('Failed to send verification email', 500);
            }
        } catch (Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            return $this->sendError('Internal server error', 500);
        }
    }
    

}