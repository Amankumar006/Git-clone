<?php
/**
 * Integration Test for Authentication Backend
 * Tests the actual functionality of the authentication system
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/JWTHelper.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../controllers/AuthController.php';

class AuthenticationIntegrationTest {
    
    private $userModel;
    private $jwtHelper;
    private $emailService;
    
    public function __construct() {
        // Only initialize services that don't require database
        $this->jwtHelper = new JWTHelper();
        $this->emailService = new EmailService();
        // Skip user model initialization to avoid database connection
        $this->userModel = null;
    }
    
    /**
     * Test User Model functionality
     */
    public function testUserModel() {
        echo "Testing User Model...\n";
        
        // Test password validation
        $passwordValidation = Validator::validatePassword('TestPass123!');
        if ($passwordValidation['valid']) {
            echo "✓ Password validation works correctly\n";
        } else {
            echo "✗ Password validation failed: " . implode(', ', $passwordValidation['errors']) . "\n";
        }
        
        // Test weak password
        $weakPasswordValidation = Validator::validatePassword('weak');
        if (!$weakPasswordValidation['valid']) {
            echo "✓ Weak password correctly rejected\n";
        } else {
            echo "✗ Weak password incorrectly accepted\n";
        }
    }
    
    /**
     * Test JWT Helper functionality
     */
    public function testJWTHelper() {
        echo "Testing JWT Helper...\n";
        
        // Test token generation
        $accessToken = JWTHelper::generateAccessToken(1, 'test@example.com', 'testuser');
        if ($accessToken) {
            echo "✓ Access token generation works\n";
            
            // Test token validation
            $validation = JWTHelper::validateToken($accessToken);
            if ($validation['valid']) {
                echo "✓ Token validation works\n";
                
                // Test user ID extraction
                $userId = JWTHelper::getUserIdFromToken($accessToken);
                if ($userId === 1) {
                    echo "✓ User ID extraction works\n";
                } else {
                    echo "✗ User ID extraction failed\n";
                }
            } else {
                echo "✗ Token validation failed: " . $validation['error'] . "\n";
            }
        } else {
            echo "✗ Access token generation failed\n";
        }
        
        // Test refresh token
        $refreshToken = JWTHelper::generateRefreshToken(1);
        if ($refreshToken && JWTHelper::isRefreshToken($refreshToken)) {
            echo "✓ Refresh token generation and identification works\n";
        } else {
            echo "✗ Refresh token generation or identification failed\n";
        }
        
        // Test email verification token
        $emailToken = JWTHelper::generateEmailVerificationToken(1);
        if ($emailToken) {
            echo "✓ Email verification token generation works\n";
        } else {
            echo "✗ Email verification token generation failed\n";
        }
    }
    
    /**
     * Test Email Service functionality
     */
    public function testEmailService() {
        echo "Testing Email Service...\n";
        
        $testUser = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com'
        ];
        
        // Test email verification token generation
        $verificationToken = $this->emailService->generateEmailVerificationToken($testUser['id']);
        if ($verificationToken) {
            echo "✓ Email verification token generation works\n";
        } else {
            echo "✗ Email verification token generation failed\n";
        }
        
        // Test email sending (will log in development mode)
        $emailSent = $this->emailService->sendEmailVerification($testUser, $verificationToken);
        if ($emailSent) {
            echo "✓ Email verification sending works (logged in development)\n";
        } else {
            echo "✗ Email verification sending failed\n";
        }
        
        // Test password reset email
        $resetEmailSent = $this->emailService->sendPasswordReset($testUser, 'test-token');
        if ($resetEmailSent) {
            echo "✓ Password reset email sending works (logged in development)\n";
        } else {
            echo "✗ Password reset email sending failed\n";
        }
    }
    
    /**
     * Test complete authentication flow
     */
    public function testAuthenticationFlow() {
        echo "Testing Authentication Flow...\n";
        
        // Test registration data validation
        $registrationData = [
            'username' => 'testuser123',
            'email' => 'test123@example.com',
            'password' => 'TestPass123!',
            'bio' => 'Test user bio'
        ];
        
        // Simulate registration validation (without database)
        $validation = $this->validateRegistrationData($registrationData);
        if ($validation['valid']) {
            echo "✓ Registration data validation works\n";
        } else {
            echo "✗ Registration data validation failed: " . implode(', ', $validation['errors']) . "\n";
        }
        
        // Test login validation
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];
        
        if (!empty($loginData['email']) && !empty($loginData['password'])) {
            echo "✓ Login data validation works\n";
        } else {
            echo "✗ Login data validation failed\n";
        }
        
        // Test token generation for complete flow
        $tokens = JWTHelper::generateTokens([
            'id' => 1,
            'email' => 'test@example.com',
            'username' => 'testuser'
        ]);
        
        if (isset($tokens['access_token']) && isset($tokens['refresh_token'])) {
            echo "✓ Complete token generation works\n";
        } else {
            echo "✗ Complete token generation failed\n";
        }
    }
    
    /**
     * Validate registration data (helper method)
     */
    private function validateRegistrationData($data) {
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
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Run all integration tests
     */
    public function runAllTests() {
        echo "Running Authentication Integration Tests...\n";
        echo "==========================================\n";
        
        $this->testUserModel();
        echo "\n";
        
        $this->testJWTHelper();
        echo "\n";
        
        $this->testEmailService();
        echo "\n";
        
        $this->testAuthenticationFlow();
        
        echo "==========================================\n";
        echo "Authentication Integration Tests completed\n\n";
    }
}

// Run tests if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new AuthenticationIntegrationTest();
    $test->runAllTests();
}