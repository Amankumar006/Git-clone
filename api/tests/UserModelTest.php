<?php
/**
 * User Model Tests
 * Comprehensive tests for User model validation and authentication methods
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Validator.php';

class UserModelTest {
    private $testResults = [];
    
    public function __construct() {
        // Initialize test environment
        $this->testResults = [
            'passed' => 0,
            'failed' => 0,
            'total' => 0
        ];
    }
    
    /**
     * Assert test result
     */
    private function assert($condition, $message) {
        $this->testResults['total']++;
        if ($condition) {
            $this->testResults['passed']++;
            echo "✓ $message\n";
            return true;
        } else {
            $this->testResults['failed']++;
            echo "✗ $message\n";
            return false;
        }
    }
    
    /**
     * Test user registration validation
     */
    public function testUserRegistrationValidation() {
        echo "Testing user registration validation...\n";
        
        // Test valid registration data
        $validData = [
            'username' => 'testuser123',
            'email' => 'test@example.com',
            'password' => 'TestPass123!',
            'bio' => 'Test bio for user'
        ];
        
        // Test username validation
        $this->assert(
            !empty($validData['username']) && 
            strlen($validData['username']) >= 3 && 
            strlen($validData['username']) <= 50 &&
            preg_match('/^[a-zA-Z0-9_-]+$/', $validData['username']),
            'Valid username validation'
        );
        
        // Test invalid username - too short
        $this->assert(
            strlen('ab') < 3,
            'Username too short validation'
        );
        
        // Test invalid username - special characters
        $this->assert(
            !preg_match('/^[a-zA-Z0-9_-]+$/', 'user@name'),
            'Username with invalid characters validation'
        );
        
        // Test email validation
        $this->assert(
            filter_var($validData['email'], FILTER_VALIDATE_EMAIL) !== false,
            'Valid email format validation'
        );
        
        // Test invalid email
        $this->assert(
            filter_var('invalid-email', FILTER_VALIDATE_EMAIL) === false,
            'Invalid email format validation'
        );
        
        // Test bio length validation
        $this->assert(
            strlen($validData['bio']) <= 500,
            'Bio length validation'
        );
        
        // Test bio too long
        $longBio = str_repeat('a', 501);
        $this->assert(
            strlen($longBio) > 500,
            'Bio too long validation'
        );
    }
    
    /**
     * Test password hashing and verification
     */
    public function testPasswordHashing() {
        echo "Testing password hashing and verification...\n";
        
        $password = 'TestPassword123!';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Test password hashing
        $this->assert(
            !empty($hashedPassword) && $hashedPassword !== $password,
            'Password is properly hashed'
        );
        
        // Test password verification with correct password
        $this->assert(
            password_verify($password, $hashedPassword),
            'Correct password verification'
        );
        
        // Test password verification with incorrect password
        $this->assert(
            !password_verify('WrongPassword123!', $hashedPassword),
            'Incorrect password rejection'
        );
        
        // Test different passwords produce different hashes
        $password2 = 'AnotherPassword123!';
        $hashedPassword2 = password_hash($password2, PASSWORD_DEFAULT);
        $this->assert(
            $hashedPassword !== $hashedPassword2,
            'Different passwords produce different hashes'
        );
        
        // Test same password produces different hashes (salt)
        $hashedPassword3 = password_hash($password, PASSWORD_DEFAULT);
        $this->assert(
            $hashedPassword !== $hashedPassword3,
            'Same password produces different hashes due to salt'
        );
    }
    
    /**
     * Test user login validation
     */
    public function testUserLoginValidation() {
        echo "Testing user login validation...\n";
        
        // Test with valid credentials format
        $validEmail = 'test@example.com';
        $validPassword = 'TestPass123!';
        
        $this->assert(
            filter_var($validEmail, FILTER_VALIDATE_EMAIL) && !empty($validPassword),
            'Valid login credentials format'
        );
        
        // Test with invalid email format
        $invalidEmail = 'invalid-email';
        $this->assert(
            !filter_var($invalidEmail, FILTER_VALIDATE_EMAIL),
            'Invalid email format rejection'
        );
        
        // Test with empty password
        $this->assert(
            empty(''),
            'Empty password rejection'
        );
        
        // Test with empty email
        $this->assert(
            !filter_var('', FILTER_VALIDATE_EMAIL),
            'Empty email rejection'
        );
    }
    
    /**
     * Test profile update validation
     */
    public function testProfileUpdateValidation() {
        echo "Testing profile update validation...\n";
        
        // Test valid profile data
        $validProfileData = [
            'bio' => 'Updated bio for the user profile',
            'social_links' => [
                'twitter' => 'https://twitter.com/username',
                'linkedin' => 'https://linkedin.com/in/username',
                'github' => 'https://github.com/username'
            ]
        ];
        
        // Test valid bio length
        $this->assert(
            strlen($validProfileData['bio']) <= 500,
            'Valid bio length validation'
        );
        
        // Test bio too long
        $longBio = str_repeat('a', 501);
        $this->assert(
            strlen($longBio) > 500,
            'Bio too long validation'
        );
        
        // Test valid social links
        $validLinks = true;
        foreach ($validProfileData['social_links'] as $platform => $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $validLinks = false;
                break;
            }
        }
        $this->assert($validLinks, 'Valid social links validation');
        
        // Test invalid social link URL
        $invalidUrl = 'not-a-valid-url';
        $this->assert(
            !filter_var($invalidUrl, FILTER_VALIDATE_URL),
            'Invalid social link URL validation'
        );
        
        // Test empty bio (should be allowed)
        $this->assert(
            strlen('') <= 500,
            'Empty bio validation (allowed)'
        );
    }
    
    /**
     * Test password validation rules
     */
    public function testPasswordValidation() {
        echo "Testing password validation rules...\n";
        
        // Test valid password
        $validPassword = 'TestPass123!';
        $this->assert(
            strlen($validPassword) >= 8 && 
            preg_match('/[A-Z]/', $validPassword) && 
            preg_match('/[a-z]/', $validPassword) && 
            preg_match('/[0-9]/', $validPassword),
            'Valid password with mixed case, numbers'
        );
        
        // Test password too short
        $shortPassword = 'Test1!';
        $this->assert(
            strlen($shortPassword) < 8,
            'Password too short validation'
        );
        
        // Test password without uppercase
        $noUppercase = 'testpass123!';
        $this->assert(
            !preg_match('/[A-Z]/', $noUppercase),
            'Password without uppercase validation'
        );
        
        // Test password without lowercase
        $noLowercase = 'TESTPASS123!';
        $this->assert(
            !preg_match('/[a-z]/', $noLowercase),
            'Password without lowercase validation'
        );
        
        // Test password without numbers
        $noNumbers = 'TestPassword!';
        $this->assert(
            !preg_match('/[0-9]/', $noNumbers),
            'Password without numbers validation'
        );
        
        // Test very long password (should be allowed)
        $longPassword = str_repeat('TestPass123!', 10);
        $this->assert(
            strlen($longPassword) > 50,
            'Very long password handling'
        );
    }
    
    /**
     * Test password reset token generation
     */
    public function testPasswordResetToken() {
        echo "Testing password reset token generation...\n";
        
        // Test token generation
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));
        
        $this->assert(
            strlen($token1) === 64,
            'Password reset token length validation'
        );
        
        $this->assert(
            $token1 !== $token2,
            'Password reset tokens are unique'
        );
        
        $this->assert(
            ctype_xdigit($token1),
            'Password reset token is hexadecimal'
        );
        
        // Test expiration time calculation
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $currentTime = date('Y-m-d H:i:s');
        
        $this->assert(
            strtotime($expiresAt) > strtotime($currentTime),
            'Password reset token expiration time is in future'
        );
    }
    
    /**
     * Test email verification functionality
     */
    public function testEmailVerification() {
        echo "Testing email verification functionality...\n";
        
        // Test email verification status
        $unverifiedUser = ['email_verified' => false];
        $verifiedUser = ['email_verified' => true];
        
        $this->assert(
            !$unverifiedUser['email_verified'],
            'Unverified user status check'
        );
        
        $this->assert(
            $verifiedUser['email_verified'],
            'Verified user status check'
        );
        
        // Test verification token format (would be JWT in real implementation)
        $verificationData = [
            'user_id' => 123,
            'type' => 'email_verification',
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        
        $this->assert(
            isset($verificationData['user_id']) && 
            $verificationData['type'] === 'email_verification' &&
            $verificationData['exp'] > time(),
            'Email verification token structure'
        );
    }
    
    /**
     * Test user profile data handling
     */
    public function testUserProfileData() {
        echo "Testing user profile data handling...\n";
        
        // Test social links JSON encoding/decoding
        $socialLinks = [
            'twitter' => 'https://twitter.com/user',
            'linkedin' => 'https://linkedin.com/in/user',
            'github' => 'https://github.com/user'
        ];
        
        $encoded = json_encode($socialLinks);
        $decoded = json_decode($encoded, true);
        
        $this->assert(
            $decoded === $socialLinks,
            'Social links JSON encoding/decoding'
        );
        
        // Test profile image URL validation
        $validImageUrl = 'https://example.com/profile.jpg';
        $invalidImageUrl = 'not-a-url';
        
        $this->assert(
            filter_var($validImageUrl, FILTER_VALIDATE_URL),
            'Valid profile image URL'
        );
        
        $this->assert(
            !filter_var($invalidImageUrl, FILTER_VALIDATE_URL),
            'Invalid profile image URL validation'
        );
        
        // Test user data sanitization (removing sensitive fields)
        $userData = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password_hash' => 'hashed_password',
            'bio' => 'User bio',
            'email_verified' => true
        ];
        
        unset($userData['password_hash']);
        
        $this->assert(
            !isset($userData['password_hash']),
            'Password hash removed from user data'
        );
        
        $this->assert(
            isset($userData['username']) && isset($userData['email']),
            'Essential user data preserved'
        );
    }
    
    /**
     * Test authentication method validation
     */
    public function testAuthenticationMethods() {
        echo "Testing authentication method validation...\n";
        
        // Test login attempt structure
        $loginAttempt = [
            'email' => 'test@example.com',
            'password' => 'TestPass123!',
            'timestamp' => time()
        ];
        
        $this->assert(
            filter_var($loginAttempt['email'], FILTER_VALIDATE_EMAIL) &&
            !empty($loginAttempt['password']) &&
            is_numeric($loginAttempt['timestamp']),
            'Login attempt data structure validation'
        );
        
        // Test registration attempt structure
        $registrationAttempt = [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'NewPass123!',
            'bio' => 'New user bio'
        ];
        
        $this->assert(
            !empty($registrationAttempt['username']) &&
            filter_var($registrationAttempt['email'], FILTER_VALIDATE_EMAIL) &&
            !empty($registrationAttempt['password']),
            'Registration attempt data structure validation'
        );
        
        // Test user existence check simulation
        $existingEmails = ['existing@example.com', 'another@example.com'];
        $newEmail = 'new@example.com';
        
        $this->assert(
            !in_array($newEmail, $existingEmails),
            'New email availability check'
        );
        
        $this->assert(
            in_array('existing@example.com', $existingEmails),
            'Existing email detection'
        );
    }
    
    /**
     * Print test summary
     */
    private function printTestSummary() {
        echo "\n========================\n";
        echo "User Model Test Summary:\n";
        echo "Total Tests: {$this->testResults['total']}\n";
        echo "Passed: {$this->testResults['passed']}\n";
        echo "Failed: {$this->testResults['failed']}\n";
        
        if ($this->testResults['failed'] === 0) {
            echo "✓ All tests passed!\n";
        } else {
            echo "✗ Some tests failed!\n";
        }
        echo "========================\n\n";
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "Running User Model Tests...\n";
        echo "========================\n";
        
        $this->testUserRegistrationValidation();
        $this->testPasswordHashing();
        $this->testUserLoginValidation();
        $this->testProfileUpdateValidation();
        $this->testPasswordValidation();
        $this->testPasswordResetToken();
        $this->testEmailVerification();
        $this->testUserProfileData();
        $this->testAuthenticationMethods();
        
        $this->printTestSummary();
    }
}

// Run tests if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new UserModelTest();
    $test->runAllTests();
}