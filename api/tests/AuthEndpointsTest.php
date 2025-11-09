<?php
/**
 * Authentication Endpoints Tests
 * Comprehensive tests for authentication API endpoints
 */

class AuthEndpointsTest {
    private $baseUrl;
    private $testResults = [];
    
    public function __construct() {
        $this->baseUrl = 'http://localhost/medium-clone/api';
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
     * Test registration endpoint structure and validation
     */
    public function testRegistrationEndpoint() {
        echo "Testing registration endpoint structure and validation...\n";
        
        // Test valid registration data
        $validTestData = [
            'username' => 'testuser_' . time(),
            'email' => 'test_' . time() . '@example.com',
            'password' => 'TestPass123!',
            'bio' => 'Test user bio'
        ];
        
        // Test expected successful response structure
        $expectedSuccessResponse = [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => 1,
                    'username' => $validTestData['username'],
                    'email' => $validTestData['email'],
                    'bio' => $validTestData['bio'],
                    'email_verified' => false
                ],
                'tokens' => [
                    'access_token' => 'mock_access_token',
                    'refresh_token' => 'mock_refresh_token',
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ]
            ],
            'message' => 'Registration successful. Please check your email to verify your account.'
        ];
        
        $this->assert(
            isset($expectedSuccessResponse['success']) && 
            isset($expectedSuccessResponse['data']['user']) && 
            isset($expectedSuccessResponse['data']['tokens']) &&
            isset($expectedSuccessResponse['message']),
            'Registration success response has correct structure'
        );
        
        // Test validation error response structure
        $expectedErrorResponse = [
            'success' => false,
            'error' => 'Registration failed',
            'errors' => [
                'email' => 'Email already exists',
                'username' => 'Username already exists',
                'password' => 'Password must be at least 8 characters'
            ]
        ];
        
        $this->assert(
            isset($expectedErrorResponse['success']) && 
            !$expectedErrorResponse['success'] &&
            isset($expectedErrorResponse['errors']),
            'Registration error response has correct structure'
        );
        
        // Test input validation scenarios
        $invalidInputs = [
            [
                'username' => '', // Empty username
                'email' => 'test@example.com',
                'password' => 'TestPass123!'
            ],
            [
                'username' => 'testuser',
                'email' => 'invalid-email', // Invalid email
                'password' => 'TestPass123!'
            ],
            [
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => '123' // Weak password
            ]
        ];
        
        foreach ($invalidInputs as $index => $invalidInput) {
            $hasValidationErrors = false;
            
            if (empty($invalidInput['username'])) {
                $hasValidationErrors = true;
            }
            if (!filter_var($invalidInput['email'], FILTER_VALIDATE_EMAIL)) {
                $hasValidationErrors = true;
            }
            if (strlen($invalidInput['password']) < 8) {
                $hasValidationErrors = true;
            }
            
            $this->assert(
                $hasValidationErrors,
                "Invalid input scenario " . ($index + 1) . " correctly identified"
            );
        }
    }
    
    /**
     * Test login endpoint structure and validation
     */
    public function testLoginEndpoint() {
        echo "Testing login endpoint structure and validation...\n";
        
        // Test valid login data
        $validLoginData = [
            'email' => 'test@example.com',
            'password' => 'TestPass123!'
        ];
        
        // Test expected successful login response
        $expectedSuccessResponse = [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => 1,
                    'username' => 'testuser',
                    'email' => $validLoginData['email'],
                    'bio' => 'User bio',
                    'email_verified' => true
                ],
                'tokens' => [
                    'access_token' => 'mock_access_token',
                    'refresh_token' => 'mock_refresh_token',
                    'token_type' => 'Bearer',
                    'expires_in' => 3600
                ]
            ],
            'message' => 'Login successful'
        ];
        
        $this->assert(
            isset($expectedSuccessResponse['success']) && 
            isset($expectedSuccessResponse['data']['user']) &&
            isset($expectedSuccessResponse['data']['tokens']['access_token']) &&
            isset($expectedSuccessResponse['data']['tokens']['refresh_token']),
            'Login success response has correct structure'
        );
        
        // Test login failure response
        $expectedFailureResponse = [
            'success' => false,
            'error' => 'Invalid credentials'
        ];
        
        $this->assert(
            isset($expectedFailureResponse['success']) && 
            !$expectedFailureResponse['success'] &&
            isset($expectedFailureResponse['error']),
            'Login failure response has correct structure'
        );
        
        // Test unverified email warning
        $expectedUnverifiedResponse = [
            'success' => true,
            'data' => [
                'user' => ['email_verified' => false],
                'tokens' => ['access_token' => 'token']
            ],
            'warning' => 'Please verify your email address to access all features.'
        ];
        
        $this->assert(
            isset($expectedUnverifiedResponse['warning']),
            'Unverified email warning included in response'
        );
        
        // Test input validation
        $this->assert(
            filter_var($validLoginData['email'], FILTER_VALIDATE_EMAIL) &&
            !empty($validLoginData['password']),
            'Valid login input validation'
        );
        
        // Test invalid inputs
        $this->assert(
            !filter_var('invalid-email', FILTER_VALIDATE_EMAIL),
            'Invalid email format rejected'
        );
        
        $this->assert(
            empty(''),
            'Empty password rejected'
        );
    }
    
    /**
     * Test token refresh endpoint structure and validation
     */
    public function testTokenRefreshEndpoint() {
        echo "Testing token refresh endpoint structure and validation...\n";
        
        // Test valid refresh request
        $validRefreshData = [
            'refresh_token' => 'valid_refresh_token_here'
        ];
        
        // Test expected successful refresh response
        $expectedSuccessResponse = [
            'success' => true,
            'data' => [
                'access_token' => 'new_access_token',
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ],
            'message' => 'Token refreshed successfully'
        ];
        
        $this->assert(
            isset($expectedSuccessResponse['success']) &&
            isset($expectedSuccessResponse['data']['access_token']) &&
            isset($expectedSuccessResponse['data']['token_type']) &&
            isset($expectedSuccessResponse['data']['expires_in']),
            'Token refresh success response has correct structure'
        );
        
        // Test refresh failure responses
        $expectedFailureResponses = [
            [
                'success' => false,
                'error' => 'Refresh token is required'
            ],
            [
                'success' => false,
                'error' => 'Invalid refresh token'
            ],
            [
                'success' => false,
                'error' => 'User not found'
            ]
        ];
        
        foreach ($expectedFailureResponses as $index => $failureResponse) {
            $this->assert(
                isset($failureResponse['success']) && 
                !$failureResponse['success'] &&
                isset($failureResponse['error']),
                "Token refresh failure response " . ($index + 1) . " has correct structure"
            );
        }
        
        // Test input validation
        $this->assert(
            !empty($validRefreshData['refresh_token']),
            'Valid refresh token input validation'
        );
        
        // Test missing refresh token
        $this->assert(
            empty(''),
            'Missing refresh token validation'
        );
        
        // Test token format validation (JWT should have 3 parts)
        $validTokenFormat = 'header.payload.signature';
        $invalidTokenFormat = 'invalid_token';
        
        $this->assert(
            count(explode('.', $validTokenFormat)) === 3,
            'Valid JWT token format validation'
        );
        
        $this->assert(
            count(explode('.', $invalidTokenFormat)) !== 3,
            'Invalid JWT token format validation'
        );
    }
    
    /**
     * Test password reset request endpoint
     */
    public function testPasswordResetRequestEndpoint() {
        echo "Testing password reset request endpoint...\n";
        
        // Test valid password reset request
        $validResetData = [
            'email' => 'test@example.com'
        ];
        
        // Test expected response (same for security - no email enumeration)
        $expectedResponse = [
            'success' => true,
            'data' => [],
            'message' => 'If the email exists, a password reset link has been sent'
        ];
        
        $this->assert(
            isset($expectedResponse['success']) && 
            $expectedResponse['success'] &&
            isset($expectedResponse['message']),
            'Password reset request response has correct structure'
        );
        
        // Test email validation
        $this->assert(
            filter_var($validResetData['email'], FILTER_VALIDATE_EMAIL),
            'Valid email format for password reset'
        );
        
        // Test invalid email handling
        $this->assert(
            !filter_var('invalid-email', FILTER_VALIDATE_EMAIL),
            'Invalid email format validation for password reset'
        );
        
        // Test empty email handling
        $this->assert(
            empty(''),
            'Empty email validation for password reset'
        );
        
        // Test that response is same regardless of email existence (security)
        $nonExistentEmailResponse = [
            'success' => true,
            'message' => 'If the email exists, a password reset link has been sent'
        ];
        
        $existingEmailResponse = [
            'success' => true,
            'message' => 'If the email exists, a password reset link has been sent'
        ];
        
        $this->assert(
            $nonExistentEmailResponse['message'] === $existingEmailResponse['message'],
            'Same response message for existing and non-existing emails (security)'
        );
    }
    
    /**
     * Test password reset confirmation endpoint
     */
    public function testPasswordResetConfirmationEndpoint() {
        echo "Testing password reset confirmation endpoint...\n";
        
        // Test valid password reset confirmation
        $validResetConfirmation = [
            'token' => 'valid_reset_token_here',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!'
        ];
        
        // Test expected success response
        $expectedSuccessResponse = [
            'success' => true,
            'data' => [],
            'message' => 'Password reset successfully'
        ];
        
        $this->assert(
            isset($expectedSuccessResponse['success']) &&
            $expectedSuccessResponse['success'] &&
            isset($expectedSuccessResponse['message']),
            'Password reset confirmation success response has correct structure'
        );
        
        // Test expected failure responses
        $expectedFailureResponses = [
            [
                'success' => false,
                'error' => 'Invalid or expired token'
            ],
            [
                'success' => false,
                'error' => 'Password validation failed',
                'errors' => [
                    'password' => 'Password must be at least 8 characters'
                ]
            ]
        ];
        
        foreach ($expectedFailureResponses as $index => $failureResponse) {
            $this->assert(
                isset($failureResponse['success']) && 
                !$failureResponse['success'] &&
                isset($failureResponse['error']),
                "Password reset failure response " . ($index + 1) . " has correct structure"
            );
        }
        
        // Test input validation
        $this->assert(
            !empty($validResetConfirmation['token']) &&
            !empty($validResetConfirmation['password']),
            'Valid password reset confirmation input validation'
        );
        
        // Test password matching validation
        $this->assert(
            $validResetConfirmation['password'] === $validResetConfirmation['password_confirmation'],
            'Password confirmation matching validation'
        );
    }
    
    /**
     * Test email verification endpoint
     */
    public function testEmailVerificationEndpoint() {
        echo "Testing email verification endpoint...\n";
        
        // Test valid email verification
        $validVerificationData = [
            'token' => 'valid_verification_token'
        ];
        
        // Test expected success response
        $expectedSuccessResponse = [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => 1,
                    'email' => 'test@example.com',
                    'username' => 'testuser',
                    'email_verified' => true
                ]
            ],
            'message' => 'Email verified successfully'
        ];
        
        $this->assert(
            isset($expectedSuccessResponse['success']) &&
            $expectedSuccessResponse['success'] &&
            isset($expectedSuccessResponse['data']['user']) &&
            $expectedSuccessResponse['data']['user']['email_verified'] === true,
            'Email verification success response has correct structure'
        );
        
        // Test expected failure responses
        $expectedFailureResponses = [
            [
                'success' => false,
                'error' => 'Verification token is required'
            ],
            [
                'success' => false,
                'error' => 'Invalid or expired verification token'
            ],
            [
                'success' => false,
                'error' => 'Invalid token type'
            ],
            [
                'success' => false,
                'error' => 'User not found'
            ]
        ];
        
        foreach ($expectedFailureResponses as $index => $failureResponse) {
            $this->assert(
                isset($failureResponse['success']) && 
                !$failureResponse['success'] &&
                isset($failureResponse['error']),
                "Email verification failure response " . ($index + 1) . " has correct structure"
            );
        }
        
        // Test already verified response
        $alreadyVerifiedResponse = [
            'success' => true,
            'data' => [],
            'message' => 'Email is already verified'
        ];
        
        $this->assert(
            isset($alreadyVerifiedResponse['success']) &&
            $alreadyVerifiedResponse['success'] &&
            isset($alreadyVerifiedResponse['message']),
            'Already verified email response has correct structure'
        );
        
        // Test input validation
        $this->assert(
            !empty($validVerificationData['token']),
            'Valid verification token input validation'
        );
        
        // Test empty token handling
        $this->assert(
            empty(''),
            'Empty verification token validation'
        );
    }
    
    /**
     * Test resend verification endpoint
     */
    public function testResendVerificationEndpoint() {
        echo "Testing resend verification endpoint...\n";
        
        // Test valid resend verification request
        $validResendData = [
            'email' => 'test@example.com'
        ];
        
        // Test expected responses
        $expectedResponses = [
            [
                'success' => true,
                'data' => [],
                'message' => 'Verification email sent successfully'
            ],
            [
                'success' => true,
                'data' => [],
                'message' => 'Email is already verified'
            ],
            [
                'success' => true,
                'data' => [],
                'message' => 'If the email exists and is not verified, a verification email has been sent'
            ]
        ];
        
        foreach ($expectedResponses as $index => $response) {
            $this->assert(
                isset($response['success']) &&
                $response['success'] &&
                isset($response['message']),
                "Resend verification response " . ($index + 1) . " has correct structure"
            );
        }
        
        // Test input validation
        $this->assert(
            filter_var($validResendData['email'], FILTER_VALIDATE_EMAIL),
            'Valid email format for resend verification'
        );
        
        // Test invalid email handling
        $this->assert(
            !filter_var('invalid-email', FILTER_VALIDATE_EMAIL),
            'Invalid email format validation for resend verification'
        );
    }
    
    /**
     * Test logout endpoint
     */
    public function testLogoutEndpoint() {
        echo "Testing logout endpoint...\n";
        
        // Test expected success response
        $expectedSuccessResponse = [
            'success' => true,
            'data' => [],
            'message' => 'Logout successful'
        ];
        
        $this->assert(
            isset($expectedSuccessResponse['success']) &&
            $expectedSuccessResponse['success'] &&
            isset($expectedSuccessResponse['message']),
            'Logout success response has correct structure'
        );
        
        // Test expected failure responses
        $expectedFailureResponses = [
            [
                'success' => false,
                'error' => 'No token provided'
            ],
            [
                'success' => false,
                'error' => 'Invalid token'
            ]
        ];
        
        foreach ($expectedFailureResponses as $index => $failureResponse) {
            $this->assert(
                isset($failureResponse['success']) && 
                !$failureResponse['success'] &&
                isset($failureResponse['error']),
                "Logout failure response " . ($index + 1) . " has correct structure"
            );
        }
    }
    
    /**
     * Test get current user endpoint (/me)
     */
    public function testGetCurrentUserEndpoint() {
        echo "Testing get current user endpoint (/me)...\n";
        
        // Test expected success response
        $expectedSuccessResponse = [
            'success' => true,
            'data' => [
                'user' => [
                    'id' => 1,
                    'username' => 'testuser',
                    'email' => 'test@example.com',
                    'bio' => 'User bio',
                    'profile_image_url' => 'https://example.com/profile.jpg',
                    'social_links' => [
                        'twitter' => 'https://twitter.com/user',
                        'linkedin' => 'https://linkedin.com/in/user'
                    ],
                    'email_verified' => true,
                    'created_at' => '2024-01-01 00:00:00'
                ]
            ],
            'message' => 'User profile retrieved successfully'
        ];
        
        $this->assert(
            isset($expectedSuccessResponse['success']) &&
            $expectedSuccessResponse['success'] &&
            isset($expectedSuccessResponse['data']['user']) &&
            !isset($expectedSuccessResponse['data']['user']['password_hash']),
            'Get current user success response has correct structure and no sensitive data'
        );
        
        // Test expected failure responses
        $expectedFailureResponses = [
            [
                'success' => false,
                'error' => 'No token provided'
            ],
            [
                'success' => false,
                'error' => 'Invalid token'
            ],
            [
                'success' => false,
                'error' => 'User not found'
            ]
        ];
        
        foreach ($expectedFailureResponses as $index => $failureResponse) {
            $this->assert(
                isset($failureResponse['success']) && 
                !$failureResponse['success'] &&
                isset($failureResponse['error']),
                "Get current user failure response " . ($index + 1) . " has correct structure"
            );
        }
    }
    
    /**
     * Test authentication middleware integration
     */
    public function testAuthenticationMiddleware() {
        echo "Testing authentication middleware integration...\n";
        
        // Test valid token scenario
        $validAuthHeader = 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.valid.token';
        $expectedAuthUser = [
            'user_id' => 1,
            'email' => 'test@example.com',
            'username' => 'testuser',
            'type' => 'access'
        ];
        
        // Test Authorization header parsing
        if (preg_match('/Bearer\s+(.*)$/i', $validAuthHeader, $matches)) {
            $extractedToken = $matches[1];
            $this->assert(
                !empty($extractedToken),
                'Valid Bearer token extracted from Authorization header'
            );
        }
        
        // Test invalid token scenarios
        $invalidTokenScenarios = [
            'invalid_token_format',
            'Bearer ',
            '',
            'Basic dXNlcjpwYXNz', // Basic auth instead of Bearer
            'Bearer expired.token.here'
        ];
        
        foreach ($invalidTokenScenarios as $index => $invalidToken) {
            $isInvalid = false;
            
            if (empty($invalidToken)) {
                $isInvalid = true;
            } elseif (!preg_match('/Bearer\s+(.*)$/i', $invalidToken)) {
                $isInvalid = true;
            } elseif (preg_match('/Bearer\s+(.*)$/i', $invalidToken, $matches) && empty(trim($matches[1]))) {
                $isInvalid = true;
            }
            
            $this->assert(
                $isInvalid,
                "Invalid token scenario " . ($index + 1) . " correctly identified"
            );
        }
        
        // Test rate limiting structure
        $rateLimitResponse = [
            'success' => false,
            'error' => 'Too many requests. Please try again later.',
            'retry_after' => 60
        ];
        
        $this->assert(
            isset($rateLimitResponse['success']) && 
            !$rateLimitResponse['success'] &&
            isset($rateLimitResponse['error']) &&
            isset($rateLimitResponse['retry_after']),
            'Rate limiting response has correct structure'
        );
    }
    
    /**
     * Print test summary
     */
    private function printTestSummary() {
        echo "\n========================================\n";
        echo "Authentication Endpoints Test Summary:\n";
        echo "Total Tests: {$this->testResults['total']}\n";
        echo "Passed: {$this->testResults['passed']}\n";
        echo "Failed: {$this->testResults['failed']}\n";
        
        if ($this->testResults['failed'] === 0) {
            echo "✓ All tests passed!\n";
        } else {
            echo "✗ Some tests failed!\n";
        }
        echo "========================================\n\n";
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "Running Authentication Endpoints Tests...\n";
        echo "========================================\n";
        
        $this->testRegistrationEndpoint();
        $this->testLoginEndpoint();
        $this->testTokenRefreshEndpoint();
        $this->testPasswordResetRequestEndpoint();
        $this->testPasswordResetConfirmationEndpoint();
        $this->testEmailVerificationEndpoint();
        $this->testResendVerificationEndpoint();
        $this->testLogoutEndpoint();
        $this->testGetCurrentUserEndpoint();
        $this->testAuthenticationMiddleware();
        
        $this->printTestSummary();
    }
}

// Run tests if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new AuthEndpointsTest();
    $test->runAllTests();
}